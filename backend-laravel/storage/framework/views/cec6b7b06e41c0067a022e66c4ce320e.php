<!doctype html>
<html lang="<?php echo e(app()->getLocale()); ?>" dir="<?php echo e(app()->getLocale() === 'ar' ? 'rtl' : 'ltr'); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo $__env->yieldContent('title','Warqna Zone | منصة ألعاب ورق عربية'); ?></title>
    <meta name="description" content="Warqna Zone منصة ألعاب ورق عربية اجتماعية: طرنيب، هاند، بناكل، بلوت، تركس، دومينو وطاولة مع غرف، مجموعات، منافسات ومتجر.">
    <meta name="keywords" content="ألعاب ورق, طرنيب, هاند, بناكل, بلوت, تركس, دومينو, طاولة, Warqna">
    <meta property="og:title" content="Warqna Zone">
    <meta property="og:description" content="منصة ألعاب ورق عربية احترافية وآمنة وممتعة.">
    <meta name="theme-color" content="#0B3F1D">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/assets/icons/icon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/assets/icons/icon-192.png">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="canonical" href="<?php echo e(url()->current()); ?>">
    <meta name="robots" content="index,follow,max-image-preview:large">
    <meta property="og:url" content="<?php echo e(url()->current()); ?>">
    <meta property="og:type" content="website">
    <script type="application/ld+json">
    {"@context":"https://schema.org","@type":"WebApplication","name":"Warqna Zone","applicationCategory":"GameApplication","operatingSystem":"Web","inLanguage":"ar","description":"منصة ألعاب ورق عربية اجتماعية احترافية"}
    </script>
    <link rel="stylesheet" href="/assets/css/app.css?v=139-mobile-app-no-studio">
    <link rel="stylesheet" href="/assets/css/mobile-app.css?v=139-mobile-app-no-studio">
    <script>window.WARQNA_V130=true; window.WARQNA_V129=true; window.WARQNA_V128=true; window.WARQNA_V122=true; window.WARQNA_V123=true; window.WARQNA_V124=true; window.CSRF='<?php echo e(csrf_token()); ?>'; window.WARQNA_LOCALE='<?php echo e(app()->getLocale()); ?>'; window.AUTH_ID=<?php echo e(auth()->check() ? auth()->id() : 'null'); ?>; window.PREF_URL='<?php echo e(auth()->check() ? route('preferences.quick') : ''); ?>';</script>
    <script defer src="/assets/js/app.js?v=139-mobile-app-no-studio"></script>
    <script defer src="/assets/js/mobile-app.js?v=139-mobile-app-no-studio"></script>
</head>
<?php
    $currentUser = auth()->user();
    $currentProfile = $currentUser?->profile;
    $soundEnabled = $currentUser ? (($currentProfile?->sound_enabled !== false) ? '1' : '0') : '1';
    $nameColor = $currentProfile?->name_color ?? '#facc15';
    $textColor = $currentProfile?->chat_color ?? ($currentProfile?->text_color ?? '#ffffff');
    $globalTheme = class_exists('\App\Models\SiteSetting') ? \App\Models\SiteSetting::getValue('default_theme','royal') : 'royal';
    $forceGlobalTheme = class_exists('\App\Models\SiteSetting') ? \App\Models\SiteSetting::getValue('force_global_theme',false) : false;
    $siteTheme = $forceGlobalTheme ? $globalTheme : ($currentProfile?->active_site_theme ?? $globalTheme ?? 'royal');
    $nameFrame = $currentProfile?->active_name_frame ?? 'glow-gold';
    $ownedEmojis = $currentUser ? $currentUser->inventoryItems()->with('storeItem')->whereHas('storeItem', fn($q)=>$q->where('category','emoji_pack'))->get()->flatMap(fn($inv)=>preg_split('//u', (string)($inv->storeItem?->payload['emojis'] ?? ''), -1, PREG_SPLIT_NO_EMPTY))->filter()->values()->all() : [];
    $freeEmojis = ['😂','🤣','😍','👋','👍','😡','😢','😭','😱','🤔','☕','🌹'];
    $emojiList = array_values(array_unique(array_merge($freeEmojis,$ownedEmojis)));
    $navGames = $currentUser ? \App\Models\Game::where('active',true)->orderBy('id')->get() : collect();
    $recentNotifs = $currentUser ? $currentUser->notifications()->latest()->limit(8)->get() : collect();
    $clubNotif = $currentUser ? $currentUser->notifications()->where('read',false)->where('type','like','%club%')->count() : 0;
    $gameNotif = $currentUser ? $currentUser->notifications()->where('read',false)->whereIn('type',['room_invite','game_invite'])->count() : 0;
    $tourNotif = $currentUser ? $currentUser->notifications()->where('read',false)->where('type','like','%tournament%')->count() : 0;
    $msgNotif = $currentUser ? $currentUser->notifications()->where('read',false)->whereIn('type',['private_message','friend_request'])->count() : 0;
    $activeRoom = $currentUser ? \App\Models\Room::with('game')->whereHas('players', fn($q)=>$q->where('user_id',$currentUser->id)->where('is_bot',false))->whereIn('status',['waiting','bidding','playing'])->latest()->first() : null;
?>
<?php $globalAnnouncement = class_exists('\App\Models\SiteSetting') ? \App\Models\SiteSetting::getValue('global_announcement','') : ''; $customCss = class_exists('\App\Models\SiteSetting') ? \App\Models\SiteSetting::getValue('custom_css','') : ''; ?>
<body class="warqna-pro-social theme-<?php echo e($siteTheme); ?> <?php echo e(request()->routeIs('store') ? 'is-store-page' : ''); ?> <?php echo e(request()->routeIs('room.show') ? 'is-room-page' : ''); ?>" data-sound="<?php echo e($soundEnabled); ?>" data-user="<?php echo e($currentUser?->username ?? ''); ?>" data-theme="<?php echo e($siteTheme); ?>" data-country-code="<?php echo e($currentProfile?->country_code ?? 'PS'); ?>" data-country-name="<?php echo e(country_name($currentProfile?->country_code ?? 'PS')); ?>" style="--my-name-color:<?php echo e($nameColor); ?>;--my-text-color:<?php echo e($textColor); ?>">
    <?php if($globalAnnouncement): ?><div class="global-announcement"><?php echo e($globalAnnouncement); ?></div><?php endif; ?>
    <?php if($customCss): ?><style id="adminCustomCss"><?php echo $customCss; ?></style><?php endif; ?>
    <?php
        $uiGet = fn($key,$default)=> class_exists('\App\Models\SiteSetting') ? \App\Models\SiteSetting::getValue($key,$default) : $default;
        $uiPx = fn($key,$default)=> (int)$uiGet($key,$default).'px';
        $uiPct = fn($key,$default)=> (int)$uiGet($key,$default).'%';
        $uiColor = fn($key,$default)=> preg_match('/^#[0-9a-fA-F]{6}$/',(string)$uiGet($key,$default)) ? $uiGet($key,$default) : $default;
        $buttonStyle=$uiGet('ui_button_style','gradient');
        $cardShadow=$uiGet('ui_card_shadow','medium');
        $tableShape=$uiGet('ui_table_shape','rounded');
        $animationLevel=$uiGet('ui_animation_level','soft');
    ?>
    <style id="adminNoCodeDesignerCss">
    body{
        --admin-btn-w:<?php echo e($uiPx('ui_button_width',126)); ?>;--admin-btn-h:<?php echo e($uiPx('ui_button_height',46)); ?>;--admin-btn-radius:<?php echo e($uiPx('ui_button_radius',16)); ?>;--admin-btn-font:<?php echo e($uiPx('ui_button_font',14)); ?>;--admin-btn-gap:<?php echo e($uiPx('ui_button_gap',8)); ?>;
        --admin-btn-bg:<?php echo e($uiColor('ui_button_bg','#2e225f')); ?>;--admin-btn-text:<?php echo e($uiColor('ui_button_text','#ffffff')); ?>;--admin-primary-1:<?php echo e($uiColor('ui_primary_bg','#facc15')); ?>;--admin-primary-2:<?php echo e($uiColor('ui_primary_bg2','#ec4899')); ?>;
        --admin-panel-bg:<?php echo e($uiColor('ui_panel_bg','#0f172a')); ?>;--admin-card-bg:<?php echo e($uiColor('ui_card_bg','#1e293b')); ?>;--admin-site-bg1:<?php echo e($uiColor('ui_site_bg1','#07170f')); ?>;--admin-site-bg2:<?php echo e($uiColor('ui_site_bg2','#020617')); ?>;
        --admin-card-radius:<?php echo e($uiPx('ui_card_radius',24)); ?>;--admin-card-padding:<?php echo e($uiPx('ui_card_padding',18)); ?>;--admin-card-gap:<?php echo e($uiPx('ui_card_gap',16)); ?>;--admin-card-min-h:<?php echo e($uiPx('ui_card_min_height',220)); ?>;
        --admin-page-padding:<?php echo e($uiPx('ui_page_padding',18)); ?>;--admin-page-max:<?php echo e($uiPx('ui_page_max_width',1500)); ?>;--admin-nav-height:<?php echo e($uiPx('ui_nav_height',60)); ?>;--admin-nav-radius:<?php echo e($uiPx('ui_nav_radius',16)); ?>;
        --admin-store-card-w:<?php echo e($uiPx('ui_store_card_width',220)); ?>;--admin-store-card-h:<?php echo e($uiPx('ui_store_card_height',270)); ?>;--admin-store-icon:<?php echo e($uiPx('ui_store_icon_size',72)); ?>;--admin-store-price:<?php echo e($uiColor('ui_store_price_color','#facc15')); ?>;
        --admin-game-card-w:<?php echo e($uiPx('ui_game_card_width',230)); ?>;--admin-game-card-h:<?php echo e($uiPx('ui_game_card_height',230)); ?>;--admin-game-icon:<?php echo e($uiPx('ui_game_icon_size',64)); ?>;
        --admin-table-radius:<?php echo e($uiPx('ui_table_radius',46)); ?>;--admin-table-border:<?php echo e($uiPx('ui_table_border',16)); ?>;--admin-table-height:<?php echo e($uiPx('ui_table_min_height',610)); ?>;--admin-table-scale:<?php echo e((int)$uiGet('ui_table_center_scale',92)); ?>;--admin-table-bg1:<?php echo e($uiColor('ui_table_bg1','#16a34a')); ?>;--admin-table-bg2:<?php echo e($uiColor('ui_table_bg2','#064e3b')); ?>;--admin-table-border-color:<?php echo e($uiColor('ui_table_border_color','#5b3718')); ?>;
        --admin-play-card-w:<?php echo e($uiPx('ui_card_play_width',58)); ?>;--admin-play-card-h:<?php echo e($uiPx('ui_card_play_height',82)); ?>;--admin-player-avatar:<?php echo e($uiPx('ui_player_avatar',56)); ?>;
        --admin-chat-w:<?php echo e($uiPx('ui_chat_width',340)); ?>;--admin-chat-h:<?php echo e($uiPx('ui_chat_height',560)); ?>;--admin-chat-radius:<?php echo e($uiPx('ui_chat_radius',24)); ?>;--admin-chat-font:<?php echo e($uiPx('ui_chat_font',14)); ?>;--admin-chat-btn-w:<?php echo e($uiPx('ui_chat_button_width',82)); ?>;--admin-chat-btn-h:<?php echo e($uiPx('ui_chat_button_height',40)); ?>;--admin-chat-btn-radius:<?php echo e($uiPx('ui_chat_button_radius',14)); ?>;--admin-chat-input-h:<?php echo e($uiPx('ui_chat_input_height',44)); ?>;--admin-chat-emoji:<?php echo e($uiPx('ui_chat_emoji_size',34)); ?>;--admin-chat-gap:<?php echo e($uiPx('ui_chat_gap',8)); ?>;--admin-notif-w:<?php echo e($uiPx('ui_notif_width',420)); ?>;--admin-profile-w:<?php echo e($uiPx('ui_profile_width',560)); ?>;--admin-profile-font:<?php echo e($uiPx('ui_profile_font',13)); ?>;--admin-nav-bg:<?php echo e($uiColor('ui_nav_bg','#020617')); ?>;--admin-chat-bg:<?php echo e($uiColor('ui_chat_bg','#0f172a')); ?>;--admin-chat-head:<?php echo e($uiColor('ui_chat_header_bg','#312e81')); ?>;--admin-chat-btn-bg:<?php echo e($uiColor('ui_chat_button_bg','#2e225f')); ?>;--admin-chat-btn-text:<?php echo e($uiColor('ui_chat_button_text','#ffffff')); ?>;--admin-chat-input-bg:<?php echo e($uiColor('ui_chat_input_bg','#020617')); ?>;--admin-chat-message-bg:<?php echo e($uiColor('ui_chat_message_bg','#1e293b')); ?>;
        background:radial-gradient(circle at top,var(--admin-site-bg1),var(--admin-site-bg2) 68%)!important;
    }
    .page{max-width:var(--admin-page-max)!important;margin-inline:auto!important;padding:var(--admin-page-padding)!important}.topbar,.userbar{min-height:var(--admin-nav-height)!important;border-radius:0 0 var(--admin-nav-radius) var(--admin-nav-radius)!important;background:color-mix(in srgb,var(--admin-nav-bg),transparent 8%)!important}.btn,button,.topbar a,.userbar button{min-height:var(--admin-btn-h)!important;border-radius:var(--admin-btn-radius)!important;font-size:var(--admin-btn-font)!important;gap:var(--admin-btn-gap)!important;color:var(--admin-btn-text)!important}<?php if($buttonStyle==='gradient'): ?>.btn,button,.topbar a,.userbar button{background:linear-gradient(135deg,var(--admin-btn-bg),color-mix(in srgb,var(--admin-primary-2),#000 10%))!important}.primary,button.primary{background:linear-gradient(135deg,var(--admin-primary-1),var(--admin-primary-2))!important}<?php elseif($buttonStyle==='glass'): ?>.btn,button,.topbar a,.userbar button{background:color-mix(in srgb,var(--admin-btn-bg),transparent 48%)!important;backdrop-filter:blur(16px)!important;border:1px solid color-mix(in srgb,var(--admin-primary-1),transparent 55%)!important}<?php elseif($buttonStyle==='outline'): ?>.btn,button,.topbar a,.userbar button{background:transparent!important;border:1px solid var(--admin-primary-1)!important}.primary,button.primary{background:var(--admin-primary-1)!important;color:#06110d!important}<?php else: ?>.btn,button,.topbar a,.userbar button{background:var(--admin-btn-bg)!important}.primary,button.primary{background:var(--admin-primary-1)!important;color:#06110d!important}<?php endif; ?>
    .game-card,.store-card,.club-card,.tournament-card,.room-card,.pro-card,.mini-card,.admin-card,.builder-card,.store-product-card-v127{border-radius:var(--admin-card-radius)!important;padding:var(--admin-card-padding)!important;background:linear-gradient(145deg,var(--admin-card-bg),color-mix(in srgb,var(--admin-panel-bg),#000 20%))!important;min-height:var(--admin-card-min-h)}<?php if($cardShadow==='strong'): ?>.game-card,.store-card,.pro-card,.mini-card{box-shadow:0 28px 90px rgba(0,0,0,.58)!important}<?php elseif($cardShadow==='medium'): ?>.game-card,.store-card,.pro-card,.mini-card{box-shadow:0 18px 48px rgba(0,0,0,.38)!important}<?php elseif($cardShadow==='soft'): ?>.game-card,.store-card,.pro-card,.mini-card{box-shadow:0 10px 28px rgba(0,0,0,.24)!important}<?php else: ?>.game-card,.store-card,.pro-card,.mini-card{box-shadow:none!important}<?php endif; ?>
    .store-grid,.store-products-grid-v127{grid-template-columns:repeat(auto-fill,minmax(var(--admin-store-card-w),1fr))!important;gap:var(--admin-card-gap)!important}.store-card,.store-product-card-v127{min-height:var(--admin-store-card-h)!important}.shop-icon,.product-generic-v127,.emoji-store-icon{font-size:var(--admin-store-icon)!important}.product-actions-v127 .price,.price,.tokens,.admin-demo-price-v137{color:var(--admin-store-price)!important}.game-grid{grid-template-columns:repeat(auto-fill,minmax(var(--admin-game-card-w),1fr))!important;gap:var(--admin-card-gap)!important}.game-card{min-height:var(--admin-game-card-h)!important}.game-icon,.game-icon-pro-v130{font-size:var(--admin-game-icon)!important}
    .game-table.premium-table,.game-table{min-height:var(--admin-table-height)!important;border-radius:<?php if($tableShape==='stadium'): ?>999px <?php elseif($tableShape==='square-soft'): ?>var(--admin-table-radius) <?php else: ?> var(--admin-table-radius) <?php endif; ?>!important;border-width:var(--admin-table-border)!important;border-color:var(--admin-table-border-color)!important;background:radial-gradient(circle at center,var(--admin-table-bg1),var(--admin-table-bg2) 72%)!important}.center-board{scale:calc(var(--admin-table-scale) / 100)!important}.hand-row .card,.card{width:var(--admin-play-card-w)!important;height:var(--admin-play-card-h)!important}.seat-profile img,.player-ring,.player-ring img{width:var(--admin-player-avatar)!important;height:var(--admin-player-avatar)!important}.chat-dock{width:var(--admin-chat-w)!important;height:var(--admin-chat-h)!important;border-radius:var(--admin-chat-radius)!important;font-size:var(--admin-chat-font)!important;background:linear-gradient(145deg,var(--admin-chat-bg),color-mix(in srgb,var(--admin-chat-bg),#000 28%))!important}.chat-head{background:linear-gradient(135deg,var(--admin-chat-head),color-mix(in srgb,var(--admin-chat-head),#000 34%))!important}.chat-tabs{gap:var(--admin-chat-gap)!important;padding:var(--admin-chat-gap)!important}.chat-tabs button,.chat-head button,.chat-send button{min-width:var(--admin-chat-btn-w)!important;min-height:var(--admin-chat-btn-h)!important;border-radius:var(--admin-chat-btn-radius)!important;background:var(--admin-chat-btn-bg)!important;color:var(--admin-chat-btn-text)!important}.chat-send input,.chat-search{min-height:var(--admin-chat-input-h)!important;background:var(--admin-chat-input-bg)!important}.chat-body .msg,.game-chat-msg{background:var(--admin-chat-message-bg)!important}.emoji-palette button,.quick-reactions-box-v132 button{font-size:var(--admin-chat-emoji)!important;min-width:calc(var(--admin-chat-emoji) + 18px)!important;min-height:calc(var(--admin-chat-emoji) + 18px)!important}.notification-drawer,.notification-drawer-v136{width:min(var(--admin-notif-w),calc(100vw - 24px))!important}.profile-modal,.profile-modal-card{width:min(var(--admin-profile-w),calc(100vw - 22px))!important;font-size:var(--admin-profile-font)!important}<?php if($animationLevel==='none'): ?>*,*:before,*:after{animation:none!important;transition:none!important}<?php elseif($animationLevel==='premium'): ?>.btn:hover,button:hover,.game-card:hover,.store-card:hover{transform:translateY(-4px) scale(1.015)!important;filter:brightness(1.08)!important}<?php endif; ?>
    </style>
    <div class="topbar">
        <a class="brand" href="<?php echo e(auth()->check() ? route('games') : route('home')); ?>">ورقنا زون</a>
        <?php if(auth()->guard()->check()): ?>
            <button type="button" class="nav-drop-btn games-top-only-v128" onclick="toggleTopPanel('gamesCurtain')" data-i18n="all_games">🎮 الألعاب ▾</button>
            <a href="<?php echo e(route('game.rules')); ?>" data-i18n="rules">قوانين الألعاب</a>
            <a href="<?php echo e(route('store')); ?>" data-i18n="store">المتجر</a><a href="<?php echo e(route('rewards')); ?>">المكافآت</a>
            <a href="<?php echo e(route('clubs')); ?>" data-i18n="groups">المجموعات</a>
            <a href="<?php echo e(route('tournaments')); ?>" data-i18n="competitions">المنافسات</a>
            <a class="nav-drop-btn" href="<?php echo e(route('notifications')); ?>">الإشعارات</a>
            <button type="button" class="nav-drop-btn" onclick="openProfile(<?php echo e(auth()->id()); ?>)"><span data-i18n="my_profile">بروفايلي</span></button>
            <a href="<?php echo e(route('settings')); ?>" data-i18n="settings">الإعدادات</a>
            <a href="<?php echo e(route('about')); ?>"><span data-i18n="about">حول</span></a>
            <a href="<?php echo e(route('contact')); ?>" data-i18n="contact">اتصل بنا</a>
            <?php if($currentUser?->is_admin): ?><a href="<?php echo e(route('admin')); ?>"><span data-i18n="admin">الإدارة</span></a><?php endif; ?>
        <?php endif; ?>
    </div>
    <?php if(auth()->guard()->check()): ?>
        
        
        <div id="gamesCurtain" class="wz-games-menu-v130 hidden" role="dialog" aria-label="قائمة الألعاب">
            <div class="wz-games-menu-card-v130">
                <div class="wz-games-menu-head-v130">
                    <div>
                        <b>🎮 الألعاب</b>
                        <small>اختر لعبة بسرعة. تختفي القائمة مباشرة بعد اختيار اللعبة أو الضغط خارجها.</small>
                    </div>
                    <div class="wz-games-menu-tools-v130">
                        <input id="navGameSearchV130" type="search" placeholder="ابحث عن لعبة...">
                        <a href="<?php echo e(route('games')); ?>">صفحة الألعاب</a>
                        <button type="button" class="wz-games-close-v130" data-games-close-v130>×</button>
                    </div>
                </div>
                <?php
                    $navFamilies=['all'=>'الكل'];
                ?>
                <div class="wz-games-tabs-v130">
                    <?php $__currentLoopData = $navFamilies; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fk=>$fl): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <button type="button" data-game-family-v130="<?php echo e($fk); ?>" class="<?php echo e($fk==='all'?'active':''); ?>"><?php echo e($fl); ?></button>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <div class="wz-games-grid-v130">
                    <?php $__currentLoopData = $navGames; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $g): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php $family=$g->rules['family'] ?? 'training'; $engine=$g->rules['engine'] ?? ''; ?>
                        <a class="wz-game-pop-v130 <?php echo e($g->key); ?>"
                           data-game-link-v130
                           data-family="<?php echo e($family); ?>"
                           data-name="<?php echo e(strtolower($g->key.' '.($g->name['ar'] ?? '').' '.($g->name['en'] ?? '').' '.$engine)); ?>"
                           href="<?php echo e(route('rooms.index',$g->key)); ?>">
                            <span class="game-icon-pro-v130"><?php echo e($g->rules['icon'] ?? game_icon($g->key)); ?></span>
                            <b><?php echo e($g->name['ar'] ?? $g->key); ?></b>
                            <small><?php echo e($g->min_players); ?>-<?php echo e($g->max_players); ?> • <?php echo e($g->partnership ? 'شراكة' : 'فردي'); ?></small>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </div>

        <div id="themePanel" class="top-panel theme-picker-panel hidden">
            <b>🎨 الثيمات</b>
            <p class="muted">اختر ثيم الموقع، ويتم تفعيله مباشرة على حسابك.</p>
            <div class="theme-grid-v108">
                <button type="button" data-theme-pick="royal" onclick="setSiteTheme('royal');toggleTopPanel('themePanel')">👑 ملكي ذهبي</button>
                <button type="button" data-theme-pick="midnight" onclick="setSiteTheme('midnight');toggleTopPanel('themePanel')">🌙 ليلي أزرق</button>
                <button type="button" data-theme-pick="emerald" onclick="setSiteTheme('emerald');toggleTopPanel('themePanel')">💎 زمردي فاخر</button>
                <button type="button" data-theme-pick="desert" onclick="setSiteTheme('desert');toggleTopPanel('themePanel')">🏜️ صحراوي</button>
                <button type="button" data-theme-pick="galaxy" onclick="setSiteTheme('galaxy');toggleTopPanel('themePanel')">🌌 مجرة نيون</button>
                <button type="button" data-theme-pick="crimson" onclick="setSiteTheme('crimson');toggleTopPanel('themePanel')">❤️ قرمزي</button>
                <button type="button" data-theme-pick="ocean" onclick="setSiteTheme('ocean');toggleTopPanel('themePanel')">🌊 محيطي</button>
            </div>
        </div>

        <div id="languagePanel" class="top-panel language-picker-panel hidden">
            <b data-i18n="site_language">لغة الموقع</b>
            <p class="muted">اختر اللغة وسيتم تطبيق الاتجاه والترجمة مباشرة على الواجهة.</p>
            <div class="language-grid-v138">
                <button type="button" data-lang-pick="ar" onclick="setWarqnaLang('ar');toggleTopPanel('languagePanel')">🇵🇸 عربي</button>
                <button type="button" data-lang-pick="en" onclick="setWarqnaLang('en');toggleTopPanel('languagePanel')">🇬🇧 English</button>
                <button type="button" data-lang-pick="de" onclick="setWarqnaLang('de');toggleTopPanel('languagePanel')">🇩🇪 Deutsch</button>
                <button type="button" data-lang-pick="tr" onclick="setWarqnaLang('tr');toggleTopPanel('languagePanel')">🇹🇷 Türkçe</button>
                <button type="button" data-lang-pick="fr" onclick="setWarqnaLang('fr');toggleTopPanel('languagePanel')">🇫🇷 Français</button>
                <button type="button" data-lang-pick="es" onclick="setWarqnaLang('es');toggleTopPanel('languagePanel')">🇪🇸 Español</button>
            </div>
        </div>

        <div class="userbar">
            <button type="button" class="user-chip player-glow <?php echo e($nameFrame); ?>" onclick="openProfile(<?php echo e(auth()->id()); ?>)" style="--player-color:<?php echo e($nameColor); ?>">
                <?php echo flag_img($currentProfile?->country_code ?? 'PS','flag-img flag-small'); ?>

                <img class="avatar-xs" src="<?php echo e($currentProfile?->avatar ?: '/assets/avatars/default.svg'); ?>" alt="avatar">
                <span><?php echo e($currentUser->username); ?></span>
            </button>
            <span class="pasha pasha-days-chip-v136"><img class="pasha-mini-icon-v136" src="/assets/store/basha1.png" alt="باشا"><span data-i18n="pasha">باشا</span>: <?php echo e($currentProfile?->pasha_days ?? 0); ?> <span data-i18n="days">يوم</span></span>
            <a class="tokens tokens-ledger-link-v136" href="<?php echo e(route('tokens')); ?>" title="سجل التوكنز">🪙 <?php echo e(number_format($currentUser->wallet?->tokens ?? 0)); ?></a>
            <span id="siteClock" class="site-clock">--:--</span>
            <button type="button" class="theme-switch-btn" onclick="toggleTopPanel('themePanel')" title="الثيمات">🎨</button>
            <button type="button" class="language-switch-btn" onclick="toggleTopPanel('languagePanel')" title="اللغات">🌐</button>
            <label class="sound-range-wrap" title="تحكم بالصوت من 0 إلى 100"><span>🔊</span><input id="soundVolumeRange" type="range" min="0" max="100" step="1" value="80" aria-label="مستوى الصوت"></label>
            <div id="clubPanel" class="top-panel hidden"><b>إشعارات المجموعات</b><p>طلبات الانضمام وتحديثات المجموعة تظهر هنا داخل نفس الصفحة.</p><a href="<?php echo e(route('clubs')); ?>">فتح المجموعات</a></div>
            <div id="invitePanel" class="top-panel hidden"><b>دعوات الألعاب</b><p>أي دعوة غرفة من لاعب آخر تظهر هنا بدون مغادرة الصفحة.</p><a href="<?php echo e(route('notifications')); ?>">كل الدعوات</a></div>
            <div id="tourPanel" class="top-panel hidden"><b>المنافسات</b><p>متابعة المنافسات المفتوحة والمكتملة.</p><a href="<?php echo e(route('tournaments')); ?>">فتح المنافسات</a></div>
            <div id="msgPanel" class="top-panel hidden chat-center-panel"><b>مركز الدردشة</b><div class="mini-chat-tabs"><button type="button" onclick="setChatMode('room');document.getElementById('chatDock')?.classList.remove('hidden')">دردشة اللعبة</button><button type="button" onclick="setChatMode('friends');document.getElementById('chatDock')?.classList.remove('hidden')">الأصدقاء</button><button type="button" onclick="setChatMode('search');document.getElementById('chatDock')?.classList.remove('hidden')">بحث</button></div><input placeholder="ابحث عن لاعب أو صديق" oninput="filterChatList(this.value)"><p>تبويبة دردشة اللعبة مدمجة مع مركز الدردشة على اليسار وتعمل داخل الغرفة نفسها.</p><a href="<?php echo e(route('friends')); ?>">الأصدقاء</a></div>
            <div id="notifPanel" class="top-panel hidden notification-drawer notification-drawer-v136">
                <div class="notif-drawer-head-v136"><b>🔔 مركز الإشعارات</b><form method="post" action="<?php echo e(route('notifications.readAll')); ?>" data-ajax-soft><?php echo csrf_field(); ?><button type="submit">قراءة الكل</button></form></div>
                <div class="notif-type-list-v136">
                    <?php $notifGroups=['room_invite'=>'دعوات الألعاب','game_invite'=>'دعوات الألعاب','tournament'=>'المسابقات','club'=>'النادي','private_message'=>'الرسائل','friend_request'=>'الأصدقاء']; ?>
                    <?php $__empty_1 = true; $__currentLoopData = $recentNotifs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $n): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php $label='عام'; foreach($notifGroups as $needle=>$txt){ if(str_contains((string)$n->type,$needle)){ $label=$txt; break; } } ?>
                        <article class="drawer-row notif-row-v136 <?php echo e($n->read ? 'is-read' : 'is-unread'); ?>">
                            <span class="notif-kind-v136"><?php echo e($label); ?></span>
                            <b><?php echo e($n->title['ar'] ?? $n->type); ?></b>
                            <p><?php echo e($n->body['ar'] ?? ''); ?></p>
                            <div class="notif-actions-v136">
                                <?php if($n->url): ?><a href="<?php echo e($n->url); ?>">فتح</a><?php endif; ?>
                                <form method="post" action="<?php echo e(route('notifications.read',$n)); ?>" data-ajax-soft><?php echo csrf_field(); ?><button type="submit">قراءة</button></form>
                                <form method="post" action="<?php echo e(route('notifications.delete',$n)); ?>" data-ajax-soft data-confirm="حذف هذا الإشعار؟"><?php echo csrf_field(); ?><button type="submit" class="danger">حذف</button></form>
                            </div>
                        </article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><p class="muted">لا توجد إشعارات جديدة.</p><?php endif; ?>
                </div>
            </div>

            <div class="top-icons" aria-label="مركز الإشعارات">
                <button type="button" class="notif-live-btn notif-page-go" data-notif-type="club" onclick="toggleTopPanel('notifPanel')" title="إشعارات المجموعات">🏛️<b class="<?php echo e($clubNotif ? '' : 'hidden'); ?>"><?php echo e($clubNotif); ?></b></button>
                <button type="button" class="notif-live-btn notif-page-go" data-notif-type="game" onclick="toggleTopPanel('notifPanel')" title="دعوات الألعاب">🎮<b class="<?php echo e($gameNotif ? '' : 'hidden'); ?>"><?php echo e($gameNotif); ?></b></button>
                <button type="button" class="notif-live-btn notif-page-go" data-notif-type="competition" onclick="toggleTopPanel('notifPanel')" title="المنافسات">🏆<b class="<?php echo e($tourNotif ? '' : 'hidden'); ?>"><?php echo e($tourNotif); ?></b></button>
                <button type="button" class="notif-live-btn notif-page-go" data-notif-type="message" onclick="setChatMode('friends');reopenChat();" title="الرسائل والأصدقاء">💬<b class="<?php echo e($msgNotif ? '' : 'hidden'); ?>"><?php echo e($msgNotif); ?></b></button>
            </div>
            <button type="button" onclick="changeFont(1);window.WarqnaSound?.ui()">A+</button>
            <button type="button" onclick="changeFont(-1);window.WarqnaSound?.ui()">A-</button>
            <button type="button" id="soundToggle" title="تشغيل/إيقاف الصوت" onclick="window.WarqnaSound?.toggleMute?.()">🔊</button>
            <?php if($activeRoom): ?>
                <a class="active-room-chip active-room-right" href="<?php echo e(route('rooms.show',$activeRoom->code)); ?>">🎮 داخل لعبة <?php echo e($activeRoom->code); ?></a>
                <form method="post" class="global-leave-game active-room-right" action="<?php echo e(route('rooms.leave',$activeRoom->code)); ?>" data-confirm="هل تريد الخروج من اللعبة؟ تنبيه: إذا خرجت 3 مرات من نفس اللعبة لن تستطيع العودة لها مرة أخرى."><?php echo csrf_field(); ?><button type="submit">🚪 خروج من اللعبة</button></form>
            <?php endif; ?>
            <form method="post" action="<?php echo e(route('logout')); ?>" data-confirm="هل تريد تسجيل الخروج؟"><?php echo csrf_field(); ?><button type="submit"><span data-i18n="logout">خروج</span></button></form>
        </div>
    <?php endif; ?>
    <main class="page">
        <?php if(session('ok')): ?><script>window.addEventListener('DOMContentLoaded',()=>showNotice(<?php echo json_encode(session('ok'), 15, 512) ?>));</script><?php endif; ?>
        <?php if($errors->any()): ?><script>window.addEventListener('DOMContentLoaded',()=>showNotice(<?php echo json_encode($errors->first(), 15, 512) ?>));</script><?php endif; ?>
        <?php echo $__env->yieldContent('content'); ?>
<button id="installAppBtn" class="install-app-btn hidden" type="button">📲 تثبيت التطبيق</button>
<div id="mobileSafeToast" class="mobile-safe-toast hidden"></div>
    </main>
    <?php if(auth()->guard()->check()): ?>
        <aside id="chatDock" class="chat-dock chat-expanded">
            <div class="chat-head"><span data-i18n="chat_center">مركز الدردشة</span> <span><button type="button" onclick="toggleChat()">—</button><button type="button" onclick="minimizeChat()">▾</button><button type="button" onclick="maximizeChat()">□</button><button type="button" onclick="closeChat()">×</button></span></div>
            <div class="chat-tabs">
                <button type="button" data-chat-tab="room" onclick="setChatMode('room')"><span data-i18n="game_chat">دردشة اللعبة</span></button>
                <button type="button" data-chat-tab="friends" onclick="setChatMode('friends')"><span data-i18n="friends">الأصدقاء</span></button>
                <button type="button" data-chat-tab="search" onclick="setChatMode('search')"><span data-i18n="search">بحث</span></button>
            </div>
            <input id="chatSearch" class="chat-search" placeholder="ابحث باسم لاعب أو صديق" oninput="chatSearchChanged(this.value)">
            <div class="emoji-palette" id="emojiPalette"></div>
            <div class="chat-body" id="chatBody"><p class="muted">اختر <span data-i18n="game_chat">دردشة اللعبة</span> أو صديقًا للبدء.</p></div>
            <form class="chat-send" onsubmit="sendChat(event)"><input id="chatInput" placeholder="<?php echo e(app()->getLocale()==="ar" ? "اكتب رسالة واضغط Enter" : "Type message"); ?>"><button type="submit"><span data-i18n="send">إرسال</span></button></form>
        </aside>
        <script>window.WARQNA_EMOJIS=<?php echo json_encode($emojiList, 15, 512) ?>; window.CHAT_HAS_ROOM=<?php echo json_encode(request()->routeIs('room.show'), 15, 512) ?>; window.CHAT_ROOM_LABEL=<?php echo json_encode($activeRoom?->code ?? null, 15, 512) ?>;</script>
        <button id="chatReopen" class="chat-reopen hidden" type="button" onclick="reopenChat()">💬</button>
        <div id="profileModal" class="profile-modal hidden"></div>
    <?php endif; ?>
<script>
if('serviceWorker' in navigator){window.addEventListener('load',()=>navigator.serviceWorker.register('/sw.js').catch(()=>{}));}
</script>
</body>
</html>
<?php /**PATH C:\xampp\htdocs\warqna-v142-real-engines-social-admin-premium\backend-laravel\resources\views/layouts/app.blade.php ENDPATH**/ ?>