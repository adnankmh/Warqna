<?php

namespace App\Services\GameEngine;

/**
 * Canonical registry used by the mobile API, admin audit and client UI.
 * Rules are original summaries of common public-domain game mechanics.
 */
final class EngineRegistry
{
    /** @return array<string,array<string,mixed>> */
    public static function all(): array
    {
        return [
            'tarneeb' => self::entry('طرنيب','Tarneeb','tarneeb_standalone_v2',4,4,true,13,52,['bid','pass','choose_trump','play_card'],
                'توزّع 13 ورقة لكل لاعب. المزايدة من 7 إلى 13، وصاحب أعلى طلب يختار الطرنيب. يجب اتباع النوع إن وُجد، وتفوز أعلى ورقة من النوع المتصدر ما لم تُلعب ورقة طرنيب.',
                'Each player receives 13 cards. Bids run from 7 to 13; the highest bidder chooses trump. Players must follow suit when possible.'),
            'syrian_tarneeb' => self::entry('طرنيب سوري','Syrian Tarneeb','global_syrian_tarneeb_final_v1',4,4,true,13,52,['bid','pass','choose_trump','play_card'],
                'طرنيب شراكة لأربعة لاعبين مع مزايدة 7–13، دوران عكسي، وإلزام اتباع النوع.',
                'Four-player partnership Tarneeb with 7–13 bidding, counter-clockwise turns and mandatory follow-suit.'),
            'tarneeb_400' => self::entry('طرنيب 400','Tarneeb 400','global_tarneeb_400_final_v1',4,4,true,13,52,['bid','pass','play_card'],
                'لعبة شراكة هدفها الوصول إلى 400 نقطة، والكبة هي الطرنيب الثابت. يحتسب الطلب واللمّات وفق نظام 400.',
                'A partnership game played to 400 points with Hearts as fixed trump and 400-style bidding/scoring.'),
            'trix' => self::entry('تركس','Trix','global_trix_final_v1',4,4,false,13,52,['choose_contract','play_card'],
                'أربع ممالك لكل لاعب، ويختار صاحب المملكة واحدة من خمس طلبات: شيخ الكبة، بنات، ديناري، لطوش أو تركس. الفوز للأعلى نقاطاً بعد اكتمال الممالك.',
                'Each player owns a kingdom and selects one of five contracts. The highest total score after all kingdoms wins.'),
            'trix_partner' => self::entry('تركس شراكة','Partnership Trix','global_trix_partner_final_v1',4,4,true,13,52,['choose_contract','play_card'],
                'قواعد تركس مع فريقين متقابلين، وتجمع نقاط الشريكين معاً.',
                'Trix rules played by two opposing partnerships; partners combine their scores.'),
            'trix_complex' => self::entry('تركس كمبلكس','Trix Complex','global_trix_complex_final_v1',4,4,false,13,52,['choose_contract','play_card'],
                'يجمع الكمبلكس عقوبات شيخ الكبة والبنات والديناري واللطوش في طلب واحد، مع طلب تركس منفصل حسب النمط.',
                'Complex combines King of Hearts, Queens, Diamonds and Tricks penalties into one contract, with Trix handled separately.'),
            'hand' => self::entry('هاند','Hand','global_saudi_hand_final_v1',2,4,false,14,106,['draw_deck','draw_discard','meld','discard'],
                'تستخدم مجموعتان مع جوكرين. يسحب اللاعب ثم ينزل مجموعات أو يركّب، ويجب أن يرمي ورقة قبل انتهاء دوره. تنتهي الجولة عند نفاد يد لاعب.',
                'Uses two decks plus two jokers. Draw, optionally meld/lay off, then discard; the round ends when a player empties their hand.'),
            'hand_partner' => self::entry('هاند شراكة','Partnership Hand','global_hand_partnership_final_v1',4,4,true,14,106,['draw_deck','draw_discard','meld','discard'],
                'هاند بنظام فريقين متقابلين، مع احتساب نزول ونقاط الفريق كوحدة واحدة.',
                'Hand played by two partnerships, with team meld thresholds and combined scoring.'),
            'saudi_hand' => self::entry('هاند سعودي','Saudi Hand','global_saudi_hand_final_v1',2,5,false,14,106,['draw_deck','draw_discard','meld','discard'],
                'هاند سعودي لعدد 2–5 لاعبين، سحب ثم نزول/تركيب ثم رمي، وتحتسب الأوراق المتبقية على الخاسرين.',
                'Saudi Hand for 2–5 players: draw, meld/lay off, discard, and score remaining cards against losing players.'),
            'banakil' => self::entry('بناكيل','Banakil','global_banakil_final_v1',2,4,true,14,106,['draw_deck','draw_discard','meld','discard'],
                'تستخدم مجموعتين من الورق مع الجوكر والبناكل، والهدف التخلص من اليد عبر المجموعات والتركيب والوصول إلى الهدف المحدد.',
                'Uses two decks with jokers and wild twos; players meld and lay off to empty their hands and reach the target score.'),
            'pinochle' => self::entry('بناكل كلاسيك','Classic Pinochle','global_banakil_classic_final_v1',2,4,true,14,106,['bid','pass','choose_trump','meld','play_card'],
                'نسخة كلاسيكية تعتمد المزايدة والطرنيب والمشاريع ثم لعب اللمّات وحساب النقاط.',
                'Classic bidding, trump selection, meld declaration and trick play with score calculation.'),
            'baloot' => self::entry('بلوت','Baloot','global_baloot_final_v1',4,4,true,8,32,['bid','pass','choose_trump','play_card'],
                'أربعة لاعبين بفريقين، 32 ورقة، شراء صن أو حكم ثم لعب اللمّات مع ترتيب وقيم مختلفة بين الصن والحكم.',
                'Four players in partnerships using 32 cards; bidding selects Sun or Hokm, each with its own rank and scoring order.'),
            'solitaire_multiplayer' => self::entry('سوليتير تنافسي','Competitive Solitaire','global_competitive_solitaire_final_v1',1,4,false,7,52,['draw_stock','move_to_foundation','organize'],
                'سباق تنافسي لترتيب الأوراق في الأساسات وفق النوع والترتيب، مع احتساب السرعة والحركات الصحيحة.',
                'A competitive race to build foundations by suit and rank, scored by speed and legal moves.'),
            'domino' => self::entry('دومينو','Dominoes','universal_domino_rules',2,4,false,7,28,['play_tile','pass','draw_tile'],
                'تستخدم 28 قطعة دبل-ستة، يحصل اللاعب على 7 قطع، وتوضع القطع على طرف يطابق الرقم المفتوح. يفوز من ينهي قطعه أولاً.',
                'Uses a double-six set of 28 tiles. Players receive seven tiles and match open ends; first to empty their hand wins.'),
            'basra' => self::entry('باصرة','Basra','universal_basra_rules',2,2,false,4,52,['play_card','capture'],
                'أربعة أوراق لكل لاعب وأربع على الأرض. يلتقط اللاعب الورقة المماثلة أو مجموعاً يساوي قيمة ورقته، وللولد و7 الديناري أحكام خاصة.',
                'Four cards per player and four on the table. Capture equal ranks or combinations matching the played value; Jacks and 7♦ have special powers.'),
            'jackaroo' => self::entry('جاكارو','Jackaroo','universal_jackaroo_rules',4,4,true,4,52,['play_card','move_piece'],
                'فريقان، أربعة أحجار لكل لاعب، والبطاقات تحرك الأحجار بقيم وقدرات مختلفة. يفوز الفريق عند إدخال جميع أحجاره إلى الأمان.',
                'Two partnerships move four marbles each using card-specific movement rules; the first team to bring all marbles home wins.'),
            'backgammon' => self::entry('طاولة الزهر','Backgammon','universal_backgammon_rules',2,2,false,15,0,['roll_dice','move_checker'],
                'لكل لاعب 15 حجراً، وتحدد النردات الحركات القانونية. يجب إدخال الحجر المضروب أولاً، والفائز من يخرج أحجاره كلها.',
                'Each player has 15 checkers. Dice determine legal moves; hit checkers must re-enter first, and the first player to bear off all checkers wins.'),
            'chess' => self::entry('شطرنج','Chess','universal_chess_rules',2,2,false,16,0,['move_piece','offer_draw','resign'],
                'شطرنج قياسي مع منع النقلات غير القانونية، كش/كش مات، تعادل، استسلام وسجل كامل للنقلات.',
                'Standard chess with legal-move enforcement, check/checkmate, draws, resignation and full move history.'),
        ];
    }

    /** @return array<string,mixed> */
    private static function entry(string $ar,string $en,string $engine,int $min,int $max,bool $partnership,int $hand,int $deck,array $actions,string $rulesAr,string $rulesEn): array
    {
        return compact('engine','min','max','partnership','hand','deck','actions') + [
            'name'=>['ar'=>$ar,'en'=>$en],
            'rules'=>['ar'=>$rulesAr,'en'=>$rulesEn],
            'server_authoritative'=>true,
            'free_play'=>true,
            'fair_shuffle'=>'server_seeded_shuffle_with_unique_deck_validation',
        ];
    }

    /** @return array<string,mixed>|null */
    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }
}
