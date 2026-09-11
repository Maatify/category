# Changelog

All notable changes to this package will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

These unreleased notes describe the proposed `v1.0.0` release line. No stable
tag, release date, or owner-approved release metadata is claimed.

### Added

- Initial standalone extraction of the reusable Category and optional Category
  Content domain/application contracts.
- MySQL schema, PDO repositories, shared Persistence transaction runner, and real-engine
  Integration tests for hierarchy, lifecycle, ordering, and visibility.
- Root Package Reference and standards-aligned Composer/CI configuration.
- Consumer Readiness Stage 3 management query expansion: shared Persistence
  pagination for all management list APIs, Category-owned SQL search by code,
  public management `getByCode()`, and unit, MySQL Integration, and standalone
  consumer coverage through the public Facade APIs.
- Consumer Readiness Stage 4 mutation ergonomics: typed Content `name` and
  `description` inline updates, typed Content Field value inline updates that
  preserve the atomic format/value invariant, and Unit, MySQL Integration, and
  standalone consumer coverage through the public Facade APIs.
- Consumer Readiness Stage 5 Image Assignment consumer workflow: explicit
  `assign()`, `reorder()`, and `remove()` operations, typed exact
  scope input, and complete lifecycle/default/order/read coverage through the
  public Facade APIs.
- Consumer Readiness Stage 6 audit closure: reconciled the public `src/`
  inventory, stale API/alias sweep, package documentation, boundary checks,
  and standalone external Composer consumer verification with the current
  v1 runtime; this records verification scope and does not publish or accept
  the Consumer Readiness Draft.

### Changed

- Removed Category-owned UTC timestamp normalization. Hosts provide
  `Maatify\SharedCommon\Contracts\ClockInterface` and its timezone; Category
  persists timestamp values as supplied and hydrates them using that Host Clock
  timezone.
- Reconciled package documentation with the selective pinned standards adoption
  and the current v1 runtime contract.
- Replaced the former command-service composition with a thin `CategoryFacade`,
  five Domain APIs, split domain services, domain-owned read contracts and PDO
  adapters, and a framework-neutral package-level `CategoryFactory`.
- Documented separate management and consumer visibility reads, bounded
  unpaginated lists, deterministic ordering, and the completed Stage 3
  management query expansion while keeping Search Category-owned and SQL-based.
- Corrected the Stage 3 pagination sort-key contract: Image Assignment and
  Content Field default to the explicit `business_order` key while
  `category_id` retains direct Category ID semantics.
- Marked Consumer Readiness Stages 1–5, including the current Stage 5 Image
  Assignment consumer workflow, as implemented in the v1 line; later stages
  remain separate scope.
- Kept existing full-form mutation operations intact while exposing typed
  partial operations for safe inline editing. Category and Image Role retain
  their existing dedicated operations; Image Assignment exposes consumer-oriented
  `assign()`, `reorder()`, and `remove()` operations with typed
  default and restore mutations. No generic `updateField()` contract or
  Media/Storage workflow was added.
- Prepared release-facing documentation for owner approval without creating a
  tag, release, or Packagist publication.
- Corrected the previous content model to unified Category Content,
  supporting one unlocalized (`language_code = NULL`) row and localized rows
  under a database-enforced logical identity.
- Added first-class Category Image Assignments with exact nullable
  language/platform scopes, stable NULL-safe identity, independent shared
  ordering, soft-delete/restore lifecycle, management reads, and ancestor-aware
  consumer reads. Category stores only the host-provided Media Asset identity;
  Media lifecycle remains outside the package.
- Added explicit Category Image Assignment defaults within exact
  (category_id, role_id, language_code, platform) scopes. Each scope allows
  zero or one active default, with shared-transaction Set/Clear mutations and a
  conditional generated MySQL uniqueness identity; no automatic fallback or
  promotion exists.
- Added the package-owned Category Image Role registry with immutable globally
  unique keys, typed active/inactive status, soft-delete/restore lifecycle,
  permanent key reservation, management reads, and Role-aware exact Image
  Assignment identity, ordering, and consumer visibility.
- Added first-class Host-defined Category Content Fields with exact nullable
  language/platform scopes, typed text/html/json formats, LONGTEXT storage,
  NULL-safe identity reserved across soft deletion, independent shared ordering,
  management reads, and exact ancestor-aware consumer reads.
- Migrated Category orchestration from its local transaction implementation to
  the published `maatify/persistence:^1.3` transaction contract. Hosts provide
  `PdoTransactionRunner` with the same PDO used by Category persistence;
  caller-owned outer transactions remain Host-owned.

[Unreleased]: https://github.com/Maatify/category/compare/main...HEAD
