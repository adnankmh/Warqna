<?php $__env->startSection('content'); ?>
<?php
$userLevel = (int)(auth()->user()->profile?->level ?? 1);
$isTarneeb = in_array($game->key,['tarneeb','tarneeb_400','tarneeb_41'],true);
$targets = $isTarneeb ? [31,41,61] : ((array)($game->rules['targets'] ?? []));
?>
<h1>غرف <?php echo e($game->name['ar']); ?></h1>
<p class="muted">قوانين اللعبة في صفحة القوانين فقط. <a class="btn" href="<?php echo e(route('game.rules')); ?>#game-<?php echo e($game->key); ?>">عرض القوانين</a></p>
<div class="rooms-layout">
 <section class="leaders"><h3>المتصدرون</h3><?php $__currentLoopData = $leaders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $u): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><div><?php echo flag_img($u->profile?->country_code); ?> <?php echo e($u->username); ?> • <?php echo e(number_format($u->profile?->games_played ?? 0)); ?> لعبة</div><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></section>
 <section class="rooms-list">
  <?php $__empty_1 = true; $__currentLoopData = $rooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $room): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
   <div class="room-card"><a href="<?php echo e(route('rooms.show',$room->code)); ?>"><b>غرفة <?php echo e($room->code); ?></b></a><span class="room-status-pill"><?php echo e($room->status==='waiting' ? 'مفتوحة' : ($room->status==='playing' ? 'جارية' : $room->status)); ?> • <?php echo e($room->players->count()); ?>/<?php echo e($room->max_players); ?></span><div><?php $__currentLoopData = $room->players; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $p): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><span class="seat-mini"><img src="<?php echo e($p->user?->profile?->avatar ?: ($p->is_bot?'/assets/bots/player.svg':'/assets/avatars/default.svg')); ?>"> <?php echo e($p->user?->username ?: $p->bot_key); ?></span><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div><?php if (! ($room->players->contains('user_id',auth()->id()))): ?><form method="post" action="<?php echo e(route('rooms.join',$room->code)); ?>"><?php echo csrf_field(); ?> <?php if($room->visibility==='private'): ?><input name="password" placeholder="كلمة السر"><?php endif; ?><button>دخول</button></form><?php endif; ?></div>
  <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
   <div class="empty-room"><button class="create-new-room-hero large-empty" type="button" onclick="openCreateRoomModal()">＋ <span>أنشئ لعبة جديدة</span></button><p>لا توجد غرف مفتوحة لهذه اللعبة.</p></div>
  <?php endif; ?>
 </section>
 <aside class="create-room compact-create-room" id="createRoomPanel"><button class="create-new-room-hero" type="button" onclick="openCreateRoomModal()">＋ <span>أنشئ لعبة جديدة</span></button><h2>إنشاء لعبة</h2>
  <form method="post" action="<?php echo e(route('rooms.store')); ?>" data-ajax-room="1"><?php echo csrf_field(); ?>
   <input type="hidden" name="game_id" value="<?php echo e($game->id); ?>">
   <input type="hidden" name="voice_room" id="voiceRoomFlag" value="0">
   <label>نوع اللعبة</label>
   <select name="room_type" id="roomTypeSelect" onchange="roomTypeChanged(this)"><option value="public">عامة</option><option value="private">خاصة</option><option value="voice">لعبة صوتية - تخصم 100 توكنز من كل لاعب</option></select><small class="voice-fee-hint hidden">اللعبة الصوتية تخصم 100 توكنز عند إنشاء/دخول الغرفة.</small>
   <input id="privatePasswordInput" class="hidden" name="password" placeholder="كلمة السر للعبة الخاصة">
   <label>عدد المقاعد</label>
   <?php if(count($allowedSeats)===1): ?>
    <select name="max_players"><option value="<?php echo e($allowedSeats[0]); ?>" selected><?php echo e($allowedSeats[0]); ?> مقاعد</option></select>
   <?php else: ?>
    <select name="max_players"><?php $__currentLoopData = $allowedSeats; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($i); ?>" <?php echo e($i==$game->max_players?'selected':''); ?>><?php echo e($i); ?> مقاعد</option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select>
   <?php endif; ?>
   <label>سرعة اللعب</label><select name="speed"><option value="slow">بطيئة - 10 ثوانٍ</option><option value="medium" selected>متوسطة - 7 ثوانٍ</option><option value="fast">سريعة - 5 ثوانٍ</option></select>
   <label>إمكانية الطرد لصاحب الغرفة الباشا</label><select name="allow_owner_kick"><option value="0" selected>معطلة</option><option value="1">مفعّلة — للباشا فقط</option></select>
   <label>خصم XP عند الخروج اليدوي</label><select name="leave_xp_penalty"><option value="0" selected>بدون خصم</option><option value="1">خصم 200 XP إذا خرج اللاعب وحده أثناء اللعبة</option></select>
   <label>أقل مستوى للدخول</label><select name="min_level"><?php for($lvl=1;$lvl<=min(100,$userLevel);$lvl++): ?><option value="<?php echo e($lvl); ?>">المستوى <?php echo e($lvl); ?></option><?php endfor; ?></select>
   <?php if(count($targets)): ?><label>نهاية اللعبة</label><select name="target_score"><?php $__currentLoopData = $targets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $target): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($target); ?>"><?php echo e($target); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select><?php endif; ?>
   <button class="btn primary create-room-submit" type="submit">إنشاء الغرفة</button>
  </form>
 </aside>
</div>
<div id="createRoomModal" class="create-room-modal hidden" onclick="if(event.target===this)this.classList.add('hidden')">
 <div class="create-room-modal-card">
  <button type="button" class="modal-x" onclick="document.getElementById('createRoomModal').classList.add('hidden')">×</button>
  <h2>إنشاء لعبة جديدة</h2>
  <p class="muted">اختر نفس خيارات الإنشاء بسرعة من منتصف الصفحة، ثم سيتم فتح الغرفة مباشرة.</p>
  <div id="createRoomModalBody"></div>
 </div>
</div>
<script>
function roomTypeChanged(sel){const scope=sel.closest('form')||document; const pass=scope.querySelector('[name="password"]'),voice=scope.querySelector('[name="voice_room"]'),hint=scope.querySelector('.voice-fee-hint'); if(pass)pass.classList.toggle('hidden',sel.value!=='private'); if(voice)voice.value=sel.value==='voice'?'1':'0'; if(hint)hint.classList.toggle('hidden',sel.value!=='voice');}
function openCreateRoomModal(){const modal=document.getElementById('createRoomModal'),body=document.getElementById('createRoomModalBody'),form=document.querySelector('#createRoomPanel form'); if(!modal||!body||!form)return; const clone=form.cloneNode(true); clone.removeAttribute('id'); clone.classList.add('modal-create-room-form'); clone.querySelectorAll('[id]').forEach((el,i)=>{el.id=el.id+'Modal'+i}); body.innerHTML=''; body.appendChild(clone); modal.classList.remove('hidden'); clone.querySelector('select')?.focus();}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\warqna-v142-real-engines-social-admin-premium\backend-laravel\resources\views/room/index.blade.php ENDPATH**/ ?>