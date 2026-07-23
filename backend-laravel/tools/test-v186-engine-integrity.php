<?php
/**
 * Warqnaa V186+ standalone integrity audit for the expanded product catalog.
 * Runs without Laravel/Composer and exercises real engine objects and payloads.
 */
$engineBase = dirname(__DIR__).'/app/Services/GameEngine';
require_once dirname(__DIR__).'/app/Services/WarqnaPro/PlayActionNormalizer.php';
foreach ([
    'GameRuleContract.php','Card.php','DeckFactory.php','AbstractCardRules.php',
    'DominoRules.php','BasraRules.php','BackgammonRules.php','JackarooRules.php','LeekhaRules.php',
    'ChessRules.php','TarneebRules.php','GlobalCardEngineRules.php',
    'UniversalSocialGameRules.php','EngineRegistry.php','GameFactory.php',
] as $file) require_once $engineBase.'/'.$file;
require_once dirname(__DIR__).'/app/Services/Games/GameCatalog.php';

use App\Services\GameEngine\{EngineRegistry,GameFactory};
use App\Services\Games\GameCatalog;

function v186Check(bool $ok,string $message): void {
    if(!$ok){file_put_contents('php://stderr',"[FAIL] $message\n");exit(1);}
    echo "[PASS] $message\n";
}
function v186Assert(bool $ok,string $message): void {
    if(!$ok){file_put_contents('php://stderr',"[FAIL] $message\n");exit(1);}
}
function v186Players(int $count): array {
    $players=[];for($i=0;$i<$count;$i++)$players[]='user:'.($i+1);return $players;
}
function v186Payload(array $action,array $state=[],string $turn=''): array {
    $type=(string)($action['type']??'');
    unset($action['type'],$action['reason']);
    if($type==='pass_cards')$action['cards']=array_slice($state['hands'][$turn]??[],0,3);
    return $action;
}
function v186ChooseAction(array $actions): ?array {
    $priority=[
        'bid','choose_trump','choose_contract','double_card','finish_doubling','play_card','play_tile','move',
        'move_to_foundation','move_to_tableau','meld_many','meld','layoff',
        'discard','draw_deck','draw_discard',
        'draw_stock','recycle_stock','draw','roll','next_round','pass_trix','pass',
    ];
    foreach($priority as $type)foreach($actions as $action)if(($action['type']??'')===$type)return $action;
    return $actions[0]??null;
}

$expected=[
    'tarneeb','trix','hand','banakil','baloot','basra','tarneeb_400',
    'syrian_tarneeb','trix_complex','saudi_hand','hand_partner','trix_partner',
    'tarneeb_41','tarneeb_61','pinochle','solitaire_multiplayer','domino','backgammon',
    'jackaroo','leekha',
];
v186Check(EngineRegistry::PRODUCT_KEYS===$expected,'canonical product list contains the 20 Flutter games');
$registryKeys=array_keys(EngineRegistry::all());$catalogKeys=array_keys(GameCatalog::all());
sort($registryKeys);sort($catalogKeys);$sortedExpected=$expected;sort($sortedExpected);
v186Check($registryKeys===$sortedExpected && $catalogKeys===$sortedExpected,'registry and Laravel catalog expose the same 20 games');
$catalog=GameCatalog::all();
v186Check(
    ($catalog['banakil']['targets']??[])===[222,150]
        && ($catalog['pinochle']['targets']??[])===[222,150],
    'Banakil defaults to 222 for partnerships and offers 150 for one-versus-one'
);
try { GameFactory::make('forged_game_key'); v186Check(false,'unknown engines must fail closed'); }
catch(InvalidArgumentException) { v186Check(true,'unknown engines fail closed instead of using a permissive fallback'); }

foreach(EngineRegistry::PRODUCT_KEYS as $key){
    $meta=EngineRegistry::get($key);$count=(int)$meta['max'];$rules=GameFactory::make($key);
    $state=$rules->initialState(v186Players($count),['target'=>($key==='basra'?121:41),'player_count'=>$count]);
    v186Check(count(array_unique($state['players']??[]))===count($state['players']??[]) && !empty($state['turn']) && !empty($state['phase']),$key.' initializes a complete state with unique seats');
    if(method_exists($rules,'availableActions') && ($state['phase']??'')!=='finished'){
        $turn=(string)$state['turn'];$actions=$rules->availableActions($state,$turn);
        v186Check(!empty($actions),$key.' advertises at least one legal action for the current turn');
        $action=$actions[0];$type=(string)($action['type']??'');
        v186Check($type!=='' && $type!=='wait' && $rules->validate($state,$turn,$type,v186Payload($action,$state,$turn)),$key.' advertised action passes full engine validation');
        $other=current(array_values(array_filter($state['players'],fn($player)=>$player!==$turn)));
        if($other!==false)v186Check(!$rules->validate($state,(string)$other,$type,v186Payload($action,$state,$turn)),$key.' rejects the same action out of turn');
    }
}

// Advance every real engine through many consecutive state transitions. This
// catches second-turn/phase bugs that an initialization-only test cannot see.
foreach(EngineRegistry::PRODUCT_KEYS as $key){
    $meta=EngineRegistry::get($key);$count=(int)$meta['max'];$rules=GameFactory::make($key);
    $state=$rules->initialState(v186Players($count),['target'=>($key==='basra'?121:41),'player_count'=>$count]);
    $moves=0;
    for($step=0;$step<260 && ($state['phase']??'')!=='finished';$step++){
        $turn=(string)($state['turn']??'');$actions=$rules->availableActions($state,$turn);$chosen=v186ChooseAction($actions);
        if(!$chosen)break;
        foreach(array_slice($actions,0,20) as $advertised){
            $advertisedType=(string)($advertised['type']??'');
            v186Assert($advertisedType!=='' && $advertisedType!=='wait' && $rules->validate($state,$turn,$advertisedType,v186Payload($advertised,$state,$turn)),$key.' advertises an invalid '.$advertisedType.' action at transition '.($moves+1));
        }
        $type=(string)$chosen['type'];$payload=v186Payload($chosen,$state,$turn);
        v186Assert($rules->validate($state,$turn,$type,$payload),$key.' rejects advertised transition '.($moves+1).' ('.$type.')');
        $state=$rules->apply($state,$turn,$type,$payload);
        v186Assert(empty($state['last_error'])&&empty($state['last_error_message']),$key.' fails transition '.($moves+1).' ('.$type.')');
        $moves++;
    }
    v186Check($moves>=8 || ($state['phase']??'')==='finished',$key.' completes a '.$moves.'-transition engine simulation');
}

// Hand family: official 15/14 opening, discard-first and 2..5 players.
foreach([2,3,4,5] as $count){
    $state=GameFactory::make('hand')->initialState(v186Players($count),['player_count'=>$count]);
    v186Check(count($state['hands']['user:1'])===15 && count($state['hands']['user:'.$count])===14 && ($state['engine_phase']??'')==='discard','Hand supports '.$count.' players with the audited 15/14 opening');
}

// Competitive Solitaire: every player owns a complete independent Klondike deck.
$solitaire=GameFactory::make('solitaire_multiplayer')->initialState(v186Players(4),['player_count'=>4]);
$global=$solitaire['_global_engine'];
foreach(v186Players(4) as $player){
    $columns=$global['tableau'][$player]??[];$tableauCount=0;
    foreach($columns as $column)$tableauCount+=count($column['down']??[])+count($column['up']??[]);
    v186Check(count($columns)===7 && count($global['hands'][$player]??[])===24 && $tableauCount===28,'Solitaire gives '.$player.' seven columns plus a 24-card stock');
}
$badSolitaire=$solitaire;$badSolitaire['turn']='user:1';$badSolitaire['_global_engine']['currentIndex']=0;
v186Check(!GameFactory::make('solitaire_multiplayer')->validate($badSolitaire,'user:1','move_to_tableau',['source'=>'tableau','from_column'=>0,'from_index'=>0,'to_column'=>99,'card'=>'A_clubs']),'Solitaire rejects a forged target column');

// Dedicated engines retain their cardinal invariants.
$basra=GameFactory::make('basra')->initialState(v186Players(2),['target'=>121]);
v186Check((int)$basra['target']===121 && count($basra['deck'])+count($basra['table'])+array_sum(array_map('count',$basra['hands']))===52,'Basra conserves 52 cards and uses the 121 target');
$domino=GameFactory::make('domino')->initialState(v186Players(4),['target'=>100]);
$tiles=$domino['boneyard'];foreach($domino['hands'] as $hand)$tiles=array_merge($tiles,$hand);
v186Check(count($tiles)===28 && count(array_unique($tiles))===28,'Domino conserves all 28 unique double-six tiles');
$backgammon=GameFactory::make('backgammon')->initialState(v186Players(2));$checkers=0;
foreach($backgammon['points'] as $point)$checkers+=(int)$point['count'];
v186Check($checkers===30 && GameFactory::make('backgammon')->validate($backgammon,'user:1','roll',[]),'Backgammon starts with 30 checkers and a legal server roll');

// Jackaroo rotates the dealer and starts the next four-card round after them.
$jackarooRules=GameFactory::make('jackaroo');$jackaroo=$jackarooRules->initialState(v186Players(4));
foreach(v186Players(4) as $player)$jackaroo['hands'][$player]=['2_clubs'];
foreach(v186Players(4) as $player)$jackaroo=$jackarooRules->apply($jackaroo,$player,'pass',[]);
v186Check(
    (int)$jackaroo['round']===2 && (int)$jackaroo['dealer_index']===0 && $jackaroo['turn']==='user:2',
    'Jackaroo rotates the dealer and next-round starter'
);

// Leekha does not end merely because partnership totals sum to 101; an
// individual must reach 101, and the dealer/right-hand starter then rotate.
$leekhaRules=GameFactory::make('leekha');$leekha=$leekhaRules->initialState(v186Players(4));
$leekha['phase']='playing';$leekha['turn']='user:1';$leekha['trick']=[];
$leekha['score']=['user:1'=>60,'user:2'=>0,'user:3'=>41,'user:4'=>0];
$leekha['hands']=['user:1'=>['A_clubs'],'user:2'=>['2_clubs'],'user:3'=>['3_clubs'],'user:4'=>['4_clubs']];
foreach(['user:1'=>'A_clubs','user:2'=>'2_clubs','user:3'=>'3_clubs','user:4'=>'4_clubs'] as $player=>$card){
    $leekha=$leekhaRules->apply($leekha,$player,'play_card',['card'=>$card]);
}
v186Check(
    $leekha['phase']==='passing' && (int)$leekha['round']===2 && $leekha['turn']==='user:2',
    'Leekha waits for an individual 101 and rotates the next-round starter'
);

echo "[PASS] V186+ completed real-engine integrity coverage for all 20 product games.\n";
