# تقرير التدقيق لمحتوى Module Building Profile

### 1. Baseline & Scope
* **الـ HEAD المرجعية (Starting SHA):** `b7bde270ed5928b0deb56e832d28270c36392922`
* **النطاق:** `drafts/MODULE_BUILDING_STANDARD.md`
* **الهدف:** Content Audit لتقييم مدى جاهزية الملف ليصبح Base Module Profile وتحديد التداخلات وتسرب القواعد من بيئات Host أو مستودعات مكررة.

---

### 2. الأدلة المستخدمة
* `drafts/MODULE_BUILDING_STANDARD.md`
* `drafts/MODULE_SLIM_BUILDING_STANDARD.md`
* `drafts/MODULE_PROJECT_AWARE_STANDARD.md`
* المعايير المعتمدة في `standards/packages/` (PACKAGE, COMPOSER, CI, LIBRARY_PRESENTATION)
* `docs/decisions/INITIAL_STANDARDS_GOVERNANCE_DECISIONS_AR.md`
* `docs/audits/INITIAL_PHP_ENGINEERING_STANDARDS_AUDIT_AR.md`

---

### 3. تعريف الـ Module والـ Applicability (Current Profile Definition)
**النتيجة: NOT READY**

يصف الملف الـ Module بأنه Standalone و Extractable و Host-agnostic و PDO-based بشكل غير مشروط، لكنه يتجاهل حقيقة أن هناك Modules قد تكون منطقية ولا تمتلك قواعد بيانات. هذا يخالف `PACKAGE_BUILDING_STANDARD` الذي يُلزم بقواعد الـ Persistence بشكل مشروط فقط إذا امتلكت الحزمة قاعدة بيانات.

---

### 4. Ownership Matrix (خريطة الملكية الشاملة)
**النتيجة: NOT READY**

| القسم / القاعدة | التصنيف الصحيح | التعليق (التصحيح المقترح) |
| --- | --- | --- |
| **Module-specific architecture و Boundaries و Inheritance Contract** | `KEEP IN MODULE` | `MODULE_BUILDING_STANDARD` يملكها بصفته Base Module Profile. |
| **القواعد العامة للحزمة القابلة للاستخراج** | `CONDITIONAL` | يملكها `PACKAGE_BUILDING_STANDARD` فقط إذا قرر المالك أن الـ Base Module هو Composer Package. |
| **PHPStan configuration / static-analysis rules و package test architecture** | `CROSS-REFERENCE` | يملكها `PACKAGE_BUILDING_STANDARD`. |
| **Workflow / Check execution** | `CROSS-REFERENCE` | يملكها `CI_WORKFLOW_STANDARD`. |
| **`composer.json` والتبعيات والـ constraints** | `CROSS-REFERENCE` | يملكها `COMPOSER_PACKAGE_STANDARD`. |
| **README structure والـ badges والملفات release-facing** | `CROSS-REFERENCE` | يملكها `LIBRARY_PRESENTATION_STANDARD`. |
| **HTTP Controllers / Routes / Twig / JavaScript / Permissions / Admin UI integration** | `MOVE TO SLIM` | يملكها Slim، وموجودة خطأً في Base. |
| **استثناءات الـ Host (cross-module joins، عدم قابلية الاستخراج)** | `PROJECT-AWARE OVERRIDE` | يملكها Project-Aware حصراً. |

---

### 5. Gaps Analysis (التعارضات والتكرار وتسرب المفاهيم)
**Internal Consistency: NOT READY**
**Host/Slim Boundary: NOT READY**

1. **Date Validation Contradiction:**
   - **الملف والقسم:** `drafts/MODULE_BUILDING_STANDARD.md`، Section 7 و Section 21.5.
   - **المعنى الحالي:** في Section 7 يذكر `new \DateTimeImmutable($value)`، بينما في Section 21.5 يرفضها ويُلزم بـ `createFromFormat` بسبب الـ Silent Normalization.
   - **سبب المشكلة:** تعارض داخلي.
   - **المالك الصحيح:** Base Module/Package Rules.
   - **التصنيف:** `EXTERNAL VERIFICATION`.
   - **التصحيح الأصغر اللاحق:** إزالة التعارض، وانتظار فحص خارجي للفرق بين general date-time parsing و strict date-only validation، مع تحديد الـ format المطلوب بدقة.

2. **Exception Hierarchy and Named Constructors:**
   - **الملف والقسم:** `drafts/MODULE_BUILDING_STANDARD.md`، Section 6.
   - **المعنى الحالي:** يفرض local marker interface، ويفرض Named Constructors لكل الاستثناءات، ويفرض قائمة (NotFound/InvalidArgument...) على كل Module.
   - **سبب المشكلة:** يخالف `PACKAGE_BUILDING_STANDARD` الذي ينص على استخدام hierarchy من `maatify/exceptions` ويجعل Named Constructors `SHOULD/MAY`، ويمنع فرض قائمة لا تناسب الـ domain.
   - **المالك الصحيح:** `PACKAGE_BUILDING_STANDARD` للـ base exception contracts، والـ Module للـ domain classes الخاصة به.
   - **التصنيف:** `CORRECTION`.
   - **التصحيح الأصغر اللاحق:** يبقى الـ marker interface محلياً ويمتد `Throwable`، لكن تتوافق الـ hierarchy مع `maatify/exceptions`، وتُزال القائمة الثابتة المفروضة وتُحوّل الـ Constructors إلى `SHOULD/MAY` عند وجود semantic constructor والبناء المباشر عند السماح به.

3. **SQLSTATE Error Classification (Duplicate Code vs Check Violations):**
   - **الملف والقسم:** `drafts/MODULE_BUILDING_STANDARD.md`، Section 6 (المثال `23xxx`).
   - **المعنى الحالي:** يفترض أن أي خطأ `23xxx` هو Duplicate Key، ويُكرر هذا الافتراض من المعيار المعتمد.
   - **سبب المشكلة:** `23xxx` قد يكون FK أو Check Violation، وليس Unique Key فقط.
   - **المالك الصحيح:** Database Architecture (`PACKAGE_BUILDING_STANDARD` أولاً ثم Base Module).
   - **التصنيف:** `EXTERNAL VERIFICATION` ثم منسق `CORRECTION`.
   - **التصحيح الأصغر اللاحق:** وضع التحقق الدقيق لخطأ الـ constraint في الـ External Verification Queue للتحقق التقني من الـ Database Driver، ولاحقاً تصحيح الخطأ في المالك المعتمد أولاً قبل وضع Cross-reference في Module.

4. **Base vs Slim Leakage (Controller/Permissions and Presentation):**
   - **الملف والقسم:** `drafts/MODULE_BUILDING_STANDARD.md`، Section 12 و Section 20.
   - **المعنى الحالي:** Section 12 يقول Business orchestration داخل Services. Section 20 يصف Controller بأنه مسؤول عن "Business logic, permission aggregation" ويشمل أمثلة Twig/JS/Controller.
   - **سبب المشكلة:** تسرب للـ Controller والـ Permissions والـ Presentation إلى Base Profile، وتعارض داخلي حول مكان Business Logic.
   - **المالك الصحيح:** Base يملك framework-neutral presentation separation، و Slim يملك Controllers/Permissions/Twig/JS.
   - **التصنيف:** `CORRECTION` و `MOVE TO SLIM`.
   - **التصحيح الأصغر اللاحق:** إبقاء قاعدة الفصل (framework-neutral) في Base Module، ونقل كل ذكر للـ Controller والـ Twig والـ JS وصلاحيات الـ Host إلى الـ Slim Profile.

5. **Admin/Customer Classification:**
   - **الملف والقسم:** `drafts/MODULE_BUILDING_STANDARD.md`، Section 4 و Section 13.
   - **المعنى الحالي:** يفرض فصل `Admin` / `Customer` على أنه معماري إلزامي.
   - **سبب المشكلة:** قد يكون Actor-specific namespace هو Boundary شرعي، لكنه اختياري (Conditional) وليس إجبارياً على جميع الموديولات، والـ Admin HTTP UI يملكه Slim.
   - **المالك الصحيح:** Module/Package Architecture (Conditional).
   - **التصنيف:** `CORRECTION`.
   - **التصحيح الأصغر اللاحق:** جعل هيكل Actor-specific اختيارياً (Conditional/Domain-driven) وليس Mandatory baseline.

6. **Ordering, Pagination, and Compaction Mechanics:**
   - **الملف والقسم:** `drafts/MODULE_BUILDING_STANDARD.md`، Sections 4 و 5 و 10 و 14.
   - **المعنى الحالي:** يصف آليات Pagination و Ordering محلية، ويشير لـ `compactScopeAfterRemoval()` عند الـ Hard-delete.
   - **سبب المشكلة:** يعيد تنفيذ Shared Stable mechanics التي يملكها `maatify/persistence`. الـ Compaction API المذكورة غير مستقرة (ADR مؤجل) ولا يجوز للمستهلك ادعاء الاعتماد عليها.
   - **المالك الصحيح:** `PACKAGE_BUILDING_STANDARD` (عبر `maatify/persistence`).
   - **التصنيف:** `CORRECTION`.
   - **التصحيح الأصغر اللاحق:** الاعتماد على `maatify/persistence` عبر `PACKAGE_BUILDING_STANDARD`. التفريق بين Reusable mechanics (تُزال) و Entity Deletion/Orchestration (تظل مملوكة للـ المستهلك). الـ Compaction تُزال بالكامل كـ Correction لعدم وجود Stable API لها، ما لم تصدر ADR صريحة تفيد بأنها Domain-specific.

7. **Translation and Analytics Patterns:**
   - **الملف والقسم:** `drafts/MODULE_BUILDING_STANDARD.md`، Section 11 و Section 21.
   - **المعنى الحالي:** يفرض Analytics (Pre-Aggregated) و Translations بقواعد مفصلة.
   - **المالك الصحيح:** Module domain logic.
   - **التصنيف:** `CORRECTION`.
   - **التصحيح الأصغر اللاحق:** تصنيفها كـ Conditional Optional rules تُطبّق فقط حين يحتاج الموديول إليها، مع نقل التفاصيل التطبيقية المطولة إلى Guides مخصصة.

8. **Financial Type Contradiction:**
   - **الملف والقسم:** `drafts/MODULE_BUILDING_STANDARD.md`، Section 17 مقابل Section 20.
   - **المعنى الحالي:** Section 17 يفرض أن تكون القيم المالية `string` (DECIMAL precision) ويمنع استخدام `float`. في حين أن Section 20 يسمح ضمن أمثلة الـ Hydration بتحويل amount إلى `(float) $amount`.
   - **سبب المشكلة:** تعارض Normative صريح قد يؤدي لفقدان دقة DECIMAL في حالة التحويل.
   - **المالك الصحيح:** Base Module/Package architecture مع Cross-reference للقاعدة المعتمدة عند انطباق Composer Package.
   - **التصنيف:** `CORRECTION`.
   - **التصحيح الأصغر اللاحق:** منع تحويل monetary/fixed-precision values إلى float صراحة، مع الإبقاء على casting للأنواع غير المالية فقط.

9. **Bootstrap / PHP-DI Leakage:**
   - **الملف والقسم:** `drafts/MODULE_BUILDING_STANDARD.md`، Section 16 ومواضع PHPStan/DI annotations في Section 19.
   - **المعنى الحالي:** يعرض `ContainerBuilder` و `ContainerInterface` و PHP-DI bindings كهيكل أساسي داخل الموديول.
   - **سبب المشكلة:** المعيار المعتمد `PACKAGE_BUILDING_STANDARD` يمنع Slim/Laravel/Symfony/PHP-DI bindings كمتطلب داخل الحزمة ويترك wiring للـ Host.
   - **المالك الصحيح:** Base Module يملك framework-neutral construction/contracts فقط؛ بينما Host/Slim يملك container wiring.
   - **التصنيف:** `CORRECTION` و `MOVE TO HOST/SLIM`.
   - **التصحيح الأصغر اللاحق:** إزالة الإلزام والـ framework-specific example من Base Module، والسماح بـ factories/providers محايدة فقط إذا لزم الأمر.

10. **Composer/CI/Presentation Repetition:**
    - **الملف والقسم:** `drafts/MODULE_BUILDING_STANDARD.md`، Section 2 (required files/composer.json/README/CHANGELOG)، Section 19 (PHPStan configuration)، و Section 22 (Completion checklist).
    - **المعنى الحالي:** يعيد كتابة وإدراج تفاصيل PHPStan و `composer.json` و `README` ومتطلبات العرض والاختبار.
    - **سبب المشكلة:** التكرار يخلق خطر وجود مصدرَي حقيقة متعارضين لنفس القاعدة.
    - **المالك الصحيح:**
      - Static-analysis/Test Architecture: `PACKAGE_BUILDING_STANDARD`.
      - Workflow/check execution: `CI_WORKFLOW_STANDARD`.
      - Composer metadata/constraints: `COMPOSER_PACKAGE_STANDARD`.
      - README/badges/release files: `LIBRARY_PRESENTATION_STANDARD`.
    - **التصنيف:** `CORRECTION` و `CROSS-REFERENCE`.
    - **التصحيح الأصغر اللاحق:** استبدال الأقسام المتكررة بإحالات صريحة إلى معايير `standards/packages/` لكل تخصص وتجنب إدراج التفاصيل المكررة.

11. **Project-Aware Inheritance Impact:**
    - **الملف والقسم:** `drafts/MODULE_PROJECT_AWARE_STANDARD.md`، Section 1/Table والإشارات المشابهة في Downstream.
    - **المعنى الحالي:** يصرح الملف التابع بأنه يمتد من Base و Slim ويسمح بـ cross-module JOINs وأنه غير قابل للاستخراج.
    - **سبب المشكلة:** غياب **formal override/precedence contract** يحصر القواعد المستثناة (مثل تجاوز حدود الاستقلالية وارتباطه بالـ Host وتجاوز قيد Slim لحصر الـ wrapper في موديول واحد) ويمنع تفسير الوراثة بشكل متناقض.
    - **المالك الصحيح:** Project-Aware Profile نفسه يجب أن يملك هذه الاستثناءات صراحةً.
    - **التصنيف:** `CORRECTION` لاحق في الملف التابع.
    - **التصحيح الأصغر اللاحق:** إضافة `explicit override section` داخل Project-Aware Profile يعدد الاستثناءات المثبتة فقط بالاسم، مع النص صراحةً على أن باقي قواعد Base المنطبقة — ومنها قيود الـ PDO والـ Persistence عند امتلاك Database behavior — تظل سارية ما لم يوجد قرار مستقل موثق.

12. **Neutrality Section:**
    - **الملف والقسم:** Base Module Profile / Downstream Impact.
    - **المعنى الحالي:** Base Profile الحالي بشكله المحايد (`maa_something`) لا يحتوي على تسرب مشروع فعلي. `SettingsSlim` المذكور يخص الملف التابع وليس Base.
    - **التصحيح الأصغر اللاحق:** لا توجد Gap neutrality مثبتة في مراجعة الـ Base هذه، وتظل الأمثلة الحقيقية محصورة في الملفات التابعة ضمن Downstream Impact.

---

### 6. Owner Decisions Required
**القرار المرجعي الأساسي المتبقي للـ Owner هو:**
*   هل `Base Module Profile` مخصص فقط لـ Reusable/Extractable Composer Package أم أنه Profile أوسع يشمل الموديولات غير القابلة للاستخراج؟
*   *الأثر:* بناءً على ذلك سيتم تحديد ما إذا كان التزام الـ Extractability و Composer Package contract شرطاً دائماً للـ Base Module، أم أن Cross-references للحزم تُطبّق فقط عند اختيار جعل الموديول Reusable Package. (في كلتا الحالتين، Host joins وعدم القابلية للاستخراج يظلان داخل Project-Aware Profile حصراً).

---

### 7. External Verification Queue
**النتيجة: BLOCKED**
العناصر التي تحتاج فحص خارجي لاحق:
1. **PDO Named Placeholders:** هل PDO يمنع بشكل موثوق استخدام نفس الـ Parameter مرتين أم هي قيود قديمة؟
2. **DateTime parsing vs Date-only formats:** سلوك `DateTimeImmutable` المعني بالـ silent normalization بين الـ Strict format مقابل General parsing.
3. **SQLSTATE Error Classification:** الفروق التقنية (Database Driver level) لتصنيف Duplicate مقابل FK vs Check violations داخل Exceptions.
4. **Validation Claims:** ادعاءات السلوك المحددة لـ `filter_var(FILTER_VALIDATE_INT)` مقابل `is_numeric` للـ Primary Keys إذا أُريد الإبقاء عليها كـ Normative.
5. ادعاءات **PHPStan behavior** الدقيقة.

---

### 8. Execution Order (ترتيب التنفيذ المقترح)

1. حسم Owner Decision الوحيدة الخاصة بنطاق Base Module (قابلية الاستخراج وإلزامية الحزمة).
2. تنفيذ External Verification Queue للفصل في الادعاءات التقنية.
3. تصحيح أي Source of Truth معتمد أولاً عند وجود Gap مشتركة (خصوصاً SQLSTATE وأي claim خارجي مثبت).
4. تصحيح `MODULE_BUILDING_STANDARD.md` وإزالة التكرار والتسرب والتعارضات وتطبيق التصحيحات (Corrections و Cross-References و Move To Slim).
5. تدقيق/تصحيح Slim Profile بعد استقرار Base boundaries.
6. تدقيق/تصحيح Project-Aware Profile وإضافة formal override contract للاستثناءات.
7. Final Readiness Review منفصلة قبل Adoption.

---

### 9. النتيجة النهائية

*   **Applicability:** NOT READY
*   **Ownership:** NOT READY
*   **Internal Consistency:** NOT READY
*   **Host/Slim Boundary:** NOT READY
*   **External Verification:** BLOCKED
*   **Downstream Inheritance:** NOT READY
*   **Overall:** NOT READY FOR ADOPTION

---
