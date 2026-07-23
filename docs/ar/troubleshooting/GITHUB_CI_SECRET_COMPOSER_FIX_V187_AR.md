# إصلاح GitHub Actions لـ Warqnaa V0.3.6+187

هذه الحزمة تعالج السببين اللذين أوقفا جميع الـ workflows:

1. `Secret-bearing files must not ship: backend-laravel/.env`
2. عدم تطابق `backend-laravel/composer.lock` مع `composer.json` وغياب `phpunit/phpunit` و`mockery/mockery` من القفل.

## ما الذي يفعله الإصلاح؟

- ينسخ أي ملف `.env` احتياطيًا إلى مجلد **خارج مشروع Warqnaa** ثم يحذفه من الحزمة ومن Git إذا كان متتبعًا.
- يقوّي `.gitignore` في الجذر وLaravel لمنع إعادة رفع الأسرار مستقبلًا.
- يبقي `phpunit/phpunit` و`mockery/mockery` ضمن `require-dev`.
- يعدّل جميع ملفات GitHub Actions التي تحتوي `composer install` بحيث تنفّذ أولًا:
  - `composer update phpunit/phpunit mockery/mockery --with-all-dependencies`
  - `composer validate`
  - ثم أمر `composer install` الأصلي، حفاظًا على عقود CI السابقة.
- يشغّل `tools/validate_release.py` بعد الإصلاح.
- ينشئ ملفين بجانب مجلد المشروع:
  - `Warqnaa_V0.3.6_B187_FULL_CI_FIXED.zip`
  - `Warqnaa_V0.3.6_B187_PATCH_CI_FIXED.zip`
- ينشئ بصمة SHA-256 لكل ملف مضغوط.

## التشغيل على Windows

بعد فك الحزمة داخل جذر مشروع Warqnaa شغّل:

`scripts\windows\current\APPLY_V187_CI_HOTFIX_WINDOWS.bat`

## التشغيل على Linux أو macOS

`bash scripts/unix/current/apply-v187-ci-hotfix.sh`

## بعد النجاح

افتح GitHub Desktop. يجب أن ترى حذف `backend-laravel/.env` وتعديل `.gitignore` وملفات workflows. نفّذ Commit ثم Push.

## تنبيه أمني ضروري

إذا كان ملف `.env` قد رُفع سابقًا إلى GitHub وكان يحتوي مفاتيح أو كلمات مرور حقيقية، فحذفه من آخر Commit لا يلغي احتمال انكشافها في تاريخ Git. يجب تغيير تلك المفاتيح وكلمات المرور من مزوّدي الخدمات، ثم استخدام GitHub Secrets أو إعدادات الخادم بدل وضعها في المستودع.
