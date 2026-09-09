# Category Package Reference

`maatify/category` is the canonical, framework-neutral package for reusable
hierarchical categories, optional Category Content, and Category-owned Image
Assignments. This file is the package's
single stable contract reference. Detailed implementation notes belong under
`docs/` and must link back here.

## Scope and boundaries

The package owns Category, Category Content, and the direct Image Assignment
relationship behavior only. It does not
own Catalog identity, Product, Pricing, Inventory, Media, HTTP, framework
integration, permissions, presentation, or dependency-injection bindings.

The package is host-agnostic:

- Host-owned identities are accepted as validated scalar IDs and are never
  joined to or constrained by package-owned tables.
- Internal Category and Content relationships use package-owned foreign
  keys only.
- Image Assignments reference host-provided Media Asset identities without a
  Media, Platform, or Language foreign key or lifecycle dependency.
- Category owns the syntactic and storage validation of non-NULL `language_code`
  values required by its contract, including the constraints enforced by the
  Runtime.
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

## Domain Content model

Category is the structural entity. Its identity, stable `code`, hierarchy,
status, ordering, timestamps, and soft-deletion lifecycle are independent from
human-readable content; `name` and `description` are not copied into the
Category table.

Category Content is the single content persistence concept. Its
`(category_id, language_code)` identity supports both forms:

- `language_code = NULL` is ordinary, unlocalized Category Content.
- A non-NULL `language_code` is localized Content for that language.

The database enforces at most one unlocalized row per Category and at most one
row for each non-NULL language code. The Package applies only syntactic/storage
validation; the Host owns semantic language availability, fallback, and locale
policy. The Package performs no implicit fallback.

### Category Image Assignment model

Category owns a direct, soft-deletable assignment relation to an external
`mediaAssetId`. The immutable stable identity is
`(category_id, media_asset_id, language_code, platform)`, including
soft-deleted rows. `language_code` and `platform` are nullable exact scope
dimensions; all four combinations are supported, empty strings are invalid,
and no platform enum or fallback is defined. The same Media Asset may be used
in different scopes. Ordering is independent for each Category and exact scope.

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
- `CategoryContentDTO`
- `CategoryCollectionDTO`
- `CategoryContentCollectionDTO`
- `CategoryImageAssignmentScopeDTO`
- `CategoryImageAssignmentDTO`
- `CategoryImageAssignmentCollectionDTO`
- `CategoryListCriteriaDTO`
- `CategoryContentListCriteriaDTO`
- `CategoryImageAssignmentListCriteriaDTO`
- `CategoryVisibleListCriteriaDTO`

### Commands

- `CreateCategoryCommand`
- `MoveCategoryCommand`
- `SoftDeleteCategoryCommand`
- `RestoreCategoryCommand`
- `UpdateCategoryStatusCommand`
- `UpdateCategoryDisplayOrderCommand`
- `CreateCategoryContentCommand`
- `UpdateCategoryContentCommand`
- `SoftDeleteCategoryContentCommand`
- `RestoreCategoryContentCommand`
- `CreateCategoryImageAssignmentCommand`
- `UpdateCategoryImageAssignmentDisplayOrderCommand`
- `SoftDeleteCategoryImageAssignmentCommand`
- `RestoreCategoryImageAssignmentCommand`

Every public DTO and collection DTO is immutable and implements
`JsonSerializable`. Collections also retain typed `IteratorAggregate` behavior.
Date-time fields serialize as RFC 3339 strings; enum fields serialize using
their backing values.

Commands and DTOs validate their input/domain invariants. `CategoryDTO` and
`CategoryContentDTO` and `CategoryImageAssignmentDTO` require canonical positive
identities. A Category
cannot use itself as its parent. `UpdateCategoryContentCommand` accepts only
  content fields, preserving the logical identity
`(category_id, language_code)`. Category mutation Commands do not expose `code`, so
the stable Category code remains immutable after creation.

### Services and contracts

- `CategoryCommandServiceInterface` and `CategoryCommandService` own mutation
  orchestration for creation, content creation/content update/soft
  delete/restore, parent movement, cycle prevention, Category soft delete,
  restore, status, and display order, plus Image Assignment creation, exact
  scope ordering, soft deletion, and restoration.
- `CategoryQueryServiceInterface` and `CategoryQueryService` expose visible
  identity and list reads.
- `CategoryManagementQueryServiceInterface` and
  `CategoryManagementQueryService` expose management identity and list reads.
- `CategoryCommandRepositoryInterface` is the Category write port.
- `CategoryContentCommandRepositoryInterface` is the Content
  lifecycle write port.
- `CategoryImageAssignmentCommandRepositoryInterface` is the Image Assignment
  lifecycle and ordering write port.
- `CategoryQueryReaderInterface` is the mutation-support read port. Its
  `findById()` includes soft-deleted rows; `findActiveById()` excludes them;
  explicit `ForUpdate` methods lock Category, Content, and Image Assignment rows inside the
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

CreateCategoryContentCommand(string|int $categoryId, ?string $languageCode, string $name, ?string $description)
UpdateCategoryContentCommand(string|int $contentId, string $name, ?string $description)
SoftDeleteCategoryContentCommand(string|int $contentId)
RestoreCategoryContentCommand(string|int $contentId)

CreateCategoryImageAssignmentCommand(string|int $categoryId,
                                     string|int $mediaAssetId,
                                     ?string $languageCode = null,
                                     ?string $platform = null)
UpdateCategoryImageAssignmentDisplayOrderCommand(string|int $assignmentId,
                                                 int $displayOrder)
SoftDeleteCategoryImageAssignmentCommand(string|int $assignmentId)
RestoreCategoryImageAssignmentCommand(string|int $assignmentId)
```

Commands are `final readonly` and implement `JsonSerializable`. Category code,
content `categoryId`, and content `languageCode` are not mutable through
an update command.

#### DTOs and criteria constructors

```text
CategoryIdDTO(string|int $value, string $field = 'id')
CategoryDTO(int $id, ?int $parentId, string $code, CategoryStatusEnum $status,
            int $displayOrder, DateTimeImmutable $createdAt,
            DateTimeImmutable $updatedAt, ?DateTimeImmutable $deletedAt)
CategoryContentDTO(int $id, int $categoryId, ?string $languageCode,
                   string $name, ?string $description,
                   DateTimeImmutable $createdAt,
                   DateTimeImmutable $updatedAt,
                   ?DateTimeImmutable $deletedAt)
CategoryImageAssignmentScopeDTO(?string $languageCode = null,
                                ?string $platform = null)
CategoryImageAssignmentDTO(int $id, int $categoryId, int $mediaAssetId,
                           ?string $languageCode, ?string $platform,
                           int $displayOrder, DateTimeImmutable $createdAt,
                           DateTimeImmutable $updatedAt,
                           ?DateTimeImmutable $deletedAt)
CategoryCollectionDTO(array $items)
CategoryContentCollectionDTO(array $items)
CategoryImageAssignmentCollectionDTO(array $items)
CategoryListCriteriaDTO(?CategoryStatusEnum $status = null,
                        CategoryDeletedStateEnum $deletedState = NON_DELETED,
                        int $maxResults = 100)
CategoryContentListCriteriaDTO(?int $categoryId = null,
                                   CategoryDeletedStateEnum $deletedState = NON_DELETED,
                                   int $maxResults = 100)
CategoryVisibleListCriteriaDTO(int $maxResults = 100)
CategoryImageAssignmentListCriteriaDTO(?int $categoryId = null,
                                       ?CategoryImageAssignmentScopeDTO $scope = null,
                                       CategoryDeletedStateEnum $deletedState = NON_DELETED,
                                       int $maxResults = 100)
```

All DTOs and collections are `final readonly` and `JsonSerializable`;
collections also implement typed `IteratorAggregate` and `Countable`. The four
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
  createContent(CreateCategoryContentCommand): int
  createImageAssignment(CreateCategoryImageAssignmentCommand): int
  move(MoveCategoryCommand): void
  softDelete(SoftDeleteCategoryCommand): void
  restore(RestoreCategoryCommand): void
  updateStatus(UpdateCategoryStatusCommand): void
  updateDisplayOrder(UpdateCategoryDisplayOrderCommand): void
  updateContent(UpdateCategoryContentCommand): void
  softDeleteContent(SoftDeleteCategoryContentCommand): void
  restoreContent(RestoreCategoryContentCommand): void
  updateImageAssignmentDisplayOrder(UpdateCategoryImageAssignmentDisplayOrderCommand): void
  softDeleteImageAssignment(SoftDeleteCategoryImageAssignmentCommand): void
  restoreImageAssignment(RestoreCategoryImageAssignmentCommand): void

CategoryQueryServiceInterface
  getById(int): CategoryDTO
  listRootCategories(CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO()): CategoryCollectionDTO
  listChildren(int $parentId, CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO()): CategoryCollectionDTO
  listContents(int $categoryId, CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO()): CategoryContentCollectionDTO
  listImageAssignments(int $categoryId, CategoryImageAssignmentScopeDTO $scope, CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO()): CategoryImageAssignmentCollectionDTO

CategoryManagementQueryServiceInterface
  getById(int, CategoryDeletedStateEnum = NON_DELETED): CategoryDTO
  listCategories(CategoryListCriteriaDTO): CategoryCollectionDTO
  listRootCategories(CategoryListCriteriaDTO): CategoryCollectionDTO
  listChildren(int, CategoryListCriteriaDTO): CategoryCollectionDTO
  getContentById(int, CategoryDeletedStateEnum = NON_DELETED): CategoryContentDTO
  listContents(CategoryContentListCriteriaDTO): CategoryContentCollectionDTO
  getImageAssignmentById(int, CategoryDeletedStateEnum = NON_DELETED): CategoryImageAssignmentDTO
  listImageAssignments(CategoryImageAssignmentListCriteriaDTO): CategoryImageAssignmentCollectionDTO

CategoryCommandRepositoryInterface
  create(CreateCategoryCommand, DateTimeImmutable): int
  move(MoveCategoryCommand, DateTimeImmutable): bool
  softDelete(SoftDeleteCategoryCommand, DateTimeImmutable): bool
  restore(RestoreCategoryCommand, DateTimeImmutable): bool
  updateStatus(UpdateCategoryStatusCommand, DateTimeImmutable): bool
  updateDisplayOrder(UpdateCategoryDisplayOrderCommand, DateTimeImmutable): bool

CategoryContentCommandRepositoryInterface
  create(CreateCategoryContentCommand, DateTimeImmutable): int
  update(UpdateCategoryContentCommand, DateTimeImmutable): bool
  softDelete(SoftDeleteCategoryContentCommand, DateTimeImmutable): bool
  restore(RestoreCategoryContentCommand, DateTimeImmutable): bool

CategoryImageAssignmentCommandRepositoryInterface
  create(CreateCategoryImageAssignmentCommand, DateTimeImmutable): int
  updateDisplayOrder(UpdateCategoryImageAssignmentDisplayOrderCommand, DateTimeImmutable): bool
  softDelete(SoftDeleteCategoryImageAssignmentCommand, DateTimeImmutable): bool
  restore(RestoreCategoryImageAssignmentCommand, DateTimeImmutable): bool

CategoryReadQueryInterface
  findVisibleById(int): ?CategoryDTO
  listVisibleRootCategories(CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO()): CategoryCollectionDTO
  listVisibleChildren(int $parentId, CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO()): CategoryCollectionDTO
  listVisibleContents(int $categoryId, CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO()): CategoryContentCollectionDTO
  listVisibleImageAssignments(int $categoryId, CategoryImageAssignmentScopeDTO $scope, CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO()): CategoryImageAssignmentCollectionDTO

CategoryManagementReadQueryInterface
  findById(int, CategoryDeletedStateEnum): ?CategoryDTO
  listCategories(CategoryListCriteriaDTO): CategoryCollectionDTO
  listRootCategories(CategoryListCriteriaDTO): CategoryCollectionDTO
  listChildren(int, CategoryListCriteriaDTO): CategoryCollectionDTO
  findContentById(int, CategoryDeletedStateEnum): ?CategoryContentDTO
  listContents(CategoryContentListCriteriaDTO): CategoryContentCollectionDTO
  findImageAssignmentById(int, CategoryDeletedStateEnum): ?CategoryImageAssignmentDTO
  listImageAssignments(CategoryImageAssignmentListCriteriaDTO): CategoryImageAssignmentCollectionDTO

CategoryQueryReaderInterface [internal mutation-support port]
  findById(int): ?CategoryDTO
  findByCode(string): ?CategoryDTO
  findActiveById(int): ?CategoryDTO
  findActiveByIdForUpdate(int): ?CategoryDTO
  findByIdForUpdate(int): ?CategoryDTO
  hasNonDeletedChildrenForUpdate(int): bool
  findContentById(int): ?CategoryContentDTO
  findContentByIdForUpdate(int): ?CategoryContentDTO
  findImageAssignmentById(int): ?CategoryImageAssignmentDTO
  findImageAssignmentByIdForUpdate(int): ?CategoryImageAssignmentDTO

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
                       CategoryContentCommandRepositoryInterface,
                       CategoryImageAssignmentCommandRepositoryInterface,
                       CategoryTransactionInterface,
                       ClockInterface)
CategoryQueryService(CategoryReadQueryInterface)
CategoryManagementQueryService(CategoryManagementReadQueryInterface)

PdoCategoryCommandRepository(PDO, ScopedOrderingManager)
PdoCategoryContentCommandRepository(PDO)
PdoCategoryImageAssignmentCommandRepository(PDO, ScopedOrderingManager)
PdoCategoryQueryReader(PDO)
PdoCategoryReadQuery(PDO)
PdoCategoryManagementReadQuery(PDO)
PdoCategoryTransaction(PDO)
```

The concrete adapters implement the public contracts listed above and contain
no Host framework/container bindings.

Management Category lists accept `CategoryListCriteriaDTO`, apply an optional
status filter and an explicit `CategoryDeletedStateEnum`, and are bounded to
at most 100 rows per call. Management Content lists accept
`CategoryContentListCriteriaDTO`, optionally filter by Category, apply an
explicit deleted state, and use the same bound. Category lists are ordered by
`display_order, id`; Content lists are ordered by `language_code, id`.
The package does not add local pagination, search, or language fallback. Content
collections may contain the single NULL-language row together with zero or more
language-specific rows; Category queries never join an unrestricted Content
collection in a way that multiplies Category rows.
Management Image Assignment lists accept `CategoryImageAssignmentListCriteriaDTO`,
apply exact nullable scope predicates only when a scope object is supplied,
and are ordered by Category, exact scope, `display_order, id`. Visible Image
Assignment lists require an exact `CategoryImageAssignmentScopeDTO`, exclude
deleted rows, and apply complete ancestor visibility with no fallback.
Consumer Category lists use the same maximum of 100 through their separate
criteria DTO; root and child lists use `display_order, id`, and Content
lists use `language_code, id`. The bound is applied by the persistence query
with a typed integer parameter; pagination and search remain deferred.

### Content parent-state contract

The current Runtime does not couple Content lifecycle mutations to the
parent Category's status after the required parent-existence check. The
mutation-support names `findActiveById()` and `findActiveByIdForUpdate()` mean
non-deleted Category lifecycle state; they do not mean
`CategoryStatusEnum::ACTIVE`.

- `createContent()` requires the Category to exist and have
  `deleted_at IS NULL`. A Category with status `INACTIVE` is valid; a
  soft-deleted Category is rejected.
- `updateContent()` depends on the Content's own non-deleted lifecycle
  and is allowed when the parent Category is inactive or soft-deleted.
- `softDeleteContent()` depends on the Content's own non-deleted
  lifecycle and is allowed when the parent Category is inactive or
  soft-deleted.
- `restoreContent()` depends on the Content row existing in its
  soft-deleted lifecycle and is allowed when the parent Category is inactive
  or soft-deleted.

These semantics are proven against the real MySQL schema by
`CategoryPdoIntegrationTest::testContentMutationsFollowParentLifecycleStateContractOnMySql`.
They are the v1 contract; no parent-state redesign is implied.

## Business invariants

- Category `code` is immutable and unique among all stored identities.
- Category Content logical identity `(category_id, language_code)` is
  immutable and unique, including the database-enforced single NULL-language
  identity per Category.
- Content creation rejects an existing identity, including a soft-deleted
  row; restoration reuses that same identity.
- Parent movement rejects direct self-parenting and every indirect cycle,
  including `A → B → C → A`.
- Soft delete is rejected while a Category has non-deleted children.
- Image Assignment identity `(category_id, media_asset_id, language_code,
  platform)` is immutable and unique, including soft-deleted rows; the same
  Media Asset may be assigned in other exact scopes.
- Image Assignment ordering is independent per Category and exact scope.
- Restore reuses the same Category or Content identity.
- Every mutation updates `updated_at` using the application `ClockInterface`.
- Hierarchy/lifecycle checks and writes execute inside a real transaction with
  the required row locks.

## Query visibility contract

Management query methods expose stored Category and Content state for
management/use-case consumers. They do not apply consumer ancestor visibility
rules. Management reads provide Category get-by-ID, bounded all/root/child
lists, Content get-by-ID, and bounded Content lists. Deleted records
are returned only when the caller explicitly selects `include_deleted` or
`deleted_only`; management get-by-code, search, and pagination are not part of
the v1 public contract.

Visible query methods:

- Read one Category by identity.
- List root Categories.
- List direct children by `parent_id`.
- Read Category Contents.
- Read Image Assignments for an exact language/platform scope.

They exclude soft-deleted and inactive Categories. A descendant is hidden when
any ancestor in its complete parent path is inactive or soft-deleted. Query
methods return typed DTOs and do not select a language or apply fallback. Their
criteria cannot opt out of inactive/deleted filtering.
Image Assignment reads also exclude soft-deleted assignments and never fall
back between exact scopes. Management `scope = null` means no scope filter;
`new CategoryImageAssignmentScopeDTO()` means exact NULL/NULL scope.

## Persistence contract

The canonical schema is [`schema/category.sql`](schema/category.sql). It owns
exactly three tables:

- `maa_category_categories`
- `maa_category_category_contents`
- `maa_category_category_image_assignments`

The schema uses InnoDB, `utf8mb4`, `ON DELETE RESTRICT`, `ON UPDATE RESTRICT`,
stable unique keys, status/language/platform `CHECK` enforcement, and
package-owned self-parent triggers. A stored generated language identity maps
NULL to one uniqueness value, so MySQL enforces both the single unlocalized
row and the per-language uniqueness. MySQL 8.0.16 or later is required because
earlier MySQL 8 releases accepted but did not enforce `CHECK` constraints.

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
- `CategoryContentNotFoundException`
- `CategoryContentAlreadyExistsException`
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
  `CategoryContentNotFoundException::withId()`.
- `CategoryCodeAlreadyExistsException::withCode()` and
  `CategoryContentAlreadyExistsException::withIdentity()`.
- `CategoryCycleException::forMove()` and
  `CategoryHasNonDeletedChildrenException::withId()`.
- `CategoryPersistenceException::queryFailed()`,
  `invalidAutoIncrementIdentity()`, `invalidContentAutoIncrementIdentity()`,
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
