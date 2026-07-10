<?php $__env->startSection('content'); ?>
<?php
$familyLabels=['all'=>'الكل'];
$familyFor=fn($g)=>$g->rules['family'] ?? 'training';
$featured=['tarneeb','hand','trix_complex','baloot','domino','ludo','jackaroo','spades'];
?>

<section class="wz-lobby-v130" id="wzLobbyV130">
 <header class="wz-lobby-hero-v130">
  <div class="hero-copy-v130">
   <span>Warqna Pro Lobby</span>
   <h1>صالة ألعاب فخمة وسريعة</h1>
   <p>كل الألعاب مرتبة داخل شاشة واحدة بدون طفح، مع بحث سريع وتصنيفات ومحركات لعب جاهزة.</p>
  </div>
  <div class="hero-actions-v130">
   <a class="primary" href="<?php echo e(route('store')); ?>">💎 المتجر</a>
   <a href="<?php echo e(route('tournaments')); ?>">🏆 المنافسات</a>
   <a href="<?php echo e(route('clubs')); ?>">🏛️ المجموعات</a>
  </div>
 </header>

 <div class="wz-lobby-toolbar-v130">
  <input id="gameSearchV130" placeholder="ابحث عن لعبة...">
  <button type="button" id="compactGamesV130">⚡ ضغط البطاقات</button>
  <button type="button" id="showFeaturedV130">🔥 المميزة</button>
 </div>

 <nav class="wz-family-tabs-v130">
  <?php $__currentLoopData = $familyLabels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
   <button type="button" data-family-filter-v130="<?php echo e($key); ?>" class="<?php echo e($key==='all'?'active':''); ?>"><?php echo e($label); ?></button>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
 </nav>

 <main class="wz-games-wall-v130">
  <?php $__currentLoopData = $games; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $game): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
   <?php
    $family=$familyFor($game);
    $engine=$game->rules['engine'] ?? 'engine';
    $icon=$game->rules['icon'] ?? game_icon($game->key);
    $isFeatured=in_array($game->key,$featured,true);
   ?>
   <a class="wz-game-card-v130 <?php echo e($isFeatured?'featured':''); ?>"
      data-featured="<?php echo e($isFeatured?1:0); ?>"
      data-family="<?php echo e($family); ?>"
      data-name="<?php echo e(strtolower($game->key.' '.($game->name['ar'] ?? '').' '.($game->name['en'] ?? '').' '.$engine)); ?>"
      href="<?php echo e(route('rooms.index',$game->key)); ?>">
    <span class="game-orb-v130"><?php echo e($icon); ?></span>
    <strong><?php echo e($game->name['ar'] ?? $game->key); ?></strong>
    <small><?php echo e($game->min_players); ?>-<?php echo e($game->max_players); ?> لاعبين • <?php echo e($game->partnership ? 'شراكة' : 'فردي'); ?></small>
    <em><?php echo e($engine); ?></em>
   </a>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
 </main>
</section>

<script>
(function(){
 const root=document.getElementById('wzLobbyV130');
 const search=document.getElementById('gameSearchV130');
 const tabs=[...document.querySelectorAll('[data-family-filter-v130]')];
 const compact=document.getElementById('compactGamesV130');
 const featured=document.getElementById('showFeaturedV130');
 let onlyFeatured=false;
 function apply(){
  const q=(search?.value||'').toLowerCase().trim();
  const fam=document.querySelector('[data-family-filter-v130].active')?.dataset.familyFilterV130||'all';
  document.querySelectorAll('.wz-game-card-v130').forEach(card=>{
   const okFam=fam==='all'||card.dataset.family===fam;
   const okText=!q||(card.dataset.name||'').includes(q);
   const okFeatured=!onlyFeatured||card.dataset.featured==='1';
   card.hidden=!(okFam&&okText&&okFeatured);
  });
 }
 tabs.forEach(btn=>btn.addEventListener('click',()=>{tabs.forEach(b=>b.classList.toggle('active',b===btn));apply();}));
 search?.addEventListener('input',apply);
 compact?.addEventListener('click',()=>root.classList.toggle('compact'));
 featured?.addEventListener('click',()=>{onlyFeatured=!onlyFeatured; featured.classList.toggle('active',onlyFeatured); apply();});
 apply();
})();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\warqna-v142-real-engines-social-admin-premium\backend-laravel\resources\views/games/index.blade.php ENDPATH**/ ?>