# Category schema

The canonical stable contract is [CATEGORY_PACKAGE_REFERENCE.md](../CATEGORY_PACKAGE_REFERENCE.md).

This directory contains the package-owned persistence contract for Categories
and Category Contents, extensible Category Content Fields, plus Category Image
Assignments.

The package requires MySQL 8.0.16 or later. This minimum is part of the storage
contract because the database schema relies on enforced `CHECK` constraints.
Integration verification uses the same required runtime version.

## Included tables

- `maa_category_categories`
- `maa_category_category_contents`
- `maa_category_category_content_fields`
- `maa_category_category_image_assignments`

Apply [category.sql](category.sql). It creates exactly the four tables and the
package-owned self-parent triggers:

- `trg_maa_category_categories_parent_not_self_ai`
- `trg_maa_category_categories_parent_not_self_bu`

The schema contains no Host, Catalog, Product, Pricing, Inventory, or Media
tables. Timestamps are supplied by the Category application in UTC, and soft
deletion uses nullable `deleted_at`. Internal foreign keys use `RESTRICT` for
delete and update operations. Category creation obtains the next positive
`display_order` for the nullable `parent_id` scope through the shared
`maatify/persistence` Ordering API inside the application transaction.

Category Image Assignments are owned by Category and store only a validated
host-provided `media_asset_id`; there is deliberately no Media, Platform, or
Language foreign key. The exact scope is `(language_code, platform)`, where
each nullable dimension is a real value and not a fallback request. The stable
identity `(category_id, media_asset_id, language_code, platform)` remains unique
across soft deletion through generated NULL-safe identity columns. The
generated `ordering_scope` lets the application use the shared Ordering API
independently for each Category and exact scope.

Category Content creation, content updates, soft deletion, and restoration
are exposed through the package command service; consumers do not need direct
SQL for that lifecycle. The `(category_id, language_code)` identity remains
unique and immutable. `language_code = NULL` represents unlocalized Content; a
non-NULL value represents localized Content. A stored generated identity column
makes NULL a single database uniqueness value, so MySQL enforces at most one
unlocalized row per Category as well as one row per non-NULL language code.
Empty-string language codes are rejected by the schema and are not an
alternative to NULL.

Category Content Fields are Host-defined key/value records with immutable
`(category_id, field_key, language_code, platform)` identity. The four exact
scope combinations are supported independently; management criteria distinguish
an omitted scope filter from an exact NULL/NULL scope, while consumer reads
require one exact scope and never fallback. `format` is `text`, `html`, or
`json`; values use `LONGTEXT`, JSON syntax is enforced for JSON fields, and
Category does not sanitize, render, or interpret Host-defined keys. Field
ordering is independent per Category and exact scope through the shared
`maatify/persistence` Ordering API. The generated NULL-safe identity remains
unique across soft deletion.
