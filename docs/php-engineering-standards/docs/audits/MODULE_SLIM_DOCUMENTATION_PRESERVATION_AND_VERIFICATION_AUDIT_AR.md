# تقرير التدقيق وحفظ توثيق Module Slim Profile

## 1. Baseline, Scope & Evidence Limitations

* **Baseline SHA:** `2b585cbe64e3ce3411255521f684b9b702301983`
* **Scope:** `drafts/MODULE_SLIM_BUILDING_STANDARD.md`
* **Evidence Limitations:** هذه Repository مخصصة للتوثيق والمعايير فقط، ولا تحتوي على Runtime Implementation كامل أو مشاريع استهلاك كافية (مثل `AdminKernel`، وRoutes، وPermissions، وTwig، وJavaScript، وControllers) للتحقق التجريبي من كل تفاصيل تطبيق Slim Profile. لذلك:
  * لا يعتبر غياب Runtime code فجوة في المستند.
  * لا يُحكم أن التفاصيل Host-specific خاطئة لمجرد عدم وجود مصدر تنفيذ داخل الريبو.
  * سيتم حفظ المعرفة كما هي حتى يتوفر التطبيق الفعلي للمطابقة. وجود تعارضات موثقة لا يجيز حذف أو تعديل Slim Profile قبل التحقق من مصادر التنفيذ.

## 2. Preservation Contract

* **المصدر الحالي للمعرفة:** `MODULE_SLIM_BUILDING_STANDARD.md` يمثل مصدر المعرفة الحالي لمعمارية Slim Profile وتطبيقاته.
* **منع الحذف أو الاختصار:** لا يجوز حذف أو اختصار أي تفاصيل تقنية أو أمثلة لمجرد عدم وجود تطبيق برمجي داخل هذه Repository الحالية للتحقق منها، قبل إجراء Source Implementation Verification.
* **التصحيح المستقبلي:** أي Correction لاحق يجب أن يكون evidence-backed (مدعوماً بأدلة من بيئة التنفيذ الفعلية) وأن يحافظ على المحتوى المتبقي الذي لم يتأثر.

## 3. Section-by-Section Inventory & Categorization

### 3.1. What is a Slim Module? (تعريف Slim وعلاقته بـ Base وProject-Aware)
* **What it claims:** Slim Profile هو واجهة Admin واختيارية تغلف Core Module، ولا يكرر Business logic. يوفر API endpoints و UI pages، ويتكامل مع AdminKernel متبعاً "exact patterns".
* **Classification:**
  * قاعدة أن Slim اختياري ويلف Base Module واحدة ولا يكرر Business Logic: `PROFILE_RULE_CONFIRMED`.
  * ادعاءات التكامل الفعلي مع `AdminKernel` واتباع "exact patterns": `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION`.
* **Provable here:** أن الـ Slim اختياري ويلف Base Module (تم إثباته من القرارات المعمارية).
* **Unprovable:** التكامل الفعلي مع `AdminKernel` ومدى اتباع الأنماط المحددة.
* **Required Source:** AdminKernel reference.
* **Preservation Status:** يُحفظ دون تغيير حالياً.

### 3.2. Directory Structure
* **What it claims:** هيكلية مجلدات محددة تتضمن `Admin/Security` و `Http/Controllers` (لـ Api و Ui) و `Domain/List` و `Validation` و `Bootstrap` و `permissions_seed.sql`.
* **Classification:** `PRESERVE_CURRENT_KNOWLEDGE`
* **Provable here:** لا يوجد.
* **Unprovable:** الهيكلية وتوافقها مع بيئة التشغيل الفعلية ومسارات التطبيق.
* **Required Source:** Real Slim module implementations.
* **Preservation Status:** يُحفظ دون تغيير حالياً.

### 3.3. Permissions, Roles & Seed SQL
* **What it claims:** كيفية تعريف Permissions عبر `SettingAdminPermissionMapProvider` و `SettingAdminPermissionPackage`، وعمل Seed للـ Permissions في قاعدة البيانات.
* **Classification:** `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION`
* **Provable here:** لا يوجد كود هنا، NOT VERIFIED.
* **Unprovable:** عقود `PermissionRequirementDefinition`، وواجهات `ProvidesPermissionMapsInterface`، واستدعاؤها لـ AdminKernel، وبنية الجداول الفعلية (Schema).
* **Required Source:** AdminKernel source, permission migrations/schema.
* **Preservation Status:** يُحفظ دون تغيير حالياً.

### 3.4. List Capabilities & Filters
* **What it claims:**
  * `ListCapabilities` تعرّف `searchableColumns` و`filterableColumns`.
  * `ListFilterResolver` يعالج/يتحقق من أسماء Column filters وسلوكه مع unknown filters (يرفض أو يزيل).
  * `ValidationGuard` يتحقق من Request schemas.
  * UI capability booleans تستخدم داخل UI Controller/Twig.
* **Classification:** `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION` و `INTERNAL_CONTRADICTION` (سلوك `unknown filters` غير محسوم بين الرفض والإزالة).
* **Provable here:** لا يوجد كود هنا، NOT VERIFIED.
* **Unprovable:** تفاصيل الـ contracts وطريقة استخدام `ValidationGuard` وعمل `ListFilterResolver`.
* **Required Source:** AdminKernel list/validation contracts, Real Slim module implementations.
* **Preservation Status:** يُحفظ دون تغيير حالياً (يجب عدم الحسم بين الرفض والإزالة الآن).

### 3.5. Controllers & Endpoint Contracts
* **What it claims:** بنية Controllers محددة (API مقابل UI) وتوقع تمرير Request/Response واستخدام أنماط ردود متنوعة مثل `ApiHandler.json(...)` ومثال Analytics الذي يستخدم `responseFactory->data(...)`.
* **Classification:** `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION`
* **Provable here:** لا يوجد تطبيق فعلي لـ Controllers هنا، NOT VERIFIED.
* **Unprovable:** الأشكال الفعلية للـ endpoints و contracts والتطابق الفعلي للردود مع AdminKernel.
* **Required Source:** Host application bootstrap/routes, Real Slim module implementations, AdminKernel exception/response contracts.
* **Preservation Status:** يُحفظ دون تغيير حالياً.

### 3.6. UI Controllers & Capabilities
* **What it claims:** الـ UI Controller يستخدم `$this->twig->render(...)` ويمرر البيانات الخاصة بالـ capabilities إلى القالب.
* **Classification:** `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION`
* **Provable here:** لا يوجد، NOT VERIFIED.
* **Unprovable:** طريقة تمرير البيانات وتوافقها مع محرك القوالب.
* **Required Source:** Real Slim module implementations, Templates.
* **Preservation Status:** يُحفظ دون تغيير حالياً.

### 3.7. Routes & Middleware
* **What it claims:** نمط تسجيل `ApiRoutes` و `UiRoutes` وترتيب واستخدام الـ Middleware (تحديداً `AuthorizationGuardMiddleware`).
* **Classification:** `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION` و `REQUIRES_EXTERNAL_VERIFICATION` (لمعرفة سلوك إطار العمل).
* **Provable here:** لا يوجد، NOT VERIFIED.
* **Unprovable:** ترتيب الـ Middleware وطريقة التسجيل النهائية.
* **Required Source:** Host application bootstrap/routes, Official Slim Framework middleware documentation.
* **Preservation Status:** يُحفظ دون تغيير حالياً.

### 3.8. Twig & Templates
* **What it claims:** استخدام مسار `layouts/base.twig` ومسارات تحت `app/Modules/AdminKernel/Templates/...`.
* **Classification:** `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION`
* **Provable here:** لا توجد قوالب هنا، NOT VERIFIED.
* **Unprovable:** وجود مسارات Twig الفعلية.
* **Required Source:** Templates and JavaScript assets.
* **Preservation Status:** يُحفظ دون تغيير حالياً.

### 3.9. JavaScript والأمان (XSS/URL handling)
* **What it claims:** الاعتماد على أنماط محددة: `window.settingsCapabilities`، `window.settingsApi`، `ApiHandler.call()`، `createTable()`، `escapeHtml()`، `encodeURIComponent()`، `safeExternalLink()`، واستخدام `JSON_HEX_*` لـ XSS prevention.
* **Classification:** `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION` و `REQUIRES_EXTERNAL_VERIFICATION` (لادعاءات أمان `json_encode|raw`).
* **Provable here:** لا يوجد، NOT VERIFIED.
* **Unprovable:** دوال الـ JavaScript الفعلية والأدوات المستعملة لحماية الثغرات.
* **Required Source:** Templates and JavaScript assets, Official PHP/Twig documentation.
* **Preservation Status:** يُحفظ دون تغيير حالياً.

### 3.10. Main App Registration & DI
* **What it claims:** تسجيل Permission packages في `public/index.php`، وتسجيل API/UI routes، وService injection/bindings (استخدام `$builderHook`).
* **Classification:** `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION`
* **Provable here:** لا يوجد، NOT VERIFIED.
* **Unprovable:** عملية التسجيل في التطبيق المستضيف.
* **Required Source:** Host application bootstrap/routes, DI logic.
* **Preservation Status:** يُحفظ دون تغيير حالياً.

### 3.11. Exception Adaptation
* **What it claims:** تحويل استثناءات Module (Core) إلى استثناءات AdminKernel داخل Controllers، مثل تحويل `*InvalidArgumentException` إلى `InvalidIdentifierFormatException` و `*FeeConflictException` إلى `EntityAlreadyExistsException`.
* **Classification:** `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION` و `CONFLICT_WITH_BASE_PROFILE` (بخصوص `RuntimeException`).
* **Provable here:** لا يوجد هنا، NOT VERIFIED.
* **Unprovable:** صحة هذا التحويل العام واعتمادية استثناءات AdminKernel.
* **Required Source:** error middleware and exception contracts, AdminKernel reference, Base module exceptions.
* **Preservation Status:** يُحفظ دون تغيير حالياً حتى التحقق من مصدر AdminKernel.

### 3.12. Analytics Pattern & Cron Scripts
* **What it claims:** نمط مخصص لصفحات التحليلات (GET API, Twig Charts, Cron scripts) وقواعدها. يشير إلى `MODULE_BUILDING_STANDARD.md §21`.
* **Classification:** `PRESERVE_CURRENT_KNOWLEDGE` و `CONFLICT_WITH_BASE_PROFILE` (مرجع §21 أصبح §12).
* **Provable here:** لا يوجد، NOT VERIFIED.
* **Unprovable:** وجود أو شكل الإحصائيات أو سكربت الـ Cron.
* **Required Source:** Real Slim module implementations.
* **Preservation Status:** يُحفظ دون تغيير حالياً.

### 3.13. Checklists, Operational Instructions & Real Examples
* **What it claims:** يوفر Checklist للإنشاء، وتعليمات تشغيلية (SQL seeds، Cron)، وأمثلة حقيقية مثل `SettingsSlim` و `CurrencySlim`.
* **Classification:**
  * الأمثلة الحقيقية تصنف كـ `NON_NORMATIVE_REAL_EXAMPLE`.
  * الـ Checklist والتعليمات التشغيلية تُصنف كـ `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION`.
* **Provable here:** لا يوجد، NOT VERIFIED.
* **Unprovable:** توافق الأمثلة والتعليمات التشغيلية مع التنفيذ.
* **Required Source:** Real Slim module implementations.
* **Preservation Status:** يُحفظ دون تغيير حالياً.

---

## 4. Separation of Concerns (فصل المحتوى)

* **Profile-level Normative Rules:** القواعد المعيارية لفصل Slim عن Base، استخدام Core module بدلاً من تكراره، ونمط تحويل الـ Exceptions.
* **Host Integration Contract:** آليات تسجيل الـ Routes في التطبيق المستضيف، تسجيل permissions، وService injection.
* **Real Implementation Examples:** أمثلة `SettingsSlim`، `CurrencySlim`، و `AppReleaseSlim` و `Analytics`. (`NON_NORMATIVE_REAL_EXAMPLE`).
* **Operational Instructions:** متطلبات الجداول (SQL seeds)، قواعد تسلسل Migrations، و Checklists التشغيل. (`REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION`).
* **Security-sensitive Claims:** ادعاءات الاعتماد على `JSON_HEX_*`، `encodeURIComponent()`، و `escapeHtml()`.
* **External Framework/Library Claims:** ترتيب تنفيذ Middleware، وادعاءات `json_encode|raw` داخل `script`. (`REQUIRES_EXTERNAL_VERIFICATION`).

---

## 5. Internal Contradictions (التعارضات الداخلية المثبتة)

### 5.1 INTERNAL_CONTRADICTION
1. **سلوك Unknown Filters:**
   المستند يقول أنّ ListFilterResolver `REJECTS or REMOVES` الفلاتر المجهولة، ثم يبني Flow يفترض الإزالة. هذا يحتاج ListFilterResolver source لتحديد السلوك.

### 5.2 CONFLICT_WITH_BASE_PROFILE
2. **شجرة الاستثناءات (Exception Hierarchy):**
   قسم Exception Handling يدعي أن استثناءات Core تمتد `RuntimeException` مباشرة. هذا يتعارض بشكل مباشر وثابت مع Base Profile الذي يلزم بتوريث مناسب من `maatify/exceptions`.
3. **مراجع غير محدثة (Stale Cross-References):**
   فقرة Analytics تشير إلى `MODULE_BUILDING_STANDARD.md §21`، بينما الإصدار المحدث في Base يعالج هذا في القسم 12.
4. **ميكانيكا Pagination المتكررة:**
   الـ Slim Profile يعرض Pagination clamping كأنه ميكانيكا محلية مع JavaScript fallback، بينما حزم Base / Package standards تمنع إعادة تنفيذ shared mechanics وتوجه لاستخدام `maatify/persistence`.

### 5.3 Scope/Ambiguity Needs Source Verification
(مصنفة تحت `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION`)
5. **استخدام ValidationGuard مقابل Try-Catch:**
   المستند ينص في قسم "Controllers" على `Use ValidationGuard: Don't use try-catch` وفي جدول الأخطاء `Using try-catch instead of ValidationGuard`. ثم ينص لاحقاً: `Slim controllers MUST catch all core module exceptions` وكل Controller يستدعي Service يحتاج try/catch. هذا التناقض الظاهري غير محسوم؛ فقد يكون المنع الأول خاصاً بـ request validation فقط والثاني بترجمة domain exceptions. يتطلب التحقق من عقود ValidationGuard و Error Handling الفعلي في Controllers.
6. **معلمات UiPermissionService::hasPermission():**
   مثال يمرر Route names (`settings.get.api`) بينما أجزاء أخرى تطلب Permission keys (`orders.view`) وتفرق بينهما. هذا غموض يحتاج AdminKernel source للتحقق والفصل فيه بشكل قاطع.
7. **التحويل العام للـ Exceptions:**
   ادعاء أن كل `*InvalidArgumentException` يُحول إلى `InvalidIdentifierFormatException` يحتاج تدقيقاً على عقود AdminKernel والموديولات الفعلية.
8. **HTTP Methods مقابل الأمثلة:**
   المستند يذكر `POST endpoints (queries, mutations)` في سياق يقابل أمثلة فعلية تستخدم GET لصفحات Detail و Analytics. يحتاج Route Source للتحقق.

---

## 6. Verification Source Map (خريطة مصادر التحقق المستقبلية)

يجب توفير المصادر (Repositories / Projects / Files / External Docs) التالية للقيام بعملية التدقيق التنفيذي لاحقاً:

### المراجع الداخلية (Internal Repositories/Projects)
1. **AdminKernel permission/list/validation/exception contracts:** للتحقق من `ProvidesPermissionMapsInterface`، `ListFilterResolver`، واختلاف Permission Keys/Route names و Exceptions.
2. **Host bootstrap, routes, middleware, DI and error middleware:** للتحقق من `public/index.php` وتسجيل الخدمات والمسارات وعمليات اعتراض الأخطاء.
3. **Real Slim implementations and their wrapped Base modules:** للتحقق من الأمثلة والممارسات (مثل `SettingsSlim`، و pagination delegation، و Controllers).
4. **Actual Twig templates and JavaScript assets:** للتحقق من `layouts/base.twig` ودوال مثل `escapeHtml()` و `JSON_HEX_*`.
5. **Permission migrations/schema:** للتحقق من الجداول والـ SQL seeds.
6. **`maatify/persistence` integration used by the real Base modules:** للتحقق من تداخل Pagination clamping مع الـ Base profile.

### المراجع الخارجية (External Documentation)
7. **Official Slim Framework middleware documentation:** للتحقق من ادعاءات ترتيب تنفيذ Middleware (`AuthorizationGuardMiddleware`).
8. **Official PHP/Twig documentation والمراجع الأمنية الموثوقة:** للتحقق من `json_encode`, `JSON_HEX_*`, auto-escaping، و script-context security claims.

---

## 7. النتيجة النهائية

```text
DOCUMENTATION PRESERVED
PROFILE BOUNDARY PARTIALLY VERIFIED
SOURCE IMPLEMENTATION VERIFICATION REQUIRED
NOT READY FOR CORRECTION OR ADOPTION
```
