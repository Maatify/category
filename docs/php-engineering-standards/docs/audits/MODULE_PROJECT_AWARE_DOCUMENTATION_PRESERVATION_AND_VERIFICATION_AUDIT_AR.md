# تقرير التدقيق لمحتوى Project-Aware Profile (Documentation Preservation & Verification)

### 1. Baseline & Scope
* **الـ HEAD المرجعية (Starting SHA):** `461ff8b300c4e3395539b0ce6627d9e2bf7ec0df`
* **النطاق:** `drafts/MODULE_PROJECT_AWARE_STANDARD.md`
* **الهدف:** توثيق وحفظ المعرفة الحالية للـ Project-Aware Slim Module، وتصنيف الادعاءات وتحديد مصادر التحقق المطلوبة، دون تعديل المستند الأصلي نظراً لغياب التنفيذ الفعلي (Runtime Implementation) في هذا المستودع.


### 1.1 Preservation Contract
* Project-Aware Profile يُعتبر هو مصدر المعرفة الحالي في هذا النطاق.
* يمنع منعاً باتاً حذف أو اختصار أو تصحيح أي معلومة قبل توفر الـ Source Implementation أو إجراء External Verification.
* أي تصحيح لاحق يجب أن يكون evidence-backed (مدعوماً بأدلة قاطعة) مع وجوب الحفاظ التام على المحتوى غير المتأثر.

### 2. غياب قسم حاكم صريح (Formal Override)
الملف لا يحتوي على قسم مخصص يوضح ترتيب الأولوية أو كيفية تجاوز قواعد Base و Slim بشكل صريح ورسمي، وإنما يتم استنتاج ذلك.
* **التصنيف:** `FORMAL_OVERRIDE_CONTRACT_MISSING`

### 3. تدقيق المحتوى قسمًا بقسم

#### 3.1. تعريف Project-Aware وعلاقته بـ Base وSlim
* **What it claims:** الـ Project-Aware Slim Module يغلف ارتباطات عبر الموديولات (cross-module) مرتبطة بالـ Host، وهو غير قابل للاستخراج ويسمح بـ JOINs متعددة لأن التطبيق يملكها، بخلاف الـ Standard Slim الذي يعتمد على Core module واحد فقط.
* **Classification:** `PROFILE_RULE_CONFIRMED`
* **Provable here:** Project-Aware مرتبط بالـ Host وغير قابل للاستخراج، ولا يلغي بقية قواعد Base أو Slim المنطبقة إلا باستثناء مسمى صراحة، وقواعد PDO تظل سارية.
* **Unprovable:** لا يوجد كود فعلي للتحقق منه.
* **Required Source:** Host application schema و Real Project-Aware modules.
* **Preservation Status:** يُحفظ دون تغيير.

#### 3.2. الاستثناءات المعلنة وحدود cross-module JOINs
* **What it claims:** يسمح بـ Cross-Module JOINs لأن المشروع المستضيف يملك كل الجداول المستضافة. يمكنه تجاوز قيد Slim الخاص بالالتفاف حول Base Module واحدة.
* **Classification:** `EXPLICIT_OVERRIDE_CONFIRMED`
* **Provable here:** هذا استثناء Project-Aware مثبت ولا يعتبر تعارضاً مع Base Profile.
* **Unprovable:** لا يوجد تنفيذ لاختبار حدود هذا الاستثناء.
* **Required Source:** Repositories and Host implementations.
* **Preservation Status:** يُحفظ دون تغيير.

#### 3.3. Latest-record derived JOIN و `MAX(order_id)`
* **What it claims:** يعتمد على استعلام فرعي لضمان صف واحد، ويستخدم `MAX(order_id)` بادعاء أن الـ `order_id` تصاعدي (monotonic) لتمثيل أحدث طلب.
* **Classification:** `REQUIRES_EXTERNAL_VERIFICATION`, `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION` و `NON_NORMATIVE_REAL_EXAMPLE`
* **Provable here:** لا يوجد كود هنا، NOT VERIFIED.
* **Unprovable:** ادعاء monotonic `order_id` وتمثيله لأحدث Order هو قرار خاص بالمشروع.
* **Required Source:** MySQL/MariaDB documentation (لسلوك الـ SQL العام)، و Host application schema / migrations / indexes والبيانات الفعلية.
* **Preservation Status:** يُحفظ دون تغيير.

#### 3.4. Conditional JOIN وربط قيمة الفلتر بالقيمة المعروضة
* **What it claims:** تغيير الـ JOIN بناءً على الفلتر لتجنب أخطاء عرض بيانات غير مطابقة للبحث. ويستخدم الكود `is_numeric()` ثم `(int)` cast للتحقق.
* **Classification:** `CONFLICT_WITH_BASE_PROFILE` (استخدام `is_numeric()` والـ cast يتعارض مع الـ canonical ID validation الصارم في Base) و `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION`.
* **Provable here:** التعارض مع سياسة الـ Base Profile ثابت نصيًا.
* **Unprovable:** فعالية الـ Conditional JOIN الفعلي في الـ Repositories.
* **Required Source:** Host application implementations و Repositories.
* **Preservation Status:** يُحفظ المثال دون تعديل حاليًا (لا يُصحح).

#### 3.5. Filtered count وtotal count
* **What it claims:** الـ Filtered count يجب أن يحتوي على نفس الـ JOINs المستخدمة في استعلام البيانات، بينما الـ Total count يكون على الجدول الأساسي بدون JOINs.
* **Classification:** `REQUIRES_EXTERNAL_VERIFICATION` و `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION`
* **Provable here:** لا يوجد كود هنا لإثبات صحة هذه الـ semantics، NOT VERIFIED. لا يوجد تعارض مثبت خاص بهذا القسم مع سياسة الـ Base.
* **Unprovable:** توافق الكود الفعلي مع هذه القواعد.
* **Required Source:** MySQL/MariaDB documentation (للسلوك العام للاستعلامات)، و Repositories الفعلية ومخطط البيانات.
* **Preservation Status:** يُحفظ دون تغيير.

#### 3.6. Endpoint reuse وasset keys
* **What it claims:** يُمكن إعادة استخدام endpoints من موديولات أخرى لبناء روابط في الواجهة بدون إنشاء مسارات خلفية (backend routes) جديدة، مثل استخدام asset keys (مثل `ar_image:ID:original_image`).
* **Classification:** `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION` و `NON_NORMATIVE_REAL_EXAMPLE`.
* **Provable here:** لا يمكن الحسم من هنا.
* **Unprovable:** بنية الـ endpoints وقواعد إعادة الاستخدام وعقود الـ asset keys.
* **Required Source:** Host implementation، و Upload endpoints، وعقود الـ asset keys.
* **Preservation Status:** يُحفظ دون تغيير.

#### 3.7. Detail UI Controller وAdminContext وcapabilities
* **What it claims:** جلب الـ Entity، تجهيز الـ capabilities للمستخدم، وتمرير AdminContext مع الـ entity إلى قوالب Twig. كما أن المثال يستخدم `ctype_digit($args['id'])` متبوعًا بـ `(int)` cast للتحقق من المعرّف.
* **Classification:** `CONFLICT_WITH_BASE_PROFILE` (استخدام `ctype_digit` و cast لا يطبق canonical positive `int|string` contract الكامل في Base، إذ يقبل leading-zero strings ولا يوضح overflow-safe bounds) و `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION`.
* **Provable here:** التعارض النصي في canonical ID validation مثبت من المستندات. بينما AdminContext والـ controller runtime غير متحققين.
* **Unprovable:** بنية AdminContext وكيفية عمل الـ UI Controllers.
* **Required Source:** AdminKernel و Host UI Controllers.
* **Preservation Status:** يُحفظ دون تغيير.

#### 3.8. Twig و`JSON_HEX_*` و`json_encode|raw`
* **What it claims:** تمرير capabilities كـ explicit boolean extraction دون استخدام فلاتر JSON، بينما يتم تمرير entity context كـ `script` context باستخدام `json_encode(JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)|raw` لتجنب ثغرات XSS.
* **Classification:** `REQUIRES_EXTERNAL_VERIFICATION` و `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION`.
* **Provable here:** لا يمكن الإثبات.
* **Unprovable:** أمان استخدام الـ JSON flags مع raw فلتر في سياق Twig script.
* **Required Source:** Official PHP / Twig Documentation للـ External Verification، و Twig templates للفحص.
* **Preservation Status:** يُحفظ دون تغيير.

#### 3.9. JavaScript Pattern B وURL/HTML safety
* **What it claims:** استخدام Helpers (مثل `escapeHtml`, `encodeURIComponent`, `safeExternalLink`) وأفعال Components (API actions).
* **Classification:** `REQUIRES_EXTERNAL_VERIFICATION` و `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION`.
* **Provable here:** لا يمكن الإثبات.
* **Unprovable:** صحة دوال الحماية وأمان URL.
* **Required Source:** Web platform documentation للـ External Verification، و JavaScript assets.
* **Preservation Status:** يُحفظ دون تغيير.

#### 3.10. Lazy-load sections
* **What it claims:** استخدام زر لبدء API call ثم تقديم العرض ورد الفعل (Feedback) للحفظ.
* **Classification:** `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION`
* **Provable here:** لا يوجد تنفيذ، NOT VERIFIED.
* **Unprovable:** سلوك الواجهة.
* **Required Source:** Frontend JavaScript assets.
* **Preservation Status:** يُحفظ دون تغيير.

#### 3.11. Permission reuse وpermission seeds
* **What it claims:** إعادة استخدام أذونات موجودة (مثلاً إذن من core module) واستخدامها كـ gate (مثل ادعاء أن order_id أو number "ليس sensitive"). ويجب توثيق ذلك في SQL Seeds.
* **Classification:** `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION` و `OWNER_DECISION_REQUIRED` (في حال عدم وجود سياسة حاكمة).
* **Provable here:** الادعاء بأن بيانات معينة ليست "sensitive" هو data-classification/authorization policy خاص بالمشروع، وليس حقيقة يمكن إثباتها من شكل التنفيذ فقط. لا يمكن الحسم من هنا.
* **Unprovable:** آليات AdminKernel والأذونات الفعلية في الجداول.
* **Required Source:** AdminKernel permissions/middleware، Permission migrations/seeds، و Host authorization policy/security classification.
* **Preservation Status:** يُحفظ دون تغيير.

#### 3.12. List-to-detail navigation وURL filter prefill
* **What it claims:** النقر على ID ينقل للـ detail، وقراءة URL query parameters لملء الفلاتر عبر `URLSearchParams`.
* **Classification:** `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION`
* **Provable here:** لا يوجد تنفيذ، NOT VERIFIED.
* **Unprovable:** عمل الكود المذكور في الواجهة.
* **Required Source:** Frontend JavaScript assets.
* **Preservation Status:** يُحفظ دون تغيير.

#### 3.13. File upload وFormData
* **What it claims:** استبدال أصول الـ Source باستخدام Event Delegation وإرسال FormData عبر fetch. كما أن المستند يذكر helper باسم `escapeAttr()`، لكن المثال يمرر `data-asset-key` باستخدام `escapeHtml(asset.assetKey)`.
* **Classification:** `REQUIRES_EXTERNAL_VERIFICATION` و `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION` (لوجود Security-sensitive ambiguity في استخدام دوال الهروب).
* **Provable here:** التعارض في المثال بين ذكر `escapeAttr()` واستخدام `escapeHtml()` في الـ attribute. لا يمكن الحسم كثغرة فعلية قبل التحقق.
* **Unprovable:** سلوك Upload الفعلي، formData processing، وسلامة دوال الـ Escaping في الواجهة الفعليّة.
* **Required Source:** Web platform documentation (لـ FormData و fetch) و JavaScript assets / Upload endpoints.
* **Preservation Status:** يُحفظ دون تغيير.

#### 3.14. Backend/Frontend checklists
* **What it claims:** توفير قوائم فحص لمكونات Backend و Frontend اللازمة للموديول.
* **Classification:** `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION`
* **Provable here:** لا يوجد، NOT VERIFIED.
* **Unprovable:** تطابق الـ checklist مع التنفيذ المعتمد.
* **Required Source:** Real Project-Aware module implementations.
* **Preservation Status:** يُحفظ دون تغيير.

#### 3.15. Split INSERT + UPDATE aggregation
* **What it claims:** استخدام نمط Split INSERT + UPDATE لمعالجة بيانات من جداول Modules مختلفة بدلاً من جملة INSERT...SELECT واحدة تسبب مشاكل مع `ONLY_FULL_GROUP_BY`. وتتم الإشارة إلى `MODULE_BUILDING_STANDARD.md §21` كحالة استخدام Simple DELETE + INSERT.
* **Classification:** `STALE_CROSS_REFERENCE`, `REQUIRES_EXTERNAL_VERIFICATION` و `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION`.
* **Provable here:** الإشارة إلى `MODULE_BUILDING_STANDARD.md §21` هي `STALE_CROSS_REFERENCE`. القسم القديم لم يعد موجودًا، والقسم 12 الحالي في Base يحتوي توجيهًا عامًا مشروطًا للـ Analytics وليس blueprint للـ Simple DELETE + INSERT. لا يمكن إثبات صحة النمط نفسه هنا.
* **Unprovable:** فعالية وصحة هذا النمط في بيئة العمل الحقيقية وتطابق الـ Aggregate tables.
* **Required Source:** Official MySQL/MariaDB documentation لسلوك `ONLY_FULL_GROUP_BY` والـ aggregation/query semantics، و Analytics tables/aggregation implementations و Cron scripts.
* **Preservation Status:** يُحفظ دون تغيير.

#### 3.16. Transactions و`ONLY_FULL_GROUP_BY`
* **What it claims:** الـ Aggregation steps تتم داخل Transaction، ويتوافق النمط مع قواعد `ONLY_FULL_GROUP_BY`.
* **Classification:** `REQUIRES_EXTERNAL_VERIFICATION` و `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION`.
* **Provable here:** قواعد PDO وPersistence تظل سارية عند امتلاك Database behavior.
* **Unprovable:** ضمان صحة الكود مع وضعيات SQL المختلفة.
* **Required Source:** MySQL/MariaDB documentation (لـ `ONLY_FULL_GROUP_BY` ومعاملات Transactions) والتنفيذ الداخلي للتحقق.
* **Preservation Status:** يُحفظ دون تغيير.

#### 3.17. Cron وDI/`$builderHook`
* **What it claims:** تسجيل الخدمات عبر `$builderHook` في سكربتات الـ Cron.
* **Classification:** `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION`
* **Provable here:** لا يُعتبر استخدام `$builderHook` أو تقنيات الـ DI بشكل تلقائي تسربًا (Base Leakage)، لأن Project-Aware Profile هو مخصص للـ Host (Host-specific). ومع ذلك، لا يُمكن الجزم بأنه "مسموح" أوتوماتيكيًا أو أن PHP-DI مُثبت في غياب الكود المصدري للـ Host.
* **Unprovable:** صحة العقد والتقنية والـ registration في بيئة المستضيف.
* **Required Source:** Host Application bootstrap/DI / Cron scripts.
* **Preservation Status:** يُحفظ المحتوى دون تغيير حتى Source Implementation Verification.

#### 3.18. جميع الأمثلة الحقيقية والجداول والمسارات والأسماء
* **What it claims:** المستند يعتمد بقوة على `OrderAdminSlim` وجداول مثل `orders`، `order_subscriptions`، `arp_subscriptions`، والمسارات والـ asset keys الحقيقية كأمثلة تطبيقية.
* **Classification:** `NON_NORMATIVE_REAL_EXAMPLE` و `REQUIRES_SOURCE_IMPLEMENTATION_VERIFICATION`.
* **Provable here:** لا يوجد كود للتحقق.
* **Unprovable:** الجداول الحقيقية وأسماء المسارات.
* **Required Source:** Host application schema / Real modules.
* **Preservation Status:** تُحفظ دون تغيير.

### 4. Verification Source Map (مصادقة التحقق)

#### 4.1 Internal Implementation Sources
* **Host application schema/migrations:** للتحقق من جداول `orders`، `order_subscriptions` وعلاقاتها، والـ monotonic IDs، والجداول الفعلية والمفاتيح.
* **Real Project-Aware modules & Base/Slim modules:** للتحقق من الأمثلة الفعلية، Controllers، DTOs، والـ Repositories.
* **AdminKernel permissions/routes/middleware/response contracts:** للتحقق من صلاحيات الأذونات وإعادة الاستخدام ومسارات الـ endpoints.
* **Twig templates و JavaScript assets:** للتحقق من بيئات الـ Frontend و JS Patterns والـ safety functions.
* **Upload endpoints & asset-key contracts:** للتحقق من أزرار الـ Replace وعمليات الرفع للـ assets.
* **Analytics tables & aggregation implementations:** للتحقق من استعلامات Aggregation ونمط Split INSERT/UPDATE.
* **Cron scripts و DI/bootstrap:** للتحقق من تسجيل الخدمات (builderHook) وإجراءات Cron.

#### 4.2 External Documentation
* **MySQL/MariaDB documentation:** لمراجعة تصرف `ONLY_FULL_GROUP_BY`، والمعاملات (Transactions)، وسلوك الـ JOINs.
* **PHP و Twig documentation:** لتوثيق عوامل الحماية `JSON_HEX_*` وسلوك `json_encode|raw` داخل `script`.
* **Slim Framework middleware/routing documentation:** للتحقق من سير عمل الـ Routes.
* **Web platform documentation:** لتوثيق أمان الـ URLs (مثل `URLSearchParams`)، سلوك `FormData` وعمليات الرفع (Upload behavior)، وكذلك Official HTML/Web platform escaping/context documentation (للتحقق من `escapeHtml` مقابل `escapeAttr`).

### 5. النتيجة النهائية

```text
DOCUMENTATION PRESERVED
PROJECT-AWARE BOUNDARY PARTIALLY VERIFIED
FORMAL OVERRIDE CONTRACT MISSING
SOURCE IMPLEMENTATION AND EXTERNAL VERIFICATION REQUIRED
NOT READY FOR CORRECTION OR ADOPTION
```
