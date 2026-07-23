# Warqnaa — ورقنا

الإصدار الحالي: **0.3.6+187 FINAL CI FIXED**

ابدأ من: `START_HERE_AR.md`

المشروع منظم إلى:
- `flutter_app/`: تطبيق Web وAndroid وiOS وPWA.
- `backend-laravel/`: الخادم، الحسابات، الغرف، الاقتصاد ومحركات اللعب.
- `assets/`: أصول النشر والمتاجر.
- `docs/`: أدلة التشغيل، سجل الإصدارات، تقارير الجودة والمرجعيات.
- `tools/`: فحوص CI والتحقق من المصدر.
- `scripts/`: أدوات Windows وLinux/macOS.
- `.github/workflows/`: بناء Web وAndroid وiOS وBackend وبوابة الإصدار.

قبل النشر شغّل GitHub Actions وانتظر نجاح:
- Build and deploy Flutter Web
- Build Android APK & AAB
- Flutter iOS
- Backend CI
- Production Release Gate

أهم ما يميز V187:

- 20 لعبة ظاهرة وقابلة للعب محليًا دون خادم، مع بقاء Laravel هو المرجع الآمن للعب التنافسي.
- إضافة محركي جاكارو وليخة مع اختبارات قواعد مستقلة.
- إصلاح سياسة الجذر النظيف لتقبل ملفات ترقية V142/V143 المعروفة فقط.
- واجهة تنزيل وتركيب يدوية لهاند وبناكل، وترتيب سحب وإفلات لورق اللاعب.
- تبويب سلامة المحركات في لوحة الإدارة، وعقود CI تمنع اختلاف كتالوج Flutter وLaravel.

## V187 FINAL CI hardening

- Full clean-source replacement package.
- Secret-bearing runtime files are excluded and ignored.
- Stale Composer locks are repaired safely in CI.
- GitHub Actions use current Node 24-compatible official action majors.
- Run `tools/verify_ci_hardening_v187.py` for the dedicated regression gate.
