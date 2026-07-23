# رفع Warqnaa V0.3.6 إلى GitHub

ارفع محتويات مجلد المشروع نفسه إلى جذر مستودع **adnankmh/Warqnaa** مع الحفاظ على `.git`، ثم نفّذ Commit وPush.

الإصدار الصحيح في `RELEASE_VERSION.json` هو **0.3.6+187**.

يجب نجاح المسارات التالية قبل النشر:

- Production Release Gate
- Backend CI and Security Foundation
- Build and deploy Flutter Web
- Android APK/AAB
- iOS unsigned build عند تشغيله يدويًا

يتضمن Backend CI محاكاة مستقلة لجميع الألعاب العشرين، بينما تشغّل مسارات Flutter عقد V187 واختبارات المحركات المحلية. لا ترفع ملفات ZIP داخل جذر المستودع.

ملفات V142/V143 التاريخية المسماة صراحة في `LEGACY_ROOT_ENTRIES` مقبولة للتوافق مع Patch قديم، لكن أي ملف جذر غير معروف سيبقى سببًا لفشل الفحص.
