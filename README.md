<div align="center">

# Maatify Category

![Maatify.dev](https://www.maatify.dev/assets/img/img/maatify_logo_white.svg)

[![Maatify Ecosystem](https://img.shields.io/badge/Maatify-Ecosystem-blueviolet)](https://github.com/Maatify)
[![PHP](https://img.shields.io/badge/PHP-8.4%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-2F9E44.svg)](phpstan.neon)
[![Changelog](https://img.shields.io/badge/Changelog-View-blue.svg)](CHANGELOG.md)
[![Package Reference](https://img.shields.io/badge/Reference-Read-blue.svg)](CATEGORY_PACKAGE_REFERENCE.md)
[![Security Policy](https://img.shields.io/badge/Security-Policy-blue.svg)](SECURITY.md)
[![Contributing Guide](https://img.shields.io/badge/Contributing-Guide-blue.svg)](CONTRIBUTING.md)

Framework-neutral hierarchical categories and translations for reusable PHP applications.

**Status:** v1.0.0 preparation · owner release metadata pending · not published

</div>

---

## Package Summary

`maatify/category` provides typed Category domain/application contracts, PDO
adapters, MySQL schema, hierarchy invariants, lifecycle operations, ordering,
and separate management and visible query/list behavior. It is independent of
Catalog, Product, Admin, Slim, HTTP, permissions, and presentation layers.

## Key Features

- Typed immutable Category and Category Translation DTOs.
- Stable immutable Category codes and translation identities.
- Parent movement with complete cycle prevention.
- Category and Translation create, update, soft-delete, and restore lifecycle
  mutations, plus Category status and display-order mutations.
- Transaction and row-locking contracts for hierarchy/lifecycle invariants.
- Shared `maatify/persistence` Ordering API for root and nested scopes.
- MySQL recursive ancestor visibility filtering for query/list reads.
- Deterministic, bounded consumer visibility lists and management reads with
  separate typed criteria; management criteria expose explicit status and
  deleted-state controls while consumer criteria do not.
- No associative arrays as public or domain contracts.

## Public Runtime API

The package exposes ten typed mutation Commands, immutable Category and
Translation DTOs, three bounded criteria DTOs, two enums, typed service and
repository contracts, and framework-neutral PDO adapters. The complete
constructor and method inventory is maintained in the
[Category Package Reference](CATEGORY_PACKAGE_REFERENCE.md).

The internal `findByCode()` mutation-support lookup is deliberately not exposed
as a management service method. Pagination, search, and public management
get-by-code are deferred from v1.

## Query and list behavior

Management reads and consumer visibility reads are separate contracts.
Management criteria can select status and deleted state; consumer criteria
cannot bypass active/non-deleted ancestor visibility. Every unpaginated list is
bounded to at most 100 rows. Category lists use `display_order, id`, and
Translation lists use `language_code, id`.

## Requirements

- PHP 8.4 or a compatible later PHP 8.x release, as constrained by Composer.
- Composer.
- MySQL 8.0.16 or later, required by the package storage contract because
  enforced `CHECK` constraints are part of the schema behavior.

## Installation

The package is currently prepared for independent publication from the
`Maatify/category` repository. After publication, install it with:

```bash
composer require maatify/category
```

Until the first stable release is published, use the repository checkout for
development and do not rely on a Packagist version claim.

## Quick Usage

```php
use DateTimeImmutable;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\Enum\CategoryStatusEnum;

$category = new CategoryDTO(
    id: 1,
    parentId: null,
    code: 'clothing',
    status: CategoryStatusEnum::ACTIVE,
    displayOrder: 1,
    createdAt: new DateTimeImmutable('2026-01-01 00:00:00 UTC'),
    updatedAt: new DateTimeImmutable('2026-01-01 00:00:00 UTC'),
    deletedAt: null,
);
```

The Category package owns the syntactic and storage validation of `language_code`
required by its contract and Runtime. The host application owns dependency
injection, semantic language validation, fallback/locale policy, HTTP response
envelopes, and presentation formatting.

## Documentation

- [Category Package Reference](CATEGORY_PACKAGE_REFERENCE.md)
- [Category architecture](docs/architecture/CATEGORY_ARCHITECTURE.md)
- [Category schema](schema/category.sql)
- [Schema notes](schema/README.md)
- [Changelog](CHANGELOG.md)
- [Contributing](CONTRIBUTING.md)
- [Security policy](SECURITY.md)

The standalone consumer verification builds a clean temporary Composer project
from the package VCS source and exercises installation, optimized PSR-4
autoloading, platform checks, schema installation, and host-isolation checks.

## Quality Status

Run the package checks from the repository root:

```bash
composer validate --strict
composer dump-autoload --optimize --strict-psr
composer check-platform-reqs
composer audit --no-interaction --abandoned=fail
composer analyse
find src tests -type f -name '*.php' -exec php -l {} \;
composer test:unit
composer test:integration
composer test
```

Integration tests require `CATEGORY_TEST_DSN`, `CATEGORY_TEST_DB_USER`, and
`CATEGORY_TEST_DB_PASSWORD` and run against the same required MySQL runtime
version: 8.0.16 or later.

For local Integration or full-suite runs, copy `env.testing.example` to
`env.testing`, set the local MySQL credentials, and create the configured
database before running PHPUnit. `tests/bootstrap.php` loads this ignored file
as local defaults without overriding environment variables supplied by CI.

Example local setup:

```bash
cp env.testing.example env.testing
composer test:integration
composer test
```

Packagist, tags, GitHub Releases, and a stable `v1.0.0` publication are not
performed by this repository preparation.

## License

MIT. See [LICENSE](LICENSE).

## Author

Engineered by **Mohamed Abdulalim** ([@megyptm](https://github.com/megyptm))<br>
Backend Lead & Technical Architect<br>
[https://www.maatify.dev](https://www.maatify.dev)

---

<div align="center">

[Built with ❤️ by Maatify.dev — Unified Ecosystem for Modern PHP Libraries](https://www.maatify.dev)

</div>
