# تعليمات الوكلاء — Maatify/category

## 1. نطاق المشروع

هذا المستودع هو مكتبة PHP مستقلة قابلة لإعادة الاستخدام عبر Composer، ويمثل
`Category` و`Category Translation` كـ **Base Module** قابل للاستخراج.

القواعد العامة لا تُعاد كتابتها هنا. مصدرها التنفيذي هو النسخة المحلية المثبتة
من معايير Maatify في:

```text
docs/php-engineering-standards/
```

## 2. النسخة المعيارية المثبتة

تم نقل النسخة المحلية من:

```text
https://github.com/Maatify/php-engineering-standards.git
```

وهي مثبتة عند commit:

```text
cda994090b659435e72cfd1df35256eb3895bff3
```

تم التحقق من تطابق الملفات الـ22 المنقولة داخل هذه النسخة مع نفس commit
upstream، بما في ذلك `standards/` وملفات الحوكمة والتدقيق.

النسخ المعيارية المحلية المعتمدة:

- `standards/ai/AI_COLLABORATION_WORKFLOW_AR.md` — `3.0.0`
- `standards/GITHUB_PHASE_STACK_WORKFLOW_AR.md` — `1.0.0`

لا تعتمد على `main` المتحرك، ولا تعدّل أي ملف تحت
`docs/php-engineering-standards/` أثناء بناء المكتبة. ترقية هذه النسخة أو تعديل
المعيار نفسه تغيير مستقل يحتاج قرارًا ومراجعة منفصلين.

## 3. القراءة الإلزامية قبل العمل

قبل التخطيط أو التنفيذ أو المراجعة، اقرأ هذا الملف كاملًا ثم اقرأ:

1. [`AI_COLLABORATION_WORKFLOW_AR.md`](docs/php-engineering-standards/standards/ai/AI_COLLABORATION_WORKFLOW_AR.md)
2. [`GITHUB_PHASE_STACK_WORKFLOW_AR.md`](docs/php-engineering-standards/standards/GITHUB_PHASE_STACK_WORKFLOW_AR.md)
3. [`COMPOSER_PACKAGE_STANDARD.md`](docs/php-engineering-standards/standards/packages/COMPOSER_PACKAGE_STANDARD.md)
4. [`PACKAGE_BUILDING_STANDARD.md`](docs/php-engineering-standards/standards/packages/PACKAGE_BUILDING_STANDARD.md)
5. [`CI_WORKFLOW_STANDARD.md`](docs/php-engineering-standards/standards/packages/CI_WORKFLOW_STANDARD.md)
6. [`LIBRARY_PRESENTATION_STANDARD.md`](docs/php-engineering-standards/standards/packages/LIBRARY_PRESENTATION_STANDARD.md)
7. [`MODULE_BUILDING_STANDARD.md`](docs/php-engineering-standards/standards/modules/MODULE_BUILDING_STANDARD.md)

تنطبق Profile الـ Base Module لأن وثيقة المشروع تعرف `category` بهذه الصفة.
وتنطبق قواعد Persistence لأن المكتبة تملك schema وسلوك PDO/MySQL. لا تنطبق
Profiles الخاصة بـ Slim أو Project-Aware أو Admin على هذه المكتبة.

عند العمل داخل `docs/php-engineering-standards/` يجب أيضًا قراءة
[`docs/php-engineering-standards/AGENTS.md`](docs/php-engineering-standards/AGENTS.md)،
مع الالتزام بعدم تعديل النسخة المعيارية المثبتة في هذه المهمة.

## 4. قرارات ونطاق Category الخاص

المكتبة تملك فقط:

- Category وCategory Translation والعقود والـ DTOs والاستثناءات الخاصة بهما.
- orchestration الخاص بالمجال وطبقات PDO والجداول المملوكة للحزمة.
- schema MySQL المملوك للحزمة واختبارات التكامل مع الخدمة الحقيقية.

المضيف يملك Dependency Injection وHTTP وRoutes وPermissions وLanguage
validation/fallback وPresentation، وأي علاقات مع جداول Host. لا تُضاف داخل هذه
الحزمة Catalog أو Product أو Pricing أو Inventory أو Media أو Framework
bindings.

التفاصيل المستقرة الخاصة بالعقد موجودة في
[`CATEGORY_PACKAGE_REFERENCE.md`](CATEGORY_PACKAGE_REFERENCE.md)، والتفاصيل
المعمارية في [`docs/architecture/CATEGORY_ARCHITECTURE.md`](docs/architecture/CATEGORY_ARCHITECTURE.md)،
والـ schema في [`schema/README.md`](schema/README.md). لا تنشئ Package Reference
منافسًا داخل `docs/`.

## 5. قواعد التنفيذ والتسليم

اتبع قواعد الصلاحيات، النطاق المغلق، الـ Phase Stack، Review Staging، التحقق،
واللغة كما هي موثقة في الـ standards المشار إليها أعلاه؛ لا تكررها في هذا
الملف. أي استثناء خاص بـ `category` يجب توثيقه صراحةً هنا أو في ADR مستقل.

ابدأ كل مهمة بإعادة بناء حالة Git الفعلية، وحدد الملفات المملوكة وشروط القبول
قبل التعديل. لا تنفذ Commit أو Push أو Merge أو تفتح PR إلا بالصلاحية الصريحة
المحددة للمهمة. قرار Merge النهائي يظل لمالك المشروع.
