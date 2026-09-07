<div align="center">

# Maatify Category

![Maatify.dev](https://www.maatify.dev/assets/img/img/maatify_logo_white.svg)

[![Maatify Ecosystem](https://img.shields.io/badge/Maatify-Ecosystem-blueviolet)](https://github.com/Maatify)

Framework-neutral hierarchical categories and translations for reusable PHP applications.

**Status:** Development / pre-1.0.0 · Install after publication with `composer require maatify/category`

</div>

---

## Package Summary

`maatify/category` provides typed Category domain/application contracts, PDO
adapters, MySQL schema, hierarchy invariants, lifecycle operations, ordering,
and visible query/list behavior. It is independent of Catalog, Product, Admin,
Slim, HTTP, permissions, and presentation layers.

## Key Features

- Typed immutable Category and Category Translation DTOs.
- Stable immutable Category codes and translation identities.
- Parent movement with complete cycle prevention.
- Category and Translation create, update, soft-delete, and restore lifecycle
  mutations, plus Category status and display-order mutations.
- Transaction and row-locking contracts for hierarchy/lifecycle invariants.
- Shared `maatify/persistence` Ordering API for root and nested scopes.
- MySQL recursive ancestor visibility filtering for query/list reads.
- No associative arrays as public or domain contracts.

## Requirements

- PHP 8.2 or later.
- Composer.
- MySQL 8.0.16 or later for Integration verification.

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

The host application owns dependency injection, language validation/fallback,
HTTP response envelopes, and presentation formatting.

## Documentation

- [Category Package Reference](CATEGORY_PACKAGE_REFERENCE.md)
- [Category architecture](docs/architecture/CATEGORY_ARCHITECTURE.md)
- [Category schema](schema/category.sql)
- [Schema notes](schema/README.md)
- [Changelog](CHANGELOG.md)
- [Contributing](CONTRIBUTING.md)
- [Security policy](SECURITY.md)

## Quality Status

Run the package checks from the repository root:

```bash
composer validate --strict
composer dump-autoload --optimize --strict-psr
composer check-platform-reqs
composer audit --no-interaction --abandoned=fail
composer analyse
composer test:unit
composer test:integration
composer test
```

Integration tests require `CATEGORY_TEST_DSN`, `CATEGORY_TEST_DB_USER`, and
`CATEGORY_TEST_DB_PASSWORD` and run against real MySQL 8.0.16 or later.

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
