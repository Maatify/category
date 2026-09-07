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
- Language-code validation and language fallback remain Host responsibilities.
- Public and domain contracts use typed DTOs and collections, never associative
  arrays.

## Runtime API

The production namespace is `Maatify\Category\`.

### Status

`Maatify\Category\Enum\CategoryStatusEnum` is a string-backed enum with:

- `active`
- `inactive`

Status is independent from soft deletion.

### DTOs

The immutable record DTOs are:

- `CategoryDTO`
- `CategoryTranslationDTO`
- `CategoryCollectionDTO`
- `CategoryTranslationCollectionDTO`

Operation input DTOs are:

- `CategoryIdDTO`
- `CreateCategoryDTO`
- `MoveCategoryDTO`
- `SoftDeleteCategoryDTO`
- `RestoreCategoryDTO`
- `UpdateCategoryStatusDTO`
- `UpdateCategoryDisplayOrderDTO`
- `UpdateCategoryTranslationDTO`

DTOs validate their input/domain invariants. `CategoryDTO` and
`CategoryTranslationDTO` require canonical positive identities. A Category
cannot use itself as its parent. `UpdateCategoryTranslationDTO` accepts only
translation content, preserving the logical identity
`(category_id, language_code)`. Category mutation DTOs do not expose `code`, so
the stable Category code remains immutable after creation.

### Services and contracts

- `CategoryCommandServiceInterface` and `CategoryCommandService` own mutation
  orchestration for creation, parent movement, cycle prevention, soft delete,
  restore, status, display order, and translation content.
- `CategoryQueryServiceInterface` and `CategoryQueryService` expose visible
  identity and list reads.
- `CategoryCommandRepositoryInterface` is the Category write port.
- `CategoryTranslationCommandRepositoryInterface` is the translation-content
  write port.
- `CategoryQueryReaderInterface` is the mutation-support read port. Its
  `findById()` includes soft-deleted rows; `findActiveById()` excludes them;
  explicit `ForUpdate` methods lock rows inside the application transaction.
- `CategoryReadQueryInterface` is the dedicated visible query/read port and is
  separate from mutation-support reads.
- `CategoryTransactionInterface` defines the transaction boundary used by the
  application service.

Root and child lists are ordered by `display_order, id`. The package does not
add local pagination or language fallback.

## Business invariants

- Category `code` is immutable and unique among all stored identities.
- Category Translation logical identity `(category_id, language_code)` is
  immutable and unique.
- Parent movement rejects direct self-parenting and every indirect cycle,
  including `A → B → C → A`.
- Soft delete is rejected while a Category has non-deleted children.
- Restore reuses the same Category or Translation identity.
- Every mutation updates `updated_at` using the application `ClockInterface`.
- Hierarchy/lifecycle checks and writes execute inside a real transaction with
  the required row locks.

## Query visibility contract

Visible query methods:

- Read one Category by identity.
- List root Categories.
- List direct children by `parent_id`.
- Read Category Translations.

They exclude soft-deleted and inactive Categories. A descendant is hidden when
any ancestor in its complete parent path is inactive or soft-deleted. Query
methods return typed DTOs and do not select a language or apply fallback.

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

Display-order mutations consume the stable `maatify/persistence` Ordering API,
including nullable root scopes and atomic `updated_at` mutation. The package
does not implement a local ordering or pagination substitute.

Package-owned storage/hydration failures use the appropriate
`CategoryPersistenceException` hierarchy. An external `PDOException` is not
wrapped and propagates unchanged.

## Exceptions

The package marker is `CategoryExceptionInterface`. Named exceptions are used
for distinct failure semantics:

- `CategoryInvalidArgumentException`
- `CategoryNotFoundException`
- `CategoryTranslationNotFoundException`
- `CategoryCodeAlreadyExistsException`
- `CategoryCycleException`
- `CategoryHasNonDeletedChildrenException`
- `CategoryTransactionException`
- `CategoryPersistenceException`

They use the stable hierarchy from `maatify/exceptions`.

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
`CATEGORY_TEST_DB_USER`, and `CATEGORY_TEST_DB_PASSWORD`.

## Non-goals and deferred work

- Catalog, Product, Pricing, Inventory, and Media composition.
- HTTP/API routes, controllers, middleware, permissions, Twig, and JavaScript.
- Presentation serialization and response envelopes.
- Local pagination or host language fallback.
- A separate Catalog entity or Catalog identity.
