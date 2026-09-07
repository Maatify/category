# التحقق الخارجي لادعاءات Module Building Profile التقنية

## 1. Baseline و Scope
- **Starting SHA:** `3c8bdd41d02798129cdd56412baf9bff784d90de`
- **Scope:** External Verification لادعاءات تقنية محددة في `MODULE_BUILDING_PROFILE`.

## 2. Source Policy والـ Traceability
- **تاريخ الاطلاع:** 2026-07-18
- تم الاعتماد على المصادر الرسمية التالية المباشرة لكل Claim:
  - **PDO::prepare:** https://www.php.net/manual/en/pdo.prepare.php
  - **DateTimeImmutable::createFromFormat:** https://www.php.net/manual/en/datetimeimmutable.createfromformat.php
  - **DateTimeImmutable::getLastErrors:** https://www.php.net/manual/en/datetimeimmutable.getlasterrors.php
  - **filter_var/FILTER_VALIDATE_INT:** https://www.php.net/manual/en/filter.filters.validate.php
  - **is_numeric:** https://www.php.net/manual/en/function.is-numeric.php
  - **PDOException/errorInfo:** https://www.php.net/manual/en/class.pdoexception.php (يثبت أن `$errorInfo` هي `?array`)
  - **MySQL Error Reference:** https://dev.mysql.com/doc/mysql-errors/8.4/en/server-error-reference.html (لإثبات الرموز الخاصة بـ MySQL مثل `1062` لـ Duplicate، و `ER_CHECK_CONSTRAINT_VIOLATED` = 3819 / `HY000`)
  - **MariaDB Error Reference:** نتائج MariaDB (مثل `4025` للـ Check Constraint) موسومة هنا كـ `OBSERVED PROBE ONLY` للتمييز بينها وبين الحقائق الرسمية لـ MySQL 8.4.
  - **PHPStan Rule Levels:** https://phpstan.org/user-guide/rule-levels
  - **PHPStan Generics:** https://phpstan.org/blog/generics-in-php-using-phpdocs

## 3. Verification Environment والـ Probes
تم تنفيذ التجارب العملية في البيئة التالية لتوثيق السلوك الفعلي:
- **PHP Version:** 8.3.6 (cli) NTS
- **Database Server:** `10.11.14-MariaDB` (Client: `mysqlnd 8.3.6`) - *ملاحظة: النتائج المتعلقة بـ MariaDB هنا تُعامل كـ Observed Behavior لتمييزها عن MySQL Target Facts.*
- **SQL Mode:** `STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION`
- **PHPStan Version:** 2.2.5 (مع PHP 8.3.6، بدون إضافات/إعدادات مخصصة، الأمر: `phpstan analyse -l max file.php`)

### 3.1. PDO Placeholder Probe
- **الإعداد:** `PDO::ATTR_ERRMODE = ERRMODE_EXCEPTION`
- **الأمر (Emulated):** `$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true); $pdo->prepare('SELECT :v, :v')->execute(['v'=>1]);`
- **النتيجة (Emulated):** ينجح الاستعلام بدون أخطاء.
- **الأمر (Native):** `$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false); $pdo->prepare('SELECT :v, :v')->execute(['v'=>1]);`
- **النتيجة (Native):** يفشل بخطأ `SQLSTATE[HY093]: Invalid parameter number`.

### 3.2. Date Parsing Probe
- **الأمر:**
  ```php
  $parseFormat = '!Y-m-d'; $outputFormat = 'Y-m-d'; $input = '2026-02-31';
  echo (new DateTimeImmutable($input))->format($outputFormat);
  $dt = DateTimeImmutable::createFromFormat($parseFormat, $input);
  $errors = DateTimeImmutable::getLastErrors();
  ```
- **النتيجة المرصودة:**
  - `new DateTimeImmutable` يُرجع `2026-03-03` (Silent normalization/overflow).
  - `createFromFormat` يُرجع كائناً (وليس `false`)، وصيغته `2026-03-03` (Overflow مسموح به داخلياً). لكن `getLastErrors()` يسجل warning `The parsed date was invalid`.

### 3.3. SQLSTATE Probe
- **الإعداد:** جدول بـ Unique و Check (في MariaDB) وجدول Parent/Child لـ Foreign Key.
- **الأوامر والنتائج (Observed Probe Only on MariaDB 10.11):**
  - **Unique (Duplicate):** `SQLSTATE 23000`, `Driver code 1062`
  - **Foreign Key:** `SQLSTATE 23000`, `Driver code 1452` (إدراج child value غير موجودة في الـ parent).
  - **Explicit NULL:** `SQLSTATE 23000`, `Driver code 1048`
  - **Omitted Required (No Default):** `SQLSTATE HY000`, `Driver code 1364`
  - **Check Constraint:** `SQLSTATE 23000`, `Driver code 4025`

### 3.4. ID Matrix Probe
- **الأمر:** تمرير قيم مختلفة وتقييم كل من `is_numeric`، `FILTER_VALIDATE_INT`، و `(int)`.

| القيمة | النوع | `is_numeric` | `filter_var(INT)` | `(int)` Cast | Canonical Policy Decision |
|---|---|---|---|---|---|
| `1` | int | true | `1` | `1` | Accepted |
| `"1"` | string | true | `1` | `1` | Accepted |
| `"01"` | string | true | false | `1` | Rejected (Leading zeros not canonical) |
| `"+1"` | string | true | `1` | `1` | Rejected (Sign character not canonical) |
| `"-1"` | string | true | `-1` | `-1` | Rejected (Negative ID) |
| `"0"` | string | true | `0` | `0` | Rejected (Must be strictly > 0) |
| `" 1 "` | string | true | `1` | `1` | Rejected (Whitespace not canonical) |
| `"1.0"` | string | true | false | `1` | Rejected (Decimal notation not canonical) |
| `"1.5"` | string | true | false | `1` | Rejected (Not an integer) |
| `"1e3"` | string | true | false | `1000` | Rejected (Scientific notation not canonical) |
| `1000.0` | double | true | `1000` | `1000` | Rejected (float type is outside the canonical int|string ID contract) |
| `true` | bool | false | `1` | `1` | Rejected (Not a numeric type) |
| `false` | bool | false | false | `0` | Rejected |
| `null` | null | false | false | `0` | Rejected |
| `PHP_INT_MAX` | int | true | `PHP_INT_MAX` | `PHP_INT_MAX` | Accepted |
| `PHP_INT_MAX + 1` | double | true | false | `-9223372036854775808` | Rejected (Overflow causes negative truncation) |

### 3.5. PHPStan Probe
- **PHPStan 2.2.5 Level 10 (max)**
- **(int) mixed:**
  - `(int) ($row['id'] ?? 0)` يُبلغ عن `Cannot cast mixed to int.` (Identifier: `cast.int`).
- **(bool) mixed:**
  - `(bool) ($row['is_active'] ?? false)` **لا يُبلغ عن أي خطأ cast** (يقبل تحويل mixed إلى bool).
- **Generics (IteratorAggregate):**
  - فئة تُنفذ `\IteratorAggregate` بدون تعليق `@implements` تبلغ عن `missingType.generics`.
- **Container / `get()` Probe:**
  - `class Test { public function noVar() { $s = $this->c->get('service'); $this->use($s); } }` يُبلغ عن خطأ أن الدالة `use()` تتوقع `Service` ولكن `mixed` أُعطي.
  - تمرير `@var Service $s`، أو استخدام `assert($s instanceof Service)` يُزيل الخطأ بنجاح.

## 4. Claim-by-claim Analysis

### 4.1. PDO Placeholder Findings
- **الادعاء الحالي:** PDO لا يدعم بشكل موثوق تكرار Named Placeholders.
- **الخلاصة:** **Verified behavior under native prepares + Maatify compatibility policy.** السلوك مثبت في وثائق `PDO::prepare` عند الاعتماد على Native Prepares. فرض الأسماء الفريدة هو سياسة (Policy) لضمان التوافقية المستقرة.

### 4.2. Date Parsing Findings
- **الادعاء الحالي:** `new DateTimeImmutable()` يقوم بتطبيع صامت للتواريخ الخاطئة، لذا يجب استخدام `createFromFormat`.
- **الخلاصة:** **PARTIALLY VERIFIED**.
  - لـ Strict known-format date-only: `createFromFormat` ضروري. لكن بما أنه يسمح بالـ overflow، فإن **Exact round-trip comparison ليست بديلًا اختياريًا**؛ هي ה־Guard الذي يثبت أن النص مطابق للصيغة ولم يحدث overflow. دالة `getLastErrors()` مفيدة لاكتشاف warnings (يجب الانتباه أنها ترجع `false` في PHP 8.2+ عند عدم وجود أخطاء) لكنها ليست كافية وحدها عند اشتراط canonical lexical format.
  - لـ General/free-form date-time: الـ Constructor `new DateTimeImmutable` شرعي ولا يجوز منعه كلياً.

### 4.3. SQLSTATE Findings
- **الادعاء الحالي:** فحص `str_starts_with($e->getCode(), '23')` كافٍ لالتقاط Duplicate Key Violation وتحويله لـ `CodeAlreadyExistsException`.
- **الخلاصة:** **FALSE / OVERBROAD**. Class 23 يعبر عن فئة واسعة من الـ Constraints (Duplicate، Foreign Key `1452`، Explicit NULL `1048`). علاوة على ذلك، في MySQL 8.x، الـ Check Constraint `3819` هو `HY000`. يجب التحقق صراحة من الـ Driver code لتحديد الـ Duplicate (مثل `1062` لـ MySQL/MariaDB). وبما أن `PDOException::$errorInfo` هي `?array`، فيجب التحقق من وجود `[1]` صراحة لمنع الأخطاء.

### 4.4. ID Validation Findings
- **الادعاء الحالي:** استخدام `FILTER_VALIDATE_INT` وحده بدل `is_numeric` للـ Primary Keys.
- **الخلاصة:** **FALSE / OVERBROAD**.
  - `is_numeric` يقبل الـ overflow floats مما قد يسبب Truncation لقيم سالبة عند الـ cast.
  - `FILTER_VALIDATE_INT` يقبل `true` كـ `1`، والـ sign `+1`، والـ whitespace.
  - رفض `+1` و `"01"` و Whitespace هو **Canonical Positive ID Policy** تتبناها Maatify، وليس حقيقة رياضية عن صلاحية الرقم. يجب أن يكون الفحص overflow-safe ثم يطبق قواعد الـ Canonical representation والـ positive range check.

### 4.5. PHPStan Findings
- **الادعاء الحالي:** Level `max` يرفض casting لـ `mixed` إلى scalar عموماً.
- **الخلاصة:** **FALSE / OVERBROAD**.
  - **Verified Behavior:** PHPStan يرفض `(int) mixed`، ولكنه **لا يرفض** `(bool) mixed`. لذلك الادعاء الواسع بمنع الـ casting كلياً غير دقيق.
  - **Verified Behavior:** يتطلب `IteratorAggregate` توفير generic type arguments (عبر `@implements`).
  - **Maatify Policy:** استخدام `@var` لـ Type narrowing بعد `Container::get()` ليس الحل الوحيد في PHPStan (يمكن استخدام `assert` أو type-aware extension)، بل هو تفضيل (Policy) من Maatify لتبسيط الكود في غياب الـ Extensions.

## 5. Required Coordinated Correction Targets
- **`standards/packages/PACKAGE_BUILDING_STANDARD.md`**: يجب تصحيح Gap الـ SQLSTATE `23xxx` هنا أولاً (وهو مصدر النمط).
- **`drafts/MODULE_BUILDING_STANDARD.md`**: يجب تصحيح الأقسام المتعلقة بـ PDO Placeholders، Date Parsing، SQLSTATE Exception Handling (بعد تصحيح Package Profile)، ID Validation Policy، وتحديداً الادعاء الخاص بـ PHPStan casting لـ `mixed` إلى scalars.

## 6. Exact Recommended Normative Wording

- **PDO Named Placeholders:**
  > "For cross-driver compatibility and to support Native Prepared Statements (`PDO::ATTR_EMULATE_PREPARES = false`), a named placeholder MUST NOT be reused in the same query. Every placeholder must appear exactly once per SQL string."

- **Date Parsing:**
  > "For strict date-only inputs with a known format (e.g. `Y-m-d`), you MUST use `DateTimeImmutable::createFromFormat()`. Because `createFromFormat` allows overflow, you MUST perform an exact round-trip string comparison (`$parsed !== false && $parsed->format('Y-m-d') === $input`) to guard against format normalization. Inspecting `getLastErrors()` can be an additional check but does not replace the exact string match for canonical formats. Do not ban `new \DateTimeImmutable()` for general, free-form date/time parsing."

- **SQLSTATE Error Classification:**
  > "SQLSTATE Class 23 (`23xxx`) covers a broad category of integrity constraint violations (Unique, Foreign Key, Not Null) and varies by DBMS (e.g., MySQL 8.x classifies Check Constraints as `HY000`). You MUST NOT map all `23xxx` errors to a Duplicate Exception. When targeting MySQL/MariaDB, you MUST explicitly check the driver-specific error code in `$e->errorInfo` (e.g., verifying `isset($e->errorInfo[1])` and checking for `1062` for Duplicate Key) before throwing an AlreadyExists exception."

- **ID Validation:**
  > "For Primary Key / ID validation from untyped input, neither `is_numeric()` nor `filter_var(..., FILTER_VALIDATE_INT)` is sufficient on its own to enforce a canonical, overflow-safe positive ID. You MUST enforce a canonical positive domain policy (rejecting signs, leading zeros, and whitespace) and perform a strict bounds check to prevent integer overflow truncation before casting to `int`."

- **PHPStan Hydration & Typing:**
  > "At PHPStan Level max (Level 10), casting a `mixed` array offset to `int` (e.g., `(int) $row['id']`) is rejected with `cast.int`, though `(bool)` is allowed. You MUST narrow the type first before casting to `int`. Furthermore, `IteratorAggregate` implementations MUST declare generics (`@implements \IteratorAggregate<TKey, TValue>`). When fetching services from a PSR-11 Container, you MUST use a reliable type narrowing mechanism; per Maatify policy, this is typically done using an inline `@var` annotation."

## 7. Final Result
```text
EXTERNAL VERIFICATION COMPLETE
```
