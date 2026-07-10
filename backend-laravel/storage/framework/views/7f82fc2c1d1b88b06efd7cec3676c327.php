<?php
$p = $player ?? null;
$key = $p ? ($p->is_bot ? 'bot:'.$p->id : 'user:'.$p->user_id) : 'empty';
$isTurn = $p && (($room->state['turn'] ?? null) === $key);
$color = $p?->user?->profile?->name_color ?: ($p?->is_bot ? '#38bdf8' : '#facc15');
$avatar = $p ? ($p->is_bot ? bot_avatar_url($p->bot_key, (int)($p->id ?? 1)) : ($p->user?->profile?->avatar ?: '/assets/avatars/default.svg')) : bot_avatar_url('معتصم', 1);
$frame = $p?->user?->profile?->active_name_frame ?: ($p?->is_bot ? 'glow-ocean' : 'glow-gold');
$countryCode = $p?->user?->profile?->country_code ?? 'PS';
$isSeatedInRoom = $room->players->where('user_id',auth()->id())->count() > 0;
?>
<?php if($p): ?>
 <div class="seat-box <?php echo e($p->is_bot ? 'bot-box' : 'human-box'); ?>">
  <button type="button" class="seat-profile player-glow <?php echo e($frame); ?> <?php echo e($p->is_bot ? 'bot-seat' : 'human-seat'); ?> <?php echo e($isTurn ? 'is-turn' : ''); ?>" data-player-key="<?php echo e($key); ?>" style="--player-color:<?php echo e($color); ?>" <?php if(!$p->is_bot && $p->user_id): ?> onclick="openProfile(<?php echo e($p->user_id); ?>)" <?php endif; ?>>
   <span class="player-ring"><img src="<?php echo e($avatar); ?>" alt="avatar"></span>
   <span class="player-name" style="color:<?php echo e($color); ?>"><?php echo e($p->user?->username ?: $p->bot_key); ?></span>
   <small><?php if($p->user): ?><?php echo flag_img($countryCode); ?> <?php echo e(country_name($countryCode)); ?> <?php else: ?> 🤖 BOT جاهز <?php endif; ?> • <?php echo e($seatName ?? $p->seat); ?> <?php if(!$p->connected): ?> • منقطع <?php endif; ?></small>
  </button>
  <?php if($p->is_bot && !$room->players->where('user_id',auth()->id())->count()): ?>
   
  <?php endif; ?>
  <?php if($p->user_id && $p->user_id !== auth()->id() && (($room->owner_id === auth()->id() && (auth()->user()->profile?->pasha_days ?? 0) > 0) || auth()->user()->is_admin)): ?>
   <details class="pasha-seat-control pasha-seat-dropdown-v136"><summary>👑 خيارات الباشا</summary><form method="post" action="<?php echo e(route('rooms.replacePlayer', [$room->code, $p->id])); ?>" data-confirm="استبدال هذا اللاعب ببوت؟"><?php echo csrf_field(); ?><button type="submit">استبدال اللاعب ببوت</button></form></details>
  <?php endif; ?>

  <?php if(!empty(($room->state['voice_room'] ?? false)) && !$p->is_bot): ?>
   <div class="voice-seat-icons" title="تحكم صوت هذا اللاعب">
    <?php if($p->user_id===auth()->id()): ?>
     <button type="button" title="تشغيل المايك" onclick="WarqnaVoice?.start()">🎙️</button>
     <button type="button" title="كتم/تشغيل نفسي" onclick="WarqnaVoice?.mute()">🔇</button>
     <button type="button" title="إيقاف المايك" onclick="WarqnaVoice?.stop()">⏹️</button>
    <?php else: ?>
     <button type="button" title="كتم هذا اللاعب عندي فقط" onclick="WarqnaVoice?.mutePeer?.('<?php echo e($key); ?>')">🔇</button>
    <?php endif; ?>
   </div>
  <?php endif; ?>
  <div class="seat-played-card" data-player-key="<?php echo e($key); ?>"></div>
 </div>
<?php else: ?>
 <div class="empty-seat bot-placeholder">
  <span class="player-ring"><img src="<?php echo e(bot_avatar_url('معتصم',1)); ?>" alt="bot"></span><b>مقعد فارغ</b><small><?php echo e($seatName ?? ''); ?></small>
  <?php if (! ($isSeatedInRoom)): ?><form method="post" action="<?php echo e(route('rooms.join',$room->code)); ?>"><?php echo csrf_field(); ?><input type="hidden" name="seat" value="<?php echo e($seat ?? ''); ?>"><button type="submit">اجلس هنا</button></form><?php else: ?><small class="seat-locked-v136">تم اختيار مقعدك، لا يمكن تغيير المكان داخل نفس اللعبة.</small><?php endif; ?>
 </div>
<?php endif; ?>
<?php /**PATH C:\xampp\htdocs\warqna-v142-real-engines-social-admin-premium\backend-laravel\resources\views/room/seat.blade.php ENDPATH**/ ?>