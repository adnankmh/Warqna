<?php

namespace App\Services\GameEngine;

/**
 * Four-player partnership Leekha.
 *
 * Implements simultaneous three-card passing, follow-suit, forced Leekha
 * disposal (Q♠ / 10♦ when void), partnership penalties and the 101-point match
 * threshold. Lower score wins.
 */
class LeekhaRules extends AbstractCardRules
{
    public function initialState(array $players, array $options = []): array
    {
        $players = array_values(array_slice($players, 0, 4));
        while (count($players) < 4) {
            $players[] = 'bot:leekha_'.count($players);
        }

        $state = [
            'phase' => 'passing',
            'game_type' => 'leekha',
            'players' => $players,
            'teams' => $this->teams($players),
            'turn' => $players[0],
            'hands' => [],
            'trick' => [],
            'last_trick' => [],
            'score' => array_fill_keys($players, 0),
            'team_score' => ['teamA' => 0, 'teamB' => 0],
            'round_penalties' => array_fill_keys($players, 0),
            'pending_passes' => [],
            'pass_direction' => 'right',
            'dealer_index' => 3,
            'round' => 0,
            'messages' => [],
            'engine_quality' => 'leekha_partnership_complete_v187',
        ];

        return $this->startRound($state);
    }

    public function validate(array $state, string $playerId, string $action, array $payload): bool
    {
        if (($state['phase'] ?? null) === 'finished' || ($state['turn'] ?? null) !== $playerId) {
            return false;
        }

        if (($state['phase'] ?? null) === 'passing') {
            if ($action !== 'pass_cards' || isset($state['pending_passes'][$playerId])) {
                return false;
            }
            $cards = array_values(array_map('strval', (array)($payload['cards'] ?? [])));
            if (count($cards) !== 3 || count(array_unique($cards)) !== 3) {
                return false;
            }
            $hand = $state['hands'][$playerId] ?? [];
            foreach ($cards as $card) {
                $index = array_search($card, $hand, true);
                if ($index === false) {
                    return false;
                }
                array_splice($hand, $index, 1);
            }
            return true;
        }

        if (($state['phase'] ?? null) !== 'playing' || $action !== 'play_card') {
            return false;
        }
        $card = (string)($payload['card'] ?? '');
        return in_array($card, $this->legalCards($state, $playerId), true);
    }

    public function apply(array $state, string $playerId, string $action, array $payload): array
    {
        if (!$this->validate($state, $playerId, $action, $payload)) {
            $state['last_error_message'] = 'حركة ليخة غير قانونية: مرّر ثلاث أوراق، واتبع النوع، وارمِ Q♠ أو 10♦ عند انعدام النوع.';
            return $state;
        }

        if ($action === 'pass_cards') {
            $cards = array_values(array_map('strval', (array)$payload['cards']));
            $state['pending_passes'][$playerId] = $cards;
            $state['messages'][] = $this->labelPlayer($playerId).' اختار ثلاث أوراق للتمرير.';
            $state['turn'] = $this->nextPlayerWithoutPass($state, $playerId);
            if (count($state['pending_passes']) === count($state['players'])) {
                $state = $this->completePasses($state);
            }
            unset($state['last_error_message']);
            return $state;
        }

        $card = (string)$payload['card'];
        $this->removeCard($state['hands'][$playerId], $card);
        $state['trick'][$playerId] = $card;
        $state['messages'][] = $this->labelPlayer($playerId).' لعب '.$this->pretty($card).'.';

        if (count($state['trick']) >= count($state['players'])) {
            $winner = $this->trickWinner($state['trick']);
            $penalty = $this->penalty($state['trick']);
            $state['score'][$winner] = (int)($state['score'][$winner] ?? 0) + $penalty;
            $state['round_penalties'][$winner] = (int)($state['round_penalties'][$winner] ?? 0) + $penalty;
            $state['last_trick'] = $state['trick'];
            $state['trick'] = [];
            $state['turn'] = $winner;
            $state['messages'][] = $this->labelPlayer($winner).' أخذ اللمّة وتحمل '.$penalty.' نقطة.';
        } else {
            $state['turn'] = $this->playerKeyNext($state['players'], $playerId);
        }

        if ($this->allHandsEmpty($state['hands'])) {
            $state = $this->finishRound($state);
        }
        unset($state['last_error_message']);
        return $state;
    }

    public function onTurnTimeout(array $state): array
    {
        $player = (string)($state['turn'] ?? '');
        if ($player === '') {
            return $state;
        }
        if (($state['phase'] ?? null) === 'passing') {
            $cards = $this->passChoice($state['hands'][$player] ?? []);
            $state['messages'][] = '⏱️ اختار الكمبيوتر أوراق التمرير قانونياً.';
            return $this->apply($state, $player, 'pass_cards', ['cards' => $cards]);
        }
        $legal = $this->legalCards($state, $player);
        if (!$legal) {
            return $state;
        }
        usort($legal, fn(string $a, string $b) => $this->danger($a) <=> $this->danger($b));
        $state['messages'][] = '⏱️ لعب الكمبيوتر ورقة قانونية.';
        return $this->apply($state, $player, 'play_card', ['card' => $legal[0]]);
    }

    /** @return array<int,array<string,mixed>> */
    public function availableActions(array $state, string $playerId): array
    {
        if (($state['turn'] ?? null) !== $playerId || ($state['phase'] ?? null) === 'finished') {
            return [];
        }
        if (($state['phase'] ?? null) === 'passing') {
            return [[
                'type' => 'pass_cards',
                'count' => 3,
                'direction' => $state['pass_direction'] ?? 'right',
            ]];
        }
        return array_map(
            fn(string $card) => ['type' => 'play_card', 'card' => $card],
            $this->legalCards($state, $playerId),
        );
    }

    /** @return array<int,string> */
    private function legalCards(array $state, string $playerId): array
    {
        if (($state['phase'] ?? null) !== 'playing' || ($state['turn'] ?? null) !== $playerId) {
            return [];
        }
        $hand = array_values($state['hands'][$playerId] ?? []);
        if (!$state['trick']) {
            return $hand;
        }
        $leadSuit = $this->suit((string)reset($state['trick']));
        $follow = array_values(array_filter($hand, fn(string $card) => $this->suit($card) === $leadSuit));
        if ($follow) {
            return $follow;
        }
        $forced = array_values(array_filter(
            $hand,
            fn(string $card) => in_array($card, ['Q_spades', '10_diamonds'], true),
        ));
        return $forced ?: $hand;
    }

    private function startRound(array $state): array
    {
        if ((int)($state['round'] ?? 0) > 0) {
            $state['dealer_index'] = ((int)($state['dealer_index'] ?? 3) + 1) % 4;
        }
        $state['round'] = (int)($state['round'] ?? 0) + 1;
        $state['phase'] = 'passing';
        $state['pass_direction'] = $state['round'] % 2 === 1 ? 'right' : 'left';
        $state['pending_passes'] = [];
        $state['trick'] = [];
        $state['last_trick'] = [];
        $state['round_penalties'] = array_fill_keys($state['players'], 0);
        [$hands] = $this->deal($state['players'], DeckFactory::standard52(true), 13);
        $state['hands'] = $hands;
        $starter = ((int)($state['dealer_index'] ?? 3) + 1) % 4;
        $state['turn'] = $state['players'][$starter];
        $state['messages'][] = 'الجولة '.$state['round'].': مرّر ثلاث أوراق '.($state['pass_direction'] === 'right' ? 'لليمين' : 'لليسار').'.';
        return $state;
    }

    private function completePasses(array $state): array
    {
        $outgoing = $state['pending_passes'];
        foreach ($outgoing as $player => $cards) {
            foreach ($cards as $card) {
                $this->removeCard($state['hands'][$player], (string)$card);
            }
        }
        foreach ($state['players'] as $index => $player) {
            $targetIndex = $state['pass_direction'] === 'right'
                ? ($index + 1) % 4
                : ($index + 3) % 4;
            $target = $state['players'][$targetIndex];
            $state['hands'][$target] = array_merge($state['hands'][$target], $outgoing[$player]);
        }
        foreach ($state['hands'] as $player => $hand) {
            $state['hands'][$player] = $this->sortHand($hand);
        }
        $state['pending_passes'] = [];
        $state['phase'] = 'playing';
        $starter = ((int)($state['dealer_index'] ?? 3) + 1) % 4;
        $state['turn'] = $state['players'][$starter];
        $state['messages'][] = 'اكتمل التمرير وبدأ اللعب.';
        return $state;
    }

    private function finishRound(array $state): array
    {
        foreach ($state['teams'] as $team => $members) {
            $state['team_score'][$team] = array_sum(array_map(
                fn(string $member) => (int)($state['score'][$member] ?? 0),
                $members,
            ));
        }
        $teamA = (int)$state['team_score']['teamA'];
        $teamB = (int)$state['team_score']['teamB'];
        $state['messages'][] = 'انتهت الجولة: الفريق A '.$teamA.' — الفريق B '.$teamB.'.';
        $individualThresholdReached = max(array_map('intval', $state['score'])) >= 101;
        if ($individualThresholdReached) {
            $state['phase'] = 'finished';
            $state['winner_team'] = $teamA <= $teamB ? 'teamA' : 'teamB';
            $state['winner'] = $state['teams'][$state['winner_team']][0] ?? null;
            $state['messages'][] = 'انتهت الليخة عند 101 نقطة؛ فاز الفريق الأقل عقوبة.';
            return $state;
        }
        return $this->startRound($state);
    }

    private function nextPlayerWithoutPass(array $state, string $current): string
    {
        $players = $state['players'];
        $index = array_search($current, $players, true);
        for ($step = 1; $step <= count($players); $step++) {
            $candidate = $players[(($index === false ? 0 : $index) + $step) % count($players)];
            if (!isset($state['pending_passes'][$candidate])) {
                return $candidate;
            }
        }
        return $current;
    }

    /** @return array<int,string> */
    private function passChoice(array $hand): array
    {
        usort($hand, fn(string $a, string $b) => $this->danger($b) <=> $this->danger($a));
        return array_values(array_slice($hand, 0, 3));
    }

    private function danger(string $card): int
    {
        if ($card === 'Q_spades') {
            return 100;
        }
        if ($card === '10_diamonds') {
            return 90;
        }
        if ($this->suit($card) === 'hearts') {
            return 40 + $this->cardValue($card);
        }
        return $this->cardValue($card);
    }

    private function penalty(array $trick): int
    {
        $penalty = 0;
        foreach ($trick as $card) {
            if ($card === 'Q_spades') {
                $penalty += 13;
            } elseif ($card === '10_diamonds') {
                $penalty += 10;
            } elseif ($this->suit((string)$card) === 'hearts') {
                $penalty += 1;
            }
        }
        return $penalty;
    }

    private function pretty(string $card): string
    {
        return str_replace(
            ['_clubs', '_diamonds', '_spades', '_hearts'],
            ['♣', '♦', '♠', '♥'],
            $card,
        );
    }
}
