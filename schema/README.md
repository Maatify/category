# Category schema

The canonical stable contract is [CATEGORY_PACKAGE_REFERENCE.md](../CATEGORY_PACKAGE_REFERENCE.md).

This directory contains the package-owned persistence contract for Categories
and Category Translations.

Integration verification requires MySQL 8.0.16 or later so the database
`CHECK` constraints are enforced.

## Included tables

- `maa_category_categories`
- `maa_category_category_translations`

Apply [category.sql](category.sql). It creates exactly the two tables and the
package-owned self-parent triggers:

- `trg_maa_category_categories_parent_not_self_ai`
- `trg_maa_category_categories_parent_not_self_bu`

The schema contains no Host, Catalog, Product, Pricing, Inventory, or Media
tables. Timestamps are supplied by the Category application in UTC, and soft
deletion uses nullable `deleted_at`. Internal foreign keys use `RESTRICT` for
delete and update operations.
