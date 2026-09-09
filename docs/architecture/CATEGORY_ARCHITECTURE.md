# Category Package Architecture

The canonical stable contract is [CATEGORY_PACKAGE_REFERENCE.md](../../CATEGORY_PACKAGE_REFERENCE.md).

Normative standards provenance is resolved by
[`docs/php-engineering-standards/STANDARDS_MANIFEST.md`](../php-engineering-standards/STANDARDS_MANIFEST.md),
currently pinned to adoption commit `f386948aa873fef9960680411c8918d095d29b93`.

## Purpose

`maatify/category` is a reusable Base Module for hierarchical Categories and
Category Translations. It can be consumed by a Catalog, a navigation system,
an access taxonomy, or another Host without knowing the Host's framework or
schema.

## Ownership

The package owns:

- Category and Translation DTOs, typed mutation Commands, and input validation.
- Category mutation/query contracts.
- Business orchestration and domain exceptions.
- Package-local PDO persistence adapters and transaction boundaries.
- The two package-owned MySQL tables and their internal constraints.

The Package owns the syntactic and storage validation of `language_code`
required by its contract and Runtime. The Host owns dependency injection, HTTP,
permissions, semantic language validation, fallback/locale policy, presentation
serialization, and relationships to Host-owned tables.

## Domain model

Categories form a tree through nullable `parent_id`. A `NULL` parent identifies
a root. `code` is immutable after creation and remains unique across soft
deletion. A Translation is identified logically by
`(category_id, language_code)`; content may change but that identity cannot.

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

The schema uses MySQL 8.0.16+ because enforced `CHECK` constraints are part of
the status contract. Package-owned triggers enforce `parent_id <> id` after
database-generated identity allocation and on updates.

Translation creation, content update, soft deletion, and restoration are
package-owned command operations. Their immutable `(category_id,
language_code)` identity is enforced by the schema unique key and is never
accepted by content-update commands.

## Query contract

The package exposes two separate public query ports:

- `CategoryManagementReadQueryInterface` and
  `CategoryManagementQueryServiceInterface` expose stored management reads.
  Their typed criteria explicitly select Category status and deleted state,
  and bound each list to at most 100 rows. Category lists are ordered by
  `display_order, id`; Translation lists are ordered by `language_code, id`.
- `CategoryReadQueryInterface` and `CategoryQueryServiceInterface` expose
  consumer visibility reads and apply the complete ancestor visibility rule.
  Their separate `CategoryVisibleListCriteriaDTO` bounds every list to at most
  100 rows without exposing status/deleted-state controls. Category lists use
  `display_order, id`; Translation lists use `language_code, id`.

Management reads do not reuse consumer visibility queries. The v1 contract has
no public management get-by-code, search, or local pagination implementation.
Results are typed DTOs and collections, never associative arrays.

`CategoryQueryReaderInterface::findByCode()` is an internal
mutation-support lookup only. It is not exposed by either query service and is
not part of the public management API.

The v1 query contract intentionally defers pagination and search, bounds every
unpaginated list to 100 rows, and uses `display_order, id` for Category ordering
and `language_code, id` for Translation ordering.

## Non-goals

The package does not define a Catalog entity, Product/Pricing/Inventory/Media
composition, controllers, routes, middleware, permissions, Twig, JavaScript,
or framework/container bindings.
