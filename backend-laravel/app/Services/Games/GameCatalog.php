<?php
namespace App\Services\Games;

class GameCatalog
{
    public static function all(): array
    {
        return [
            'tarneeb'=>['ar'=>'طرنيب','en'=>'Tarneeb','min'=>4,'max'=>4,'partners'=>true,'engine'=>'tarneeb','targets'=>[31,41,61],'family'=>'tarneeb','icon'=>'🃏','summary'=>'طرنيب كامل: طلب 7-13، اختيار طرنيب، اتباع النوع، لمّات وفوز/خسارة للطلب.'],
            'tarneeb_41'=>['ar'=>'طرنيب 41','en'=>'Tarneeb 41','min'=>4,'max'=>4,'partners'=>true,'engine'=>'tarneeb','targets'=>[41],'family'=>'tarneeb','icon'=>'♣️','summary'=>'نسخة طرنيب سريعة بهدف 41.'],
            'tarneeb_61'=>['ar'=>'طرنيب 61','en'=>'Tarneeb 61','min'=>4,'max'=>4,'partners'=>true,'engine'=>'tarneeb','targets'=>[61],'family'=>'tarneeb','icon'=>'♦️','summary'=>'طرنيب طويل بهدف 61، بنفس نظام الطلب والطرنيب.'],
            'syrian_tarneeb'=>['ar'=>'طرنيب سوري','en'=>'Syrian Tarneeb','min'=>4,'max'=>4,'partners'=>true,'engine'=>'tarneeb','targets'=>[61],'family'=>'tarneeb','icon'=>'♠️','summary'=>'طرنيب سوري بطلب وطرنيب واتباع نوع.'],
            'tarneeb_400'=>['ar'=>'طرنيب 400','en'=>'Tarneeb 400','min'=>4,'max'=>4,'partners'=>true,'engine'=>'tarneeb','targets'=>[400],'family'=>'tarneeb','icon'=>'♥️','summary'=>'طرنيب طويل بهدف 400 مع طلب وطرنيب ولمّات.'],
            'hand'=>['ar'=>'هاند','en'=>'Hand','min'=>2,'max'=>4,'partners'=>false,'engine'=>'meld_draw','targets'=>[51,101],'family'=>'meld','icon'=>'🂡','summary'=>'سحب/رمي، مجموعات وسلاسل، نزول قانوني، والفائز الأقل نقاطًا.'],
            'hand_partner'=>['ar'=>'هاند شراكة','en'=>'Hand Partner','min'=>4,'max'=>4,'partners'=>true,'engine'=>'meld_draw','targets'=>[51,101],'family'=>'meld','icon'=>'🤝','summary'=>'هاند بنظام فريقين وشراكة.'],
            'saudi_hand'=>['ar'=>'هاند سعودي','en'=>'Saudi Hand','min'=>2,'max'=>4,'partners'=>false,'engine'=>'meld_draw','targets'=>[51,101],'family'=>'meld','icon'=>'🂱','summary'=>'هاند سعودي بنظام نزول وسحب ورمي.'],
            'pinochle'=>['ar'=>'بناكل','en'=>'Pinochle','min'=>2,'max'=>4,'partners'=>false,'engine'=>'meld_draw','targets'=>[100],'family'=>'meld','icon'=>'🂮','summary'=>'بناكل/تجميع وسلاسل مع محرك هاند آمن.'],
            'banakil'=>['ar'=>'بناكيل','en'=>'Banakil','min'=>2,'max'=>4,'partners'=>false,'engine'=>'meld_draw','targets'=>[100],'family'=>'meld','icon'=>'🂲','summary'=>'بناكيل بنظام السحب والتجميع.'],
            'solitaire_multiplayer'=>['ar'=>'سوليتير تنافسي','en'=>'Solitaire Multiplayer','min'=>2,'max'=>4,'partners'=>false,'engine'=>'meld_draw','targets'=>[100],'family'=>'meld','icon'=>'🃁','summary'=>'سوليتير تنافسي مبسط بسحب وترتيب وإنهاء سريع.'],
            'trix'=>['ar'=>'تركس','en'=>'Trix','min'=>4,'max'=>4,'partners'=>false,'engine'=>'trix','targets'=>[],'family'=>'trix','icon'=>'👑','summary'=>'عقود الممالك واللطوش بنظام تركس.'],
            'trix_partner'=>['ar'=>'تركس شراكة','en'=>'Partner Trix','min'=>4,'max'=>4,'partners'=>true,'engine'=>'trix_partner','targets'=>[],'family'=>'trix','icon'=>'🤝','summary'=>'تركس بنظام فريقين متقابلين.'],
            'trix_complex'=>['ar'=>'تركس كمبلكس','en'=>'Trix Complex','min'=>4,'max'=>4,'partners'=>false,'engine'=>'contract','targets'=>[],'family'=>'trix','icon'=>'💎','summary'=>'تركس كمبلكس مع عقود ونقاط.'],
            'baloot'=>['ar'=>'بلوت','en'=>'Baloot','min'=>4,'max'=>4,'partners'=>true,'engine'=>'baloot','targets'=>[152],'family'=>'gulf','icon'=>'🪙','summary'=>'بلوت 4 لاعبين، صن/حكم، شراكة ونقاط.']
        ];
    }

    public static function rules(string $key): string
    {
        $game=self::all()[$key] ?? ['ar'=>$key,'summary'=>'قواعد قابلة للتوسعة'];
        $base=[
            'tarneeb'=>'طرنيب: 4 لاعبين، فريقان متقابلان، 52 ورقة، 13 ورقة لكل لاعب. الطلب من 7 إلى 13 أو Pass. اللاعب الذي يمرر لا يطلب مرة أخرى في نفس الجولة. تنتهي المزايدة بعد 3 Pass بعد أعلى طلب. صاحب أعلى طلب يختار الطرنيب ويبدأ اللعب. يجب اتباع نوع أول ورقة إن توفر. اللمة لأعلى ورقة من النوع المطلوب أو لأعلى طرنيب. إذا حقق فريق الطالب الطلب تُضاف له اللمات، وإذا فشل تُخصم قيمة الطلب ويُضاف للخصم ما أخذه من لمات.',
            'hand'=>'هاند: سحب من الدك أو الرمي، ثم تنزيل مجموعات/سلاسل 3+ حسب شرط النزول، ثم رمي ورقة. نهاية اليد عند التخلص من الأوراق، ويتم حساب أوراق الخصوم.',
            'baloot'=>'بلوت: 32 ورقة، صن/حكم، مشاريع، ترتيب وقيم خاصة في الحكم والصن، واحتساب نقاط الفريق. محرك Warqna يفصل الاختيار واللعب واحتساب النقاط.',
            'trix'=>'تركس: عقود ممالك، إلزام اتباع النوع، عقوبات للعقود السلبية، وتدبيل للأوراق الخاصة في الكمبلكس.',
            'domino'=>'دومينو: طقم 0-6، مطابقة الطرف الأيسر أو الأيمن، السحب أو المرور عند عدم وجود حركة، وحساب النقاط عند الإغلاق.',
            'ludo'=>'لودو: النرد يولد من السيرفر، القطع تتحرك حسب الرقم، الأكل يعيد الخصم للبيت، والفوز بإدخال كل القطع.',
            'backgammon'=>'طاولة: رمي نرد من السيرفر، حركة قانونية، ضرب القطعة المفردة، إخراج القطع عند اكتمال البيت.',
        ];
        $family=$game['family'] ?? '';
        $txt=$base[$key] ?? ($base[$family] ?? ($game['ar'].': '.$game['summary']));
        return $txt."\n\nمبدأ Warqna: السيرفر هو الحكم، لا تُرسل أوراق الخصوم، يتم التحقق من الدور والحركة واتباع النوع والنقاط، ويتم التعامل مع الانقطاع عبر Auto-play مؤقت.";
    }

    public static function translations(string $key): array
    {
        $names = self::all()[$key] ?? ['en'=>$key,'ar'=>$key]; $en=$names['en'] ?? $key;
        $short = self::all()[$key]['summary'] ?? $en;
        return [
            'ar'=>self::rules($key),
            'en'=>$en.': '.$short.' Server-authoritative validation, legal turn/action checks, scoring, timeout auto-play and anti-cheat.',
            'tr'=>$en.': '.$short.' Sunucu otoritesiyle sıra, yasal hamle, puanlama, otomatik oynama ve anti-hile uygulanır.',
            'fr'=>$en.' : '.$short.' Validation côté serveur, actions légales, score, auto-play et anti-triche.',
            'de'=>$en.': '.$short.' Servervalidierung, legale Aktionen, Wertung, Auto-Spiel und Anti-Cheat.',
            'es'=>$en.': '.$short.' Validación del servidor, acciones legales, puntuación, auto-jugada y anti-trampa.',
        ];
    }
}
