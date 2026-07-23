Warqnaa V0.3.6+187 - CI HOTFIX ONE CLICK

هذا ملف إصلاح شامل يطبق فوق مشروع V187 الحالي دون حذف الميزات.

1) فك محتويات ZIP داخل جذر مشروع Warqnaa مع الاستبدال/الدمج.
2) شغّل:
   scripts\windows\current\APPLY_V187_CI_HOTFIX_WINDOWS.bat
3) بعد نجاح الفحص ستجد بجانب مجلد المشروع:
   Warqnaa_V0.3.6_B187_FULL_CI_FIXED.zip
   Warqnaa_V0.3.6_B187_PATCH_CI_FIXED.zip
4) افتح GitHub Desktop ثم Commit وPush.

الإصلاح يحذف backend-laravel/.env من المشروع بعد حفظ نسخة احتياطية خارج المشروع،
ويعالج composer.lock داخل GitHub Actions قبل composer install.
