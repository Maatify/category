# Category Package Reference

`maatify/category` is the canonical, framework-neutral package for reusable
hierarchical categories and category translations. This file is the package's
single stable contract reference. Detailed implementation notes belong under
`docs/` and must link back here.

## Scope and boundaries

The package owns Category and Category Translation behavior only. It does not
own Catalog identity, Product, Pricing, Inventory, Media, HTTP, framework
integration, permissions, presentation, or dependency-injection bindings.

The package is host-agnostic:

- Host-owned identities are accepted as validated scalar IDs and are never
  joined to or constrained by package-owned tables.
- Internal Category and Translation relationships use package-owned foreign
  keys only.
- Category owns the syntactic and storage validation of `language_code` required
  by its contract, including the constraints enforced by the Runtime.
- The Host owns semantic language validation, such as confirming that a language
  is supported or known, together with fallback and locale policy.
- Public read contracts use typed DTOs and collections, while mutation
  contracts use typed Commands; neither uses associative arrays.

## Current standards provenance

The current normative standards adoption is the repository-local selective
pinning recorded in [`docs/php-engineering-standards/STANDARDS_MANIFEST.md`](docs/php-engineering-standards/STANDARDS_MANIFEST.md).
Its exact adoption commit is `f386948aa873fef9960680411c8918d095d29b93`.
The manifest, not a floating upstream branch or a historical roadmap claim,
resolves the active and inherited profiles for this package.

## Standards Applicability Decision

The Category domain is translation-only. The base Category table does not own a
localized `name`; localized `name` and `description` values are owned by
Category Translation rows.

The logical identity of a translation is `(category_id, language_code)`. The
Host remains responsible for semantic language validation and fallback
behavior.

The Translation-only architecture is part of the current Domain contract and
is not described as a local exception. Earlier standards SHAs that may appear
in historical roadmap evidence are provenance only; they are not the current
normative adoption for this package.

## Runtime API

The production namespace is `Maatify\Category\`.

### Status

`Maatify\Category\Enum\CategoryStatusEnum` is a string-backed enum with:

- `active`
- `inactive`

Status is independent from soft deletion.

`Maatify\Category\Enum\CategoryDeletedStateEnum` explicitly selects
`non_deleted`, `include_deleted`, or `deleted_only` for management reads.

### DTOs

The immutable record DTOs are:

- `CategoryIdDTO`
- `CategoryDTO`
- `CategoryTranslationDTO`
- `CategoryCollectionDTO`
- `CategoryTranslationCollectionDTO`
- `CategoryListCriteriaDTO`
- `CategoryTranslationListCriteriaDTO`
- `CategoryVisibleListCriteriaDTO`

### Commands

- `CreateCategoryCommand`
- `MoveCategoryCommand`
- `SoftDeleteCategoryCommand`
- `RestoreCategoryCommand`
- `UpdateCategoryStatusCommand`
- `UpdateCategoryDisplayOrderCommand`
- `CreateCategoryTranslationCommand`
- `UpdateCategoryTranslationCommand`
- `SoftDeleteCategoryTranslationCommand`
- `RestoreCategoryTranslationCommand`

Every public DTO and collection DTO is immutable and implements
`JsonSerializable`. Collections also retain typed `IteratorAggregate` behavior.
Date-time fields serialize as RFC 3339 strings; enum fields serialize using
their backing values.

Commands and DTOs validate their input/domain invariants. `CategoryDTO` and
`CategoryTranslationDTO` require canonical positive identities. A Category
cannot use itself as its parent. `UpdateCategoryTranslationCommand` accepts only
translation content, preserving the logical identity
`(category_id, language_code)`. Category mutation Commands do not expose `code`, so
the stable Category code remains immutable after creation.

### Services and contracts

- `CategoryCommandServiceInterface` and `CategoryCommandService` own mutation
  orchestration for creation, translation creation/content update/soft
  delete/restore, parent movement, cycle prevention, Category soft delete,
  restore, status, and display order.
- `CategoryQueryServiceInterface` and `CategoryQueryService` expose visible
  identity and list reads.
- `CategoryManagementQueryServiceInterface` and
  `CategoryManagementQueryService` expose management identity and list reads.
- `CategoryCommandRepositoryInterface` is the Category write port.
- `CategoryTranslationCommandRepositoryInterface` is the Translation
  lifecycle write port.
- `CategoryQueryReaderInterface` is the mutation-support read port. Its
  `findById()` includes soft-deleted rows; `findActiveById()` excludes them;
  explicit `ForUpdate` methods lock Category and Translation rows inside the
  application transaction.
- `CategoryReadQueryInterface` is the dedicated visible query/read port and is
  separate from mutation-support reads.
- Consumer visibility list methods accept only the typed
  `CategoryVisibleListCriteriaDTO`, which bounds each call to 1–100 rows. It
  exposes no status or deleted-state override, preserving consumer visibility
  semantics and the complete ancestor rule.
- `CategoryManagementReadQueryInterface` is the dedicated management read port
  and is separate from both mutation-support reads and consumer visibility
  reads.
- `CategoryTransactionInterface` defines the transaction boundary used by the
  application service.

### Complete public runtime inventory

The following inventory is generated from the current `src/` tree and is the
stable v1 API surface. Concrete PDO adapters are public host-wiring classes;
their public methods implement the corresponding contracts below.

#### Commands and constructors

```text
CreateCategoryCommand(string $code, string|int|null $parentId = null, CategoryStatusEnum $status = ACTIVE)
MoveCategoryCommand(string|int $categoryId, string|int|null $parentId)
UpdateCategoryStatusCommand(string|int $categoryId, CategoryStatusEnum $status)
UpdateCategoryDisplayOrderCommand(string|int $categoryId, int $displayOrder)
SoftDeleteCategoryCommand(string|int $categoryId)
RestoreCategoryCommand(string|int $categoryId)

CreateCategoryTranslationCommand(string|int $categoryId, string $languageCode, string $name, ?string $description)
UpdateCategoryTranslationCommand(string|int $translationId, string $name, ?string $description)
SoftDeleteCategoryTranslationCommand(string|int $translationId)
RestoreCategoryTranslationCommand(string|int $translationId)
```

Commands are `final readonly` and implement `JsonSerializable`. Category code,
translation `categoryId`, and translation `languageCode` are not mutable through
an update command.

#### DTOs and criteria constructors

```text
CategoryIdDTO(string|int $value, string $field = 'id')
CategoryDTO(int $id, ?int $parentId, string $code, CategoryStatusEnum $status,
            int $displayOrder, DateTimeImmutable $createdAt,
            DateTimeImmutable $updatedAt, ?DateTimeImmutable $deletedAt)
CategoryTranslationDTO(int $id, int $categoryId, string $languageCode,
                       string $name, ?string $description,
                       DateTimeImmutable $createdAt,
                       DateTimeImmutable $updatedAt,
                       ?DateTimeImmutable $deletedAt)
CategoryCollectionDTO(array $items)
CategoryTranslationCollectionDTO(array $items)
CategoryListCriteriaDTO(?CategoryStatusEnum $status = null,
                        CategoryDeletedStateEnum $deletedState = NON_DELETED,
                        int $maxResults = 100)
CategoryTranslationListCriteriaDTO(?int $categoryId = null,
                                   CategoryDeletedStateEnum $deletedState = NON_DELETED,
                                   int $maxResults = 100)
CategoryVisibleListCriteriaDTO(int $maxResults = 100)
```

All DTOs and collections are `final readonly` and `JsonSerializable`;
collections also implement typed `IteratorAggregate` and `Countable`. The three
criteria DTOs reject limits outside `1..100`.

#### Enums

```text
CategoryStatusEnum: ACTIVE = 'active', INACTIVE = 'inactive'
CategoryDeletedStateEnum: NON_DELETED = 'non_deleted',
                          INCLUDE_DELETED = 'include_deleted',
                          DELETED_ONLY = 'deleted_only'
```

#### Public contracts and method signatures

```text
CategoryCommandServiceInterface
  create(CreateCategoryCommand): int
  createTranslation(CreateCategoryTranslationCommand): int
  move(MoveCategoryCommand): void
  softDelete(SoftDeleteCategoryCommand): void
  restore(RestoreCategoryCommand): void
  updateStatus(UpdateCategoryStatusCommand): void
  updateDisplayOrder(UpdateCategoryDisplayOrderCommand): void
  updateTranslation(UpdateCategoryTranslationCommand): void
  softDeleteTranslation(SoftDeleteCategoryTranslationCommand): void
  restoreTranslation(RestoreCategoryTranslationCommand): void

CategoryQueryServiceInterface
  getById(int): CategoryDTO
  listRootCategories(CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO()): CategoryCollectionDTO
  listChildren(int $parentId, CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO()): CategoryCollectionDTO
  listTranslations(int $categoryId, CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO()): CategoryTranslationCollectionDTO

CategoryManagementQueryServiceInterface
  getById(int, CategoryDeletedStateEnum = NON_DELETED): CategoryDTO
  listCategories(CategoryListCriteriaDTO): CategoryCollectionDTO
  listRootCategories(CategoryListCriteriaDTO): CategoryCollectionDTO
  listChildren(int, CategoryListCriteriaDTO): CategoryCollectionDTO
  getTranslationById(int, CategoryDeletedStateEnum = NON_DELETED): CategoryTranslationDTO
  listTranslations(CategoryTranslationListCriteriaDTO): CategoryTranslationCollectionDTO

CategoryCommandRepositoryInterface
  create(CreateCategoryCommand, DateTimeImmutable): int
  move(MoveCategoryCommand, DateTimeImmutable): bool
  softDelete(SoftDeleteCategoryCommand, DateTimeImmutable): bool
  restore(RestoreCategoryCommand, DateTimeImmutable): bool
  updateStatus(UpdateCategoryStatusCommand, DateTimeImmutable): bool
  updateDisplayOrder(UpdateCategoryDisplayOrderCommand, DateTimeImmutable): bool

CategoryTranslationCommandRepositoryInterface
  create(CreateCategoryTranslationCommand, DateTimeImmutable): int
  update(UpdateCategoryTranslationCommand, DateTimeImmutable): bool
  softDelete(SoftDeleteCategoryTranslationCommand, DateTimeImmutable): bool
  restore(RestoreCategoryTranslationCommand, DateTimeImmutable): bool

CategoryReadQueryInterface
  findVisibleById(int): ?CategoryDTO
  listVisibleRootCategories(CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO()): CategoryCollectionDTO
  listVisibleChildren(int $parentId, CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO()): CategoryCollectionDTO
  listVisibleTranslations(int $categoryId, CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO()): CategoryTranslationCollectionDTO

CategoryManagementReadQueryInterface
  findById(int, CategoryDeletedStateEnum): ?CategoryDTO
  listCategories(CategoryListCriteriaDTO): CategoryCollectionDTO
  listRootCategories(CategoryListCriteriaDTO): CategoryCollectionDTO
  listChildren(int, CategoryListCriteriaDTO): CategoryCollectionDTO
  findTranslationById(int, CategoryDeletedStateEnum): ?CategoryTranslationDTO
  listTranslations(CategoryTranslationListCriteriaDTO): CategoryTranslationCollectionDTO

CategoryQueryReaderInterface [internal mutation-support port]
  findById(int): ?CategoryDTO
  findByCode(string): ?CategoryDTO
  findActiveById(int): ?CategoryDTO
  findActiveByIdForUpdate(int): ?CategoryDTO
  findByIdForUpdate(int): ?CategoryDTO
  hasNonDeletedChildrenForUpdate(int): bool
  findTranslationById(int): ?CategoryTranslationDTO
  findTranslationByIdForUpdate(int): ?CategoryTranslationDTO

CategoryTransactionInterface
  run(Closure): mixed
```

`findByCode()` is intentionally present only on the internal
mutation-support port. It is not a public management read, service method, or
v1 get-by-code contract.

#### Services and PDO adapters

```text
CategoryCommandService(CategoryCommandRepositoryInterface,
                       CategoryQueryReaderInterface,
                       CategoryTranslationCommandRepositoryInterface,
                       CategoryTransactionInterface,
                       ClockInterface)
CategoryQueryService(CategoryReadQueryInterface)
CategoryManagementQueryService(CategoryManagementReadQueryInterface)

PdoCategoryCommandRepository(PDO, ScopedOrderingManager)
PdoCategoryTranslationCommandRepository(PDO)
PdoCategoryQueryReader(PDO)
PdoCategoryReadQuery(PDO)
PdoCategoryManagementReadQuery(PDO)
PdoCategoryTransaction(PDO)
```

The concrete adapters implement the public contracts listed above and contain
no Host framework/container bindings.

Management Category lists accept `CategoryListCriteriaDTO`, apply an optional
status filter and an explicit `CategoryDeletedStateEnum`, and are bounded to
at most 100 rows per call. Management Translation lists accept
`CategoryTranslationListCriteriaDTO`, optionally filter by Category, apply an
explicit deleted state, and use the same bound. Category lists are ordered by
`display_order, id`; Translation lists are ordered by `language_code, id`.
The package does not add local pagination, search, or language fallback.
Consumer Category lists use the same maximum of 100 through their separate
criteria DTO; root and child lists use `display_order, id`, and Translation
lists use `language_code, id`. The bound is applied by the persistence query
with a typed integer parameter; pagination and search remain deferred.

### Translation parent-state contract

The current Runtime does not couple Translation lifecycle mutations to the
parent Category's status after the required parent-existence check. The
mutation-support names `findActiveById()` and `findActiveByIdForUpdate()` mean
non-deleted Category lifecycle state; they do not mean
`CategoryStatusEnum::ACTIVE`.

- `createTranslation()` requires the Category to exist and have
  `deleted_at IS NULL`. A Category with status `INACTIVE` is valid; a
  soft-deleted Category is rejected.
- `updateTranslation()` depends on the Translation's own non-deleted lifecycle
  and is allowed when the parent Category is inactive or soft-deleted.
- `softDeleteTranslation()` depends on the Translation's own non-deleted
  lifecycle and is allowed when the parent Category is inactive or
  soft-deleted.
- `restoreTranslation()` depends on the Translation row existing in its
  soft-deleted lifecycle and is allowed when the parent Category is inactive
  or soft-deleted.

These semantics are proven against the real MySQL schema by
`CategoryPdoIntegrationTest::testTranslationMutationsFollowParentLifecycleStateContractOnMySql`.
They are the v1 contract; no parent-state redesign is implied.

## Business invariants

- Category `code` is immutable and unique among all stored identities.
- Category Translation logical identity `(category_id, language_code)` is
  immutable and unique.
- Translation creation rejects an existing identity, including a soft-deleted
  row; restoration reuses that same identity.
- Parent movement rejects direct self-parenting and every indirect cycle,
  including `A → B → C → A`.
- Soft delete is rejected while a Category has non-deleted children.
- Restore reuses the same Category or Translation identity.
- Every mutation updates `updated_at` using the application `ClockInterface`.
- Hierarchy/lifecycle checks and writes execute inside a real transaction with
  the required row locks.

## Query visibility contract

Management query methods expose stored Category and Translation state for
management/use-case consumers. They do not apply consumer ancestor visibility
rules. Management reads provide Category get-by-ID, bounded all/root/child
lists, Translation get-by-ID, and bounded Translation lists. Deleted records
are returned only when the caller explicitly selects `include_deleted` or
`deleted_only`; management get-by-code, search, and pagination are not part of
the v1 public contract.

Visible query methods:

- Read one Category by identity.
- List root Categories.
- List direct children by `parent_id`.
- Read Category Translations.

They exclude soft-deleted and inactive Categories. A descendant is hidden when
any ancestor in its complete parent path is inactive or soft-deleted. Query
methods return typed DTOs and do not select a language or apply fallback. Their
criteria cannot opt out of inactive/deleted filtering.

## Persistence contract

The canonical schema is [`schema/category.sql`](schema/category.sql). It owns
exactly two tables:

- `maa_category_categories`
- `maa_category_category_translations`

The schema uses InnoDB, `utf8mb4`, `ON DELETE RESTRICT`, `ON UPDATE RESTRICT`,
stable unique keys, status `CHECK` enforcement, and package-owned self-parent
triggers. MySQL 8.0.16 or later is required because earlier MySQL 8 releases
accepted but did not enforce `CHECK` constraints.

Timestamps are application-managed UTC values. PDO repositories persist the
supplied values and do not own time generation.

Display-order creation and mutations consume the stable `maatify/persistence`
Ordering API, including nullable root scopes and atomic `updated_at` mutation.
Creation locks the target scope inside the package transaction before asking
the API for `MAX(display_order) + 1`. The package does not implement a local
ordering or pagination substitute.

Package-owned storage/hydration failures use the appropriate
`CategoryPersistenceException` hierarchy. An external `PDOException` is not
wrapped and propagates unchanged.

## Composer and platform contract

The package is `maatify/category`, type `library`, under the
`Maatify\Category\` PSR-4 namespace. Its direct runtime requirements are PHP
`^8.4`, `ext-mbstring`, `ext-pdo`, `ext-pdo_mysql`, `maatify/exceptions:^1.0`,
`maatify/persistence:^1.2.0`, and `maatify/shared-common:^1.0`. Development
tools are declared separately in `require-dev`; the reusable library does not
commit `composer.lock`.

The package's storage contract is MySQL `8.0.16+` with InnoDB, `utf8mb4`, and
enforced `CHECK` constraints.

## Exceptions

The package marker is `CategoryExceptionInterface`. Named exceptions are used
for distinct failure semantics:

- `CategoryInvalidArgumentException`
- `CategoryNotFoundException`
- `CategoryTranslationNotFoundException`
- `CategoryTranslationAlreadyExistsException`
- `CategoryCodeAlreadyExistsException`
- `CategoryCycleException`
- `CategoryHasNonDeletedChildrenException`
- `CategoryTransactionException`
- `CategoryPersistenceException`

They use the stable hierarchy from `maatify/exceptions`.

Named factories exposed by the package are:

- `CategoryInvalidArgumentException::emptyField()`, `fieldTooLong()`,
  `invalidId()`, `nonPositiveId()`, `invalidDisplayOrder()`, `selfParent()`,
  and `invalidListLimit()`.
- `CategoryNotFoundException::withId()` and
  `CategoryTranslationNotFoundException::withId()`.
- `CategoryCodeAlreadyExistsException::withCode()` and
  `CategoryTranslationAlreadyExistsException::withIdentity()`.
- `CategoryCycleException::forMove()` and
  `CategoryHasNonDeletedChildrenException::withId()`.
- `CategoryPersistenceException::queryFailed()`,
  `invalidAutoIncrementIdentity()`, `invalidTranslationAutoIncrementIdentity()`,
  `invalidStorageValue()`, and `unexpectedColumnType()`.
- `CategoryTransactionException::alreadyActive()`.

## Verification contract

The package must pass, where applicable:

- `composer validate --strict`
- latest-compatible and lowest-supported dependency resolution
- `composer check-platform-reqs`
- fail-closed `composer audit --abandoned=fail`
- PHP syntax validation
- PHPStan level max with zero errors
- Unit tests
- Full PHPUnit suite
- Real MySQL Integration tests with cleanup and repeatability coverage
- Workflow syntax validation

The integration suite is configured with `CATEGORY_TEST_DSN`,
`CATEGORY_TEST_DB_USER`, and `CATEGORY_TEST_DB_PASSWORD`. For local runs,
copy `env.testing.example` to the ignored `env.testing`; the PHPUnit bootstrap
loads those values as defaults and preserves any externally injected values,
including CI's isolated MySQL configuration.

## Non-goals and deferred work

- Catalog, Product, Pricing, Inventory, and Media composition.
- HTTP/API routes, controllers, middleware, permissions, Twig, and JavaScript.
- Presentation serialization and response envelopes.
- Local pagination or host language fallback.
- Management search and public management get-by-code.
- A separate Catalog entity or Catalog identity.
