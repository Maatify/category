# Category Package Architecture

The canonical stable contract is [CATEGORY_PACKAGE_REFERENCE.md](../../CATEGORY_PACKAGE_REFERENCE.md).

Normative standards provenance is resolved by
[`docs/php-engineering-standards/STANDARDS_MANIFEST.md`](../php-engineering-standards/STANDARDS_MANIFEST.md),
currently pinned to adoption commit `f386948aa873fef9960680411c8918d095d29b93`.

## Purpose

`maatify/category` is a reusable Base Module for hierarchical Categories,
optional Category Content, extensible Host-defined Category Content Fields, a
Category-owned Image Role registry, and Category-owned Image Assignments. It can be consumed by a Catalog, a navigation system,
an access taxonomy, or another Host without knowing the Host's framework or
schema.

## Ownership

The package owns:

- Category, Content, Image Role, Image Assignment, and Content Field DTOs, typed mutation Commands, and input validation.
- Category mutation/query contracts.
- Business orchestration and domain exceptions.
- Package-local PDO persistence adapters and transaction orchestration through
  the shared Persistence transaction contract; the Host owns transaction
  wiring and outer transaction boundaries.
- The five package-owned MySQL tables and their internal constraints.

The Package owns the syntactic and storage validation of non-NULL
`language_code` values required by its contract and Runtime. The Host owns dependency injection, HTTP,
permissions, semantic language validation, fallback/locale policy, presentation
serialization, and relationships to Host-owned tables.

## Domain model

Categories form a tree through nullable `parent_id`. A `NULL` parent identifies
a root. `code` is immutable after creation and remains unique across soft
deletion. Category is structural and does not store `name` or `description`.
Content is identified logically by `(category_id, language_code)`; a NULL
language code means unlocalized Content, a non-NULL code means localized
Content, and content may change while that identity cannot.

Category Image Roles are package-owned registry records with immutable,
globally unique `role_key` values and typed `active`/`inactive` status. Keys
remain reserved after soft deletion, and Role semantics/cardinality remain
Host-owned.

Category Image Assignments are direct Category-owned references to a host
Media Asset identity. Their immutable logical identity is
`(category_id, media_asset_id, role_id, language_code, platform)`. `role_id`,
`language_code`, and `platform` are independent nullable exact scope dimensions,
so every combination is supported; NULL Role is the generic/unclassified
scope. New role-scoped assignments require an active, non-deleted Role, while
inactive/deleted Roles hide existing assignments from consumer reads. No
fallback or hardcoded platform enum is defined. Category does not own the Media
Asset, Language, or Platform lifecycle and creates no foreign key to those host
concepts.

Category Content Fields are arbitrary Host-defined key/value records. Their
immutable logical identity is `(category_id, field_key, language_code, platform)`
and their exact scope supports NULL/NULL, language/NULL, NULL/platform, and
language/platform without fallback. Category stores exact lowercase `text`,
`html`, and `json` values in `LONGTEXT`; the binary `utf8mb4_bin` format
collation aligns the database invariant with the PHP enum. It validates JSON
syntax for JSON fields but does not sanitize/render HTML or interpret
`field_key` semantics. The Host owns editor, sanitization, rendering, JSON
semantics, and semantic language/platform validation.

`status` (`active`/`inactive`) is independent from `deleted_at`. Query
visibility excludes inactive or soft-deleted Categories and excludes every
descendant whose complete ancestor path contains an inactive or soft-deleted
row.

## Mutation orchestration

`CategoryCommandService` owns orchestration while input Commands own syntactic and
domain validation. Parent moves lock the complete relevant ancestor chain and
reject direct and indirect cycles. Soft delete locks the Category and its
non-deleted children and rejects the operation when non-deleted children remain.
Restore uses the original identity.

Every mutation receives its timestamp from the injected
`maatify/shared-common` `ClockInterface`. Repositories persist supplied times;
they do not generate application time.

## Persistence and ordering

The package uses PDO directly and owns no Host-table foreign keys or joins.
The shared `maatify/persistence` Ordering API owns row-position locking,
shifting, transaction coordination, nullable root scopes, and atomic
`updated_at` mutation. Category creation locks its nullable `parent_id` scope,
asks that API for the next position inside the application transaction, and
persists the returned value. Category does not provide a local ordering or
pagination implementation.

`CategoryCommandService` depends directly on
`Maatify\Persistence\Pdo\Transaction\TransactionRunnerInterface` and uses
`run()` for every orchestrated mutation, including Category, Image Assignment,
and Content Field display-order updates. The Host provides
`PdoTransactionRunner` with the same PDO instance used by Category repositories
and `ScopedOrderingManager` operations. The shared runner starts and closes a
transaction only when no transaction is active; a caller-owned transaction on
that PDO remains owned by the Host.

The schema uses MySQL 8.0.16+ because enforced `CHECK` constraints are part of
the status contract. Package-owned triggers enforce `parent_id <> id` after
database-generated identity allocation and on updates.

Content creation, content update, soft deletion, and restoration are
package-owned command operations. Their immutable `(category_id,
language_code)` identity is enforced by the schema unique key, including the
single NULL-language identity, and is never accepted by content-update commands.

### Image Assignment parent-state contract

`createImageAssignment()` requires a Category that exists and is not
soft-deleted; an inactive Category is allowed. Ordering, soft deletion, and
restoration use dedicated assignment operations and the assignment's own
lifecycle. Assignment soft deletion does not release its stable identity, so
the same exact assignment cannot be recreated; restoration preserves the same
row and identity. An assignment is not a Category child for Category
soft-delete blocking purposes.

### Content parent-state contract

The current Runtime resolves parent state as follows, without introducing a
new parent/Content coupling rule:

- `createContent()` requires a Category that exists and is not
  soft-deleted. `CategoryStatusEnum::INACTIVE` does not block creation;
  `findActiveByIdForUpdate()` means non-deleted lifecycle state here, not
  status `ACTIVE`.
- `updateContent()` checks and locks the Content lifecycle only. An
  inactive or soft-deleted parent Category does not block the update.
- `softDeleteContent()` checks and locks the Content lifecycle only.
  An inactive or soft-deleted parent Category does not block the operation.
- `restoreContent()` checks and locks the Content row's existence and
  lifecycle only. An inactive or soft-deleted parent Category does not block
  restoration.

The real MySQL proof is maintained in
`CategoryPdoIntegrationTest::testContentMutationsFollowParentLifecycleStateContractOnMySql`.
This is a closed v1 contract, not an open implementation decision.

### Category Content Field parent-state and ordering contract

`createContentField()` requires a Category that exists and is not soft-deleted;
an inactive Category is allowed. Field value/format updates, soft deletion,
restoration, and ordering use the field's own lifecycle. Creation locks the
Category-owned exact ordering scope before asking the shared Ordering API for
the next position. A field's logical identity is never changed by an update,
and soft deletion does not release its uniqueness reservation.

## Query contract

The package exposes two separate public query ports:

- `CategoryManagementReadQueryInterface` and
  `CategoryManagementQueryServiceInterface` expose stored management reads.
  Their typed criteria explicitly select Category status and deleted state,
  and bound each list to at most 100 rows. Category lists are ordered by
  `display_order, id`; Content lists are ordered by `language_code, id`; Image
  Role lists are ordered by `role_key, id`; Image Assignment lists are ordered
  deterministically by Category, exact generated scope, `display_order, id`;
  Content Field lists are ordered by Category, exact generated scope,
  `display_order, id`.
- `CategoryReadQueryInterface` and `CategoryQueryServiceInterface` expose
  consumer visibility reads and apply the complete ancestor visibility rule.
  Their separate `CategoryVisibleListCriteriaDTO` bounds every list to at most
  100 rows without exposing status/deleted-state controls. Category lists use
  `display_order, id`; Content lists use `language_code, id`; Image Assignment
  and Content Field reads require an exact scope and use `display_order, id`.

Management reads do not reuse consumer visibility queries. The v1 contract has
no public management get-by-code, search, or local pagination implementation.
Results are typed DTOs and collections, never associative arrays.

`CategoryQueryReaderInterface::findByCode()` is an internal
mutation-support lookup only. It is not exposed by either query service and is
not part of the public management API.

The v1 query contract intentionally defers pagination and search, bounds every
unpaginated list to 100 rows, and uses `display_order, id` for Category ordering
and `language_code, id` for Content ordering. Content lists return the NULL
language identity alongside any language-specific identities and never perform
implicit fallback.

Visible Image Assignment reads exclude soft-deleted rows, require the
requested Category's complete ancestor chain to be active and non-deleted, and
match Role/language/platform with NULL-safe exact predicates. Role-scoped rows
also require an active, non-deleted Role. They never search a different scope
as fallback. Management Image Assignment criteria distinguish the exact
language/platform scope from the Role filter and support omitted Role, exact
NULL Role, or one concrete Role. `scope = null` omits all language/platform
predicates; an explicit scope with no `roleFilter` preserves the legacy exact
`scope->roleId` behavior.
Visible Content Field reads exclude soft-deleted fields, require an exact
`CategoryContentFieldScopeDTO`, apply complete ancestor visibility, and never
fallback. Management Content Field criteria use `scope = null` for no scope
filter and `new CategoryContentFieldScopeDTO()` for exact NULL/NULL scope.

## v1 API freeze closure

The Final API Freeze audit on baseline
`f29af0d65728c7c252b57f7b1def8e7a56d6b38d` found no known blocking Runtime gap.
The audit covered the `Maatify\Category\` namespace, all Commands, DTOs,
criteria DTOs, enums, contracts, services, PDO adapters, constructors, method
signatures, return types, exception hierarchy, package-owned table/column
contracts, indexes and constraints, Composer dependencies, PHP/MySQL support,
and Host-boundary rules. No speculative public API, duplicate contract,
legacy Catalog runtime naming, or undeclared Host dependency was found.

The freeze consequence is documentation-only: the stable inventory is recorded
in the root Package Reference, while `findByCode()` remains internal
mutation-support only and pagination, search, and public management get-by-code
remain deferred or absent as stated above.

## Release presentation state

Implementation and verification gates are prepared, but the package is not
presented as a Final Release Candidate or as ready for release. Final RC
activation remains `BLOCKED BY OWNER DECISION` until the owner approves the
release metadata and publication timing. `[Unreleased]` remains in place;
there is no Tag, GitHub Release, or Packagist publication. `SECURITY.md` may
therefore remain in Development State.

## Non-goals

The package does not define a Catalog entity, Product/Pricing/Inventory/Media
composition, controllers, routes, middleware, permissions, Twig, JavaScript,
or framework/container bindings.
