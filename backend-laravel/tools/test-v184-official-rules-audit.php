<?php
/**
 * Standalone rules alignment audit for the curated Warqnaa games.
 * No Laravel bootstrap is required.
 */
$base=__DIR__.'/../app/Services/GameEngine/GlobalEngines';
foreach([
    'SyrianTarneebEngine.php','Tarneeb400Engine.php','SaudiHandEngine.php',
    'HandPartnershipEngine.php','BanakilEngine.php','TrixEngine.php',
    'TrixPartnershipEngine.php','TrixComplexEngine.php','BalootEngine.php',
] as $file) require_once "$base/$file";

function auditPlayers(int $n): array { $out=[]; for($i=0;$i<$n;$i++) $out[]=['id'=>'p'.$i,'name'=>'P'.$i,'bot'=>false]; return $out; }
function auditCheck(bool $ok,string $message): void { if(!$ok){fwrite(STDERR,"[FAIL] $message\n");exit(1);} echo "[PASS] $message\n"; }
function auditAction(array $actions,string $type,?int $amount=null): array {
    foreach($actions as $action){
        if(($action['type']??'')!==$type) continue;
        if($amount!==null && (int)($action['amount']??-1)!==$amount) continue;
        return $action;
    }
    throw new RuntimeException("Missing action $type".($amount!==null?"/$amount":''));
}
class AuditTrixEngine extends TrixEngine { public function penalties(array $state): array { return $this->trixPenaltiesFromEvents($state); } }

// Syrian Tarneeb 41: 13 cards, exposed-card trump, independent 2-13 declarations, total >= 11.
$engine=new SyrianTarneebEngine();
$state=$engine->newGame(auditPlayers(4),['seed'=>184]);
auditCheck($state['phase']==='bidding','Syrian 41 starts with independent declarations');
auditCheck(count($state['hands']['p0'])===13 && count($state['scores'])===4,'Syrian 41 deals 13 and keeps individual scores');
auditCheck(!empty($state['revealedCard']) && in_array($state['trump'],['C','D','S','H'],true),'Syrian 41 derives fixed trump from exposed card');
$actions=$engine->availableActions($state,'p0');
auditCheck(($actions[0]['amount']??null)===2 && ($actions[count($actions)-1]['amount']??null)===13,'Syrian 41 offers declarations from 2 to 13 with no pass');
foreach([3,3,3,2] as $amount){$pid=$state['players'][$state['currentIndex']]['id'];$state=$engine->applyAction($state,$pid,['type'=>'bid','amount'=>$amount]);}
auditCheck($state['phase']==='playing' && count($state['bids'])===4,'Syrian 41 begins play after four valid declarations');

// Lebanese 400: 13 cards, independent declarations, fixed hearts, 41 victory condition.
$engine=new Tarneeb400Engine();
$state=$engine->newGame(auditPlayers(4),['seed'=>184]);
auditCheck(count($state['hands']['p0'])===13 && (int)$state['config']['targetScore']===41,'Tarneeb 400 deals 13 and uses the official 41 target condition');
foreach([3,3,3,2] as $amount){$pid=$state['players'][$state['currentIndex']]['id'];$state=$engine->applyAction($state,$pid,['type'=>'bid','amount'=>$amount]);}
auditCheck($state['phase']==='playing' && $state['trump']==='H','Tarneeb 400 uses Hearts as fixed trump');

// Hand: 106 cards, 14+15, 2-5 players, opening 51.
$engine=new SaudiHandEngine();
foreach([2,3,4,5] as $count){
    $state=$engine->newGame(auditPlayers($count),['seed'=>184+$count]);
    auditCheck(count($state['hands']['p0'])===15 && count($state['hands']['p'.($count-1)])===14,"Hand supports $count players with 15/14 deal");
    auditCheck($state['phase']==='discard' && !empty($state['starterDiscardPending']),'Hand starter must discard the extra card first');
}

// Partnership Hand: first team opens at 51, second team must beat that value by one.
$engine=new HandPartnershipEngine();
$state=$engine->newGame(auditPlayers(4),['seed'=>184]);
$state['phase']='discard';$state['starterDiscardPending']=false;$state['currentIndex']=0;
$state['hands']['p0']=['A_C','A_D','A_S','A_H','2_C'];
$state=$engine->applyAction($state,'p0',['type'=>'meld','cards'=>['A_C','A_D','A_S','A_H']]);
auditCheck(($state['teamOpened'][0]??false)===true && (int)($state['teamOpeningThresholds'][1]??0)===61,'Partnership Hand raises the second team opening above the first team value');

// Banakil: 18+19, no opening floor, 2/4 players, target 222.
$engine=new BanakilEngine();
foreach([2,4] as $count){
    $state=$engine->newGame(auditPlayers($count),['seed'=>184+$count]);
    auditCheck(count($state['hands']['p0'])===19 && count($state['hands']['p1'])===18,"Banakil supports $count players with 19/18 deal");
    auditCheck((int)$state['config']['opening']===0 && (int)$state['config']['targetScore']===222,'Banakil uses no opening floor and 222 partnership target');
}

// Trix family: 13 cards, 5 contracts/kingdom or two Complex contracts.
$engine=new TrixEngine();$state=$engine->newGame(auditPlayers(4),['seed'=>184]);
$owner=$state['players'][$state['currentIndex']]['id'];$contracts=array_column($engine->availableActions($state,$owner),'contract');
auditCheck(count($state['hands']['p0'])===13 && count($contracts)===5,'Trix deals 13 and exposes five kingdom contracts');
$girls=null;foreach($engine->availableActions($state,$owner) as $action)if(($action['contract']??'')==='girls'){$girls=$action;break;}
$state=$engine->applyAction($state,$owner,$girls);
auditCheck($state['phase']==='doubling','Trix opens the official doubling phase for Queens and King of Hearts');
while($state['phase']==='doubling'){
    $pid=$state['players'][$state['currentIndex']]['id'];$actions=$engine->availableActions($state,$pid);$double=null;
    foreach($actions as $action)if(($action['type']??'')==='double_card'){$double=$action;break;}
    $state=$engine->applyAction($state,$pid,$double??['type'=>'finish_doubling']);
}
auditCheck(count($state['doubledCards'])===4 && $state['phase']==='playing','Trix allows each Queen holder to reveal and double the card before trick play');
$scoringEngine=new AuditTrixEngine();$scoreState=$state;$scoreState['contract']='girls';$scoreState['doubledCards']=['Q_C'=>'p0'];
$scoreState['events']=[['type'=>'trick.won','data'=>['winner'=>'p1','cards'=>[['player'=>'p0','card'=>'Q_C'],['player'=>'p1','card'=>'A_C']]]]];
$penalties=$scoringEngine->penalties($scoreState);
auditCheck(($penalties['p1']??0)===-50 && ($penalties['p0']??0)===25,'Trix doubled Queen charges the taker twice and rewards the revealer once');
$partner=new TrixPartnershipEngine();$partnerState=$partner->newGame(auditPlayers(4),['seed'=>184]);
auditCheck(($partnerState['config']['partnership']??false)===true,'Partnership Trix combines opposite players');
$complex=new TrixComplexEngine();$complexState=$complex->newGame(auditPlayers(4),['seed'=>184]);
$complexContracts=array_column($complex->availableActions($complexState,'p0'),'contract');
sort($complexContracts);
auditCheck($complexContracts===['complex','trix'] && (int)$complexState['config']['rounds']===8,'Trix Complex uses Complex and Trix across eight contracts');

// Baloot: 32 cards, 5 each plus exposed buyer card, then 8 each after purchase.
$engine=new BalootEngine();$state=$engine->newGame(auditPlayers(4),['seed'=>184]);
$contracts=array_column($engine->availableActions($state,'p0'),'contract');
auditCheck(count($state['hands']['p0'])===5 && array_sum(array_map('count',$state['hands']))===20 && !empty($state['buyerCard']),'Baloot starts buying after five cards each and exposes the buyer card');
auditCheck(in_array('sun',$contracts,true) && in_array('hokm',$contracts,true),'Baloot offers Sun and Hokm contracts');
$state=$engine->applyAction($state,'p0',auditAction($engine->availableActions($state,'p0'),'choose_contract'));
auditCheck(count($state['hands']['p0'])===8 && array_sum(array_map('count',$state['hands']))===32,'Baloot completes the 8-card deal only after a legal purchase');
$hokmState=$engine->newGame(auditPlayers(4),['seed'=>186]);$hokm=$engine->availableActions($hokmState,'p0');
foreach($hokm as $action)if(($action['contract']??'')==='hokm'){$hokmState=$engine->applyAction($hokmState,'p0',$action);break;}
auditCheck($hokmState['phase']==='bidding' && ($hokmState['pendingHokmBuyer']??null)==='p0','Baloot keeps a first-round Hokm request pending while San may override it');
for($i=0;$i<3;$i++){$pid=$hokmState['players'][$hokmState['currentIndex']]['id'];$hokmState=$engine->applyAction($hokmState,$pid,['type'=>'pass']);}
auditCheck($hokmState['players'][$hokmState['currentIndex']]['id']==='p0','Baloot returns to the Hokm requester after the other three players pass');
$confirm=null;foreach($engine->availableActions($hokmState,'p0') as $action)if(($action['contract']??'')==='confirm_hokm'){$confirm=$action;break;}
$hokmState=$engine->applyAction($hokmState,'p0',$confirm);
auditCheck($hokmState['contract']==='hokm' && count($hokmState['hands']['p0'])===8,'Baloot confirms pending Hokm before completing the deal');

echo "[PASS] Warqnaa curated card engines align with the audited official rule invariants.\n";
