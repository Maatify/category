# تقرير التدقيق لمستودع php-engineering-standards

### 1. Baseline
* **الـ HEAD التي تمت مراجعتها كمرجع:** `7c2a1c03580c6a800df09b74b8a5e240544870c1`
* **قائمة الملفات الفعلية (في drafts/):** (ملاحظة: هذه قائمة تاريخية وقت بدء التدقيق وليست الحالة الحالية؛ نُقلت الملفات المعتمدة إلى مسارات `standards/`، واستُبعد `drafts/AGENTS.md` ثم حُذف بعد اعتماد root `AGENTS.md`.)
  1. `AGENTS.md`
  2. `AI_COLLABORATION_WORKFLOW_AR.md`
  3. `CI_WORKFLOW_STANDARD.md`
  4. `COMPOSER_PACKAGE_STANDARD.md`
  5. `LIBRARY_PRESENTATION_STANDARD.md`
  6. `MODULE_BUILDING_STANDARD.md`
  7. `MODULE_PROJECT_AWARE_STANDARD.md`
  8. `MODULE_SLIM_BUILDING_STANDARD.md`
  9. `PACKAGE_BUILDING_STANDARD.md`

---

### 2. خريطة الملفات

| الملف | الغرض | النطاق | الاعتمادات والعلاقات | التصنيف المقترح | الإجراء المقترح |
| --- | --- | --- | --- | --- | --- |
| `standards/ai/AI_COLLABORATION_WORKFLOW_AR.md` | تنظيم التعاون وإدارة المهام مع الذكاء الاصطناعي | عام لكل مشاريع Maatify | لا يعتمد على معيار آخر من الملفات المستوردة | AI Workflow | Adopted v1.4.0 |
| `drafts/AGENTS.md` | استثناءات وقواعد مخصصة لمشروع بعينه عند العمل مع الذكاء الاصطناعي | Project-specific، ويتضمن تجاوزات PHPUnit خاصة بالمشروع المصدر | يفعّل `AI_COLLABORATION_WORKFLOW_AR.md` ويضيف قواعد مشروع محلية | Project-specific | تم استبعاده من المعايير المركزية، وحُذف بعد اعتماد root `AGENTS.md` |
| `CI_WORKFLOW_STANDARD.md` | توحيد بنية GitHub Actions وبوابات الجودة | مكتبات Composer المستقلة | لا يعتمد على معيار آخر، وتعتمد عليه معايير الحزم الأخرى | Composer Package Profile | Adopted in `standards/packages/` |
| `COMPOSER_PACKAGE_STANDARD.md` | قواعد `composer.json` والاعتمادات والـ autoload والـ scripts وسياسة الـ lock file | مكتبات Composer المستقلة | يجب قراءته مع `PACKAGE_BUILDING_STANDARD.md` و`CI_WORKFLOW_STANDARD.md` و`LIBRARY_PRESENTATION_STANDARD.md` | Composer Package Profile | Adopted in `standards/packages/` |
| `LIBRARY_PRESENTATION_STANDARD.md` | معايير README وCHANGELOG والـ badges وهوية وعرض المستودع | مكتبات Composer المستقلة | يرتبط صراحةً بـ `PACKAGE_BUILDING_STANDARD.md` و`COMPOSER_PACKAGE_STANDARD.md` و`CI_WORKFLOW_STANDARD.md` | Composer Package Profile | Adopted in `standards/packages/` |
| `PACKAGE_BUILDING_STANDARD.md` | المعايير الهندسية لبناء مكتبات Composer من حيث الحدود والـ persistence والاستثناءات والخدمات والاختبارات | مكتبات Composer المستقلة | يعتمد على `COMPOSER_PACKAGE_STANDARD.md` و`CI_WORKFLOW_STANDARD.md` و`LIBRARY_PRESENTATION_STANDARD.md` | Composer Package Profile | Adopted in `standards/packages/` |
| `MODULE_BUILDING_STANDARD.md` | المعايير الهندسية لبناء موديولات مستقلة وقابلة للاستخراج داخل منظومة Maatify | Module Profile | معيار مستقل في صيغته الحالية | Module Profile | Adopted in `standards/modules/` |
| `MODULE_SLIM_BUILDING_STANDARD.md` | معيار متخصص لواجهة Admin مبنية على Slim وTwig وJavaScript حول موديول أساسي | Slim Admin Module Profile | يمتد من `MODULE_BUILDING_STANDARD.md` | Module Profile | Adopted in `standards/modules/` |
| `MODULE_PROJECT_AWARE_STANDARD.md` | Profile قابل لإعادة الاستخدام لوصف تنفيذات Project-Aware مرتبطة بالـ host وليست مكتبات قابلة للاستخراج | Host-tied Project-Aware modules | يمتد صراحةً من `MODULE_BUILDING_STANDARD.md` و`MODULE_SLIM_BUILDING_STANDARD.md` | Project-Aware Module Profile | Adopted in `standards/modules/` |

---

### 3. التكرارات والتعارضات

يوجد تداخل كبير بين `MODULE_BUILDING_STANDARD.md` و`PACKAGE_BUILDING_STANDARD.md`، لكنهما ليسا نسختين متطابقتين. تتكرر موضوعات مثل Schema وExceptions وCommands وDTOs وServices، بينما تختلف العقود الأساسية في نقاط مؤثرة.

**مصفوفة المقارنة:**

| الميزة / القاعدة | `MODULE_BUILDING_STANDARD.md` | `PACKAGE_BUILDING_STANDARD.md` |
| --- | --- | --- |
| **Namespaces والحدود** | يفرض `Maatify\{ModuleName}\` ويصف الموديول بأنه Standalone وHost-agnostic وقابل للاستخراج | يفرض هو الآخر استقلالية الـ package ويمنع host application namespaces مثل `App\` |
| **Admin/Customer Structure** | يفرض فصلًا واضحًا بين `Admin` و`Customer` داخل بنية الموديول | ينص على أن `Admin/Customer` غير إلزامي، وأن Domain boundaries يجب أن تعكس الفصل المنطقي الحقيقي |
| **Exception Hierarchy** | يعرّف named exceptions محلية تمتد من `RuntimeException` وتنفذ marker interface محلية | يُلزم package-defined exceptions باستخدام hierarchy مناسبة من `maatify/exceptions` |
| **Bootstrap / DI** | يفرض module-owned bindings بنمط PSR-11، مع اقتراح PHP-DI دون إلزامه | يمنع framework-specific bindings داخل الحزمة ويترك الـ wiring للـ host |
| **Schema Boundaries** | يمنع FKs وJOINs على host tables | يمنع كذلك FKs وJOINs على host tables، مع قواعد إضافية تخص domain-owned package persistence |
| **Testing / CI** | يضع قواعد اختبار وتحليل أساسية داخل معيار الموديول | يربط الحزمة بمعيار CI مستقل وبمعايير Composer والعرض العام للمكتبة |

**تداخل Slim وProject-Aware:**

- `MODULE_PROJECT_AWARE_STANDARD.md` يمتد من `MODULE_SLIM_BUILDING_STANDARD.md`، لذلك بعض التكرار قد يكون شرحًا مقصودًا للـ specialization وليس تعارضًا بذاته.
- توجد إعادة شرح لنمط `Detail Page Pattern` و`JavaScript — Pattern B (3 Files)`.
- يملك Slim التعريف العام الكامل للنمط.
- يحيل Project-Aware إلى Slim ويضيف فقط الاستثناءات أو الفروق المرتبطة بالـ host.
- لا تُكرر القاعدة كاملة بين الـ Profiles.

---

### 4. القواعد والأمثلة الخاصة بالمشاريع مقابل السياسة الموحدة

**أمثلة معتمدة لتوثيق السياسة الموحدة:**
التفاصيل التالية ضمن Module Profiles معتمدة مقصودة لتوضيح السياق، ولا تُعد تسربًا يجب تعميمه أو حذفه:
1. **`standards/modules/MODULE_SLIM_BUILDING_STANDARD.md`:**
   * استخدام قاعدة بيانات `athar` واسم التطبيق `Athar` داخل أمثلة البيانات.
   * ذكر موديولات تطبيقية مثل `SettingsSlim` و`CurrencySlim`.
2. **`standards/modules/MODULE_PROJECT_AWARE_STANDARD.md`:**
   * استخدام `OrderAdminSlim` وجداول فعلية من مشروع AtharSphere لتوضيح الـ cross-module joins.

---

### 5. الهيكل الحالي (Profiles مستقلة)

**المسارات الفعلية المعتمدة:**
```text
standards/
├── ai/
│   └── AI_COLLABORATION_WORKFLOW_AR.md
├── packages/
│   ├── PACKAGE_BUILDING_STANDARD.md
│   ├── COMPOSER_PACKAGE_STANDARD.md
│   ├── CI_WORKFLOW_STANDARD.md
│   └── LIBRARY_PRESENTATION_STANDARD.md
└── modules/
    ├── MODULE_BUILDING_STANDARD.md
    ├── MODULE_SLIM_BUILDING_STANDARD.md
    └── MODULE_PROJECT_AWARE_STANDARD.md
```

مع بقاء مسار النسخة المحلية داخل كل مشروع:
```text
docs/standards/AI_COLLABORATION_WORKFLOW_AR.md
```

تم اعتماد الإبقاء على Profiles مستقلة مع Cross-references بدلًا من استخراج Shared Core.

---

### 6. ترتيب المراجعة

الترتيب المقترح مبني على الاعتماد ونطاق الملكية:

1. ~~`AI_COLLABORATION_WORKFLOW_AR.md`~~ (مكتمل ومُعتمد في `standards/ai/`)
2. مجموعة Composer Package Profile كوحدة مترابطة:
   - ~~`PACKAGE_BUILDING_STANDARD.md`~~ (مكتمل ومُعتمد في `standards/packages/`)
   - ~~`COMPOSER_PACKAGE_STANDARD.md`~~ (مكتمل ومُعتمد في `standards/packages/`)
   - ~~`CI_WORKFLOW_STANDARD.md`~~ (مكتمل ومُعتمد في `standards/packages/`)
   - ~~`LIBRARY_PRESENTATION_STANDARD.md`~~ (مكتمل ومُعتمد في `standards/packages/`)
3. ~~`MODULE_BUILDING_STANDARD.md`~~ (مكتمل ومُعتمد في `standards/modules/`)
4. ~~`MODULE_SLIM_BUILDING_STANDARD.md`~~ (مكتمل ومُعتمد في `standards/modules/`)
5. ~~`MODULE_PROJECT_AWARE_STANDARD.md`~~ (مكتمل ومُعتمد في `standards/modules/`)
6. ~~إنشاء `AGENTS.md` جذري خاص بهذا المستودع بعد تثبيت طريقة اعتماد المعايير، وعدم اعتماد النسخة المستوردة الخاصة بالمشروع المصدر.~~ (مكتمل)
7. ~~تنظيف ما تبقى داخل `drafts/`.~~ (مكتمل)

---

### 7. قرارات المالك (Resolved)

1. **نموذج تنظيم الـ Profiles:**
   - **القرار المعتمد:** Profiles مستقلة مع Cross-references تمنع تكرار القواعد المملوكة لملف آخر.
   - **السبب:** تقليل خطر تكرار القواعد وتعارضها، وتجنب الحاجة المبكرة لاستخراج Shared Core.

2. **نطاق Slim Admin:**
   - **القرار المعتمد:** يبقى داخل مستودع PHP باعتباره معيار تكامل ضمن Profiles المشروع.
   - **السبب:** يمثل طبقة متصلة بمفاهيم الموديول.

3. **سياسة الأمثلة:**
   - **القرار المعتمد:** أمثلة AdminKernel والمشاريع الفعلية داخل Slim وProject-Aware مقصودة لتوثيق السياسة الموحدة، ولا تُعد تسربًا ولا يلزم تعميمها أو نقلها أو حذفها، حيث توفر السياق الضروري.

---

### 8. النتيجة النهائية

اكتمل مسار التدقيق والاعتماد والتنظيف، ولا توجد خطوة متبقية ضمن نطاق هذا التدقيق.
