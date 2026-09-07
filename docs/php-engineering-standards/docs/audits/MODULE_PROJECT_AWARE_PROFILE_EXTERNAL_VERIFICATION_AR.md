# تقرير التحقق الخارجي لادعاءات Project-Aware Profile

هذا الملف يوثق التحقق الخارجي (External Verification) للادعاءات التقنية المذكورة في وثيقة `MODULE_PROJECT_AWARE_STANDARD.md` بناءً على المصادر الرسمية (Official Documentation)، للتمييز بين ما هو حقيقة تقنية عامة وما هو سياسة أو تطبيق محلي خاص بالمشروع (Host Policy / Source Implementation).

## 1. نطاق التحقق والمصادر

- **تاريخ الاطلاع:** 2026-07-19
- **المصادر الرسمية المستخدمة:**
  - [MySQL 8.4 Reference Manual - Aggregate Function Descriptions (MAX)](https://dev.mysql.com/doc/refman/8.4/en/aggregate-functions.html#function_max)
  - [MariaDB Knowledge Base - MAX](https://mariadb.com/kb/en/max/)
  - [MySQL 8.4 Reference Manual - Aggregate Function Descriptions (COUNT)](https://dev.mysql.com/doc/refman/8.4/en/aggregate-functions.html#function_count)
  - [MariaDB Knowledge Base - COUNT](https://mariadb.com/kb/en/count/)
  - [MySQL 8.4 Reference Manual - GROUP BY Handling](https://dev.mysql.com/doc/refman/8.4/en/group-by-handling.html)
  - [MariaDB Knowledge Base - SQL_MODE (ONLY_FULL_GROUP_BY)](https://mariadb.com/kb/en/sql-mode/#only_full_group_by)
  - [MySQL 8.4 Reference Manual - InnoDB Transaction Model](https://dev.mysql.com/doc/refman/8.4/en/innodb-transaction-model.html)
  - [MySQL 8.4 Reference Manual - autocommit, Commit, and Rollback](https://dev.mysql.com/doc/refman/8.4/en/innodb-autocommit-commit-rollback.html)
  - [MySQL 8.4 Reference Manual - Statements That Cause an Implicit Commit](https://dev.mysql.com/doc/refman/8.4/en/implicit-commit.html)
  - [PHP Official Documentation - JSON Predefined Constants](https://www.php.net/manual/en/json.constants.php)
  - [PHP Official Documentation - json_encode](https://www.php.net/manual/en/function.json-encode.php)
  - [Twig Documentation - raw filter](https://twig.symfony.com/doc/3.x/filters/raw.html)
  - [MDN Web Docs - encodeURIComponent](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/encodeURIComponent)
  - [MDN Web Docs - URL() constructor](https://developer.mozilla.org/en-US/docs/Web/API/URL/URL)
  - [MDN Web Docs - URL.protocol](https://developer.mozilla.org/en-US/docs/Web/API/URL/protocol)
  - [MDN Web Docs - URL.href](https://developer.mozilla.org/en-US/docs/Web/API/URL/href)
  - [MDN Web Docs - URLSearchParams](https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams)
  - [MDN Web Docs - HTML attribute: rel="noopener"](https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/rel/noopener)
  - [MDN Web Docs - HTML attribute: rel="noreferrer"](https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/rel/noreferrer)
  - [MDN Web Docs - FormData](https://developer.mozilla.org/en-US/docs/Web/API/FormData)
  - [MDN Web Docs - fetch](https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API/Using_Fetch)
  - [OWASP Cross Site Scripting Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html)

## 2. التحقق من ادعاءات SQL وDatabase

### 2.1. `MAX(order_id)` كأحدث طلب
- **الادعاء الحالي:** استخدام `MAX(order_id)` لجلب "أحدث" (latest) سجل أو طلب.
- **الأدلة الرسمية:**
  - دالة `MAX()` ترجع أكبر قيمة في المجموعة (Maximum value) حسب وثائق دوال التجميع في [MySQL](https://dev.mysql.com/doc/refman/8.4/en/aggregate-functions.html#function_max) و [MariaDB](https://mariadb.com/kb/en/max/).
- **الاستنتاج الدقيق:**
  - حقيقة أن `MAX()` ترجع القيمة الأكبر هي `VERIFIED`.
  - ادعاء أن القيمة الأكبر تمثل "أحدث سجل" زمنياً هو `PROJECT_POLICY_NOT_EXTERNAL_FACT` و `SOURCE_IMPLEMENTATION_REQUIRED`.
- **ما لم يُحسم خارجيًا:** هل يعتمد ترتيب الـ ID على كونه رتيباً (Monotonic) ويزيد بمرور الوقت بانتظام؟ هذه سياسة (Schema/Business ordering policy) تعتمد على تصميم قاعدة البيانات وعقد التوليد الخاص بالمشروع.
- **Source Implementation المطلوبة:** مراجعة Schema الجداول لمعرفة نوع الـ ID وكيفية توليده.
- **الصياغة المقترحة مستقبلًا:** "عند استخدام `MAX(id)` لاستخراج أحدث سجل، يجب توثيق أن هذا يعتمد على كون الـ ID رتيباً (Monotonic) كسياسة خاصة بالمشروع، وليس حقيقة SQL مطلقة."

### 2.2. Conditional JOIN وFiltered Count
- **الادعاء الحالي:** Conditional JOIN يُستخدم عندما يستهدف الفلتر عموداً في جدول مرتبط، و"يجب" أن يتضمن Filtered Count نفس الـ JOIN، بينما Total Count يجب أن يعتمد على الـ base table فقط.
- **الأدلة الرسمية:**
  - `COUNT(*)` يعيد عدد الصفوف داخل كل group، أو عدد صفوف النتيجة عند عدم وجود `GROUP BY` ([MySQL](https://dev.mysql.com/doc/refman/8.4/en/aggregate-functions.html#function_count) و [MariaDB](https://mariadb.com/kb/en/count/)).
- **الاستنتاج الدقيق:**
  - حقيقة سلوك `COUNT(*)` بعدّ صفوف النتيجة هي `VERIFIED`.
  - المطالبة بتطابق استعلام العدد المفلتر والـ Total Count هي `PROJECT_POLICY_NOT_EXTERNAL_FACT`.
  - أثر الـ JOIN على تكرار الصفوف الأساسية هو `SOURCE_IMPLEMENTATION_REQUIRED`.
- **ما لم يُحسم خارجيًا:** الـ JOIN قد يضاعف صفوف الـ base entity بحسب الـ cardinality والـ grain. في علاقات 1:N، الـ `COUNT(*)` العادي سيكرر حساب الكيانات الأساسية، مما يتطلب تقريرًا داخليًا عن استخدام `COUNT(DISTINCT ...)` أو الحفاظ على نفس عقد الترقيم.
- **Source Implementation المطلوبة:** التنفيذ الفعلي لـ Repositories في بيئة المستضيف وعقود الـ Pagination.
- **الصياغة المقترحة مستقبلًا:** "اعتماداً على متطلبات الـ Pagination الخاصة بالمشروع، وفي حال كانت العلاقة 1-to-1 أو تم معالجة التكرار، قد يلزم أن يتطابق استعلام الـ Filtered Count مع الـ JOINs المستخدمة في استعلام البيانات لضمان دقة العدد المفلتر. يجب مراعاة تأثير تكرار الصفوف في العلاقات 1:N."

### 2.3. سلوك `ONLY_FULL_GROUP_BY`
- **الادعاء الحالي:** قواعد `ONLY_FULL_GROUP_BY` تمنع استعلامات `INSERT...SELECT` مع Correlated subqueries.
- **الأدلة الرسمية:**
  - [MySQL 8.4 Handling of GROUP BY](https://dev.mysql.com/doc/refman/8.4/en/group-by-handling.html) يوثق أن `ONLY_FULL_GROUP_BY` يمنع اختيار أعمدة غير مجمعة إلا إذا كانت معتمدة وظيفياً (Functionally dependent) على الـ GROUP BY.
  - [MariaDB SQL_MODE](https://mariadb.com/kb/en/sql-mode/#only_full_group_by) يوثق أن `ONLY_FULL_GROUP_BY` يمنع non-grouped nonaggregated columns.
- **الاستنتاج الدقيق:**
  - الادعاء العام بأن الوضع "يمنع `INSERT...SELECT` مع correlated subqueries" هو `FALSE_OR_OVERBROAD`. القاعدة الفعلية تتعلق بالـ nonaggregated expressions والـ functional dependency.
  - بالنسبة للاستعلام الدقيق الذي يحتوي correlated subquery: `VERSION_DEPENDENT` و `SOURCE_IMPLEMENTATION_REQUIRED`.
- **ما لم يُحسم خارجيًا:** لا يجوز افتراض تطابق MariaDB مع MySQL في اكتشاف الـ functional-dependency دون دليل. قبول أو رفض الاستعلام المذكور يعتمد على إصدار المحرك وطريقته في تحليل الـ dependencies.
- **Source Implementation المطلوبة:** exact query probe موثق على الإصدار المستهدف للتحقق من رفض استعلام محدد.
- **الصياغة المقترحة مستقبلًا:** "سلوك `ONLY_FULL_GROUP_BY` يمنع اختيار أعمدة غير مجمعة وغير معتمدة وظيفياً. التوجيهات يجب أن تراعي الإصدار المستهدف وتصاغ حول تجنب هذه الأعمدة بشكل صريح، بدلاً من التعميم بمنع أنماط كاملة من الاستعلامات إلا بدليل إصدار محدد."

### 2.4. Split INSERT + UPDATE Aggregation
- **الادعاء الحالي:** يجب استخدام هذا النمط كحل أساسي لجمع الإحصائيات بدلاً من `INSERT...SELECT` لكونه يعالج مشاكل `ONLY_FULL_GROUP_BY`.
- **الأدلة الرسمية:** لا توجد وثيقة SQL تعتمد هذا النمط كمتطلب عام.
- **الاستنتاج الدقيق:** `PROJECT_POLICY_NOT_EXTERNAL_FACT` و `SOURCE_IMPLEMENTATION_REQUIRED`.
- **ما لم يُحسم خارجيًا:** كفاءة هذا النمط وصحته على الـ Schema الفعلية للمشروع مقارنة بالبدائل.
- **Source Implementation المطلوبة:** التنفيذ الفعلي لجمع الإحصائيات (Aggregation tables و Cron scripts) لتقييم الأداء والصحة على الـ Schema المحددة.
- **الصياغة المقترحة مستقبلًا:** "نمط Split INSERT + UPDATE يُعد سياسة تطبيقية (Implementation Pattern) قد تناسب ظروف تجميع محددة في المشروع وتُحسن الأداء في بعض الـ Schemas، لكنه ليس متطلباً عاماً من المحرك."

### 2.5. المعاملات (Transactions) في عمليات الـ Aggregation
- **الادعاء الحالي:** كافة خطوات الـ Aggregation تتم داخل Transaction واحدة مع Rollback إما كل شيء أو لا شيء.
- **الأدلة الرسمية:**
  - [MySQL InnoDB Transaction Model](https://dev.mysql.com/doc/refman/8.4/en/innodb-transaction-model.html)
  - [MySQL autocommit, Commit, and Rollback](https://dev.mysql.com/doc/refman/8.4/en/innodb-autocommit-commit-rollback.html) توثق التحكم الصريح والمؤتمت بالمعاملات والتأكد من تطبيقها أو التراجع عنها (rollback) في الجداول التي تدعمها مثل InnoDB. بالنسبة للجداول غير الـ transactional لا يمكن إجراء rollback لتعديلاتها.
  - [MySQL Statements That Cause an Implicit Commit](https://dev.mysql.com/doc/refman/8.4/en/implicit-commit.html) توثق الأوامر التي تقطع المعاملة.
- **الاستنتاج الدقيق:** `PARTIALLY_VERIFIED` و `SOURCE_IMPLEMENTATION_REQUIRED`.
- **ما لم يُحسم خارجيًا:** ضمان العملية الكاملة يعتمد على كون الجداول transactional، وملكيتها، وعدم وجود أوامر تسبب implicit commits، والتأكد من التنفيذ الفعلي للـ rollback في بيئة التطبيق عند الفشل.
- **Source Implementation المطلوبة:** كود الـ Repositories ومحرك التخزين في بيئة المستضيف للتأكد من التنفيذ الفعلي.
- **الصياغة المقترحة مستقبلًا:** "لضمان الـ Atomicity لعمليات متعددة، يجب استخدام الـ Transactions بشكل صريح، مع التأكد من أن الجداول المعنية تدعم المعاملات وأن السياق لا يتضمن أوامر تسبب إنهاءً ضمنياً (Implicit Commit)."

## 3. التحقق من ادعاءات Twig/PHP/HTML/JavaScript

### 3.6. `json_encode` مع فلاتر `JSON_HEX_*` داخل Twig `<script>`
- **الادعاء الحالي:** استخدام `json_encode(JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)|raw` يمنع ثغرات XSS.
- **الأدلة الرسمية:**
  - [PHP json_encode](https://www.php.net/manual/en/function.json-encode.php) يثبت عملية الترميز.
  - [PHP JSON Predefined Constants](https://www.php.net/manual/en/json.constants.php) يثبت وظائف فلاتر `JSON_HEX_*` في تحويل الأحرف الخاصة إلى Unicode escapes.
  - [Twig raw filter](https://twig.symfony.com/doc/3.x/filters/raw.html) يثبت أن `raw` يعطل نظام الهروب (escaping) للقيمة المُعلّمة به بحسب موضعه.
- **الاستنتاج الدقيق:** وظائف الفلاتر والفلتر `raw` هي `VERIFIED`. أما الأمان الكلي فهو `PARTIALLY_VERIFIED` و `SOURCE_IMPLEMENTATION_REQUIRED`.
- **ما لم يُحسم خارجيًا:** الـ `raw` لا يعطل الهروب للقالب كله بل للقيمة المحددة. الأمان الكلي يعتمد على الاستهلاك الفعلي والـ final script context داخل القالب.
- **Source Implementation المطلوبة:** قوالب Twig الفعلية للتحقق من السياق (Context).
- **الصياغة المقترحة مستقبلًا:** "استخدام `json_encode` مع فلاتر `JSON_HEX_*` والفلتر `raw` يُطبق لتحضير البيانات لسياقات JavaScript داخل القوالب؛ يجب أن يُثبت أمان التطبيق عبر مراجعة السياق النهائي (Script block context)."

### 3.7. `escapeHtml()` مقابل `escapeAttr()`
- **الادعاء الحالي:** وجود دالة `escapeAttr()` كأحد الـ helpers، مع استخدام `escapeHtml()` لتمرير بيانات لسمات HTML في المثال الحالي.
- **الأدلة الرسمية:**
  - [OWASP Cross Site Scripting Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html) يثبت أن سياق الهروب للمحتوى (HTML text) يختلف عن سياق السمات (HTML attribute) ويتطلب ترميزاً (quoting) مناسباً للسمات.
- **الاستنتاج الدقيق:** الاختلاف بين السياقين والحاجة لهروب ملائم هي `VERIFIED`. ملاءمة دوال الـ Helper وتصنيف سلوك المثال الفعلي هو `SOURCE_IMPLEMENTATION_REQUIRED`.
- **ما لم يُحسم خارجيًا:** طبيعة التنفيذ الفعلي لدوال الهروب (`escapeHtml` و `escapeAttr`) غير معروفة. المثال يستخدم سمة مقتبسة (Quoted Attribute)، مما يعني أن المخاطرة وسلوك الهروب يختلفان عن السمات غير المقتبسة.
- **Source Implementation المطلوبة:** ملفات الـ JavaScript التي تعرّف هذه الدوال، والسياقات المستخدمة في القوالب.
- **الصياغة المقترحة مستقبلًا:** "يجب التفريق بشكل صارم بين الهروب لسياق النص (HTML Body) والهروب لسياق السمات (HTML Attribute)، ويجب التزام الـ Helpers بسياسات الـ Context-sensitive encoding كما هو موصى به من OWASP."

### 3.8. أمان الروابط (URL Handling)
- **الادعاء الحالي:** استخدام `encodeURIComponent`، `URLSearchParams`، و `target="_blank"` مع `rel="noopener noreferrer"`.
- **الأدلة الرسمية:**
  - [MDN URL() constructor](https://developer.mozilla.org/en-US/docs/Web/API/URL/URL) يعرّف عملية الـ parsing وقد يرمي `TypeError` للمدخلات غير الصالحة.
  - [MDN URL.protocol](https://developer.mozilla.org/en-US/docs/Web/API/URL/protocol) يوفر الـ scheme متضمناً `:`.
  - [MDN URL.href](https://developer.mozilla.org/en-US/docs/Web/API/URL/href) يعيد الـ serialized/normalized URL.
  - [MDN encodeURIComponent](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/encodeURIComponent) يُستخدم لترميز مكوّنات الرابط، وليس للرابط كاملاً.
  - [MDN URLSearchParams](https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams) تُستخدم كواجهة للـ parsing وليس للـ validation أو الـ sanitization.
  - [MDN rel="noopener"](https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/rel/noopener) يمنع وصول الصفحة الجديدة إلى `window.opener`.
  - [MDN rel="noreferrer"](https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/rel/noreferrer) يمنع الـ Referer header ويعامل الرابط أيضاً كـ `noopener`.
- **الاستنتاج الدقيق:** ميكانيكا واجهات المتصفح (Browser API mechanics) هي `VERIFIED`. تطبيق وظيفة Allowlist مخصصة (`safeExternalLink`) وملاءمة الهروب داخل `href` يظل `SOURCE_IMPLEMENTATION_REQUIRED`.
- **ما لم يُحسم خارجيًا:** تطبيق الـ allowlist للبروتوكولات (مثل `http:`/`https:`) هو سياسة ومشروع تنفيذي، واستخدام الهروب المناسب.
- **Source Implementation المطلوبة:** ملفات الـ JS للتحقق من تطبيق دوال الحماية للـ Scheme ومواصفات `safeExternalLink`.
- **الصياغة المقترحة مستقبلًا:** "استخدام واجهات المتصفح مثل `new URL()` يوفر قاعدة للتحليل، لكن يجب تطبيق سياسة Allowlist صريحة عند استخدام الروابط الخارجية كمصدر للـ `href`، واستخدام دوال الترميز الخاصة بالمكونات."

### 3.9. `FormData` و `fetch` (عمليات الرفع)
- **الادعاء الحالي:** استخدام `FormData` لإرسال الملفات عبر `fetch` يوفر آلية رفع آمنة.
- **الأدلة الرسمية:**
  - [MDN FormData](https://developer.mozilla.org/en-US/docs/Web/API/FormData)
  - [MDN fetch API](https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API/Using_Fetch) توثق إرسال البيانات بصيغة `multipart/form-data` وتحذر صراحة من ضبط الـ `Content-Type` يدوياً عند استخدام `FormData`.
- **الاستنتاج الدقيق:** `VERIFIED` (كميكانيكا متصفح لإرسال البيانات).
- **ما لم يُحسم خارجيًا:** هذه ميكانيكا طرف العميل ولا تضمن أماناً للرفع، وحماية CSRF، أو التحقق من الصلاحيات والـ MIME types. كل هذه التبعيات الأمنية تظل مسؤولية طرف الخادم (Server-side boundaries).
- **Source Implementation المطلوبة:** Backend Upload endpoints.
- **الصياغة المقترحة مستقبلًا:** "تقنيات مثل `FormData` و `fetch` مسؤولة عن ميكانيكية الإرسال وتغليف البيانات (multipart/form-data). كافة جوانب الحماية، مثل CSRF والتحقق من محتوى الملف، تقع ضمن مسؤولية حدود الخادم."

## 4. عناصر ليست External Claims كاملة

### 4.10. Permission Sensitivity
- **الادعاء:** اعتبار حقول مثل `order_id` غير حساسة.
- **التصنيف:** `PROJECT_POLICY_NOT_EXTERNAL_FACT`. هذه سياسة تصنيف بيانات (Data Classification Policy) خاصة بالـ Host.

### 4.11. `$builderHook`, DI، و AdminKernel
- **الادعاء:** تسجيل الخدمات عبر `$builderHook` في موديولات الـ Project-Aware.
- **التصنيف:** `SOURCE_IMPLEMENTATION_REQUIRED`. هذه تفاصيل تطبيق تعتمد على الـ Host application.

### 4.12. Canonical ID Validation
- **الادعاء:** استخدام `ctype_digit` ثم `(int)`.
- **التصنيف:** محال إلى تقرير `docs/audits/MODULE_BUILDING_PROFILE_EXTERNAL_VERIFICATION_AR.md`.
- **ملاحظة:** التعارض الحالي في المثال مع العقد المعرف (قبول الأصفار البادئة) يمثل `CONFLICT_WITH_BASE_PROFILE` داخلي. سلوك الـ Host الفعلي يظل `SOURCE_IMPLEMENTATION_REQUIRED`.

### 4.13. `STALE_CROSS_REFERENCE` و `FORMAL_OVERRIDE_CONTRACT_MISSING`
- **التصنيف:** `NOT_APPLICABLE_AS_EXTERNAL_CLAIM`.

## 5. النتيجة النهائية

```text
EXTERNAL CLAIM VERIFICATION COMPLETE
HOST-SPECIFIC CLAIMS REMAIN UNVERIFIED
SOURCE IMPLEMENTATION VERIFICATION REQUIRED
PROFILE NOT READY FOR CORRECTION OR ADOPTION
```