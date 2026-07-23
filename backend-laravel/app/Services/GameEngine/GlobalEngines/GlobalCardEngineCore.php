<?php
/**
 * Warqna Global Card Engine Core
 * ------------------------------------------------------------
 * مستقل عن Laravel. كل الحركات تمر عبر applyAction/availableActions.
 * Server-authoritative: لا يثق بالواجهة، ويتحقق من الدور والورق والقانون.
 */
class GameEngineException extends Exception {}

class GlobalCardEngineCore
{
    protected array $config = [];
    protected string $engineName = 'global';

    public function __construct(array $overrides = [])
    {
        $this->config = array_replace_recursive($this->defaultConfig(), $overrides);
    }

    protected function defaultConfig(): array { return []; }

    public function newGame(array $players, array $options = []): array
    {
        $cfg = array_replace_recursive($this->config, $options);
        $this->validatePlayers($players, $cfg);
        $seed = array_key_exists('seed',$options) ? (string)$options['seed'] : bin2hex(random_bytes(32));
        $playerIdsForCommitment = array_map(fn($p)=>(string)($p['id'] ?? ''), $players);
        $dealCommitment = hash('sha256', $this->engineName.'|'.$seed.'|'.implode('|', $playerIdsForCommitment));
        $rngSeed=is_numeric($seed)?((int)$seed&0x7fffffff):(hexdec(substr(hash('sha256',$seed),0,8))&0x7fffffff);
        mt_srand($rngSeed);
        $deck = $this->makeDeck($cfg['deck'] ?? '52', count($players));
        $this->shuffleDeck($deck);
        $hands = [];
        $tableau = [];
        $foundation = [];
        $waste = [];
        $discard = [];
        $mode = $cfg['mode'];
        $dealerIndex = max(0, min(count($players) - 1, (int)($options['dealerIndex'] ?? $cfg['dealerIndex'] ?? (count($players) - 1))));
        $revealedCard = null;

        foreach ($players as $i => $p) {
            $pid = (string)($p['id'] ?? ('p'.($i+1)));
            $hands[$pid] = [];
            $tableau[$pid] = [];
            $foundation[$pid] = [];
            $waste[$pid] = [];
        }

        if (in_array($mode, ['trick','trick400','syrian41','trix','trix-complex'], true)) {
            $cardsEach = intdiv(count($deck), count($players));
            for ($r=0; $r<$cardsEach; $r++) {
                foreach ($players as $p) $hands[(string)$p['id']][] = array_shift($deck);
            }
            foreach ($hands as $pid => $h) $hands[$pid] = $this->sortCards($h);
            if ($mode === 'syrian41') {
                $dealerId = (string)($players[$dealerIndex]['id'] ?? '');
                $dealerHand = array_values($hands[$dealerId] ?? []);
                $revealedCard = $dealerHand ? $dealerHand[count($dealerHand) - 1] : null;
                $cfg['fixedTrump'] = $this->oppositeSameColorSuit($this->suit((string)$revealedCard));
                $cfg['revealedCard'] = $revealedCard;
            }
            $phase = in_array($mode, ['trick','trick400','syrian41'], true) ? 'bidding' : 'contract';
        } elseif ($mode === 'baloot') {
            // Baloot buying starts after five cards each and one exposed buyer
            // card. The remaining three cards are dealt only after a contract.
            for ($r=0; $r<5; $r++) foreach ($players as $p) $hands[(string)$p['id']][] = array_shift($deck);
            foreach ($hands as $pid => $h) $hands[$pid] = $this->sortCards($h);
            $revealedCard = array_shift($deck);
            $cfg['buyerCard'] = $revealedCard;
            $phase = 'bidding';
        } elseif ($mode === 'solitaire') {
            // Every competitor receives an independent 52-card Klondike deal.
            // Sharing one deck made later players start with incomplete games.
            foreach ($players as $p) {
                $pid = (string)$p['id'];
                $privateDeck = $this->makeDeck('52', 1);
                mt_srand(($rngSeed ^ (int)sprintf('%u', crc32($pid))) & 0x7fffffff);
                $this->shuffleDeck($privateDeck);
                $columns = [];
                for ($column=0; $column<7; $column++) {
                    $down = [];
                    for ($row=0; $row<$column; $row++) $down[] = array_shift($privateDeck);
                    $up = [array_shift($privateDeck)];
                    $columns[] = ['down'=>$down, 'up'=>$up];
                }
                $tableau[$pid] = $columns;
                $hands[$pid] = array_values($privateDeck); // 24-card stock
                $foundation[$pid] = ['C'=>[], 'D'=>[], 'S'=>[], 'H'=>[]];
                $waste[$pid] = [];
            }
            $deck = [];
            $phase = 'playing';
        } else { // rummy/hand/banakil
            $cardsEach = (int)($cfg['cardsEach'] ?? 14);
            for ($r=0; $r<$cardsEach; $r++) {
                foreach ($players as $p) {
                    $card = array_shift($deck);
                    if ($card === null) throw new GameEngineException('عدد أوراق الرزمة لا يكفي لتوزيع الجولة.');
                    $hands[(string)$p['id']][] = $card;
                }
            }

            // In Banakil the starting player receives one extra card and must discard first.
            if (!empty($cfg['starterExtraCard'])) {
                $firstId = (string)($players[0]['id'] ?? '');
                $extra = array_shift($deck);
                if ($extra === null) throw new GameEngineException('تعذر توزيع الورقة الإضافية للاعب البادئ.');
                $hands[$firstId][] = $extra;
                $phase = !empty($cfg['starterMustDiscard']) ? 'discard' : 'draw';
            } else {
                $top = array_shift($deck);
                if ($top !== null) $discard[] = $top;
                $phase = 'draw';
            }

            foreach ($hands as $pid => $h) $hands[$pid] = $this->sortCards($h);
        }

        $state = [
            'engine' => $this->engineName,
            'version' => 'final-v1',
            'seed' => $seed,
            'config' => $cfg,
            'players' => array_values(array_map(fn($p,$i)=>[
                'id'=>(string)($p['id'] ?? ('p'.($i+1))),
                'name'=>(string)($p['name'] ?? ('Player '.($i+1))),
                'seat'=>$i,
                'team'=>($cfg['partnership'] ?? false) ? ($i % 2) : $i,
                'bot'=>(bool)($p['bot'] ?? false),
                'away'=>false,
                'connected'=>true,
                'missedTurns'=>0,
            ], $players, array_keys($players))),
            'phase'=>$phase,
            'currentIndex'=>0,
            'dealerIndex'=>$dealerIndex,
            'hands'=>$hands,
            'deck'=>$deck,
            'discard'=>$discard,
            'waste'=>$waste,
            'melds'=>[],
            'teamOpeningThresholds'=>[0=>(int)($cfg['opening'] ?? 51),1=>(int)($cfg['opening'] ?? 51)],
            'teamOpened'=>[0=>false,1=>false],
            'tableau'=>$tableau,
            'foundation'=>$foundation,
            'bids'=>[],
            'highestBid'=>null,
            'bidWinner'=>null,
            'trump'=>$mode === 'syrian41' ? ($cfg['fixedTrump'] ?? null) : null,
            'revealedCard'=>$revealedCard,
            'contract'=>null,
            'buyerCard'=>$revealedCard,
            'buyRound'=>1,
            'buyPasses'=>0,
            'pendingHokmBuyer'=>null,
            'pendingContractSuit'=>null,
            'contractsUsed'=>[],
            'doubledCards'=>[],
            'doublingDone'=>[],
            'kingdomOwnerIndex'=>0,
            'trixBoard'=>[
                'C'=>['started'=>false,'low'=>11,'high'=>11],
                'D'=>['started'=>false,'low'=>11,'high'=>11],
                'S'=>['started'=>false,'low'=>11,'high'=>11],
                'H'=>['started'=>false,'low'=>11,'high'=>11],
            ],
            'trixFinishOrder'=>[],
            'starterDiscardPending'=>!empty($cfg['starterMustDiscard']),
            'turnDrawSource'=>array_fill_keys(array_keys($hands), null),
            'turnMelded'=>array_fill_keys(array_keys($hands), false),
            'trick'=>[],
            'tricksWon'=>[],
            'scores'=>$this->initialScores($players, $cfg),
            'round'=>1,
            'gameOver'=>false,
            'winner'=>null,
            'events'=>[],
            'antiCheat'=>[
                'lastHash'=>null,
                'moveCounter'=>0,
                'illegalMoves'=>[],
                'dealCommitment'=>$dealCommitment,
            ],
        ];
        if (in_array($mode, ['trix','trix-complex'], true)) {
            foreach ($hands as $pid=>$hand) {
                if (in_array('7_H', $hand, true)) {
                    $state['kingdomOwnerIndex'] = $this->playerIndex($state, (string)$pid);
                    $state['currentIndex'] = $state['kingdomOwnerIndex'];
                    break;
                }
            }
        }
        $state = $this->record($state, 'game.created', ['players'=>count($players), 'mode'=>$mode]);
        return $this->finalizeState($state);
    }

    protected function validatePlayers(array $players, array $cfg): void
    {
        $allowed = $cfg['players'] ?? [4];
        if (!in_array(count($players), $allowed, true)) throw new GameEngineException('عدد اللاعبين غير مسموح لهذه اللعبة.');
        $ids = [];
        foreach ($players as $p) {
            if (empty($p['id'])) throw new GameEngineException('كل لاعب يحتاج id.');
            if (isset($ids[$p['id']])) throw new GameEngineException('تكرار id لاعب.');
            $ids[$p['id']] = true;
        }
    }

    protected function initialScores(array $players, array $cfg): array
    {
        $scores = [];
        if (!empty($cfg['individualScores'])) {
            foreach ($players as $p) $scores[(string)$p['id']] = 0;
        } elseif ($cfg['partnership'] ?? false) {
            $scores = [0=>0, 1=>0];
        } else {
            foreach ($players as $i => $p) $scores[(string)$p['id']] = 0;
        }
        return $scores;
    }

    protected function makeDeck(string $type, int $players): array
    {
        $ranks = ['2','3','4','5','6','7','8','9','10','J','Q','K','A'];
        $suits = ['C','D','S','H'];
        if ($type === 'baloot32') $ranks = ['7','8','9','J','Q','K','10','A'];
        $deck = [];
        foreach ($suits as $s) foreach ($ranks as $r) $deck[] = $r.'_'.$s;
        if (in_array($type, ['double-joker','multi52'], true)) {
            $deck = array_merge($deck, $deck);
            $deck[] = 'JOKER_R'; $deck[] = 'JOKER_B';
        }
        if ($type === 'multi52') {
            while (count($deck) < ($players * 40)) $deck = array_merge($deck, $deck);
        }
        return array_values($deck);
    }

    protected function shuffleDeck(array &$deck): void
    {
        for ($i=count($deck)-1; $i>0; $i--) { $j=mt_rand(0,$i); [$deck[$i],$deck[$j]]=[$deck[$j],$deck[$i]]; }
    }

    public function availableActions(array $state, string $playerId): array
    {
        $this->assertPlayer($state, $playerId);
        if ($state['gameOver']) return [];
        $current = $this->currentPlayerId($state);
        $isTurn = $current === $playerId;
        $mode = $state['config']['mode'];
        $actions = [];
        if (!$isTurn) return [['type'=>'wait','reason'=>'ليس دورك الآن']];

        if ($state['phase'] === 'bidding') {
            if ($mode === 'baloot') {
                $pending=(string)($state['pendingHokmBuyer'] ?? '');
                if($pending!==''){
                    if($pending===$playerId){
                        $actions[]=['type'=>'choose_contract','contract'=>'confirm_hokm','suit'=>(string)($state['pendingContractSuit'] ?? '')];
                        $actions[]=['type'=>'choose_contract','contract'=>'sun'];
                    }else{
                        $actions[]=['type'=>'pass'];$actions[]=['type'=>'choose_contract','contract'=>'sun'];
                    }
                    return $actions;
                }
                $actions[] = ['type'=>'pass'];$actions[] = ['type'=>'choose_contract','contract'=>'sun'];
                if((int)($state['buyRound'] ?? 1)===1) $actions[] = ['type'=>'choose_contract','contract'=>'hokm','suit'=>$this->suit((string)($state['buyerCard'] ?? ''))];
                else foreach(['C','D','S','H'] as $suit) if($suit!==$this->suit((string)($state['buyerCard'] ?? ''))) $actions[]=['type'=>'choose_contract','contract'=>'second_hokm','suit'=>$suit];
                return $actions;
            }
            if ($mode === 'trick400' || $mode === 'syrian41') {
                // 400 and Syrian 41 use one independent declaration per player.
                $min = $mode === 'syrian41' ? 2 : $this->tarneeb400MinimumBid($state, $playerId);
                for ($b=$min; $b<=13; $b++) $actions[] = ['type'=>'bid','amount'=>$b];
                return $actions;
            }
            $actions[] = ['type'=>'pass'];
            $min = (int)($state['config']['minBid'] ?? 7);
            if ($state['highestBid']) $min = max($min, (int)$state['highestBid']['amount'] + 1);
            $max = (int)($state['config']['maxBid'] ?? 13);
            for ($b=$min; $b<=$max; $b++) $actions[] = ['type'=>'bid','amount'=>$b];
            return $actions;
        }
        if ($state['phase'] === 'choose_trump') {
            foreach (['C','D','S','H'] as $s) $actions[] = ['type'=>'choose_trump','suit'=>$s];
            return $actions;
        }
        if ($state['phase'] === 'contract') {
            $contracts = $mode === 'trix-complex' ? ['complex','trix'] : ['tricks','girls','diamonds','king_hearts','trix'];
            $used = (array)($state['contractsUsed'] ?? []);
            foreach ($contracts as $c) if (!in_array($c,$used,true)) $actions[] = ['type'=>'choose_contract','contract'=>$c];
            return $actions;
        }
        if ($state['phase'] === 'doubling') {
            foreach((array)($state['hands'][$playerId] ?? []) as $card){
                if($this->isTrixDoublableCard($state,(string)$card) && !isset($state['doubledCards'][$card])) $actions[]=['type'=>'double_card','card'=>$card];
            }
            $actions[]=['type'=>'finish_doubling'];
            return $actions;
        }
        if ($state['phase'] === 'trix_playing') {
            $legal=$this->legalTrixCards($state,$playerId);
            foreach($legal as $card) $actions[]=['type'=>'play_card','card'=>$card];
            if(!$legal) $actions[]=['type'=>'pass_trix'];
            return $actions;
        }
        if (in_array($mode, ['trick','trick400','syrian41','trix','trix-complex','baloot'], true)) {
            foreach ($this->legalCards($state, $playerId) as $card) $actions[] = ['type'=>'play_card','card'=>$card];
            return $actions;
        }
        if ($mode === 'solitaire') {
            return $this->solitaireActions($state, $playerId);
        }
        if ($state['phase'] === 'draw') {
            $actions[] = ['type'=>'draw_deck'];
            if (!empty($state['discard'])) $actions[] = ['type'=>'draw_discard'];
            return $actions;
        }
        // rummy discard/meld phase
        $actions[] = ['type'=>'organize','strategy'=>'smart'];
        if (!empty($state['starterDiscardPending'])) {
            foreach (($state['hands'][$playerId] ?? []) as $card) $actions[] = ['type'=>'discard','card'=>$card];
            return $actions;
        }
        foreach ($this->suggestMelds($state['hands'][$playerId] ?? [], $this->rummyOpeningRequirement($state,$playerId)) as $meld) $actions[] = ['type'=>'meld','cards'=>$meld['cards']];
        if ($this->hasRummyOpened($state,$playerId)) {
            foreach (($state['melds'] ?? []) as $targetPlayer=>$meldList) {
                $scope=(string)($state['config']['layoffScope'] ?? (!empty($state['config']['partnership'])?'team':'all'));
                $allowed=$scope==='all' || ($scope==='team' && $this->teamOf($state,(string)$targetPlayer)===$this->teamOf($state,$playerId)) || ($scope==='self' && $targetPlayer===$playerId);
                if (!$allowed) continue;
                foreach ($meldList as $meldIndex=>$existing) {
                    foreach (($state['hands'][$playerId] ?? []) as $card) {
                        if ($this->isValidMeld(array_merge((array)($existing['cards'] ?? []), [$card]))) {
                            $actions[] = ['type'=>'layoff','target_player'=>(string)$targetPlayer,'meld_index'=>(int)$meldIndex,'cards'=>[$card]];
                        }
                    }
                }
            }
        }
        $suggested=$this->suggestMelds($state['hands'][$playerId] ?? [], 0);
        // Suggested melds often overlap (for example the same card may appear
        // in a rank set and a run). Never advertise meld_many unless every
        // physical card occurrence can be consumed exactly once.
        $remaining=array_count_values(array_map('strval',(array)($state['hands'][$playerId] ?? [])));$groups=[];
        foreach($suggested as $candidate){
            $cards=array_map('strval',(array)($candidate['cards'] ?? []));$needed=array_count_values($cards);$fits=true;
            foreach($needed as $card=>$count)if(($remaining[$card] ?? 0)<$count){$fits=false;break;}
            if(!$fits)continue;
            foreach($needed as $card=>$count)$remaining[$card]-=$count;
            $groups[]=$cards;if(count($groups)>=3)break;
        }
        $groupValue=array_sum(array_map(fn($cards)=>$this->meldValue($cards),$groups));
        if(count($groups)>=2 && ($this->hasRummyOpened($state,$playerId) || $groupValue >= $this->rummyOpeningRequirement($state,$playerId))) $actions[]=['type'=>'meld_many','groups'=>$groups];
        foreach (($state['hands'][$playerId] ?? []) as $card) $actions[] = ['type'=>'discard','card'=>$card];
        return $actions;
    }

    public function applyAction(array $state, string $playerId, array $action): array
    {
        $this->assertPlayer($state, $playerId);
        if ($state['gameOver']) throw new GameEngineException('اللعبة منتهية.');
        if ($this->currentPlayerId($state) !== $playerId && !in_array(($action['type'] ?? ''), ['set_away','return_from_away'], true)) throw new GameEngineException('ليست دورك.');
        $type = (string)($action['type'] ?? '');
        $mode = $state['config']['mode'];
        return match($type) {
            'pass' => $this->pass($state, $playerId),
            'bid' => $this->bid($state, $playerId, (int)$action['amount']),
            'choose_trump' => $this->chooseTrump($state, $playerId, (string)$action['suit']),
            'choose_contract' => $this->chooseContract($state, $playerId, (string)$action['contract'], isset($action['suit'])?(string)$action['suit']:null),
            'play_card' => $state['phase']==='trix_playing' ? $this->playTrixCard($state,$playerId,(string)$action['card']) : $this->playCard($state, $playerId, (string)$action['card']),
            'pass_trix' => $this->passTrix($state,$playerId),
            'double_card' => $this->doubleTrixCard($state,$playerId,(string)($action['card'] ?? '')),
            'finish_doubling' => $this->finishTrixDoubling($state,$playerId),
            'draw_deck' => $this->drawDeck($state, $playerId),
            'draw_discard' => $this->drawDiscard($state, $playerId),
            'discard' => $this->discardCard($state, $playerId, (string)$action['card']),
            'meld' => $this->meld($state, $playerId, $action['cards'] ?? []),
            'meld_many' => $this->meldMany($state,$playerId,$action['groups'] ?? []),
            'layoff' => $this->layoff($state, $playerId, (string)($action['target_player'] ?? $playerId), (int)($action['meld_index'] ?? 0), $action['cards'] ?? []),
            'organize' => $this->organize($state, $playerId, (string)($action['strategy'] ?? 'smart')),
            'draw_stock' => $this->solitaireDraw($state, $playerId),
            'recycle_stock' => $this->solitaireRecycle($state, $playerId),
            'move_to_foundation' => $this->solitaireFoundation($state, $playerId, (string)$action['card'], (string)($action['source'] ?? 'auto'), isset($action['column']) ? (int)$action['column'] : null),
            'move_to_tableau' => $this->solitaireTableauMove($state, $playerId, $action),
            'set_away' => $this->setAway($state, $playerId, true),
            'return_from_away' => $this->setAway($state, $playerId, false),
            default => throw new GameEngineException('حركة غير معروفة: '.$type),
        };
    }

    protected function pass(array $state, string $playerId): array
    {
        if ($state['phase'] !== 'bidding') throw new GameEngineException('لا يوجد طلب الآن.');
        if (($state['config']['mode'] ?? '')==='baloot') {
            $pending=(string)($state['pendingHokmBuyer'] ?? '');
            if($pending!=='' && $pending===$playerId)throw new GameEngineException('صاحب طلب الحكم يثبت الحكم أو يرفعه إلى صن.');
            $state['bids'][]=['player'=>$playerId,'contract'=>'pass','round'=>(int)($state['buyRound'] ?? 1)];
            $state['buyPasses']=(int)($state['buyPasses'] ?? 0)+1;
            $state=$this->record($state,'bid.pass',compact('playerId'));
            if($pending!==''){
                if($state['buyPasses']>=count($state['players'])-1){$state['currentIndex']=$this->playerIndex($state,$pending);return $this->finalizeState($state);}
                return $this->finalizeState($this->advance($state));
            }
            if($state['buyPasses']>=count($state['players'])){
                if((int)($state['buyRound'] ?? 1)===1){
                    $state['buyRound']=2;$state['buyPasses']=0;$state['bids']=[];
                    $state['currentIndex']=((int)($state['dealerIndex'] ?? 0)+1)%count($state['players']);
                    return $this->finalizeState($state);
                }
                $next=$this->newGame($state['players'],$state['config']);
                $next['scores']=$state['scores'];$next['round']=(int)($state['round'] ?? 1);$next['events']=$state['events'];
                return $this->record($next,'round.redeal',['reason'=>'baloot_all_passed']);
            }
            return $this->finalizeState($this->advance($state));
        }
        if (in_array(($state['config']['mode'] ?? ''), ['trick400','syrian41'], true)) throw new GameEngineException('يجب على كل لاعب إعلان طلب مستقل من 2 إلى 13.');
        $state['bids'][] = ['player'=>$playerId, 'amount'=>null];
        $state = $this->record($state, 'bid.pass', compact('playerId'));
        if (count($state['bids']) >= count($state['players'])) {
            if (!$state['highestBid']) {
                $state = $this->record($state, 'round.redeal', ['reason'=>'all_passed']);
                return $this->newGame($state['players'], $state['config']);
            }
            $fixedTrump=$state['config']['fixedTrump'] ?? null;
            if($fixedTrump){ $state['trump']=$fixedTrump; $state['phase']='playing'; }
            else $state['phase'] = ($state['config']['trump'] ?? false) ? 'choose_trump' : 'playing';
            $state['currentIndex'] = $this->playerIndex($state, $state['bidWinner']);
        } else $state = $this->advance($state);
        return $this->finalizeState($state);
    }

    protected function bid(array $state, string $playerId, int $amount): array
    {
        if ($state['phase'] !== 'bidding') throw new GameEngineException('مرحلة الطلب غير فعالة.');
        if (in_array(($state['config']['mode'] ?? ''), ['trick400','syrian41'], true)) {
            $independentMode = (string)$state['config']['mode'];
            $min = $independentMode === 'syrian41' ? 2 : $this->tarneeb400MinimumBid($state, $playerId);
            if ($amount < $min || $amount > 13) throw new GameEngineException('الطلب المستقل غير مسموح.');
            foreach ($state['bids'] as $bid) if (($bid['player'] ?? null) === $playerId) throw new GameEngineException('تم تسجيل طلبك لهذه الجولة.');
            $state['bids'][] = ['player'=>$playerId, 'amount'=>$amount];
            if (!$state['highestBid'] || $amount > (int)($state['highestBid']['amount'] ?? 0)) {
                $state['highestBid'] = ['player'=>$playerId, 'amount'=>$amount];
                $state['bidWinner'] = $playerId;
            }
            $state = $this->record($state, 'bid.made', compact('playerId','amount'));
            if (count($state['bids']) >= count($state['players'])) {
                $minimumTotal = $independentMode === 'syrian41' ? 11 : $this->tarneeb400MinimumTotal($state);
                $total = array_sum(array_map(fn($b)=>(int)($b['amount'] ?? 0), $state['bids']));
                if ($total < $minimumTotal) {
                    $state = $this->record($state, 'round.redeal', ['reason'=>$independentMode.'_low_total','total'=>$total,'minimum'=>$minimumTotal]);
                    $new = $this->newGame($state['players'], $state['config']);
                    $new['scores'] = $state['scores'];
                    $new['events'] = $state['events'];
                    return $new;
                }
                $state['trump'] = (string)($state['config']['fixedTrump'] ?? 'H');
                $state['phase'] = 'playing';
                $state['currentIndex'] = ((int)($state['dealerIndex'] ?? 0) + 1) % count($state['players']);
            } else {
                $state = $this->advance($state);
            }
            return $this->finalizeState($state);
        }
        $min = (int)($state['config']['minBid'] ?? 7); $max=(int)($state['config']['maxBid'] ?? 13);
        if ($state['highestBid']) $min = max($min, (int)$state['highestBid']['amount'] + 1);
        if ($amount < $min || $amount > $max) throw new GameEngineException('طلب غير مسموح.');
        $state['bids'][] = ['player'=>$playerId, 'amount'=>$amount];
        $state['highestBid'] = ['player'=>$playerId, 'amount'=>$amount];
        $state['bidWinner'] = $playerId;
        $state = $this->record($state, 'bid.made', compact('playerId','amount'));
        if ($amount >= $max) { $fixedTrump=$state['config']['fixedTrump'] ?? null; if($fixedTrump){$state['trump']=$fixedTrump;$state['phase']='playing';}else{$state['phase']='choose_trump';} $state['currentIndex']=$this->playerIndex($state,$playerId); }
        else $state = $this->advance($state);
        return $this->finalizeState($state);
    }

    protected function chooseTrump(array $state, string $playerId, string $suit): array
    {
        if ($state['phase'] !== 'choose_trump') throw new GameEngineException('ليست مرحلة اختيار الحكم/الطرنيب.');
        if ($state['bidWinner'] !== $playerId) throw new GameEngineException('اختيار الطرنيب لصاحب أعلى طلب فقط.');
        if (!in_array($suit, ['C','D','S','H'], true)) throw new GameEngineException('نوع غير صحيح.');
        $state['trump'] = $suit; $state['phase'] = 'playing';
        $state = $this->record($state, 'trump.chosen', compact('playerId','suit'));
        return $this->finalizeState($state);
    }

    protected function chooseContract(array $state, string $playerId, string $contract,?string $requestedSuit=null): array
    {
        $mode=(string)($state['config']['mode'] ?? '');
        if($mode==='baloot'){
            if($state['phase']!=='bidding') throw new GameEngineException('ليست مرحلة شراء البلوت.');
            if(!in_array($contract,['sun','hokm','confirm_hokm','second_hokm'],true)) throw new GameEngineException('اختر صن أو حكم قانوني.');
            $round=(int)($state['buyRound'] ?? 1);
            if($contract==='hokm' && $round!==1) throw new GameEngineException('الحكم الأول متاح في دورة الشراء الأولى فقط.');
            if($contract==='second_hokm' && $round!==2) throw new GameEngineException('الحكم الثاني متاح بعد مرور الجميع فقط.');
            if($contract==='confirm_hokm' && (string)($state['pendingHokmBuyer'] ?? '')!==$playerId)throw new GameEngineException('تثبيت الحكم لصاحب الطلب الأول فقط.');
            $suit=in_array($contract,['hokm','confirm_hokm'],true)?($contract==='hokm'?$this->suit((string)($state['buyerCard'] ?? '')):(string)($state['pendingContractSuit'] ?? '')):'';
            if($contract==='second_hokm'){
                $suit=(string)($requestedSuit ?? '');
                if(!in_array($suit,['C','D','S','H'],true) || $suit===$this->suit((string)($state['buyerCard'] ?? ''))) throw new GameEngineException('الحكم الثاني يجب أن يكون نوعاً مختلفاً عن ورقة المشتري.');
            }
            if($contract==='hokm'){
                $state['pendingHokmBuyer']=$playerId;$state['pendingContractSuit']=$suit;$state['buyPasses']=0;
                $state['bids'][]=['player'=>$playerId,'contract'=>'hokm_pending','round'=>$round];
                $state=$this->record($state,'baloot.hokm_requested',compact('playerId','suit'));
                return $this->finalizeState($this->advance($state));
            }
            $resolvedContract=$contract==='confirm_hokm'?'hokm':$contract;
            $state['contract']=$resolvedContract;
            $state['bidWinner']=$playerId;
            $state['highestBid']=['player'=>$playerId,'amount'=>$resolvedContract==='sun'?2:1];
            $state['bids'][]=['player'=>$playerId,'contract'=>$resolvedContract,'round'=>$round];
            $state['currentIndex']=$this->playerIndex($state,$playerId);
            $state['phase']='playing';
            $state['trump']=$resolvedContract==='sun'?null:$suit;
            $state['pendingHokmBuyer']=null;$state['pendingContractSuit']=null;$state['buyPasses']=0;
            $state=$this->completeBalootDeal($state,$playerId);
            $state=$this->record($state,'baloot.contract_chosen',['playerId'=>$playerId,'contract'=>$resolvedContract]);
            return $this->finalizeState($state);
        }
        if ($state['phase'] !== 'contract') throw new GameEngineException('ليست مرحلة اختيار العقد.');
        $allowed = $mode==='trix-complex' ? ['complex','trix'] : ['tricks','girls','diamonds','king_hearts','trix'];
        if (!in_array($contract, $allowed, true)) throw new GameEngineException('عقد غير مسموح.');
        if(in_array($contract,(array)($state['contractsUsed'] ?? []),true)) throw new GameEngineException('تم لعب هذا العقد في المملكة الحالية.');
        $state['contract'] = $contract;
        $doubling=in_array($contract,['girls','king_hearts','complex'],true) && ($state['config']['doubling'] ?? true);
        $state['phase']=$contract==='trix'?'trix_playing':($doubling?'doubling':'playing');
        $state['doubledCards']=[];$state['doublingDone']=[];
        $state['currentIndex']=(int)($state['kingdomOwnerIndex'] ?? $state['currentIndex']);
        $state = $this->record($state, 'contract.chosen', compact('playerId','contract'));
        return $this->finalizeState($state);
    }

    protected function isTrixDoublableCard(array $state,string $card): bool
    {
        $contract=(string)($state['contract'] ?? '');
        if($contract==='king_hearts')return $card==='K_H';
        if($contract==='girls')return str_starts_with($card,'Q_');
        if($contract==='complex')return $card==='K_H' || str_starts_with($card,'Q_');
        return false;
    }

    protected function doubleTrixCard(array $state,string $playerId,string $card): array
    {
        if($state['phase']!=='doubling')throw new GameEngineException('ليست مرحلة تدبيل تركس.');
        if(!in_array($card,(array)($state['hands'][$playerId] ?? []),true) || !$this->isTrixDoublableCard($state,$card))throw new GameEngineException('يمكن للاعب تدبيل شيخ الكبة أو بنت موجودة في يده فقط.');
        if(isset($state['doubledCards'][$card]))throw new GameEngineException('تم تدبيل هذه الورقة مسبقاً.');
        $state['doubledCards'][$card]=$playerId;
        return $this->finalizeState($this->record($state,'trix.card_doubled',compact('playerId','card')));
    }

    protected function finishTrixDoubling(array $state,string $playerId): array
    {
        if($state['phase']!=='doubling')throw new GameEngineException('ليست مرحلة تدبيل تركس.');
        $state['doublingDone'][$playerId]=true;
        if(count($state['doublingDone'])>=count($state['players'])){
            $state['phase']='playing';$state['currentIndex']=(int)($state['kingdomOwnerIndex'] ?? 0);
            return $this->finalizeState($this->record($state,'trix.doubling_finished',['cards'=>array_keys($state['doubledCards'])]));
        }
        for($offset=1;$offset<=count($state['players']);$offset++){
            $index=((int)$state['currentIndex']+$offset)%count($state['players']);$pid=(string)$state['players'][$index]['id'];
            if(empty($state['doublingDone'][$pid])){$state['currentIndex']=$index;break;}
        }
        return $this->finalizeState($state);
    }

    protected function legalCards(array $state, string $playerId): array
    {
        $hand = $state['hands'][$playerId] ?? [];
        if (empty($state['trick'])) return $hand;
        $leadSuit = $this->suit($state['trick'][0]['card']);
        $same = array_values(array_filter($hand, fn($c)=>$this->suit($c)===$leadSuit));
        return $same ?: $hand;
    }

    protected function playCard(array $state, string $playerId, string $card): array
    {
        if (!in_array($state['config']['mode'], ['trick','trick400','syrian41','trix','trix-complex','baloot'], true)) throw new GameEngineException('هذه الحركة ليست لهذه اللعبة.');
        if (!in_array($card, $state['hands'][$playerId] ?? [], true)) throw new GameEngineException('الورقة ليست في يد اللاعب.');
        if (!in_array($card, $this->legalCards($state,$playerId), true)) throw new GameEngineException('يجب اتباع نوع الورقة إذا كان موجودًا.');
        $state['hands'][$playerId] = $this->removeOneCard($state['hands'][$playerId], $card);
        $state['trick'][] = ['player'=>$playerId, 'card'=>$card];
        $state = $this->record($state, 'card.played', compact('playerId','card'));
        if (count($state['trick']) >= count($state['players'])) {
            $winner = $this->trickWinner($state);
            $team = $this->teamOf($state, $winner);
            $trickKey = in_array(($state['config']['mode'] ?? ''), ['trick400','syrian41'], true) ? $winner : $team;
            $state['tricksWon'][$trickKey] = ($state['tricksWon'][$trickKey] ?? 0) + 1;
            $state = $this->record($state, 'trick.won', ['winner'=>$winner,'team'=>$team,'score_key'=>$trickKey,'cards'=>$state['trick']]);
            $state['trick'] = [];
            $state['currentIndex'] = $this->playerIndex($state, $winner);
            if ($this->allHandsEmpty($state)) $state = $this->scoreTrickRound($state);
        } else $state = $this->advance($state);
        return $this->finalizeState($state);
    }

    protected function trickWinner(array $state): string
    {
        $leadSuit = $this->suit($state['trick'][0]['card']);
        $trump = $state['trump'] ?? null;
        $winner = $state['trick'][0];
        foreach ($state['trick'] as $play) {
            if ($this->cardBeats($play['card'], $winner['card'], $leadSuit, $trump, $state['config']['mode'])) $winner = $play;
        }
        return $winner['player'];
    }

    protected function cardBeats(string $a, string $b, string $lead, ?string $trump, string $mode): bool
    {
        $as=$this->suit($a); $bs=$this->suit($b);
        if ($trump && $as===$trump && $bs!==$trump) return true;
        if ($trump && $as!==$trump && $bs===$trump) return false;
        if ($as===$bs) return $this->rankValue($a, $mode, $trump) > $this->rankValue($b, $mode, $trump);
        if ($as===$lead && $bs!==$lead) return true;
        return false;
    }

    protected function legalTrixCards(array $state,string $playerId): array
    {
        $legal=[];
        foreach((array)($state['hands'][$playerId] ?? []) as $card){
            $suit=$this->suit($card);$value=$this->rankValue($card);$board=$state['trixBoard'][$suit] ?? ['started'=>false,'low'=>11,'high'=>11];
            if(!$board['started'] && $value===11) $legal[]=$card;
            elseif($board['started'] && ($value===(int)$board['low']-1 || $value===(int)$board['high']+1)) $legal[]=$card;
        }
        return array_values($legal);
    }

    protected function playTrixCard(array $state,string $playerId,string $card): array
    {
        if($state['phase']!=='trix_playing' || ($state['contract'] ?? null)!=='trix') throw new GameEngineException('عقد تركس غير فعّال.');
        if(!in_array($card,$this->legalTrixCards($state,$playerId),true)) throw new GameEngineException('الورقة لا تركب على سلاسل تركس الحالية.');
        $state['hands'][$playerId]=$this->removeOneCard($state['hands'][$playerId],$card);
        $suit=$this->suit($card);$value=$this->rankValue($card);
        if(empty($state['trixBoard'][$suit]['started'])) $state['trixBoard'][$suit]=['started'=>true,'low'=>11,'high'=>11];
        else { $state['trixBoard'][$suit]['low']=min((int)$state['trixBoard'][$suit]['low'],$value); $state['trixBoard'][$suit]['high']=max((int)$state['trixBoard'][$suit]['high'],$value); }
        $state=$this->record($state,'trix.card_played',compact('playerId','card'));
        if(empty($state['hands'][$playerId]) && !in_array($playerId,$state['trixFinishOrder'],true)) $state['trixFinishOrder'][]=$playerId;
        if(count($state['trixFinishOrder'])>=count($state['players'])-1){
            foreach($state['players'] as $p) if(!in_array($p['id'],$state['trixFinishOrder'],true)) $state['trixFinishOrder'][]=$p['id'];
            $awards=[200,150,100,50];
            foreach($state['trixFinishOrder'] as $i=>$pid){$key=($state['config']['partnership'] ?? false)?$this->teamOf($state,(string)$pid):(string)$pid;$state['scores'][$key]=($state['scores'][$key] ?? 0)+($awards[$i] ?? 0);}
            $state=$this->record($state,'trix.finished',['order'=>$state['trixFinishOrder'],'scores'=>$state['scores']]);
            return $this->completeTrixContract($state);
        }
        $state=$this->advance($state);
        return $this->finalizeState($state);
    }

    protected function passTrix(array $state,string $playerId): array
    {
        if($state['phase']!=='trix_playing') throw new GameEngineException('لا يوجد عقد تركس الآن.');
        if($this->legalTrixCards($state,$playerId)) throw new GameEngineException('لديك ورقة قانونية ويجب لعبها.');
        $state=$this->record($state,'trix.pass',compact('playerId'));
        return $this->finalizeState($this->advance($state));
    }

    protected function completeTrixContract(array $state): array
    {
        $contract=(string)($state['contract'] ?? '');
        if($contract!=='' && !in_array($contract,(array)($state['contractsUsed'] ?? []),true)) $state['contractsUsed'][]=$contract;
        $required=($state['config']['mode'] ?? '')==='trix-complex'?2:5;
        if(count($state['contractsUsed'])>=$required){
            $state['contractsUsed']=[];
            $state['kingdomOwnerIndex']=((int)($state['kingdomOwnerIndex'] ?? 0)+1)%count($state['players']);
        }
        $totalContracts=(int)($state['round'] ?? 1);
        $maxContracts=(int)($state['config']['rounds'] ?? (($state['config']['mode'] ?? '')==='trix-complex'?8:20));
        if($totalContracts>=$maxContracts){
            $state['gameOver']=true;
            arsort($state['scores']);
            $state['winner']=array_key_first($state['scores']);
            return $state;
        }
        return $this->newRoundFromState($state);
    }

    protected function scoreTrickRound(array $state): array
    {
        $mode = $state['config']['mode'];
        if (in_array($mode, ['trix','trix-complex'], true)) {
            $penalties = $this->trixPenaltiesFromEvents($state);
            foreach ($penalties as $key=>$pts) $state['scores'][$key] = ($state['scores'][$key] ?? 0) + $pts;
            $state=$this->record($state,'round.scored',['scores'=>$state['scores'],'tricks'=>$state['tricksWon']]);
            return $this->completeTrixContract($state);
        }
        if($mode==='syrian41'){
            foreach ($state['bids'] as $bid) {
                $pid = (string)($bid['player'] ?? '');
                $declared = (int)($bid['amount'] ?? 0);
                if ($pid === '' || $declared < 2) continue;
                $won = (int)($state['tricksWon'][$pid] ?? 0);
                $state['scores'][$pid] = (int)($state['scores'][$pid] ?? 0) + ($won >= $declared ? $declared : -$declared);
            }
            $target=(int)($state['config']['targetScore'] ?? 41);
            foreach ([0,1] as $team) {
                $members=array_values(array_filter($state['players'],fn($p)=>(int)($p['team'] ?? -1)===$team));
                if(count($members)!==2) continue;
                $a=(string)$members[0]['id']; $b=(string)$members[1]['id'];
                if(((int)($state['scores'][$a] ?? 0)>=$target && (int)($state['scores'][$b] ?? 0)>0) ||
                   ((int)($state['scores'][$b] ?? 0)>=$target && (int)($state['scores'][$a] ?? 0)>0)) {
                    $state['gameOver']=true; $state['winner']=$team;
                }
            }
        } elseif($mode==='trick400'){
            foreach ($state['bids'] as $bid) {
                $pid = (string)($bid['player'] ?? '');
                $declared = (int)($bid['amount'] ?? 0);
                if ($pid === '' || $declared < 2) continue;
                $won = (int)($state['tricksWon'][$pid] ?? 0);
                $points = $this->tarneeb400BidPoints($declared, (float)($state['scores'][$pid] ?? 0));
                $state['scores'][$pid] = (float)($state['scores'][$pid] ?? 0) + ($won >= $declared ? $points : -$points);
            }
            foreach ([0,1] as $team) {
                $members = array_values(array_filter($state['players'], fn($p)=>(int)($p['team'] ?? -1)===$team));
                if (count($members)!==2) continue;
                $a=(string)$members[0]['id']; $b=(string)$members[1]['id'];
                if ((float)($state['scores'][$a] ?? 0) >= 41 && (float)($state['scores'][$b] ?? 0) > 0) { $state['gameOver']=true; $state['winner']=$team; }
                if ((float)($state['scores'][$b] ?? 0) >= 41 && (float)($state['scores'][$a] ?? 0) > 0) { $state['gameOver']=true; $state['winner']=$team; }
            }
        } elseif($mode==='baloot'){
            $round=$this->balootRoundPoints($state);
            foreach($round as $team=>$points) $state['scores'][$team]=($state['scores'][$team] ?? 0)+$points;
        } else {
            $bidTeam = $this->teamOf($state, $state['bidWinner'] ?? $state['players'][0]['id']);
            $bid = (int)($state['highestBid']['amount'] ?? 0);
            $won = (int)($state['tricksWon'][$bidTeam] ?? 0);
            $unit = $mode === 'trick400' ? 1 : 1;
            if ($won >= $bid) $state['scores'][$bidTeam] = ($state['scores'][$bidTeam] ?? 0) + ($won * $unit);
            else $state['scores'][$bidTeam] = ($state['scores'][$bidTeam] ?? 0) - ($bid * $unit);
            foreach ($state['scores'] as $team=>$score) {
                if ((string)$team !== (string)$bidTeam) $state['scores'][$team] = ($state['scores'][$team] ?? 0) + (($state['tricksWon'][$team] ?? 0) * $unit);
            }
        }
        $state = $this->record($state, 'round.scored', ['scores'=>$state['scores'], 'tricks'=>$state['tricksWon']]);
        if (!in_array($mode, ['trick400','syrian41'], true)) {
            foreach ($state['scores'] as $key=>$score) {
                if ($score >= (int)($state['config']['targetScore'] ?? 41)) { $state['gameOver']=true; $state['winner']=$key; }
            }
        }
        if (!$state['gameOver']) $state = $this->newRoundFromState($state);
        return $state;
    }


    protected function tarneeb400MinimumBid(array $state, string $playerId): int
    {
        $score=(float)($state['scores'][$playerId] ?? 0);
        if($score>=50) return 5;
        if($score>=40) return 4;
        if($score>=30) return 3;
        return 2;
    }

    protected function tarneeb400MinimumTotal(array $state): int
    {
        $maxScore=0.0;
        foreach($state['scores'] as $score) $maxScore=max($maxScore,(float)$score);
        if($maxScore>=50) return 14;
        if($maxScore>=40) return 13;
        if($maxScore>=30) return 12;
        return 11;
    }

    protected function tarneeb400BidPoints(int $bid, float $currentScore): int
    {
        $normal=[2=>2,3=>3,4=>4,5=>10,6=>12,7=>14,8=>16,9=>27,10=>40,11=>40,12=>40,13=>40];
        $advanced=[2=>2,3=>3,4=>4,5=>5,6=>6,7=>14,8=>16,9=>27,10=>40,11=>40,12=>40,13=>40];
        return (int)(($currentScore>=30?$advanced:$normal)[$bid] ?? $bid);
    }

    protected function completeBalootDeal(array $state,string $buyer): array
    {
        $buyerCard=(string)($state['buyerCard'] ?? '');
        if($buyerCard!=='') $state['hands'][$buyer][]=$buyerCard;
        foreach($state['players'] as $player){
            $pid=(string)$player['id'];
            while(count($state['hands'][$pid] ?? [])<8){
                $card=array_shift($state['deck']);
                if($card===null) throw new GameEngineException('تعذر إكمال توزيع البلوت.');
                $state['hands'][$pid][]=$card;
            }
            $state['hands'][$pid]=$this->sortCards($state['hands'][$pid]);
        }
        $state['buyerCard']=null;
        return $state;
    }

    protected function balootRoundPoints(array $state): array
    {
        $raw=[0=>0,1=>0];
        $contract=(string)($state['contract'] ?? 'hokm');
        $trump=$state['trump'] ?? null;
        $tricks=array_values(array_filter($state['events'] ?? [],fn($e)=>($e['type'] ?? '')==='trick.won'));
        foreach($tricks as $i=>$event){
            $winner=(string)($event['data']['winner'] ?? '');
            $team=$this->teamOf($state,$winner);
            foreach((array)($event['data']['cards'] ?? []) as $play){
                $card=(string)($play['card'] ?? '');$rank=$this->rank($card);$isTrump=$trump && $this->suit($card)===$trump;
                $points=$isTrump ? (['J'=>20,'9'=>14,'A'=>11,'10'=>10,'K'=>4,'Q'=>3][$rank] ?? 0) : (['A'=>11,'10'=>10,'K'=>4,'Q'=>3,'J'=>2][$rank] ?? 0);
                $raw[$team]=($raw[$team] ?? 0)+$points;
            }
            if($i===count($tricks)-1) $raw[$team]=($raw[$team] ?? 0)+10;
        }
        $buyerTeam=(int)$this->teamOf($state,(string)($state['bidWinner'] ?? ''));
        $otherTeam=$buyerTeam===0?1:0;
        if(count($tricks)===8 && (($raw[$buyerTeam] ?? 0)===0 || ($raw[$otherTeam] ?? 0)===0)){
            $sweeper=(($raw[0] ?? 0)>0)?0:1;
            $sweepScore=[0=>0,1=>0];
            $sweepScore[$sweeper]=44;
            return $sweepScore;
        }
        $points=[
            0=>$this->roundBalootAbnat((int)($raw[0] ?? 0),$contract),
            1=>$this->roundBalootAbnat((int)($raw[1] ?? 0),$contract),
        ];
        if(($points[$buyerTeam] ?? 0)<=($points[$otherTeam] ?? 0)) return [$buyerTeam=>0,$otherTeam=>$contract==='sun'?26:16];
        return $points;
    }

    protected function roundBalootAbnat(int $raw,string $contract): int
    {
        $scaled=$contract==='sun'?($raw*2)/10:$raw/10;
        $floor=(int)floor($scaled);$fraction=$scaled-$floor;
        // Baloot tables round an exact x.5 down and x.6+ up.
        return $fraction>0.5?$floor+1:$floor;
    }

    protected function trixPenaltiesFromEvents(array $state): array
    {
        $pen = [];
        $contract = $state['contract'] ?? 'tricks';
        foreach ($state['players'] as $p) { $key=($state['config']['partnership'] ?? false)?$p['team']:(string)$p['id']; $pen[$key]=0; }
        foreach ($state['events'] as $e) if (($e['type'] ?? '') === 'trick.won') {
            $winner = $e['data']['winner'];
            $winnerKey=($state['config']['partnership'] ?? false)?$this->teamOf($state,(string)$winner):(string)$winner;
            $cards = array_column($e['data']['cards'] ?? [], 'card');
            if ($contract === 'tricks') $pen[$winnerKey] -= 15;
            if ($contract === 'girls') foreach ($cards as $c) if (str_starts_with($c,'Q_')) $pen[$winnerKey] -= 25;
            if ($contract === 'diamonds') foreach ($cards as $c) if (str_ends_with($c,'_D')) $pen[$winnerKey] -= 10;
            if ($contract === 'king_hearts') foreach ($cards as $c) if ($c==='K_H') $pen[$winnerKey] -= 75;
            if ($contract === 'complex') { foreach ($cards as $c) { if (str_starts_with($c,'Q_')) $pen[$winnerKey]-=25; if(str_ends_with($c,'_D'))$pen[$winnerKey]-=10; if($c==='K_H')$pen[$winnerKey]-=75; } $pen[$winnerKey]-=15; }
            foreach($cards as $card){
                $doubler=(string)($state['doubledCards'][$card] ?? '');if($doubler==='')continue;
                $bonus=$card==='K_H'?75:(str_starts_with($card,'Q_')?25:0);if($bonus<=0)continue;
                $pen[$winnerKey]-=$bonus;
                $doublerKey=($state['config']['partnership'] ?? false)?$this->teamOf($state,$doubler):$doubler;
                $pen[$doublerKey]=($pen[$doublerKey] ?? 0)+$bonus;
            }
        }
        return $pen;
    }

    protected function newRoundFromState(array $old): array
    {
        $players = $old['players'];
        $options = $old['config'];
        $options['dealerIndex'] = ((int)($old['dealerIndex'] ?? (count($players)-1)) + 1) % max(1,count($players));
        $new = $this->newGame($players, $options);
        $new['scores'] = $old['scores'];
        $new['round'] = ($old['round'] ?? 1) + 1;
        $new['events'] = $old['events'];
        if(in_array($old['config']['mode'] ?? '',['trix','trix-complex'],true)){
            $new['contractsUsed']=$old['contractsUsed'] ?? [];
            $new['kingdomOwnerIndex']=$old['kingdomOwnerIndex'] ?? 0;
            $new['currentIndex']=$new['kingdomOwnerIndex'];
        }
        return $this->record($new, 'round.started', ['round'=>$new['round']]);
    }

    protected function drawDeck(array $state, string $playerId): array
    {
        if ($state['phase'] !== 'draw') throw new GameEngineException('يجب أن تكون في مرحلة السحب.');
        if (empty($state['deck'])) $this->recycleDiscard($state);
        $card = array_shift($state['deck']);
        if (!$card) throw new GameEngineException('لا يوجد ورق للسحب.');
        $state['hands'][$playerId][] = $card;
        $state['phase'] = 'discard';
        $state['turnDrawSource'][$playerId] = 'deck';
        $state['turnMelded'][$playerId] = false;
        $state = $this->record($state, 'rummy.draw_deck', compact('playerId'));
        return $this->finalizeState($state);
    }

    protected function drawDiscard(array $state, string $playerId): array
    {
        if ($state['phase'] !== 'draw') throw new GameEngineException('يجب أن تكون في مرحلة السحب.');
        if (empty($state['discard'])) throw new GameEngineException('الرمي فارغ.');
        $card = array_pop($state['discard']);
        $state['hands'][$playerId][] = $card;
        $state['phase'] = 'discard';
        $state['turnDrawSource'][$playerId] = 'discard';
        $state['turnMelded'][$playerId] = false;
        $state = $this->record($state, 'rummy.draw_discard', ['playerId'=>$playerId,'card'=>$card]);
        return $this->finalizeState($state);
    }

    protected function discardCard(array $state, string $playerId, string $card): array
    {
        if ($state['phase'] !== 'discard') throw new GameEngineException('يجب السحب قبل الرمي.');
        if (!in_array($card, $state['hands'][$playerId] ?? [], true)) throw new GameEngineException('الورقة ليست في اليد.');
        if (($state['turnDrawSource'][$playerId] ?? null)==='discard' && empty($state['turnMelded'][$playerId])) throw new GameEngineException('بعد سحب الورقة المكشوفة يجب تنزيل مجموعة قانونية قبل الرمي.');
        $state['hands'][$playerId] = $this->removeOneCard($state['hands'][$playerId], $card);
        $state['discard'][] = $card;
        $state['starterDiscardPending'] = false;
        $state['turnDrawSource'][$playerId] = null;
        $state['turnMelded'][$playerId] = false;
        $state = $this->record($state, 'rummy.discard', compact('playerId','card'));
        if (empty($state['hands'][$playerId])) $state = $this->scoreRummyRound($state, $playerId, false);
        else { $state['phase']='draw'; $state=$this->advance($state); }
        return $this->finalizeState($state);
    }

    protected function meld(array $state, string $playerId, array $cards): array
    {
        if ($state['phase'] !== 'discard') throw new GameEngineException('يمكن التنزيل بعد السحب فقط.');
        if (!empty($state['starterDiscardPending'])) throw new GameEngineException('يجب على اللاعب البادئ رمي الورقة الإضافية أولاً.');
        $cards = array_values(array_map('strval', $cards));
        if (count($cards) < 3 || count($cards) > 13) throw new GameEngineException('المجموعة يجب أن تكون من 3 إلى 13 ورقة.');
        if (!$this->containsCards($state['hands'][$playerId] ?? [], $cards)) throw new GameEngineException('إحدى الأوراق المختارة غير موجودة بالعدد المطلوب في اليد.');
        if (!$this->isValidMeld($cards)) throw new GameEngineException('المجموعة/السلسلة غير صحيحة.');
        $value = $this->meldValue($cards);
        if (!$this->hasRummyOpened($state,$playerId) && $value < $this->rummyOpeningRequirement($state,$playerId)) throw new GameEngineException('قيمة الافتتاح أقل من المطلوب.');
        foreach ($cards as $c) $state['hands'][$playerId] = $this->removeOneCard($state['hands'][$playerId], $c);
        $state['melds'][$playerId][] = ['cards'=>$cards, 'value'=>$value];
        $state['turnMelded'][$playerId] = true;
        $state = $this->recordRummyOpening($state,$playerId,$value);
        $state = $this->record($state, 'rummy.meld', compact('playerId','cards','value'));
        if (empty($state['hands'][$playerId])) $state = $this->scoreRummyRound($state, $playerId, true);
        return $this->finalizeState($state);
    }

    protected function meldMany(array $state,string $playerId,array $groups): array
    {
        if($state['phase']!=='discard') throw new GameEngineException('يمكن التنزيل بعد السحب فقط.');
        if(!empty($state['starterDiscardPending'])) throw new GameEngineException('يجب رمي الورقة الإضافية أولاً.');
        if(!$groups || count($groups)>8) throw new GameEngineException('اختر من مجموعة واحدة إلى 8 مجموعات.');
        $normalized=[];$all=[];$total=0;
        foreach($groups as $group){
            $cards=array_values(array_map('strval',(array)$group));
            if(count($cards)<3 || count($cards)>13 || !$this->isValidMeld($cards)) throw new GameEngineException('إحدى مجموعات التنزيل غير قانونية.');
            $normalized[]=$cards;$all=array_merge($all,$cards);$total+=$this->meldValue($cards);
        }
        if(!$this->containsCards($state['hands'][$playerId] ?? [],$all)) throw new GameEngineException('الأوراق المختارة غير موجودة بالعدد المطلوب.');
        if(!$this->hasRummyOpened($state,$playerId) && $total<$this->rummyOpeningRequirement($state,$playerId)) throw new GameEngineException('مجموع افتتاحك أقل من المطلوب.');
        foreach($all as $card) $state['hands'][$playerId]=$this->removeOneCard($state['hands'][$playerId],$card);
        foreach($normalized as $cards) $state['melds'][$playerId][]=['cards'=>$cards,'value'=>$this->meldValue($cards)];
        $state['turnMelded'][$playerId] = true;
        $state=$this->recordRummyOpening($state,$playerId,$total);
        $state=$this->record($state,'rummy.meld_many',['playerId'=>$playerId,'groups'=>$normalized,'value'=>$total]);
        if(empty($state['hands'][$playerId])) $state=$this->scoreRummyRound($state,$playerId,true);
        return $this->finalizeState($state);
    }

    protected function layoff(array $state, string $playerId, string $targetPlayer, int $meldIndex, array $cards): array
    {
        if ($state['phase'] !== 'discard') throw new GameEngineException('يمكن التركيب بعد السحب فقط.');
        if (!empty($state['starterDiscardPending'])) throw new GameEngineException('يجب رمي الورقة الإضافية أولاً.');
        $cards = array_values(array_map('strval', $cards));
        if (!$cards) throw new GameEngineException('اختر ورقة واحدة على الأقل للتركيب.');
        if (!$this->containsCards($state['hands'][$playerId] ?? [], $cards)) throw new GameEngineException('إحدى أوراق التركيب غير موجودة بالعدد المطلوب في اليد.');
        if (!$this->hasRummyOpened($state,$playerId)) throw new GameEngineException('يجب أن يفتح اللاعب أو فريقه قبل التركيب.');
        if (!isset($state['melds'][$targetPlayer][$meldIndex])) throw new GameEngineException('المجموعة الهدف غير موجودة.');
        $scope=(string)($state['config']['layoffScope'] ?? (!empty($state['config']['partnership'])?'team':'all'));
        if ($scope==='team' && $this->teamOf($state, $targetPlayer) !== $this->teamOf($state, $playerId)) throw new GameEngineException('يمكن التركيب على مجموعاتك أو مجموعات شريكك فقط.');
        if ($scope==='self' && $targetPlayer !== $playerId) throw new GameEngineException('يمكن التركيب على مجموعاتك فقط في هذا النمط.');

        $combined = array_merge((array)$state['melds'][$targetPlayer][$meldIndex]['cards'], $cards);
        if (count($combined) > 13 || !$this->isValidMeld($combined)) throw new GameEngineException('هذه الأوراق لا تركب قانونيًا على المجموعة المختارة.');
        foreach ($cards as $c) $state['hands'][$playerId] = $this->removeOneCard($state['hands'][$playerId], $c);
        $state['melds'][$targetPlayer][$meldIndex] = ['cards'=>array_values($combined), 'value'=>$this->meldValue($combined)];
        $state = $this->record($state, 'rummy.layoff', compact('playerId','targetPlayer','meldIndex','cards'));
        if (empty($state['hands'][$playerId])) $state = $this->scoreRummyRound($state, $playerId, true);
        return $this->finalizeState($state);
    }

    protected function isValidMeld(array $cards): bool
    {
        if (count($cards) < 3 || count($cards) > 13) return false;
        $jokers = array_values(array_filter($cards, fn($c)=>str_starts_with($c,'JOKER')));
        $wildTwos = !empty($this->config['wildTwos']);
        $twos = $wildTwos ? array_values(array_filter($cards, fn($c)=>$this->rank($c)==='2')) : [];
        if (count($jokers) > (int)($this->config['maxJokersPerMeld'] ?? count($cards))) return false;
        if (count($twos) > (int)($this->config['maxTwosPerMeld'] ?? count($cards))) return false;
        $wildCount = count($jokers) + count($twos);
        $natural = array_values(array_filter($cards, fn($c)=>!str_starts_with($c,'JOKER') && (!$wildTwos || $this->rank($c)!=='2')));
        if (count($natural) < 2) return false;

        $suits = array_map(fn($c)=>$this->suit($c), $natural);
        $ranks = array_map(fn($c)=>$this->rank($c), $natural);
        $sameRank = count(array_unique($ranks)) === 1 && count(array_unique($suits)) === count($suits);
        if ($sameRank) {
            $allowedSetRanks = (array)($this->config['setRanks'] ?? []);
            if ($allowedSetRanks && !in_array($ranks[0], $allowedSetRanks, true)) return false;
            return count($natural) + $wildCount <= 4;
        }

        if (count(array_unique($suits)) !== 1) return false;
        $values = array_map(fn($c)=>$this->rankValue($c), $natural);
        if (count(array_unique($values)) !== count($values)) return false;
        return $this->runGapsCovered($values, $wildCount);
    }

    protected function runGapsCovered(array $values, int $wildCount): bool
    {
        $variants = [$values];
        if (in_array(14, $values, true)) $variants[] = array_map(fn($v)=>$v===14 ? 1 : $v, $values);
        foreach ($variants as $vals) {
            sort($vals);
            $gaps = 0;
            for ($i=1; $i<count($vals); $i++) $gaps += max(0, $vals[$i]-$vals[$i-1]-1);
            if ($gaps <= $wildCount && (max($vals)-min($vals)+1+$wildCount) <= 13) return true;
        }
        return false;
    }

    protected function hasRummyOpened(array $state,string $playerId): bool
    {
        if (!empty($state['config']['teamOpening'])) {
            $team=(int)$this->teamOf($state,$playerId);
            return (bool)($state['teamOpened'][$team] ?? false);
        }
        return !empty($state['melds'][$playerId]);
    }

    protected function rummyOpeningRequirement(array $state,string $playerId): int
    {
        if (!empty($state['config']['teamOpening'])) {
            $team=(int)$this->teamOf($state,$playerId);
            return (int)($state['teamOpeningThresholds'][$team] ?? ($state['config']['opening'] ?? 51));
        }
        return (int)($state['config']['opening'] ?? 51);
    }

    protected function recordRummyOpening(array $state,string $playerId,int $value): array
    {
        if (!empty($state['config']['teamOpening'])) {
            $team=(int)$this->teamOf($state,$playerId);
            if (empty($state['teamOpened'][$team])) {
                $state['teamOpened'][$team]=true;
                if (!empty($state['config']['openingEscalates'])) {
                    $other=$team===0?1:0;
                    if (empty($state['teamOpened'][$other])) {
                        $state['teamOpeningThresholds'][$other]=max((int)($state['teamOpeningThresholds'][$other] ?? 51),$value+1);
                    }
                }
            }
        }
        return $state;
    }

    protected function meldValue(array $cards): int
    {
        return array_sum(array_map(function ($card): int {
            if (str_starts_with($card, 'JOKER')) return 25;
            if (!empty($this->config['wildTwos']) && $this->rank($card)==='2') return 20;
            $rank = $this->rank($card);
            if ($rank === 'A') return 15;
            return min(10, $this->rankValue($card));
        }, $cards));
    }

    protected function containsCards(array $hand, array $selected): bool
    {
        $counts = array_count_values($hand);
        foreach ($selected as $card) {
            if (($counts[$card] ?? 0) < 1) return false;
            $counts[$card]--;
        }
        return true;
    }

    protected function removeOneCard(array $cards, string $target): array
    {
        $index = array_search($target, $cards, true);
        if ($index === false) return array_values($cards);
        unset($cards[$index]);
        return array_values($cards);
    }

    protected function recycleDiscard(array &$state): void
    {
        if (count($state['discard'] ?? []) <= 1) return;
        $top = array_pop($state['discard']);
        $state['deck'] = array_values($state['discard']);
        $state['discard'] = [$top];
        $this->shuffleDeck($state['deck']);
    }

    protected function suggestMelds(array $hand, int $opening): array
    {
        $out=[]; $n=count($hand);
        for($i=0;$i<$n;$i++) for($j=$i+1;$j<$n;$j++) for($k=$j+1;$k<$n;$k++) {
            $cards=[$hand[$i],$hand[$j],$hand[$k]];$value=$this->meldValue($cards);
            if($this->isValidMeld($cards) && $value >= $opening) $out[]=['cards'=>$cards,'value'=>$value];
            if(count($out)>8) return $out;
        }
        usort($out, fn($a,$b)=>$b['value']<=>$a['value']);
        return $out;
    }

    protected function organize(array $state, string $playerId, string $strategy): array
    {
        $state['hands'][$playerId] = $this->sortCards($state['hands'][$playerId] ?? []);
        $state = $this->record($state, 'hand.organized', compact('playerId','strategy'));
        return $this->finalizeState($state);
    }

    protected function scoreRummyRound(array $state, string $winnerId, bool $meldOut): array
    {
        if (!empty($state['config']['banakilScoring'])) return $this->scoreBanakilRound($state, $winnerId, $meldOut);
        $roundEvents=$this->currentRoundEvents($state);
        $winnerMeldEvents=array_values(array_filter($roundEvents,fn($e)=>in_array(($e['type'] ?? ''),['rummy.meld','rummy.meld_many'],true) && (($e['data']['playerId'] ?? '')===$winnerId)));
        $winnerLayoffEvents=array_values(array_filter($roundEvents,fn($e)=>(($e['type'] ?? '')==='rummy.layoff') && (($e['data']['playerId'] ?? '')===$winnerId)));
        $isHand=$meldOut && count($winnerMeldEvents)===1 && !$winnerLayoffEvents;
        $partnership=!empty($state['config']['partnership']);
        $winnerKey=$partnership?$this->teamOf($state,$winnerId):$winnerId;
        $state['scores'][$winnerKey]=($state['scores'][$winnerKey] ?? 0)+($isHand?-60:-30);
        foreach ($state['players'] as $p) {
            $pid=(string)$p['id'];$key=$partnership?$this->teamOf($state,$pid):$pid;
            if((string)$key===(string)$winnerKey) continue;
            $delta=array_sum(array_map(fn($c)=>$this->handPenalty((string)$c),$state['hands'][$pid] ?? []));
            if(empty($state['melds'][$pid])) $delta+=100;
            $state['scores'][$key]=($state['scores'][$key] ?? 0)+$delta;
        }
        $state = $this->record($state, 'rummy.round_scored', ['winner'=>$winnerId,'scores'=>$state['scores']]);
        if (($state['round'] ?? 1) >= (int)($state['config']['rounds'] ?? 5)) { $state['gameOver']=true; $state['winner']=$this->bestScoreKey($state['scores']); }
        else $state = $this->newRoundFromState($state);
        return $state;
    }


    protected function scoreBanakilRound(array $state, string $winnerId, bool $meldOut): array
    {
        $roundScores=[0=>0.0,1=>0.0];
        foreach((array)($state['melds'] ?? []) as $pid=>$meldList){
            $team=(int)$this->teamOf($state,(string)$pid);
            foreach((array)$meldList as $meld) foreach((array)($meld['cards'] ?? []) as $card) $roundScores[$team]+=$this->banakilCardPoints((string)$card);
        }
        foreach($state['players'] as $player){
            $pid=(string)$player['id']; $team=(int)$this->teamOf($state,$pid);
            foreach((array)($state['hands'][$pid] ?? []) as $card) $roundScores[$team]-=$this->banakilCardPoints((string)$card);
        }
        $winnerTeam=(int)$this->teamOf($state,$winnerId);
        $roundScores[$winnerTeam]+=20;
        if($meldOut){
            $priorMeldEvents=array_values(array_filter($state['events'] ?? [],fn($e)=>in_array(($e['type'] ?? ''),['rummy.meld','rummy.meld_many','rummy.layoff'],true) && (($e['data']['playerId'] ?? '')===$winnerId)));
            if(count($priorMeldEvents)<=1) $roundScores[$winnerTeam]+=51;
        }
        foreach($roundScores as $team=>$points) $state['scores'][$team]=round((float)($state['scores'][$team] ?? 0)+$points,1);
        $state=$this->record($state,'banakil.round_scored',['winner'=>$winnerId,'round_scores'=>$roundScores,'scores'=>$state['scores']]);
        foreach($state['scores'] as $team=>$score) if((float)$score>=(float)($state['config']['targetScore'] ?? 222)){ $state['gameOver']=true; $state['winner']=$team; }
        if(!$state['gameOver']) $state=$this->newRoundFromState($state);
        return $state;
    }

    protected function banakilCardPoints(string $card): float
    {
        if(str_starts_with($card,'JOKER')) return 4.0;
        $rank=$this->rank($card);
        if($rank==='2') return 2.0;
        if(in_array($rank,['3','4','5','6'],true)) return 0.5;
        return 1.0;
    }

    protected function bestScoreKey(array $scores): string|int { asort($scores); return array_key_first($scores); }

    /** @return array<int,array<string,mixed>> */
    protected function currentRoundEvents(array $state): array
    {
        $events=array_values((array)($state['events'] ?? []));$start=0;
        foreach($events as $i=>$event) if(($event['type'] ?? '')==='round.started') $start=$i+1;
        return array_slice($events,$start);
    }

    protected function handPenalty(string $card): int
    {
        if(str_starts_with($card,'JOKER')) return 15;
        $rank=$this->rank($card);
        if($rank==='A') return 11;
        if(in_array($rank,['K','Q','J','10'],true)) return 10;
        return is_numeric($rank)?(int)$rank:0;
    }

    /** @return array<int,array<string,mixed>> */
    protected function solitaireActions(array $state,string $playerId): array
    {
        $actions=[];
        if(!empty($state['hands'][$playerId] ?? [])) $actions[]=['type'=>'draw_stock'];
        elseif(!empty($state['waste'][$playerId] ?? [])) $actions[]=['type'=>'recycle_stock'];

        $waste=(array)($state['waste'][$playerId] ?? []);
        if($waste){
            $card=(string)end($waste);
            if($this->canMoveToFoundation($state,$playerId,$card)) $actions[]=['type'=>'move_to_foundation','source'=>'waste','card'=>$card];
            foreach($this->solitaireTableauTargets($state,$playerId,$card) as $to) $actions[]=['type'=>'move_to_tableau','source'=>'waste','card'=>$card,'to_column'=>$to];
        }

        foreach((array)($state['tableau'][$playerId] ?? []) as $column=>$pile){
            $up=array_values((array)($pile['up'] ?? []));
            if(!$up) continue;
            $top=(string)end($up);
            if($this->canMoveToFoundation($state,$playerId,$top)) $actions[]=['type'=>'move_to_foundation','source'=>'tableau','column'=>(int)$column,'card'=>$top];
            foreach($up as $fromIndex=>$card){
                $sequence=array_slice($up,$fromIndex);
                if(!$this->validSolitaireRun($sequence)) continue;
                foreach($this->solitaireTableauTargets($state,$playerId,(string)$card,(int)$column) as $to){
                    $actions[]=['type'=>'move_to_tableau','source'=>'tableau','from_column'=>(int)$column,'from_index'=>(int)$fromIndex,'to_column'=>$to,'card'=>(string)$card];
                }
            }
        }
        return $actions;
    }

    protected function solitaireDraw(array $state, string $playerId): array
    {
        if(($state['config']['mode'] ?? '')!=='solitaire') throw new GameEngineException('هذه الحركة ليست للسوليتير.');
        $card = array_shift($state['hands'][$playerId]);
        if (!$card) throw new GameEngineException('لا يوجد ورق في الستوك؛ أعد تدوير الويست أولاً.');
        $state['waste'][$playerId][] = $card;
        $state = $this->record($state, 'solitaire.draw', compact('playerId','card'));
        return $this->finalizeState($this->advance($state));
    }

    protected function solitaireRecycle(array $state,string $playerId): array
    {
        if(!empty($state['hands'][$playerId] ?? [])) throw new GameEngineException('لا يمكن إعادة الويست قبل نفاد الستوك.');
        $waste=array_values((array)($state['waste'][$playerId] ?? []));
        if(!$waste) throw new GameEngineException('لا توجد أوراق لإعادة التدوير.');
        $state['hands'][$playerId]=array_reverse($waste);
        $state['waste'][$playerId]=[];
        $state=$this->record($state,'solitaire.recycled',compact('playerId'));
        return $this->finalizeState($this->advance($state));
    }

    protected function solitaireFoundation(array $state, string $playerId, string $card,string $source='auto',?int $column=null): array
    {
        if(!$this->canMoveToFoundation($state,$playerId,$card)) throw new GameEngineException('الأساس يبدأ بالآس ثم يصعد بالنوع نفسه حتى الملك.');
        if($source==='waste' || ($source==='auto' && (string)end($state['waste'][$playerId])===$card)){
            if((string)end($state['waste'][$playerId])!==$card) throw new GameEngineException('يمكن تحريك أعلى ورقة في الويست فقط.');
            array_pop($state['waste'][$playerId]);
        }else{
            if($column===null) $column=$this->solitaireColumnWithTop($state,$playerId,$card);
            if($column===null) throw new GameEngineException('يمكن تحريك أعلى ورقة مكشوفة فقط.');
            $up=(array)($state['tableau'][$playerId][$column]['up'] ?? []);
            if((string)end($up)!==$card) throw new GameEngineException('يمكن تحريك أعلى ورقة مكشوفة فقط.');
            array_pop($state['tableau'][$playerId][$column]['up']);
            $this->revealSolitaireTop($state,$playerId,$column);
        }
        $s=$this->suit($card);
        $state['foundation'][$playerId][$s][]=$card;
        $state['scores'][$playerId]=(int)($state['scores'][$playerId] ?? 0)+10;
        $state=$this->record($state,'solitaire.foundation',compact('playerId','card','source'));
        return $this->finishSolitaireAction($state,$playerId);
    }

    protected function solitaireTableauMove(array $state,string $playerId,array $action): array
    {
        $to=(int)($action['to_column'] ?? -1);
        if($to<0 || $to>6) throw new GameEngineException('عمود الهدف غير صالح.');
        $source=(string)($action['source'] ?? 'tableau');
        if($source==='waste'){
            $waste=(array)($state['waste'][$playerId] ?? []);$card=(string)end($waste);
            if($card==='' || !in_array($to,$this->solitaireTableauTargets($state,$playerId,$card),true)) throw new GameEngineException('لا يمكن تركيب ورقة الويست هنا.');
            array_pop($state['waste'][$playerId]);$moving=[$card];
        }else{
            $from=(int)($action['from_column'] ?? -1);$index=(int)($action['from_index'] ?? -1);
            if($from<0 || $from>6 || $from===$to) throw new GameEngineException('مصدر السلسلة غير صالح.');
            $up=array_values((array)($state['tableau'][$playerId][$from]['up'] ?? []));
            if($index<0 || $index>=count($up)) throw new GameEngineException('موضع السلسلة غير صالح.');
            $moving=array_slice($up,$index);$card=(string)$moving[0];
            if(!$this->validSolitaireRun($moving) || !in_array($to,$this->solitaireTableauTargets($state,$playerId,$card,$from),true)) throw new GameEngineException('السلسلة ليست تنازلية بألوان متبادلة.');
            $state['tableau'][$playerId][$from]['up']=array_slice($up,0,$index);
            $this->revealSolitaireTop($state,$playerId,$from);
        }
        $state['tableau'][$playerId][$to]['up']=array_merge((array)($state['tableau'][$playerId][$to]['up'] ?? []),$moving);
        $state=$this->record($state,'solitaire.tableau_move',['playerId'=>$playerId,'cards'=>$moving,'to'=>$to]);
        return $this->finishSolitaireAction($state,$playerId);
    }

    protected function finishSolitaireAction(array $state,string $playerId): array
    {
        $count=0;foreach((array)($state['foundation'][$playerId] ?? []) as $pile)$count+=count((array)$pile);
        if($count>=52){$state['gameOver']=true;$state['winner']=$playerId;$state=$this->record($state,'solitaire.finished',compact('playerId'));}
        else $state=$this->advance($state);
        return $this->finalizeState($state);
    }

    protected function canMoveToFoundation(array $state,string $playerId,string $card): bool
    {
        $s=$this->suit($card);if(!in_array($s,['C','D','S','H'],true)) return false;
        return $this->solitaireRank($card)===count((array)($state['foundation'][$playerId][$s] ?? []))+1;
    }

    /** @return array<int,int> */
    protected function solitaireTableauTargets(array $state,string $playerId,string $card,?int $exclude=null): array
    {
        $out=[];$value=$this->solitaireRank($card);
        foreach((array)($state['tableau'][$playerId] ?? []) as $column=>$pile){
            if($exclude!==null && (int)$column===$exclude) continue;
            $up=(array)($pile['up'] ?? []);
            if(!$up){if($value===13)$out[]=(int)$column;continue;}
            $top=(string)end($up);
            if($this->solitaireRank($top)===$value+1 && $this->solitaireIsRed($top)!==$this->solitaireIsRed($card)) $out[]=(int)$column;
        }
        return $out;
    }

    protected function validSolitaireRun(array $cards): bool
    {
        for($i=1;$i<count($cards);$i++) if($this->solitaireRank((string)$cards[$i-1])!==$this->solitaireRank((string)$cards[$i])+1 || $this->solitaireIsRed((string)$cards[$i-1])===$this->solitaireIsRed((string)$cards[$i])) return false;
        return !empty($cards);
    }

    protected function revealSolitaireTop(array &$state,string $playerId,int $column): void
    {
        if(!empty($state['tableau'][$playerId][$column]['up'])) return;
        if(!empty($state['tableau'][$playerId][$column]['down'])) $state['tableau'][$playerId][$column]['up'][]=array_pop($state['tableau'][$playerId][$column]['down']);
    }

    protected function solitaireColumnWithTop(array $state,string $playerId,string $card): ?int
    {
        foreach((array)($state['tableau'][$playerId] ?? []) as $column=>$pile){$up=(array)($pile['up'] ?? []);if($up && (string)end($up)===$card)return (int)$column;}
        return null;
    }

    protected function solitaireRank(string $card): int{return ['A'=>1,'2'=>2,'3'=>3,'4'=>4,'5'=>5,'6'=>6,'7'=>7,'8'=>8,'9'=>9,'10'=>10,'J'=>11,'Q'=>12,'K'=>13][$this->rank($card)] ?? 0;}
    protected function solitaireIsRed(string $card): bool{return in_array($this->suit($card),['D','H'],true);}

    protected function setAway(array $state, string $playerId, bool $away): array
    {
        foreach ($state['players'] as &$p) if ($p['id']===$playerId) $p['away']=$away;
        $state = $this->record($state, $away?'player.away':'player.returned', compact('playerId'));
        return $this->finalizeState($state);
    }

    public function botMove(array $state): array
    {
        if ($state['gameOver']) return $state;
        $pid = $this->currentPlayerId($state);
        $actions = array_values(array_filter($this->availableActions($state, $pid), fn($a)=>($a['type']??'') !== 'wait'));
        if (!$actions) return $state;
        $choice = $this->chooseBotAction($state, $pid, $actions);
        return $this->applyAction($state, $pid, $choice);
    }

    protected function chooseBotAction(array $state, string $pid, array $actions): array
    {
        foreach ($actions as $a) if (($a['type']??'')==='bid') { $power=$this->handPower($state['hands'][$pid]??[]); if($power>85) return $a; }
        foreach (['choose_trump','choose_contract','double_card','finish_doubling','pass_trix','layoff','meld_many','meld','draw_discard','draw_deck','play_card','discard','draw_stock','move_to_foundation','pass'] as $pref) foreach ($actions as $a) if (($a['type']??'')===$pref) return $a;
        return $actions[0];
    }

    protected function handPower(array $hand): int { return array_sum(array_map(fn($c)=>$this->rankValue($c), $hand)); }

    public function playerView(array $state, string $playerId): array
    {
        $this->assertPlayer($state, $playerId);
        $view = $state;
        foreach ($view['hands'] as $pid=>$hand) {
            if ($pid !== $playerId || ($state['config']['mode'] ?? '')==='solitaire') $view['hands'][$pid] = ['count'=>count($hand)];
        }
        $this->hideSolitaireDownCards($view);
        $view['deck_count'] = count($view['deck'] ?? []);
        unset($view['deck'], $view['seed']);
        $view['antiCheat'] = [
            'lastHash' => $state['antiCheat']['lastHash'] ?? null,
            'moveCounter' => (int)($state['antiCheat']['moveCounter'] ?? 0),
            'dealCommitment' => $state['antiCheat']['dealCommitment'] ?? null,
            'dealReveal' => !empty($state['gameOver']) ? ($state['seed'] ?? null) : null,
            'serverOnly' => true,
        ];
        return $view;
    }

    public function spectatorView(array $state): array
    {
        $view = $state;
        foreach ($view['hands'] as $pid=>$hand) $view['hands'][$pid] = ['count'=>count($hand)];
        $this->hideSolitaireDownCards($view);
        $view['deck_count'] = count($view['deck'] ?? []);
        unset($view['deck'], $view['seed']);
        $view['antiCheat'] = [
            'lastHash' => $state['antiCheat']['lastHash'] ?? null,
            'moveCounter' => (int)($state['antiCheat']['moveCounter'] ?? 0),
            'dealCommitment' => $state['antiCheat']['dealCommitment'] ?? null,
            'dealReveal' => !empty($state['gameOver']) ? ($state['seed'] ?? null) : null,
            'serverOnly' => true,
        ];
        return $view;
    }

    protected function hideSolitaireDownCards(array &$view): void
    {
        if(($view['config']['mode'] ?? '')!=='solitaire') return;
        foreach((array)($view['tableau'] ?? []) as $pid=>$columns) foreach((array)$columns as $i=>$column){
            $view['tableau'][$pid][$i]['down_count']=count((array)($column['down'] ?? []));
            unset($view['tableau'][$pid][$i]['down']);
        }
    }

    public function serialize(array $state): string { return json_encode($state, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); }
    public function deserialize(string $json): array { $s=json_decode($json,true); if(!is_array($s)) throw new GameEngineException('حالة غير صالحة'); return $s; }

    protected function finalizeState(array $state): array
    {
        $state['antiCheat']['moveCounter'] = (int)($state['antiCheat']['moveCounter'] ?? 0) + 1;
        $state['antiCheat']['lastHash'] = $this->stateHash($state);
        return $state;
    }
    public function stateHash(array $state): string { $copy=$state; unset($copy['antiCheat']['lastHash']); return hash('sha256', json_encode($copy, JSON_UNESCAPED_UNICODE)); }

    protected function record(array $state, string $type, array $data=[]): array
    {
        $state['events'][] = ['n'=>count($state['events'])+1, 'type'=>$type, 'data'=>$data, 'time'=>date('c')];
        return $state;
    }
    protected function currentPlayerId(array $state): string { return (string)$state['players'][$state['currentIndex']]['id']; }
    protected function advance(array $state): array { $state['currentIndex']=($state['currentIndex']+1)%count($state['players']); return $state; }
    protected function assertPlayer(array $state, string $pid): void { foreach($state['players'] as $p) if($p['id']===$pid) return; throw new GameEngineException('اللاعب غير موجود.'); }
    protected function playerIndex(array $state, string $pid): int { foreach($state['players'] as $i=>$p) if($p['id']===$pid) return $i; throw new GameEngineException('اللاعب غير موجود.'); }
    protected function teamOf(array $state, string $pid): string|int { foreach($state['players'] as $p) if($p['id']===$pid) return $p['team']; return $pid; }
    protected function allHandsEmpty(array $state): bool { foreach($state['hands'] as $h) if(count($h)>0) return false; return true; }
    protected function oppositeSameColorSuit(string $suit): string
    {
        return match($suit){ 'C'=>'S', 'S'=>'C', 'D'=>'H', 'H'=>'D', default=>'H' };
    }
    protected function rank(string $card): string { return explode('_',$card)[0] ?? $card; }
    protected function suit(string $card): string { return explode('_',$card)[1] ?? ''; }
    protected function rankValue(string $card, ?string $mode=null, ?string $trump=null): int
    {
        if (str_starts_with($card,'JOKER')) return 20;
        $rank=$this->rank($card); $map=['A'=>14,'K'=>13,'Q'=>12,'J'=>11,'10'=>10,'9'=>9,'8'=>8,'7'=>7,'6'=>6,'5'=>5,'4'=>4,'3'=>3,'2'=>2];
        if ($mode==='baloot') { $s=$this->suit($card); if($trump && $s===$trump) $map=['J'=>20,'9'=>19,'A'=>18,'10'=>17,'K'=>16,'Q'=>15,'8'=>8,'7'=>7]; else $map=['A'=>14,'10'=>13,'K'=>12,'Q'=>11,'J'=>10,'9'=>9,'8'=>8,'7'=>7]; }
        return $map[$rank] ?? 0;
    }
    protected function sortCards(array $cards): array { usort($cards, fn($a,$b)=>[$this->suit($a),$this->rankValue($b)] <=> [$this->suit($b),$this->rankValue($a)]); return $cards; }
}
