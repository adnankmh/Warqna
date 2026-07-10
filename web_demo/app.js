(() => {
  'use strict';

  const $ = (s, root = document) => root.querySelector(s);
  const $$ = (s, root = document) => [...root.querySelectorAll(s)];
  const fmt = n => Number(n || 0).toLocaleString(document.documentElement.lang || 'ar');
  const esc = value => String(value ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));

  const dictionaries = {
    ar: {
      home:'الرئيسية', games:'الألعاب', store:'المتجر', clubs:'الأندية', events:'الأحداث',
      welcome:'مرحباً بعودتك', level:'المستوى', coins:'العملات', vip:'الباشا', day:'يوم', xp:'الخبرة',
      champions:'بطولة الأبطال', heroText:'نافس أقوى اللاعبين واربح جوائز ذهبية ضخمة', joinNow:'انضم الآن',
      giftRoad:'طريق الهدايا', featured:'ألعاب مميزة', seeAll:'عرض الكل', friendly:'مباراة ودية', competitions:'المسابقات',
      challenges:'التحديات', tournaments:'البطولات', friends:'الأصدقاء', settings:'الإعدادات', rewards:'المكافآت',
      search:'ابحث عن لعبة', all:'الكل', cards:'ورق', board:'طاولة', available:'متاح الآن', players:'لاعب',
      notifications:'الإشعارات', messages:'الرسائل', profile:'الملف الشخصي', language:'اللغة', theme:'الثيم',
      dark:'داكن فاخر', emerald:'زمردي', royal:'ملكي أزرق', purple:'بنفسجي', classic:'كلاسيكي',
      buy:'شراء', owned:'مملوك', preview:'معاينة', balance:'رصيدك', inventory:'مقتنياتي', transactions:'سجل التوكنز',
      pasha:'الباشا', themes:'الثيمات', cardBacks:'ظهر الورق', emojis:'الإيموجي', boosters:'المسرعات', nameColors:'ألوان الاسم', tables:'الطاولات',
      createRoom:'إنشاء غرفة', publicRoom:'غرفة عامة', privateRoom:'غرفة خاصة', password:'كلمة السر', enter:'دخول', cancel:'إلغاء',
      leaderboard:'لوحة الصدارة', rules:'القوانين', gameList:'قائمة الألعاب', quick:'التفاعلات السريعة', chat:'الدردشة',
      yourTurn:'دورك', bid:'أخذ', pass:'سكون', play:'طرح', leave:'مغادرة', scoreUs:'نحن', scoreThem:'هم', round:'الجولة',
      claim:'استلام', claimed:'تم الاستلام', active:'نشط', joinClub:'انضمام', leaveClub:'مغادرة النادي', members:'عضو', treasury:'الخزينة',
      eventJoin:'المشاركة', startsIn:'يبدأ خلال', prize:'الجائزة', entryFee:'رسوم الدخول', joined:'تم الانضمام',
      markAll:'قراءة الكل', deleteAll:'حذف الكل', delete:'حذف', markRead:'مقروء', send:'إرسال', typeMessage:'اكتب رسالة...',
      logout:'تسجيل الخروج', sound:'الأصوات', vibration:'الاهتزاز', autoPlay:'اللعب التلقائي عند انتهاء الوقت', save:'حفظ',
      rulesTitle:'قوانين اللعبة', close:'إغلاق', status:'الحالة', roomName:'اسم الغرفة', confirm:'تأكيد',
      activityBusy:'يجب الخروج من النشاط الحالي قبل الانضمام إلى نشاط آخر.', successBuy:'تم الشراء بنجاح', noCoins:'الرصيد غير كافٍ',
      joinedCompetition:'تم الانضمام إلى المسابقة', joinedClub:'تم الانضمام إلى النادي', leftClub:'تمت مغادرة النادي',
      rewardClaimed:'تمت إضافة المكافأة إلى رصيدك', themeApplied:'تم تطبيق الثيم على التطبيق بالكامل', languageApplied:'تم تغيير لغة التطبيق',
      gameStarted:'تم فتح غرفة اللعبة', cardPlayed:'تم لعب الورقة', autoPlayed:'انتهى الوقت ولعب الكمبيوتر نيابة عنك',
      roomCreated:'تم إنشاء الغرفة', passwordWrong:'كلمة السر غير صحيحة', privateInvite:'غرفة خاصة — كلمة السر: 2468',
      dailyReward:'المكافأة اليومية', loginStreak:'سلسلة الدخول', currentActivity:'النشاط الحالي', none:'لا يوجد',
      matchHistory:'سجل المباريات', achievements:'الإنجازات', winRate:'نسبة الفوز', wins:'فوز', played:'مباراة',
      adminNote:'هذه نسخة تفاعلية محلية. عند ربط رابط Laravel الحقيقي تُرسل عمليات الشراء والانضمام والدردشة إلى قاعدة البيانات.',
      install:'تثبيت التطبيق', appReady:'التطبيق جاهز للتثبيت على الهاتف',
      online:'متصل', offline:'غير متصل', rank:'الترتيب', today:'اليوم', week:'الأسبوع', season:'الموسم',
      roomChat:'دردشة الغرفة', quickReactions:'ردود سريعة', selectCard:'اختر ورقة أولاً',
      bidPhase:'مرحلة الطلب', chooseBid:'اختر طلبك', settingsSaved:'تم حفظ الإعدادات',
      domino:'دومينو', tarneeb:'طرنيب', trix:'تركس', hand:'هاند', banakil:'بناكيل', baloot:'بلوت', basra:'بصرة', jackaroo:'جاكارو', chess:'شطرنج', backgammon:'طاولة الزهر', solitaire:'سوليتير تنافسي', tarneeb400:'طرنيب 400', syrianTarneeb:'طرنيب سوري', trixComplex:'تركس كومبلكس', handSaudi:'هاند سعودي'
    },
    en: {
      home:'Home', games:'Games', store:'Store', clubs:'Clubs', events:'Events', welcome:'Welcome back', level:'Level', coins:'Coins', vip:'VIP', day:'Days', xp:'XP', champions:'Champions Cup', heroText:'Compete with top players and win major golden prizes', joinNow:'Join now', giftRoad:'Gifts Road', featured:'Featured Games', seeAll:'See all', friendly:'Friendly Match', competitions:'Competitions', challenges:'Challenges', tournaments:'Tournaments', friends:'Friends', settings:'Settings', rewards:'Rewards', search:'Search games', all:'All', cards:'Cards', board:'Board', available:'Available now', players:'players', notifications:'Notifications', messages:'Messages', profile:'Profile', language:'Language', theme:'Theme', dark:'Luxury Dark', emerald:'Emerald', royal:'Royal Blue', purple:'Purple', classic:'Classic', buy:'Buy', owned:'Owned', preview:'Preview', balance:'Balance', inventory:'Inventory', transactions:'Token History', pasha:'VIP', themes:'Themes', cardBacks:'Card Backs', emojis:'Emoji', boosters:'Boosters', nameColors:'Name Colors', tables:'Tables', createRoom:'Create Room', publicRoom:'Public Room', privateRoom:'Private Room', password:'Password', enter:'Enter', cancel:'Cancel', leaderboard:'Leaderboard', rules:'Rules', gameList:'Games List', quick:'Quick Reactions', chat:'Chat', yourTurn:'Your Turn', bid:'Bid', pass:'Pass', play:'Play', leave:'Leave', scoreUs:'Us', scoreThem:'Them', round:'Round', claim:'Claim', claimed:'Claimed', active:'Active', joinClub:'Join', leaveClub:'Leave Club', members:'members', treasury:'Treasury', eventJoin:'Participate', startsIn:'Starts in', prize:'Prize', entryFee:'Entry fee', joined:'Joined', markAll:'Read all', deleteAll:'Delete all', delete:'Delete', markRead:'Read', send:'Send', typeMessage:'Type a message...', logout:'Log out', sound:'Sound', vibration:'Vibration', autoPlay:'Auto-play when timer expires', save:'Save', rulesTitle:'Game Rules', close:'Close', status:'Status', roomName:'Room name', confirm:'Confirm', activityBusy:'Leave the current activity before joining another one.', successBuy:'Purchase completed', noCoins:'Insufficient balance', joinedCompetition:'Competition joined', joinedClub:'Club joined', leftClub:'Club left', rewardClaimed:'Reward added to your balance', themeApplied:'Theme applied across the app', languageApplied:'App language changed', gameStarted:'Game room opened', cardPlayed:'Card played', autoPlayed:'Timer expired; the computer played for you', roomCreated:'Room created', passwordWrong:'Wrong password', privateInvite:'Private room — password: 2468', dailyReward:'Daily reward', loginStreak:'Login streak', currentActivity:'Current activity', none:'None', matchHistory:'Match history', achievements:'Achievements', winRate:'Win rate', wins:'wins', played:'matches', adminNote:'This is an interactive local build. Once a live Laravel URL is connected, purchases, joins and chat are stored in the database.', install:'Install App', appReady:'The app is ready to install on your phone', online:'Online', offline:'Offline', rank:'Rank', today:'Today', week:'Week', season:'Season', roomChat:'Room Chat', quickReactions:'Quick Reactions', selectCard:'Select a card first', bidPhase:'Bidding phase', chooseBid:'Choose your bid', settingsSaved:'Settings saved', domino:'Domino', tarneeb:'Tarneeb', trix:'Trix', hand:'Hand', banakil:'Banakil', baloot:'Baloot', basra:'Basra', jackaroo:'Jackaroo', chess:'Chess', backgammon:'Backgammon', solitaire:'Competitive Solitaire', tarneeb400:'Tarneeb 400', syrianTarneeb:'Syrian Tarneeb', trixComplex:'Trix Complex', handSaudi:'Saudi Hand'
    },
    de:{home:'Start',games:'Spiele',store:'Shop',clubs:'Clubs',events:'Events',welcome:'Willkommen zurück',level:'Level',coins:'Münzen',vip:'VIP',day:'Tage',xp:'XP',champions:'Champions Cup',heroText:'Tritt gegen starke Spieler an und gewinne goldene Preise',joinNow:'Jetzt teilnehmen',giftRoad:'Geschenkweg',featured:'Beliebte Spiele',seeAll:'Alle anzeigen',friendly:'Freundschaftsspiel',competitions:'Wettbewerbe',challenges:'Herausforderungen',tournaments:'Turniere',friends:'Freunde',settings:'Einstellungen',rewards:'Belohnungen',search:'Spiel suchen',all:'Alle',cards:'Karten',board:'Brett',available:'Jetzt verfügbar',players:'Spieler',notifications:'Benachrichtigungen',messages:'Nachrichten',profile:'Profil',language:'Sprache',theme:'Design',buy:'Kaufen',owned:'Besitzt',preview:'Vorschau',balance:'Guthaben',inventory:'Inventar',transactions:'Token-Verlauf',createRoom:'Raum erstellen',rules:'Regeln',leaderboard:'Rangliste',chat:'Chat',quick:'Schnelle Reaktionen',yourTurn:'Du bist dran',bid:'Reizen',pass:'Passen',play:'Spielen',leave:'Verlassen',claim:'Abholen',save:'Speichern',close:'Schließen'},
    tr:{home:'Ana Sayfa',games:'Oyunlar',store:'Mağaza',clubs:'Kulüpler',events:'Etkinlikler',welcome:'Tekrar hoş geldin',level:'Seviye',coins:'Jetonlar',vip:'VIP',day:'Gün',xp:'XP',champions:'Şampiyonlar Kupası',heroText:'Güçlü oyuncularla yarış ve büyük ödüller kazan',joinNow:'Şimdi katıl',giftRoad:'Hediye Yolu',featured:'Öne Çıkan Oyunlar',seeAll:'Tümünü gör',friendly:'Dostluk Maçı',competitions:'Yarışmalar',challenges:'Görevler',tournaments:'Turnuvalar',friends:'Arkadaşlar',settings:'Ayarlar',rewards:'Ödüller',search:'Oyun ara',all:'Tümü',cards:'Kartlar',board:'Masa',available:'Şimdi açık',players:'oyuncu',notifications:'Bildirimler',messages:'Mesajlar',profile:'Profil',language:'Dil',theme:'Tema',buy:'Satın al',owned:'Sahip',preview:'Önizleme',balance:'Bakiye',inventory:'Envanter',transactions:'Jeton Geçmişi',createRoom:'Oda Oluştur',rules:'Kurallar',leaderboard:'Liderlik',chat:'Sohbet',quick:'Hızlı Tepkiler',yourTurn:'Sıra Sende',bid:'Teklif',pass:'Pas',play:'Oyna',leave:'Ayrıl',claim:'Al',save:'Kaydet',close:'Kapat'},
    fr:{home:'Accueil',games:'Jeux',store:'Boutique',clubs:'Clubs',events:'Événements',welcome:'Bon retour',level:'Niveau',coins:'Pièces',vip:'VIP',day:'Jours',xp:'XP',champions:'Coupe des champions',heroText:'Affrontez les meilleurs joueurs et gagnez de grands prix',joinNow:'Rejoindre',giftRoad:'Route des cadeaux',featured:'Jeux vedettes',seeAll:'Tout voir',friendly:'Partie amicale',competitions:'Compétitions',challenges:'Défis',tournaments:'Tournois',friends:'Amis',settings:'Paramètres',rewards:'Récompenses',search:'Rechercher un jeu',all:'Tous',cards:'Cartes',board:'Plateau',available:'Disponible',players:'joueurs',notifications:'Notifications',messages:'Messages',profile:'Profil',language:'Langue',theme:'Thème',buy:'Acheter',owned:'Possédé',preview:'Aperçu',balance:'Solde',inventory:'Inventaire',transactions:'Historique des jetons',createRoom:'Créer une salle',rules:'Règles',leaderboard:'Classement',chat:'Discussion',quick:'Réactions rapides',yourTurn:'À votre tour',bid:'Annoncer',pass:'Passer',play:'Jouer',leave:'Quitter',claim:'Réclamer',save:'Enregistrer',close:'Fermer'},
    es:{home:'Inicio',games:'Juegos',store:'Tienda',clubs:'Clubes',events:'Eventos',welcome:'Bienvenido de nuevo',level:'Nivel',coins:'Monedas',vip:'VIP',day:'Días',xp:'XP',champions:'Copa de Campeones',heroText:'Compite con los mejores y gana grandes premios',joinNow:'Unirse ahora',giftRoad:'Camino de regalos',featured:'Juegos destacados',seeAll:'Ver todo',friendly:'Partida amistosa',competitions:'Competiciones',challenges:'Desafíos',tournaments:'Torneos',friends:'Amigos',settings:'Ajustes',rewards:'Recompensas',search:'Buscar juego',all:'Todos',cards:'Cartas',board:'Tablero',available:'Disponible ahora',players:'jugadores',notifications:'Notificaciones',messages:'Mensajes',profile:'Perfil',language:'Idioma',theme:'Tema',buy:'Comprar',owned:'Comprado',preview:'Vista previa',balance:'Saldo',inventory:'Inventario',transactions:'Historial de fichas',createRoom:'Crear sala',rules:'Reglas',leaderboard:'Clasificación',chat:'Chat',quick:'Reacciones rápidas',yourTurn:'Tu turno',bid:'Apostar',pass:'Pasar',play:'Jugar',leave:'Salir',claim:'Reclamar',save:'Guardar',close:'Cerrar'}
  };

  const themePalettes = {
    dark:    {bg:'#07131f',bg2:'#0a1a29',panel:'#0f2234',panel2:'#142c42',panel3:'#18364f',gold:'#f4c66c',gold2:'#d89b31',green:'#1eb17b',green2:'#0f7652',blue:'#4b82ff',purple:'#8f63e8'},
    emerald: {bg:'#051812',bg2:'#09271e',panel:'#0d3126',panel2:'#124333',panel3:'#195541',gold:'#f6d579',gold2:'#c99936',green:'#33c98f',green2:'#117554',blue:'#4f9cff',purple:'#8b63d8'},
    royal:   {bg:'#07142d',bg2:'#0b2143',panel:'#102a54',panel2:'#17376b',panel3:'#1d4785',gold:'#ffd071',gold2:'#ca9130',green:'#27ae7d',green2:'#126a4d',blue:'#5c8cff',purple:'#9872e8'},
    purple:  {bg:'#160b23',bg2:'#241037',panel:'#301646',panel2:'#401e5d',panel3:'#552979',gold:'#ffd06a',gold2:'#ce9634',green:'#3db58b',green2:'#17684f',blue:'#718cff',purple:'#b276ff'},
    classic: {bg:'#20150d',bg2:'#302015',panel:'#3b281b',panel2:'#4c3423',panel3:'#61452f',gold:'#f2ca79',gold2:'#bc803b',green:'#329b6e',green2:'#1c6248',blue:'#6288d9',purple:'#9f6dc1'}
  };

  const games = [
    {id:'domino',icon:'🁫',type:'board',players:24315,color:'#176647'},
    {id:'tarneeb',icon:'🂡',type:'cards',players:18872,color:'#194c83'},
    {id:'trix',icon:'🃏',type:'cards',players:9456,color:'#7c3158'},
    {id:'hand',icon:'🂮',type:'cards',players:8154,color:'#845a20'},
    {id:'banakil',icon:'🎴',type:'cards',players:6420,color:'#4c3a82'},
    {id:'baloot',icon:'♠️',type:'cards',players:15220,color:'#17604c'},
    {id:'basra',icon:'♦️',type:'cards',players:5219,color:'#7a3037'},
    {id:'jackaroo',icon:'🎲',type:'board',players:12035,color:'#2b607a'},
    {id:'chess',icon:'♛',type:'board',players:7643,color:'#555e68'},
    {id:'backgammon',icon:'🎲',type:'board',players:8841,color:'#785633'},
    {id:'solitaire',icon:'🂠',type:'cards',players:4160,color:'#294c70'},
    {id:'tarneeb400',icon:'4️⃣',type:'cards',players:7315,color:'#6a2e52'},
    {id:'syrianTarneeb',icon:'🇸🇾',type:'cards',players:3520,color:'#33573b'},
    {id:'trixComplex',icon:'👑',type:'cards',players:6189,color:'#69417b'},
    {id:'handSaudi',icon:'🇸🇦',type:'cards',players:5791,color:'#1c654c'}
  ];

  const storeItems = [
    {id:'vip30',cat:'pasha',icon:'👑',nameAr:'باشا 30 يوم',nameEn:'VIP 30 Days',descAr:'مزايا حصرية، XP مضاعف وشارة ذهبية.',descEn:'Exclusive benefits, double XP and golden badge.',price:34900},
    {id:'vip90',cat:'pasha',icon:'♛',nameAr:'باشا 90 يوم',nameEn:'VIP 90 Days',descAr:'أفضل قيمة مع 90 يوماً من المزايا.',descEn:'Best value with 90 days of benefits.',price:79900},
    {id:'themeRoyal',cat:'themes',icon:'🌌',nameAr:'الثيم الملكي',nameEn:'Royal Theme',descAr:'ألوان زرقاء داكنة بلمسات ذهبية.',descEn:'Deep royal blue with gold accents.',price:12500},
    {id:'themeEmerald',cat:'themes',icon:'💚',nameAr:'الثيم الزمردي',nameEn:'Emerald Theme',descAr:'طابع زمردي فاخر لكل الشاشات.',descEn:'Luxury emerald look for all screens.',price:11800},
    {id:'cardMarble',cat:'cardBacks',icon:'🂠',nameAr:'ظهر رخام أبيض',nameEn:'White Marble Back',descAr:'ظهر ورق فاخر بتفاصيل رخامية.',descEn:'Premium card back with marble detail.',price:8900},
    {id:'cardGold',cat:'cardBacks',icon:'🎴',nameAr:'ظهر ذهبي',nameEn:'Golden Card Back',descAr:'نقوش ذهبية واضحة داخل غرفة اللعب.',descEn:'Clear golden ornaments in game rooms.',price:9400},
    {id:'emojiFun',cat:'emojis',icon:'😂',nameAr:'حزمة المرح',nameEn:'Fun Emoji Pack',descAr:'إيموجي متحركة وأصوات قصيرة.',descEn:'Animated emoji and short sounds.',price:4200},
    {id:'emojiRoyal',cat:'emojis',icon:'😎',nameAr:'حزمة الهيبة',nameEn:'Royal Emoji Pack',descAr:'تفاعلات حصرية للاعبين المميزين.',descEn:'Exclusive reactions for premium players.',price:5200},
    {id:'boost2',cat:'boosters',icon:'⚡',nameAr:'مسرع XP ×2',nameEn:'XP Booster ×2',descAr:'يضاعف نقاط الخبرة لمدة 24 ساعة.',descEn:'Doubles XP for 24 hours.',price:6800},
    {id:'boost3',cat:'boosters',icon:'🚀',nameAr:'مسرع XP ×3',nameEn:'XP Booster ×3',descAr:'ثلاثة أضعاف الخبرة لمدة 12 ساعة.',descEn:'Triple XP for 12 hours.',price:9900},
    {id:'nameGold',cat:'nameColors',icon:'🅰️',nameAr:'لون اسم ذهبي',nameEn:'Gold Name Color',descAr:'معاينة اسمك باللون الذهبي.',descEn:'Preview your name in gold.',price:3500},
    {id:'namePurple',cat:'nameColors',icon:'🟣',nameAr:'لون اسم بنفسجي',nameEn:'Purple Name Color',descAr:'لون بنفسجي واضح في الملفات والغرف.',descEn:'Clear purple color in profiles and rooms.',price:3500},
    {id:'tableEmerald',cat:'tables',icon:'🟢',nameAr:'طاولة زمردية',nameEn:'Emerald Table',descAr:'طاولة منحنية بحواف ذهبية.',descEn:'Curved table with golden rim.',price:14900},
    {id:'tableRoyal',cat:'tables',icon:'🔵',nameAr:'طاولة ملكية',nameEn:'Royal Table',descAr:'طاولة زرقاء داكنة للبطولات.',descEn:'Deep blue tournament table.',price:15900}
  ];

  const clubs = [
    {id:'falcons',icon:'🦅',name:'صقور العرب',level:18,members:46,treasury:315000,desc:'نادي تنافسي نشط يومياً.'},
    {id:'kings',icon:'👑',name:'ملوك الورق',level:25,members:50,treasury:728000,desc:'نخبة لاعبي الطرنيب والتركس.'},
    {id:'friends',icon:'🤝',name:'رفاق اللعب',level:12,members:31,treasury:146000,desc:'نادي اجتماعي للعب الودي.'}
  ];

  const events = [
    {id:'champions',icon:'🏆',title:'بطولة الأبطال',desc:'بطولة إقصائية من 64 لاعباً.',prize:250000,fee:1990,time:'02:14:37'},
    {id:'weekend',icon:'🎉',title:'تحدي نهاية الأسبوع',desc:'اربح 7 مباريات لتحصل على الصندوق.',prize:75000,fee:500,time:'14:22:10'},
    {id:'clubsWar',icon:'🛡️',title:'حرب الأندية',desc:'مواجهة أسبوعية بين أفضل الأندية.',prize:500000,fee:0,time:'1 يوم'}
  ];

  const defaultState = {
    lang:'ar', theme:'dark', tab:'home', coins:125680, level:28, xp:18560, xpNext:25000, vipDays:12,
    owned:['emojiFun'], activeActivity:null, activeClub:null, claimedRewards:[], joinedEvents:[],
    notifications:[
      {id:1,icon:'🏆',title:'بدأ التسجيل في بطولة الأبطال',body:'المقاعد محدودة والتسجيل متاح الآن.',read:false},
      {id:2,icon:'🎁',title:'مكافأتك اليومية جاهزة',body:'استلم 2,500 توكن و20 XP.',read:false},
      {id:3,icon:'👤',title:'أرسل سامر طلب صداقة',body:'يمكنك القبول من قائمة الأصدقاء.',read:false}
    ],
    transactions:[
      {id:1,label:'مكافأة يومية',amount:2500,date:'اليوم 09:12'},
      {id:2,label:'رسوم مباراة طرنيب',amount:-500,date:'أمس 22:04'},
      {id:3,label:'شراء حزمة المرح',amount:-4200,date:'أمس 18:27'}
    ],
    chat:[
      {me:false,name:'سامر',text:'بالتوفيق للجميع 👋',time:'09:41'},
      {me:true,name:'أنت',text:'مباراة جميلة، لنبدأ!',time:'09:42'}
    ],
    settings:{sound:true,vibration:true,autoPlay:true}, room:null, hero:0, storeCat:'all', gamesFilter:'all'
  };

  function loadState() {
    try {
      const saved = JSON.parse(localStorage.getItem('warqna_v142_state') || 'null');
      return {...defaultState, ...(saved || {}), settings:{...defaultState.settings, ...(saved?.settings || {})}};
    } catch (_) { return structuredClone(defaultState); }
  }
  let state = loadState();
  let deferredInstall = null;
  let roomTimer = null;
  let toastTimer = null;

  const t = key => dictionaries[state.lang]?.[key] ?? dictionaries.en[key] ?? dictionaries.ar[key] ?? key;
  const isArabic = () => state.lang === 'ar';
  const localName = item => isArabic() ? (item.nameAr || item.title || item.name) : (item.nameEn || item.title || item.name);
  const localDesc = item => isArabic() ? (item.descAr || item.desc || '') : (item.descEn || item.desc || '');

  function save() { localStorage.setItem('warqna_v142_state', JSON.stringify(state)); }
  function setCssTheme() {
    const p = themePalettes[state.theme] || themePalettes.dark;
    Object.entries(p).forEach(([k,v]) => document.documentElement.style.setProperty(`--${k}`, v));
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', p.bg);
  }
  function applyLocale() {
    document.documentElement.lang = state.lang;
    document.documentElement.dir = state.lang === 'ar' ? 'rtl' : 'ltr';
  }
  function toast(message) {
    const el = $('#toast');
    el.textContent = message;
    el.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => el.classList.remove('show'), 2400);
  }
  function setState(patch, render = true) {
    state = {...state, ...(typeof patch === 'function' ? patch(state) : patch)};
    save();
    if (render) renderApp();
  }

  function topbarHtml() {
    const unread = state.notifications.filter(n => !n.read).length;
    return `
      <button class="profile-mini" data-action="profile">
        <span class="avatar">أ</span>
        <span class="profile-copy"><b>أحمد</b><small>${t('welcome')} • ${t('online')}</small></span>
      </button>
      <div class="top-spacer"></div>
      <button class="icon-btn" data-action="notifications" aria-label="${t('notifications')}">🔔${unread ? `<i class="badge">${unread}</i>` : ''}</button>
      <button class="language-btn" data-action="languages" aria-label="${t('language')}">${state.lang.toUpperCase()}</button>
      <button class="theme-btn" data-action="themes" aria-label="${t('theme')}">🎨</button>`;
  }

  function navHtml() {
    const items = [['store','🎁'],['games','🃏'],['home','🏠'],['clubs','🛡️'],['events','📅']];
    return items.map(([id,icon]) => `<button class="nav-item ${state.tab===id?'active':''}" data-tab="${id}"><span>${icon}</span><small>${t(id)}</small></button>`).join('');
  }

  function renderApp() {
    applyLocale(); setCssTheme();
    $('#topbar').innerHTML = topbarHtml();
    $('#bottomNav').innerHTML = navHtml();
    const screen = $('#screen');
    if (state.room) {
      $('#topbar').style.display = 'none';
      $('#bottomNav').style.display = 'none';
      screen.innerHTML = roomHtml();
      startRoomTimer();
    } else {
      stopRoomTimer();
      $('#topbar').style.display = '';
      $('#bottomNav').style.display = '';
      const views = {home:homeHtml,games:gamesHtml,store:storeHtml,clubs:clubsHtml,events:eventsHtml};
      screen.innerHTML = (views[state.tab] || homeHtml)();
    }
  }

  function homeHtml() {
    return `
      <section class="stats-grid">
        <article class="stat-card"><span class="stat-label">🏅 ${t('level')}</span><div class="stat-value">${state.level}<small> ${fmt(state.xp)} / ${fmt(state.xpNext)}</small></div><div class="progress"><i style="width:${Math.min(100,state.xp/state.xpNext*100)}%"></i></div></article>
        <article class="stat-card" data-action="wallet"><span class="stat-label">🪙 ${t('coins')}</span><div class="stat-value">${fmt(state.coins)} <small>＋</small></div></article>
        <article class="stat-card" data-action="vip"><span class="stat-label">👑 ${t('vip')}</span><div class="stat-value">${state.vipDays} <small>${t('day')}</small></div></article>
      </section>
      <section class="hero">
        <div class="hero-copy"><span class="hero-kicker">WARQNA CHAMPIONSHIP</span><h1>${t('champions')}</h1><p>${t('heroText')}</p><button class="primary" data-action="join-event" data-id="champions">${t('joinNow')}</button></div><span class="hero-art">🏆</span>
      </section>
      <div class="hero-dots"><i class="active"></i><i></i><i></i><i></i></div>
      <section class="section panel gift-road">
        <div class="section-head"><h3>${t('giftRoad')}</h3><button data-action="rewards">5 / 10</button></div>
        <div class="progress"><i style="width:50%"></i></div>
        <div class="gift-row"><div class="gift-step done">🎁<small>${t('today')}</small></div><div class="gift-step current">🎁<small>10</small></div><div class="gift-step">🎁<small>20</small></div><div class="gift-step">🎁<small>30</small></div></div>
      </section>
      <section class="section">
        <div class="section-head"><h2>${t('featured')}</h2><button data-tab="games">${t('seeAll')}</button></div>
        <div class="games-grid">${games.slice(0,3).map(gameCardHtml).join('')}</div>
      </section>
      <section class="section action-grid">
        <button class="green-btn" data-action="friendly">🤝 ${t('friendly')}</button>
        <button class="gold-btn" data-action="competitions">🏆 ${t('competitions')}</button>
      </section>
      <section class="section panel quick-grid">
        ${[['challenges','🎯'],['tournaments','🏆'],['clubs','🛡️'],['friends','👥'],['settings','⚙️']].map(([a,i])=>`<button class="quick-item" data-action="${a}"><span>${i}</span><small>${t(a)}</small></button>`).join('')}
      </section>`;
  }

  function gameCardHtml(g) {
    return `<button class="game-card" data-action="open-game" data-id="${g.id}" style="background:linear-gradient(160deg,${g.color},var(--panel))"><span class="game-status">${t('available')}</span><span class="game-icon">${g.icon}</span><b>${t(g.id)}</b><small>${fmt(g.players)} ${t('players')}</small></button>`;
  }

  function gamesHtml() {
    const filtered = games.filter(g => state.gamesFilter === 'all' || g.type === state.gamesFilter);
    return `
      <div class="section-head"><h2>${t('games')}</h2><button data-action="create-room">＋ ${t('createRoom')}</button></div>
      <div class="search-row"><input class="input" id="gameSearch" placeholder="${t('search')}"><button class="icon-btn" data-action="create-room">＋</button></div>
      <div class="tabs">${[['all',t('all')],['cards',t('cards')],['board',t('board')]].map(([id,label])=>`<button class="tab ${state.gamesFilter===id?'active':''}" data-action="game-filter" data-id="${id}">${label}</button>`).join('')}</div>
      <section class="section games-grid" id="gamesGrid">${filtered.map(gameCardHtml).join('')}</section>
      <section class="section panel quick-grid">
        <button class="quick-item" data-action="leaderboard"><span>📊</span><small>${t('leaderboard')}</small></button>
        <button class="quick-item" data-action="rules-all"><span>📖</span><small>${t('rules')}</small></button>
        <button class="quick-item" data-action="competitions"><span>🏆</span><small>${t('competitions')}</small></button>
        <button class="quick-item" data-action="friends"><span>👥</span><small>${t('friends')}</small></button>
        <button class="quick-item" data-action="match-history"><span>🕘</span><small>${t('matchHistory')}</small></button>
      </section>`;
  }

  function storeHtml() {
    const cats = [['all',t('all')],['pasha',t('pasha')],['themes',t('themes')],['cardBacks',t('cardBacks')],['emojis',t('emojis')],['boosters',t('boosters')],['nameColors',t('nameColors')],['tables',t('tables')]];
    const items = storeItems.filter(x => state.storeCat === 'all' || x.cat === state.storeCat);
    return `
      <div class="section-head"><h2>${t('store')}</h2><button data-action="wallet">🪙 ${fmt(state.coins)}</button></div>
      <div class="tabs">${cats.map(([id,label])=>`<button class="tab ${state.storeCat===id?'active':''}" data-action="store-filter" data-id="${id}">${label}</button>`).join('')}</div>
      <section class="section store-grid">${items.map(productHtml).join('')}</section>
      <section class="section notice">${t('adminNote')}</section>`;
  }

  function productHtml(item) {
    const owned = state.owned.includes(item.id);
    return `<article class="product-card"><button class="product-preview" data-action="preview-product" data-id="${item.id}" style="border:0;width:100%;color:inherit">${item.icon}</button><h3>${esc(localName(item))}</h3><p>${esc(localDesc(item))}</p><div class="price-row"><span class="price">🪙 ${fmt(item.price)}</span><button class="mini-buy ${owned?'owned':''}" data-action="${owned?'preview-product':'buy'}" data-id="${item.id}">${owned?t('owned'):t('buy')}</button></div></article>`;
  }

  function clubsHtml() {
    return `
      <div class="section-head"><h2>${t('clubs')}</h2><button data-action="create-club">＋</button></div>
      ${state.activeClub ? currentClubHtml() : `<div class="notice warning">${t('currentActivity')}: ${state.activeActivity ? esc(state.activeActivity.label) : t('none')}</div>`}
      <section class="section list">${clubs.map(c=>`<article class="list-card"><span class="list-icon">${c.icon}</span><div class="list-copy"><b>${esc(c.name)} • LV.${c.level}</b><p>${esc(c.desc)}<br>${c.members}/50 ${t('members')} • ${t('treasury')}: ${fmt(c.treasury)}</p></div><div class="list-actions"><button class="green-btn" data-action="join-club" data-id="${c.id}">${state.activeClub===c.id?t('active'):t('joinClub')}</button></div></article>`).join('')}</section>`;
  }

  function currentClubHtml() {
    const c = clubs.find(x=>x.id===state.activeClub);
    if (!c) return '';
    return `<section class="panel profile-card"><div class="avatar lg">${c.icon}</div><h2>${esc(c.name)}</h2><p>${esc(c.desc)}</p><div class="profile-meta"><div><b>${c.level}</b><small>${t('level')}</small></div><div><b>${c.members}</b><small>${t('members')}</small></div><div><b>${fmt(c.treasury)}</b><small>${t('treasury')}</small></div></div><button class="danger" style="margin-top:14px" data-action="leave-club">${t('leaveClub')}</button></section>`;
  }

  function eventsHtml() {
    return `
      <div class="section-head"><h2>${t('events')}</h2><button data-action="rewards">🎁 ${t('rewards')}</button></div>
      ${state.activeActivity?.type==='event' ? `<section class="notice warning" style="display:flex;align-items:center;justify-content:space-between;gap:10px"><span>${t('currentActivity')}: ${esc(state.activeActivity.label)}</span><button class="danger" data-action="leave-activity">${t('leave')}</button></section>` : ''}
      <section class="list" style="margin-top:12px">${events.map(e=>`<article class="list-card"><span class="list-icon">${e.icon}</span><div class="list-copy"><b>${esc(e.title)}</b><p>${esc(e.desc)}<br>${t('prize')}: 🪙 ${fmt(e.prize)} • ${t('entryFee')}: ${fmt(e.fee)}</p></div><div class="list-actions"><button class="gold-btn" data-action="join-event" data-id="${e.id}">${state.joinedEvents.includes(e.id)?t('joined'):t('eventJoin')}</button></div></article>`).join('')}</section>
      <section class="section panel gift-road"><div class="section-head"><h3>${t('dailyReward')}</h3><button data-action="claim-reward" data-id="daily">${state.claimedRewards.includes('daily')?t('claimed'):t('claim')}</button></div><p style="color:var(--muted);font-size:11px">🪙 2,500 + ⭐ 20 XP</p><div class="progress"><i style="width:70%"></i></div></section>`;
  }

  function roomHtml() {
    const room = state.room;
    const hand = room.hand.map((c,i)=>`<button class="playing-card ${c.red?'red':''} ${room.selected===i?'selected':''}" data-action="select-card" data-index="${i}"><span>${c.rank}</span><span>${c.suit}</span></button>`).join('');
    const played = room.played.map(c=>`<div class="playing-card ${c.red?'red':''}"><span>${c.rank}</span><span>${c.suit}</span></div>`).join('');
    return `<div class="room-shell">
      <div class="room-head"><button class="icon-btn" data-action="leave-room">✕</button><div class="room-title"><b>${t(room.game)}</b><small>${t('round')} ${room.round} • ${room.phase==='bid'?t('bidPhase'):t('yourTurn')}</small></div><button class="icon-btn" data-action="room-settings">⚙️</button></div>
      <div class="scoreboard"><div><small>${t('scoreUs')}</small><b>${room.scoreUs}</b></div><span>VS</span><div><small>${t('scoreThem')}</small><b>${room.scoreThem}</b></div></div>
      <div class="game-area">
        <div class="table-felt"></div>
        <div class="player-seat top ${room.turn===1?'turn-ring':''}"><span class="avatar">س</span><span class="player-name">سامر • ${room.bids[1]||'—'}</span></div>
        <div class="player-seat left ${room.turn===2?'turn-ring':''}"><span class="avatar">ل</span><span class="player-name">ليلى • ${room.bids[2]||'—'}</span></div>
        <div class="player-seat right ${room.turn===3?'turn-ring':''}"><span class="avatar">ج</span><span class="player-name">جميل • ${room.bids[3]||'—'}</span></div>
        <div class="player-seat bottom ${room.turn===0?'turn-ring':''}"><span class="avatar">أ</span><span class="player-name">أحمد • ${room.bids[0]||'—'}</span></div>
        <span class="timer">⏱ 00:${String(room.timer).padStart(2,'0')}</span>
        <div class="played-cards">${played || '<div style="color:rgba(255,255,255,.3);font-size:11px">'+t('yourTurn')+'</div>'}</div>
        <div class="hand">${hand}</div>
        <div class="room-actions">
          ${room.phase==='bid' ? `<button class="green-btn" data-action="open-bid">${t('bid')}</button><button class="secondary" data-action="pass-bid">${t('pass')}</button><button class="danger" disabled style="opacity:.55">${t('play')}</button>` : `<button class="green-btn" data-action="draw-card">${t('bid')}</button><button class="secondary" data-action="pass-turn">${t('pass')}</button><button class="danger" data-action="play-card">${t('play')}</button>`}
        </div>
        <div class="room-tools">
          <button data-action="room-chat">💬</button><button data-action="toggle-reactions">😊</button><button data-action="room-rules">📖</button><button data-action="room-leaderboard">🏆</button><button data-action="room-more">•••</button>
        </div>
        <div class="reactions-pop ${room.reactionsOpen?'show':''}">${['👍','😂','😍','😮','😢','😡'].map(e=>`<button data-action="send-reaction" data-emoji="${e}">${e}</button>`).join('')}</div>
      </div>
    </div>`;
  }

  function openOverlay(html) {
    $('#sheetBody').innerHTML = html;
    $('#overlay').classList.add('show');
    $('#overlay').setAttribute('aria-hidden','false');
  }
  function closeOverlay() {
    $('#overlay').classList.remove('show');
    $('#overlay').setAttribute('aria-hidden','true');
  }

  function profileOverlay() {
    openOverlay(`<section class="panel profile-card"><div class="avatar lg">أ</div><h2>أحمد</h2><p style="color:var(--muted)">@ahmad • فلسطين 🇵🇸</p><div class="profile-meta"><div><b>61%</b><small>${t('winRate')}</small></div><div><b>842</b><small>${t('played')}</small></div><div><b>514</b><small>${t('wins')}</small></div></div></section><section class="section action-grid"><button class="secondary" data-action="match-history">🕘 ${t('matchHistory')}</button><button class="secondary" data-action="achievements">🏅 ${t('achievements')}</button></section><section class="section notice">${t('currentActivity')}: ${state.activeActivity?esc(state.activeActivity.label):t('none')}</section>`);
  }

  function notificationsOverlay() {
    const list = state.notifications.length ? state.notifications.map(n=>`<article class="notification ${n.read?'':'unread'}"><span class="list-icon">${n.icon}</span><div><b>${esc(n.title)}</b><p>${esc(n.body)}</p></div><div class="notification-actions">${!n.read?`<button data-action="mark-notification" data-id="${n.id}">✓</button>`:''}<button data-action="delete-notification" data-id="${n.id}">🗑️</button></div></article>`).join('') : `<div class="empty"><span>🔕</span>${t('notifications')}</div>`;
    openOverlay(`<div class="section-head"><h2 id="sheetTitle">${t('notifications')}</h2><div><button class="ghost" data-action="mark-all">${t('markAll')}</button> <button class="ghost" data-action="delete-all">${t('deleteAll')}</button></div></div>${list}`);
  }

  function languagesOverlay() {
    const langs = [['ar','العربية 🇸🇦'],['en','English 🇬🇧'],['de','Deutsch 🇩🇪'],['tr','Türkçe 🇹🇷'],['fr','Français 🇫🇷'],['es','Español 🇪🇸']];
    openOverlay(`<h2 id="sheetTitle">🌐 ${t('language')}</h2><div class="list">${langs.map(([id,name])=>`<button class="list-card" data-action="set-language" data-id="${id}" style="color:inherit;text-align:start"><span class="list-icon">${id.toUpperCase()}</span><div class="list-copy"><b>${name}</b><p>${state.lang===id?'✓ '+t('active'):''}</p></div></button>`).join('')}</div>`);
  }

  function themesOverlay() {
    const labels = {dark:t('dark'),emerald:t('emerald'),royal:t('royal'),purple:t('purple'),classic:t('classic')};
    openOverlay(`<h2 id="sheetTitle">🎨 ${t('theme')}</h2><div class="theme-grid">${Object.entries(themePalettes).map(([id,p])=>`<button class="theme-swatch ${state.theme===id?'active':''}" data-action="set-theme" data-id="${id}" aria-label="${labels[id]}" style="background:linear-gradient(135deg,${p.panel2},${p.gold})"></button>`).join('')}</div><div class="list" style="margin-top:13px">${Object.keys(themePalettes).map(id=>`<button class="list-card" data-action="set-theme" data-id="${id}" style="color:inherit;text-align:start"><span class="list-icon">🎨</span><div class="list-copy"><b>${labels[id]}</b><p>${state.theme===id?'✓ '+t('active'):''}</p></div></button>`).join('')}</div>`);
  }

  function walletOverlay() {
    openOverlay(`<div class="section-head"><h2 id="sheetTitle">🪙 ${t('transactions')}</h2><button class="gold-btn" data-action="add-demo-coins">＋ 10,000</button></div><section class="panel" style="padding:17px;text-align:center"><small style="color:var(--muted)">${t('balance')}</small><div style="font-size:31px;font-weight:900;color:var(--gold);margin-top:4px">${fmt(state.coins)} 🪙</div></section><section class="section panel">${state.transactions.map(x=>`<div class="wallet-row"><span>${x.amount>0?'⬇️':'⬆️'}</span><div><b>${esc(x.label)}</b><small style="display:block;color:var(--muted)">${esc(x.date)}</small></div><span class="amount ${x.amount>0?'plus':'minus'}">${x.amount>0?'+':''}${fmt(x.amount)}</span></div>`).join('')}</section>`);
  }

  function rewardsOverlay() {
    const rows = [
      {id:'daily',icon:'🎁',title:t('dailyReward'),value:'2,500 🪙 + 20 XP'},
      {id:'streak7',icon:'🔥',title:`${t('loginStreak')} 7`,value:'7,500 🪙 + ⚡'},
      {id:'games5',icon:'🏆',title:'العب 5 مباريات',value:'3,000 🪙'}
    ];
    openOverlay(`<h2 id="sheetTitle">🎁 ${t('rewards')}</h2><div class="list">${rows.map(r=>`<article class="list-card"><span class="list-icon">${r.icon}</span><div class="list-copy"><b>${r.title}</b><p>${r.value}</p></div><button class="green-btn" data-action="claim-reward" data-id="${r.id}" ${state.claimedRewards.includes(r.id)?'disabled style="opacity:.55"':''}>${state.claimedRewards.includes(r.id)?t('claimed'):t('claim')}</button></article>`).join('')}</div>`);
  }

  function settingsOverlay() {
    openOverlay(`<h2 id="sheetTitle">⚙️ ${t('settings')}</h2><section class="panel" style="padding:0 14px"><div class="setting-row"><div><label>${t('sound')}</label><small>أصوات اللعب والإيموجي</small></div><input type="checkbox" data-setting="sound" ${state.settings.sound?'checked':''}></div><div class="setting-row"><div><label>${t('vibration')}</label><small>اهتزاز خفيف في دورك</small></div><input type="checkbox" data-setting="vibration" ${state.settings.vibration?'checked':''}></div><div class="setting-row"><div><label>${t('autoPlay')}</label><small>اختيار ورقة قانونية تلقائياً</small></div><input type="checkbox" data-setting="autoPlay" ${state.settings.autoPlay?'checked':''}></div></section><section class="section action-grid"><button class="secondary" data-action="languages">🌐 ${t('language')}</button><button class="secondary" data-action="themes">🎨 ${t('theme')}</button></section><button class="primary" style="width:100%;margin-top:13px" data-action="save-settings">${t('save')}</button>`);
  }

  function competitionsOverlay() {
    openOverlay(`<h2 id="sheetTitle">🏆 ${t('competitions')}</h2><div class="list">${events.slice(0,2).map(e=>`<article class="list-card"><span class="list-icon">${e.icon}</span><div class="list-copy"><b>${e.title}</b><p>${t('prize')}: ${fmt(e.prize)} • ${t('entryFee')}: ${fmt(e.fee)}</p></div><button class="gold-btn" data-action="join-event" data-id="${e.id}">${state.joinedEvents.includes(e.id)?t('joined'):t('joinNow')}</button></article>`).join('')}</div>`);
  }

  function openGameOverlay(id) {
    const g = games.find(x=>x.id===id) || games[1];
    openOverlay(`<div style="text-align:center"><div style="font-size:73px">${g.icon}</div><h2 id="sheetTitle">${t(g.id)}</h2><p style="color:var(--muted)">${fmt(g.players)} ${t('players')} • ${t('available')}</p></div><section class="action-grid"><button class="green-btn" data-action="start-game" data-id="${g.id}">▶ ${t('friendly')}</button><button class="gold-btn" data-action="join-game-competition" data-id="${g.id}">🏆 ${t('competitions')}</button></section><section class="section quick-grid panel"><button class="quick-item" data-action="game-rules" data-id="${g.id}"><span>📖</span><small>${t('rules')}</small></button><button class="quick-item" data-action="leaderboard"><span>📊</span><small>${t('leaderboard')}</small></button><button class="quick-item" data-action="create-room" data-game="${g.id}"><span>🔒</span><small>${t('createRoom')}</small></button><button class="quick-item" data-action="friends"><span>👥</span><small>${t('friends')}</small></button><button class="quick-item" data-action="match-history"><span>🕘</span><small>${t('matchHistory')}</small></button></section>`);
  }

  function createRoomOverlay(game='tarneeb') {
    openOverlay(`<h2 id="sheetTitle">＋ ${t('createRoom')}</h2><label style="font-size:11px">${t('roomName')}</label><input id="roomNameInput" class="input" value="غرفة أحمد" style="margin:6px 0 12px"><div class="tabs"><button class="tab active" data-room-type="public">${t('publicRoom')}</button><button class="tab" data-room-type="private">${t('privateRoom')}</button></div><div id="privateFields" style="display:none;margin-top:12px"><label style="font-size:11px">${t('password')}</label><input id="roomPasswordInput" class="input" maxlength="8" value="2468" style="margin-top:6px"></div><button class="primary" style="width:100%;margin-top:15px" data-action="confirm-create-room" data-game="${game}">${t('confirm')}</button>`);
  }

  function rulesOverlay(gameId='tarneeb') {
    const rules = {
      tarneeb:'تُلعب الطرنيب بأربعة لاعبين في فريقين. يحصل كل لاعب على 13 ورقة. تبدأ مرحلة الطلب من 7 إلى 13، ويحدد صاحب أعلى طلب نوع الطرنيب. يجب اتباع النوع المطلوب إن كان متاحاً. ينجح الفريق عند تحقيق طلبه، ويفوز من يصل إلى النقاط المحددة أولاً.',
      domino:'يتخلص اللاعب من قطعه بوصل الأطراف المتشابهة. تنتهي الجولة عندما ينهي لاعب قطعه أو تُغلق الطاولة، ويحسب مجموع نقاط القطع المتبقية.',
      trix:'تتكون اللعبة من ممالك وعقود متعددة مثل اللطشات، البنات، الديناري والملك. تختلف طريقة احتساب النقاط حسب العقد المختار.',
      hand:'الهدف تكوين مجموعات وتسلسلات صحيحة ثم إنزال الورق وإنهاء اليد قبل المنافسين. تُحسب الأوراق المتبقية كنقاط سلبية.',
      banakil:'لعبة مجموعات وتسلسلات تعتمد على السحب والرمي وتكوين تشكيلات قانونية. يفوز من ينهي أوراقه بأقل نقاط متبقية.'
    };
    openOverlay(`<h2 id="sheetTitle">📖 ${t('rulesTitle')} — ${t(gameId)}</h2><div class="notice" style="font-size:12px">${rules[gameId] || rules.tarneeb}</div><h3>الفوز والخسارة</h3><p style="color:var(--muted);line-height:1.8;font-size:12px">يتم احتساب النتيجة تلقائياً بواسطة محرك اللعبة، مع منع الحركات غير القانونية، وتشغيل الكمبيوتر بذكاء عند انتهاء وقت الدور.</p>`);
  }

  function leaderboardOverlay() {
    const rows = [['🥇','ياسر',18420],['🥈','ليلى',17980],['🥉','سامر',16800],['4','أحمد',15120],['5','جميل',14540]];
    openOverlay(`<div class="section-head"><h2 id="sheetTitle">🏆 ${t('leaderboard')}</h2><div class="tabs"><button class="tab active">${t('week')}</button><button class="tab">${t('season')}</button></div></div><div class="list">${rows.map(r=>`<article class="list-card"><span class="list-icon">${r[0]}</span><div class="list-copy"><b>${r[1]}</b><p>${t('rank')} • ${fmt(r[2])} XP</p></div></article>`).join('')}</div>`);
  }

  function friendsOverlay() {
    openOverlay(`<h2 id="sheetTitle">👥 ${t('friends')}</h2><div class="search-row"><input class="input" placeholder="اسم اللاعب أو المعرف"><button class="icon-btn" data-action="fake-search">🔍</button></div><div class="list">${[['سامر','طرنيب','س'],['ليلى','دومينو','ل'],['جميل','متصل','ج']].map(x=>`<article class="list-card"><span class="avatar sm">${x[2]}</span><div class="list-copy"><b>${x[0]}</b><p>${x[1]} • ${t('online')}</p></div><div class="list-actions"><button class="secondary" data-action="message-friend" data-name="${x[0]}">💬</button><button class="green-btn" data-action="invite-friend" data-name="${x[0]}">＋</button></div></article>`).join('')}</div>`);
  }

  function chatOverlay() {
    openOverlay(`<div class="chat-panel"><div class="section-head"><h2 id="sheetTitle">💬 ${t('roomChat')}</h2><span style="color:var(--green);font-size:10px">● ${t('online')}</span></div><div class="chat-messages" id="chatMessages">${state.chat.map(m=>`<div class="bubble ${m.me?'me':''}">${esc(m.text)}<small>${esc(m.name)} • ${m.time}</small></div>`).join('')}</div><form class="chat-compose" id="chatForm"><input class="input" id="chatInput" autocomplete="off" placeholder="${t('typeMessage')}"><button type="submit">➤</button></form></div>`);
  }

  function bidOverlay() {
    openOverlay(`<h2 id="sheetTitle">🃏 ${t('chooseBid')}</h2><div class="bid-grid">${[7,8,9,10,11,12,13].map(n=>`<button data-action="choose-bid" data-bid="${n}">${n}</button>`).join('')}<button data-action="choose-bid" data-bid="PASS">${t('pass')}</button></div>`);
  }

  function productPreviewOverlay(id) {
    const item = storeItems.find(x=>x.id===id);
    if (!item) return;
    const owned = state.owned.includes(id);
    openOverlay(`<div style="text-align:center"><div class="product-preview" style="height:190px;font-size:95px">${item.icon}</div><h2 id="sheetTitle">${esc(localName(item))}</h2><p style="color:var(--muted)">${esc(localDesc(item))}</p></div><div class="notice">أحمد <span style="color:${id==='nameGold'?'#f4c66c':id==='namePurple'?'#b276ff':'inherit'};font-weight:900">— معاينة مباشرة للاسم والعنصر داخل التطبيق</span></div><button class="${owned?'secondary':'primary'}" style="width:100%;margin-top:13px" data-action="${owned?'close-overlay':'buy'}" data-id="${id}">${owned?t('owned'):`${t('buy')} • ${fmt(item.price)} 🪙`}</button>`);
  }

  function startRoom(game='tarneeb') {
    if (state.activeActivity && state.activeActivity.type !== 'game') { toast(t('activityBusy')); return; }
    closeOverlay();
    const deck = [
      {rank:'7',suit:'♣'}, {rank:'8',suit:'♣'}, {rank:'9',suit:'♣'}, {rank:'J',suit:'♦',red:true},
      {rank:'Q',suit:'♥',red:true}, {rank:'K',suit:'♠'}, {rank:'A',suit:'♠'}, {rank:'10',suit:'♥',red:true}
    ];
    state.room = {game,phase:'bid',timer:20,turn:0,round:1,scoreUs:152,scoreThem:98,hand:deck,selected:null,played:[],bids:['','','',''],reactionsOpen:false};
    state.activeActivity = {type:'game',label:t(game)};
    save(); renderApp(); toast(t('gameStarted'));
  }

  function leaveRoom() {
    state.room = null; state.activeActivity = null; save(); renderApp();
  }

  function startRoomTimer() {
    stopRoomTimer();
    roomTimer = setInterval(() => {
      if (!state.room) return stopRoomTimer();
      state.room.timer -= 1;
      if (state.room.timer <= 0) {
        if (state.settings.autoPlay) autoPlay(); else state.room.timer = 20;
      }
      save();
      const timerEl = $('.timer');
      if (timerEl) timerEl.textContent = `⏱ 00:${String(state.room.timer).padStart(2,'0')}`;
    }, 1000);
  }
  function stopRoomTimer() { if (roomTimer) clearInterval(roomTimer); roomTimer = null; }

  function autoPlay() {
    if (!state.room) return;
    if (state.room.phase === 'bid') {
      state.room.bids[0] = '7'; state.room.phase = 'play'; state.room.timer = 20;
    } else if (state.room.hand.length) {
      state.room.selected = 0; playSelectedCard(false);
    }
    save(); renderApp(); toast(t('autoPlayed'));
  }

  function playSelectedCard(showToast=true) {
    const room = state.room;
    if (!room || room.selected == null || !room.hand[room.selected]) { toast(t('selectCard')); return; }
    const [card] = room.hand.splice(room.selected,1);
    room.played.push(card); room.selected = null; room.timer = 20; room.turn = (room.turn + 1) % 4;
    if (room.played.length >= 4) { room.played = []; room.round += 1; room.scoreUs += 10; room.turn = 0; }
    save(); renderApp(); if (showToast) toast(t('cardPlayed'));
  }

  function buyItem(id) {
    const item = storeItems.find(x=>x.id===id);
    if (!item || state.owned.includes(id)) return productPreviewOverlay(id);
    if (state.coins < item.price) { toast(t('noCoins')); return; }
    state.coins -= item.price;
    state.owned.push(id);
    state.transactions.unshift({id:Date.now(),label:`${t('buy')}: ${localName(item)}`,amount:-item.price,date:new Date().toLocaleString(state.lang)});
    if (id==='vip30') state.vipDays += 30;
    if (id==='vip90') state.vipDays += 90;
    if (id==='themeRoyal') state.theme = 'royal';
    if (id==='themeEmerald') state.theme = 'emerald';
    save(); closeOverlay(); renderApp(); toast(t('successBuy'));
  }

  function joinEvent(id) {
    if (state.joinedEvents.includes(id)) { toast(t('joined')); return; }
    const e = events.find(x=>x.id===id);
    if (!e) return;
    if (state.activeActivity && (state.activeActivity.type !== 'event' || state.activeActivity.id !== id)) { toast(t('activityBusy')); return; }
    if (state.coins < e.fee) { toast(t('noCoins')); return; }
    state.coins -= e.fee; state.joinedEvents.push(id); state.activeActivity = {type:'event',id,label:e.title};
    if (e.fee) state.transactions.unshift({id:Date.now(),label:`${t('entryFee')}: ${e.title}`,amount:-e.fee,date:new Date().toLocaleString(state.lang)});
    save(); closeOverlay(); renderApp(); toast(t('joinedCompetition'));
  }

  function claimReward(id) {
    if (state.claimedRewards.includes(id)) return;
    const rewards = {daily:2500,streak7:7500,games5:3000};
    const amount = rewards[id] || 1000;
    state.claimedRewards.push(id); state.coins += amount; state.xp += 20;
    state.transactions.unshift({id:Date.now(),label:t('dailyReward'),amount,date:new Date().toLocaleString(state.lang)});
    save(); closeOverlay(); renderApp(); toast(t('rewardClaimed'));
  }

  function joinClub(id) {
    if (state.activeClub === id) return;
    if (state.activeClub && state.activeClub !== id) { toast(t('activityBusy')); return; }
    if (state.activeActivity && state.activeActivity.type !== 'club') { toast(t('activityBusy')); return; }
    state.activeClub = id; state.activeActivity = {type:'club',label:clubs.find(c=>c.id===id)?.name || t('clubs')};
    save(); renderApp(); toast(t('joinedClub'));
  }

  function handleAction(action, el) {
    switch (action) {
      case 'profile': profileOverlay(); break;
      case 'notifications': notificationsOverlay(); break;
      case 'languages': languagesOverlay(); break;
      case 'themes': themesOverlay(); break;
      case 'wallet': walletOverlay(); break;
      case 'vip': state.storeCat='pasha'; state.tab='store'; save(); renderApp(); break;
      case 'rewards': rewardsOverlay(); break;
      case 'settings': settingsOverlay(); break;
      case 'competitions': competitionsOverlay(); break;
      case 'friendly': openGameOverlay('tarneeb'); break;
      case 'open-game': openGameOverlay(el.dataset.id); break;
      case 'start-game': startRoom(el.dataset.id); break;
      case 'join-game-competition': closeOverlay(); joinEvent('champions'); break;
      case 'preview-product': productPreviewOverlay(el.dataset.id); break;
      case 'buy': buyItem(el.dataset.id); break;
      case 'game-filter': state.gamesFilter=el.dataset.id; save(); renderApp(); break;
      case 'store-filter': state.storeCat=el.dataset.id; save(); renderApp(); break;
      case 'create-room': createRoomOverlay(el.dataset.game || 'tarneeb'); break;
      case 'confirm-create-room': startRoom(el.dataset.game || 'tarneeb'); toast(t('roomCreated')); break;
      case 'leaderboard': case 'room-leaderboard': leaderboardOverlay(); break;
      case 'rules-all': case 'room-rules': rulesOverlay(state.room?.game || 'tarneeb'); break;
      case 'game-rules': rulesOverlay(el.dataset.id); break;
      case 'friends': friendsOverlay(); break;
      case 'challenges': rewardsOverlay(); break;
      case 'tournaments': competitionsOverlay(); break;
      case 'match-history': openOverlay(`<h2>${t('matchHistory')}</h2><div class="list">${[['طرنيب','فوز','+20 XP'],['دومينو','خسارة','+10 XP'],['تركس','فوز','+20 XP']].map(x=>`<div class="list-card"><span class="list-icon">🃏</span><div class="list-copy"><b>${x[0]} • ${x[1]}</b><p>${x[2]}</p></div></div>`).join('')}</div>`); break;
      case 'achievements': openOverlay(`<h2>${t('achievements')}</h2><div class="games-grid"><div class="game-card"><span class="game-icon">🏆</span><b>100 فوز</b></div><div class="game-card"><span class="game-icon">🔥</span><b>7 أيام</b></div><div class="game-card"><span class="game-icon">👑</span><b>باشا</b></div></div>`); break;
      case 'set-language': state.lang=el.dataset.id; save(); closeOverlay(); renderApp(); toast(t('languageApplied')); break;
      case 'set-theme': state.theme=el.dataset.id; save(); closeOverlay(); renderApp(); toast(t('themeApplied')); break;
      case 'mark-notification': {const n=state.notifications.find(n=>String(n.id)===el.dataset.id); if(n)n.read=true; save(); notificationsOverlay(); renderApp(); break;}
      case 'delete-notification': state.notifications=state.notifications.filter(n=>String(n.id)!==el.dataset.id); save(); notificationsOverlay(); renderApp(); break;
      case 'mark-all': state.notifications.forEach(n=>n.read=true); save(); notificationsOverlay(); renderApp(); break;
      case 'delete-all': state.notifications=[]; save(); notificationsOverlay(); renderApp(); break;
      case 'claim-reward': claimReward(el.dataset.id); break;
      case 'join-event': joinEvent(el.dataset.id); break;
      case 'join-club': joinClub(el.dataset.id); break;
      case 'leave-club': state.activeClub=null; state.activeActivity=null; save(); renderApp(); toast(t('leftClub')); break;
      case 'leave-activity': if(state.activeActivity?.type==='event'){state.joinedEvents=state.joinedEvents.filter(id=>id!==state.activeActivity.id);} state.activeActivity=null; save(); renderApp(); toast(t('leave')); break;
      case 'create-club': toast('سيتم فتح نموذج إنشاء النادي في النسخة المتصلة بالخادم.'); break;
      case 'close-overlay': closeOverlay(); break;
      case 'save-settings': $$('#sheetBody [data-setting]').forEach(x=>state.settings[x.dataset.setting]=x.checked); save(); closeOverlay(); toast(t('settingsSaved')); break;
      case 'add-demo-coins': state.coins+=10000; state.transactions.unshift({id:Date.now(),label:'شحن تجريبي',amount:10000,date:new Date().toLocaleString(state.lang)}); save(); walletOverlay(); renderApp(); break;
      case 'leave-room': leaveRoom(); break;
      case 'room-settings': settingsOverlay(); break;
      case 'select-card': state.room.selected=Number(el.dataset.index); state.room.timer=20; save(); renderApp(); break;
      case 'play-card': playSelectedCard(); break;
      case 'draw-card': toast('تم سحب ورقة تجريبية.'); break;
      case 'pass-turn': state.room.turn=(state.room.turn+1)%4; state.room.timer=20; save(); renderApp(); toast(t('pass')); break;
      case 'open-bid': bidOverlay(); break;
      case 'pass-bid': state.room.bids[0]=t('pass'); state.room.phase='play'; state.room.timer=20; save(); renderApp(); break;
      case 'choose-bid': state.room.bids[0]=el.dataset.bid; state.room.phase='play'; state.room.timer=20; save(); closeOverlay(); renderApp(); break;
      case 'toggle-reactions': state.room.reactionsOpen=!state.room.reactionsOpen; save(); renderApp(); break;
      case 'send-reaction': state.room.reactionsOpen=false; save(); renderApp(); toast(`${el.dataset.emoji} ${t('quickReactions')}`); break;
      case 'room-chat': chatOverlay(); break;
      case 'room-more': openOverlay(`<h2>•••</h2><div class="list"><button class="list-card" data-action="room-rules">📖 ${t('rules')}</button><button class="list-card" data-action="settings">⚙️ ${t('settings')}</button><button class="list-card" data-action="leave-room">🚪 ${t('leave')}</button></div>`); break;
      case 'invite-friend': toast(`${el.dataset.name}: ${t('privateInvite')}`); break;
      case 'message-friend': toast(`تم فتح محادثة ${el.dataset.name}`); break;
      case 'fake-search': toast('تم العثور على 3 لاعبين.'); break;
      case 'install': if(deferredInstall){deferredInstall.prompt(); deferredInstall=null}else toast(t('appReady')); break;
      default: toast('تم تنفيذ الأمر بنجاح.');
    }
  }

  document.addEventListener('click', event => {
    const tab = event.target.closest('[data-tab]');
    if (tab) { state.tab=tab.dataset.tab; state.room=null; save(); renderApp(); return; }
    const actionEl = event.target.closest('[data-action]');
    if (actionEl) { event.preventDefault(); handleAction(actionEl.dataset.action, actionEl); }
    const roomType = event.target.closest('[data-room-type]');
    if (roomType) {
      $$('[data-room-type]').forEach(x=>x.classList.remove('active')); roomType.classList.add('active');
      const fields=$('#privateFields'); if(fields) fields.style.display=roomType.dataset.roomType==='private'?'block':'none';
    }
  });

  document.addEventListener('input', event => {
    if (event.target.id === 'gameSearch') {
      const q = event.target.value.trim().toLowerCase();
      $$('#gamesGrid .game-card').forEach(card => card.style.display = card.textContent.toLowerCase().includes(q) ? '' : 'none');
    }
  });

  document.addEventListener('submit', event => {
    if (event.target.id === 'chatForm') {
      event.preventDefault();
      const input=$('#chatInput'); const text=input?.value.trim(); if(!text)return;
      state.chat.push({me:true,name:'أنت',text,time:new Date().toLocaleTimeString(state.lang,{hour:'2-digit',minute:'2-digit'})});
      save(); chatOverlay(); setTimeout(()=>{$('#chatMessages')?.scrollTo(0,99999);$('#chatInput')?.focus()},0);
    }
  });

  window.addEventListener('beforeinstallprompt', e => { e.preventDefault(); deferredInstall=e; });
  window.addEventListener('keydown', e => { if(e.key==='Escape') closeOverlay(); });
  if ('serviceWorker' in navigator && location.protocol.startsWith('http')) navigator.serviceWorker.register('./service-worker.js').catch(()=>{});

  renderApp();
})();
