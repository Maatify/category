# تقرير التحقق الخارجي لحزم Maatify المعتمدة

## 1. منهج التحقق والوضع الفعلي للمستودعات الخارجية

تم فحص الحالة الحالية للمستودعات الخارجية المطلوبة، وجاءت النتائج كالتالي:

### `maatify/exceptions`
*   **اسم المستودع:** `Maatify/exceptions`
*   **اسم Composer:** `maatify/exceptions`
*   **Exact current default-branch SHA:** `56f9836971e97b397e748c2244c2728fcd4bfaae`
*   **أحدث Stable Tag:** `v1.1.0`
*   **Minimum Stable Version المدعوم بالأدلة:** Exception hierarchy و`ApiAwareExceptionInterface` موجودان من `v1.0.0`.
*   **الفرق بين `main` و `v1.1.0`:** لا يوجد فرق، `main` يشير حالياً لنفس التزام `v1.1.0`.
*   **`composer.json`:** يعرف الحزمة بـ `maatify/exceptions`.
*   **الـ namespaces العامة:** `Maatify\Exceptions\`
*   **الملفات والعقود العامة الفعلية داخل `src/`:**
    *   يوجد هرم استثناءات كامل في `src/Exception/` مقسم لمجلدات.
    *   يوجد `Maatify\Exceptions\Exception\MaatifyException` وهو ليس Marker Interface، بل هو `abstract class` يمتد `RuntimeException` ويطبق `ApiAwareExceptionInterface`.
    *   يوجد العقد العام `Maatify\Exceptions\Contracts\ApiAwareExceptionInterface extends Throwable` وطُرقه العامة الفعلية هي:
        *   `public function getHttpStatus(): int;`
        *   `public function getErrorCode(): ErrorCodeInterface;`
        *   `public function getCategory(): ErrorCategoryInterface;`
        *   `public function isSafe(): bool;`
        *   `public function getMeta(): array;`
        *   `public function isRetryable(): bool;`
*   **دليل كل نتيجة:** تم التحقق من مجلد `src/Exception/` ومن محتوى الواجهات في `src/Contracts/` في الإصدارات المستقرة.

### `maatify/shared-common`
*   **اسم المستودع:** `Maatify/SharedCommon`
*   **اسم Composer:** `maatify/shared-common`
*   **Exact current default-branch SHA:** `e1eb5c71f14b57858254c61f9bd5bae08232983d`
*   **أحدث Stable Tag:** `v1.0.2`
*   **Minimum Stable Version المدعوم بالأدلة:** `ClockInterface` و`SystemClock` موجودان من `v1.0.0`.
*   **الفرق بين `main` و `v1.0.2`:** لا يوجد فرق، `main` يشير حالياً لنفس التزام `v1.0.2`.
*   **`composer.json`:** يعرف الحزمة بـ `maatify/shared-common`.
*   **الـ namespaces العامة:** `Maatify\SharedCommon\`
*   **الملفات والعقود العامة الفعلية داخل `src/`:**
    *   `ClockInterface` الفعلي متاح، ويقدم الـ signatures:
        *   `public function now(): DateTimeImmutable;`
        *   `public function getTimezone(): DateTimeZone;`
    *   `SystemClock` يطبق `ClockInterface`، ويقدم الـ signature:
        *   `public function __construct(DateTimeZone $timezone)`
        *   `public function now(): DateTimeImmutable`
        *   `public function getTimezone(): DateTimeZone`
    *   **البحث عن Frozen/Test Clock implementations:** `NOT FOUND` في `v1.0.2` (وهي نتيجة مستقلة لا تجعل ادعاء المعيار الحالي `PARTIALLY VERIFIED` لأن المعيار لا يدعي وجود Test Clock).
*   **دليل كل نتيجة:** تم التحقق من مجلد `src/Contracts/` و `src/Infrastructure/` في الإصدارات المستقرة.

### `maatify/persistence`
*   **اسم المستودع:** `Maatify/persistence`
*   **اسم Composer:** `maatify/persistence`
*   **Exact current default-branch SHA:** `5850dbea48e571eae644f1f490e137c8a4202d9d`
*   **أحدث Stable Tag:** `v1.1.0`
*   **Minimum Stable Version المدعوم بالأدلة:** Ordering API موجودة من `v1.0.0`، و Pagination API موجودة من `v1.1.0`.
*   **الفرق بين `main` و `v1.1.0`:** لا يوجد فرق، `main` يشير حالياً لنفس التزام `v1.1.0`.
*   **`composer.json`:** يعتمد على `maatify/exceptions` ولا يعتمد على `maatify/shared-common`.
*   **Public API Inventory الفعلي للمسارات والـ signatures:**
    *   `Maatify\Persistence\Pdo\Ordering\ScopedOrderingConfig::__construct(string $table, ?string $scopeColumn = null, string $idColumn = 'id', string $orderColumn = 'display_order', ?string $deletedAtColumn = 'deleted_at')`
    *   `Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager::getNextPosition(PDO $pdo, ScopedOrderingConfig $config, int|string|null $scopeValue): int`
    *   `Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager::rowExistsInScope(PDO $pdo, ScopedOrderingConfig $config, int|string|null $scopeValue, int $id): bool`
    *   `Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager::moveWithinScope(PDO $pdo, ScopedOrderingConfig $config, int|string|null $scopeValue, int $id, int $newOrder): bool`
    *   `Maatify\Persistence\Pdo\Pagination\PageRequest::__construct(int|string|null $page = null, int|string|null $perPage = null, ?string $sortBy = null, ?string $sortDirection = null)`
    *   `Maatify\Persistence\Pdo\Pagination\SortDirectionEnum`: `case ASC = 'ASC'; case DESC = 'DESC';`
    *   `Maatify\Persistence\Pdo\Pagination\SortWhitelist::__construct(array $sorts)`, `public function contains(string $key): bool`, `public function quotedIdentifierFor(string $key): string`
    *   `Maatify\Persistence\Pdo\Pagination\PaginationConfig::__construct(SortWhitelist $sortWhitelist, string $defaultSortBy, SortDirectionEnum $defaultSortDirection, string $tieBreakerSortBy, SortDirectionEnum $tieBreakerDirection, int $defaultPerPage = 20, int $minPerPage = 1, int $maxPerPage = 200)`
    *   `Maatify\Persistence\Pdo\Pagination\PdoPaginationQueryDescriptor::__construct(string $totalSql, array $totalParams, string $filteredCountSql, array $filteredCountParams, string $dataSql, array $dataParams)`
    *   `Maatify\Persistence\Pdo\Pagination\PageResult::__construct(array $data, int $page, int $perPage, int $total, int $filtered, int $totalPages, bool $hasNext, bool $hasPrevious, string $sortBy, SortDirectionEnum $sortDirection)`
    *   `Maatify\Persistence\Pdo\Pagination\PageResult::toArray(): array`
    *   `Maatify\Persistence\Pdo\Pagination\PageResult::jsonSerialize(): array`
    *   `Maatify\Persistence\Pdo\Pagination\PdoPaginator::paginate(PDO $pdo, PdoPaginationQueryDescriptor $query, PageRequest $request, PaginationConfig $config, callable $mapper): PageResult`
    *   استثناءات مشتركة في `Maatify\Persistence\Exception\` حيث يمثل `PersistenceException` واجهة (`interface extends \Throwable`). استثناءات الحزمة لا تمتد منه مباشرة كفئة، بل تمتد من base classes داخل `maatify/exceptions` مثل `SystemMaatifyException` و`ValidationMaatifyException` و`UnsupportedMaatifyException` وتطبق Marker interface `PersistenceException`.
*   **حدود Transaction boundaries الفعلية:**
    *   `getNextPosition()` لا يبدأ Transaction ولا يعمل Lock؛ الاستخدام المتزامن يحتاج caller-owned transaction/locking.
    *   `moveWithinScope()` يمتلك Transaction ويرفض التشغيل داخل active PDO transaction.
    *   `PdoPaginator` لا يمتلك Transaction ويمكنه العمل داخل caller-owned transaction دون تعديل حالتها.
*   **حدود سلوك `display_order` الفعلية:**
    *   يدعم global/scoped ordering.
    *   يدعم soft-delete filtering: `ScopedOrderingConfig` يحتوي `?string $deletedAtColumn = 'deleted_at'`، و`null` يعطل الفلترة. عمليات Ordering تتجاهل soft-deleted rows عندما يكون العمود مفعّلًا.
    *   القيم غير الموجبة تُرفض بـ `InvalidOrderingOperationException` قبل clamping.
    *   الـ clamping يطبق فقط على قيمة موجبة أعلى من `maxOrder`، ويصبح `min($newOrder, max(1, $maxOrder))` (clamping إلى أقصى position موجودة داخل الـ scope).
    *   `getNextPosition()` وحدها ترجع `MAX(order) + 1`.
    *   إذا لم يكن الهدف موجوداً (missing target) تعيد الـ method `false`.
    *   إذا كان التحرك no-op يعيد `true`.
    *   الـ shifts تكون للمدى المتأثر فقط.
    *   لا يقوم بتسوية gaps قديمة بصورة عامة.
*   **علاقة الحزمة الفعلية بـ ADR 0002 و `v1.1.0`:**
    *   الملف الفعلي: `Maatify/persistence/docs/adr/0002-ordering-hard-delete-compaction.md`
    *   حالته: `Accepted — Deferred`.
    *   Runtime status: غير منفذ ولا توجد Stable API.
    *   Release target: غير محدد.
    *   الـ ADR يثبت أن compaction ليست جزءًا من `v1.1.0` وأن `v1.1.0` تخص Pagination.

---

## 2. جدول Traceability لادعاءات PACKAGE_BUILDING_STANDARD.md

| الادعاء المزعوم / القسم في المعيار | النص الحالي في المعيار | الدليل الفعلي من المستودعات وأقل إصدار مستقر | التصنيف | الإجراء المقترح |
| :--- | :--- | :--- | :--- | :--- |
| وجود واستقرار hierarchy في `maatify/exceptions` | `Package exceptions must depend on maatify/exceptions` | المستودع موجود ويحتوي على Exceptions hierarchy كاملة ومستقرة؛ متوفرة من `v1.0.0` (بما فيها `ApiAwareExceptionInterface`). | `VERIFIED IN LATEST STABLE TAG` | Retain |
| وجود Clock و Date-Time contracts في `maatify/shared-common` | `Clock and date-time contracts must depend on maatify/shared-common` | `ClockInterface` و `SystemClock` موجودان من `v1.0.0`. غياب Frozen/Test clocks هو (NOT FOUND) لكنه لا يؤثر على الادعاء الأصلي. | `VERIFIED IN LATEST STABLE TAG` | `Replace with exact contract` أو `Convert to cross-reference` لتقليل العمومية. |
| وجود Ordering و Pagination API في `maatify/persistence` | `Packages that require reusable PDO row-position... or pagination capabilities must depend on maatify/persistence` | Ordering API متوفرة من `v1.0.0`، و Pagination متوفرة من `v1.1.0`. | `VERIFIED IN LATEST STABLE TAG` | Retain |
| حالة إصدار Pagination API المستقر | `After a stable maatify/persistence release publishes the Pagination API...` | Pagination منشورة بالفعل في stable tag `v1.1.0`. | `OUTDATED` | `Replace with exact stable availability/minimum version` |
| حوكمة الإصدار `v1.1.0` من `maatify/persistence` | `The deferred decision MUST NOT be treated as an expansion of the v1.1.0 Pagination scope` | ADR 0002 يثبت أن Compaction مؤجل ولا يوجد API مستقر له، وأن `v1.1.0` للـ Pagination فقط. | `VERIFIED IN LATEST STABLE TAG` | Retain |
| مرجع `PERSISTENCE_PACKAGE_REFERENCE.md` | `PERSISTENCE_PACKAGE_REFERENCE.md` | الملف موجود في جذر مستودع `maatify/persistence`. | `VERIFIED IN LATEST STABLE TAG` | `Convert to external cross-reference` (عند الإحالة من مستودع المعايير) |
| الإحالة إلى ADR 0002 | `../adr/0002-ordering-hard-delete-compaction.md` | رابط ADR نسبي يشير خطأ لمستودع المعايير بدلا من Persistence. | `NOT FOUND` (محلياً) | `Convert to external cross-reference` |

---

## 3. الخلاصة المطلوبة

1.  **جدول Traceability كامل لكل ادعاء:** موضح في القسم 2 أعلاه.
2.  **قائمة الادعاءات الآمنة للاعتماد (`VERIFIED IN LATEST STABLE TAG`):**
    *   أسماء الحزم، مع توضيح Minimum Stable Version لكل منها.
    *   الاعتماد على `maatify/exceptions` لوجود واجهة `ApiAwareExceptionInterface` والـ abstract base class.
    *   الاعتماد على `maatify/shared-common` لوجود `ClockInterface` و `SystemClock`.
    *   Ordering/Pagination APIs في `maatify/persistence` ومراعاة Minimum Stable Version.
    *   حوكمة Package Reference والـ ADR للـ scope.
3.  **قائمة الادعاءات الموجودة على `main` فقط:** لا يوجد.
4.  **قائمة الادعاءات الخاطئة أو القديمة (`OUTDATED` / `Convert to external cross-reference`):**
    *   `OUTDATED`: صيغة انتظار إصدار Pagination المستقر.
    *   `Replace with exact contract`: العبارة العامة الخاصة بـ Clock/Date-Time يمكن تحديدها لـ `ClockInterface` لزيادة الدقة.
    *   `Convert to external cross-reference`: رابط ADR 0002 واسم Package Reference عند الإحالة من standards repo.
5.  **التعديلات المطلوبة لاحقًا في `PACKAGE_BUILDING_STANDARD.md`:**
    *   تحديث صياغة Pagination من صيغة مستقبلية إلى توفر مستقر.
    *   تحديد Clock contract بـ `ClockInterface`.
    *   تعديل روابط الـ ADR والـ Package Reference لتكون external cross-references.
    *   ميز بوضوح بين `MaatifyException` كـ abstract base class داخل `maatify/exceptions`، `ApiAwareExceptionInterface` كـ contract عام، و `PersistenceException` كـ package marker interface داخل `maatify/persistence`.
    *   تصحيح تفاصيل Transaction limits لـ Ordering/Pagination.
6.  **هل External Verification الخاص بحزم Maatify:**
    *   **النتيجة النهائية:** `PARTIAL`
    *   (الـ packages والـ stable tags صحيحة، لكن توجد نصوص في `PACKAGE_BUILDING_STANDARD.md` تحتاج تحديثًا أو Cross-reference ولا يجوز تصنيفها كلها `Retain`).
