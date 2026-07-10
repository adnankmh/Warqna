# ابدأ من هنا — Warqna v142

هذه الحزمة تجمع ثلاثة أجزاء مترابطة:

1. `flutter_app` — تطبيق Flutter للويب وAndroid وiOS.
2. `backend-laravel` — الخادم وقاعدة البيانات والحسابات والمحفظة والمتجر والأصدقاء والدردشة ومحركات الألعاب.
3. `web_demo` — معاينة خفيفة تعمل من المتصفح، لكنها ليست بديلاً عن Flutter + Laravel في اللعب الحقيقي.

## الحساب الإداري الافتراضي

- اسم المستخدم: `Adnan`
- كلمة المرور: `Adnan123`
- الرصيد بعد تشغيل Seeder: `1,000,000,000,000,000,000` توكن

> غيّر بيانات المدير وكلمة المرور قبل النشر العام عبر ملف `.env` على الخادم.

---

## 1) تشغيل Laravel على Windows

ادخل إلى مجلد:

```text
backend-laravel
```

ثم شغّل بالترتيب:

```text
setup-windows.bat
start-windows.bat
```

يفتح الخادم على:

```text
http://127.0.0.1:8006
```

واجهة API المستخدمة في تطبيق Flutter:

```text
http://127.0.0.1:8006/api/mobile/v1
```

متطلبات التشغيل المحلي:

- PHP 8.2 أو أحدث.
- Composer.
- Node.js 18 أو أحدث.
- SQLite مفعلة داخل PHP.

ملف `setup-windows.bat` ينشئ قاعدة SQLite، يثبت الحزم، ينفذ Migrations وSeeders، ويجهز حساب المدير.

---

## 2) تشغيل Flutter على الكمبيوتر

لا تحتاج Android Studio لتشغيل نسخة الويب. تحتاج Flutter SDK فقط:

```bat
cd flutter_app
flutter create . --platforms=web,android,ios --project-name warqna_mobile --org com.warqna
flutter pub get
flutter run -d chrome --dart-define=WARQNA_API_URL=http://127.0.0.1:8006/api/mobile/v1
```

يوجد أيضاً ملف مساعد:

```text
flutter_app\RUN_FLUTTER_WEB.bat
```

## تشغيل التطبيق من هاتف على نفس الشبكة

شغّل Laravel على عنوان الشبكة:

```bat
php artisan serve --host=0.0.0.0 --port=8006
```

اعرف IPv4 الخاص بالكمبيوتر عبر:

```bat
ipconfig
```

ثم شغّل Flutter مع رابط مثل:

```bat
flutter run -d chrome --dart-define=WARQNA_API_URL=http://192.168.1.10:8006/api/mobile/v1
```

قد تحتاج السماح لـ PHP عبر Windows Firewall.

---

## 3) بناء APK وAAB بدون Android Studio

ارفع كامل الحزمة إلى مستودع GitHub، ثم:

1. افتح `Settings`.
2. افتح `Secrets and variables` ثم `Actions`.
3. أضف Repository Variable باسم:

```text
WARQNA_API_URL
```

واجعل قيمته رابط Laravel العام عبر HTTPS، مثل:

```text
https://api.example.com/api/mobile/v1
```

4. افتح `Actions`.
5. شغّل Workflow:

```text
Build Android APK and AAB
```

ستجد APK وAAB داخل Artifacts بعد اكتمال البناء.

---

## 4) نشر Flutter Web على GitHub Pages

شغّل Workflow:

```text
Build and deploy Flutter Web
```

مهم: GitHub Pages يستضيف واجهة Flutter Web فقط. لا يشغل PHP أو Laravel أو SQLite/MySQL. يجب نشر مجلد `backend-laravel` على استضافة PHP منفصلة برابط HTTPS، ثم وضع الرابط في `WARQNA_API_URL`.

---

## 5) iOS

Workflow الخاص بـ iOS يجري الفحص والبناء غير الموقع على macOS داخل GitHub Actions. النشر الفعلي على App Store يحتاج:

- حساب Apple Developer.
- Bundle Identifier نهائي.
- شهادات وتوقيع Provisioning.
- إعداد App Store Connect.

لا يحتاج المستخدم إلى Android Studio لبناء Android عبر GitHub، لكن نشر iOS يظل خاضعاً لمتطلبات Apple.

---

## محركات الألعاب

المحركات المرفقة مدمجة في الخادم ضمن:

```text
backend-laravel/app/Services/GameEngine
```

وتشمل:

- طرنيب مستقل: 13 ورقة، مزايدة، اختيار الحكم، اتباع النوع، اللمّات، أهداف 31/41/61.
- طرنيب سوري وطرنيب 400.
- تركس، تركس شراكة، تركس كمبلكس.
- هاند، هاند شراكة، هاند سعودي.
- بناكيل وبناكل كلاسيك.
- بلوت.
- سوليتير تنافسي.
- دومينو.
- باصرة.
- جاكارو.
- طاولة الزهر.
- شطرنج.

كل حركة تُرسل إلى Laravel للتحقق منها قبل اعتمادها في وضع الخادم. لا تُرسل أوراق الخصوم إلى العميل، وتظهر فقط اليد الخاصة بالمستخدم وعدد أوراق الآخرين.

## اللعب المجاني والمتجر

- إنشاء الغرف واللعب لا يخصمان التوكنز.
- الخصم محصور في شراء منتجات المتجر.
- الشراء يتطلب تأكيداً صريحاً.
- سجل المحفظة يحفظ الشراء والتحويل والعمولة والمكافآت.
- تحويل التوكنز يخصم 10% إضافية من المرسل وتدخل في محفظة المدير.

## الأصدقاء والدردشة

الـ API يدعم:

- البحث عن لاعب.
- إرسال وقبول ورفض وإلغاء طلب الصداقة.
- الحظر وإلغاء الحظر.
- دردشة خاصة بين الأصدقاء.
- دردشة الغرفة.
- نقل التوكنز.

## ملاحظات إنتاجية مهمة

- اللعب بين أجهزة متعددة يحتاج Laravel منشوراً وقاعدة بيانات مركزية.
- التحديث اللحظي الكامل في الإنتاج يفضل ربطه بخدمة WebSocket مستقرة وإدارة Queue.
- الذكاء الاصطناعي الحالي بوت قائم على القواعد والحركات القانونية والأولويات، وليس نموذج تعلم آلي خارجياً.
- الدفع المالي الحقيقي وعمليات App Store/Google Play ليست مفعلة؛ المتجر الحالي يعمل بتوكنز داخلية.
- قبل الإطلاق العام: غيّر مفاتيح الخادم، بيانات المدير، CORS، وفعّل HTTPS والنسخ الاحتياطي.

اقرأ أيضاً:

- `WARQNA_V142_REAL_ENGINES_FULLSTACK_AR.md`
- `QUALITY_REPORT_V142_AR.md`
- `CHANGELOG_V142_AR.md`
