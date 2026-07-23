# إصلاح Flutter Analyzer للإصدار V0.3.6+187 — الحزمة R4

تعالج هذه الحزمة نتائج Flutter Analyzer التالية دون تعطيل التحذيرات أو تخفيف بوابة الجودة:

- إزالة الدالة غير المستخدمة `_destinationOpen` من محرك الطاولة.
- إزالة دالة `_team` غير المستخدمة من محرك Leekha، مع الإبقاء على الدالة الأخرى المستخدمة في محرك Jackaroo.
- ترحيل قائمة ترتيب أوراق اللاعب من `onReorder` المهجور إلى `onReorderItem`.
- إزالة تعديل `newIndex` اليدوي؛ لأن `onReorderItem` يعيد الفهرس بعد ضبطه تلقائيًا.
- إضافة حواجز رجوع إلى `tools/test_v184_flutter_quality_contract.py` لمنع عودة الأخطاء نفسها.

## التحقق داخل GitHub Actions

يستمر Workflow في تشغيل:

```bash
bash ../tools/flutter_analyze_ci.sh
```

ولا يتم تجاهل تحذيرات Dart أو Flutter الحقيقية. يجب أن ينتهي التحليل بلا `warning` أو استخدام مهجور في المواضع التي عالجتها هذه الحزمة.
