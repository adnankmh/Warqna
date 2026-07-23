# رفع Warqnaa V0.3.5 إلى GitHub

ارفع محتويات مجلد المشروع نفسه إلى جذر مستودع **adnankmh/Warqnaa** مع الحفاظ على `.git`، ثم نفّذ Commit وPush.

الإصدار الصحيح في `RELEASE_VERSION.json` هو **0.3.5+186**.

يجب نجاح المسارات التالية قبل النشر:

- Production Release Gate
- Backend CI and Security Foundation
- Build and deploy Flutter Web
- Android APK/AAB
- iOS unsigned build عند تشغيله يدويًا

يتضمن Backend CI اختبار `test-v186-engine-integrity.php` لجميع الألعاب الـ18، بينما تشغّل مسارات Flutter عقد `test_v186_engine_integrity_contract.py` لضمان عدم اختلاف كتالوج الهاتف عن الخادم.
