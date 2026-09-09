# Category Library Roadmap

## 1. Purpose

هذه الوثيقة هي الـMaster Implementation Roadmap لمكتبة:

```text
maatify/category
```

وهي المرجع التنفيذي الذي يحدد المعالم الهندسية المطلوبة من بداية تأسيس المكتبة وحتى جاهزية أول إصدار Stable.

تمت إعادة هيكلة الـ18 Phase القديمة إلى مجموعات تنفيذية (Execution Batches/Milestones) تعكس قدرات هندسية حقيقية لضمان عدم الخلط بين التطوير الفعلي وبوابات المراجعة.

الهدف النهائي هو إنتاج مكتبة Category:

* Standalone.
* Reusable.
* Installable عبر Composer.
* Host-agnostic.
* Framework-neutral.
* متوافقة بالكامل مع Maatify Engineering Standards.
* مكتملة وظيفيًا من ناحية Category وCategory Content وCategory Content Field وCategory Image Assignment lifecycle.
* مكتملة من ناحية CRUD.
* مكتملة من ناحية Persistence وConcurrency.
* موثقة ومختبرة وقابلة للإصدار.

### 1.1 Baseline reconciliation and current status

تستخدم هذه الوثيقة التصنيفات التالية بدل اعتبار وجود الملفات وحده دليلًا على اكتمال المرحلة:

- `ALREADY IMPLEMENTED + PROVEN`: التنفيذ موجود، ومعايير القبول مثبتة بأدلة حالية أو Real MySQL/CI tests مناسبة.
- `COMPLETED + PROVEN`: أُنجزت Phase في Draft الحالية مع evidence محدد.
- `COMPLETED BY THIS BATCH`: إغلاق توثيقي/حوكمي أو audit ناتج عن Batch الحالية.
- `BLOCKED BY OWNER DECISION`: التنفيذ والـgates جاهزة، لكن تفعيل Final Release Candidate يتطلب release metadata وموافقة المالك.

الحالة المجمعة على baseline `f29af0d65728c7c252b57f7b1def8e7a56d6b38d` هي (Old Phases 0-17):

| Phase | الحالة | الدليل/الحدود |
|---|---|---|
| 0 | `COMPLETED BY THIS BATCH` | Standards lock، قرارات v1، وحسم Content parent-state contract مع Real MySQL proof |
| 1 | `ALREADY IMPLEMENTED + PROVEN` | `4fabccafdce4638fa5f050c9f72fa8241e343a31` وComposer/CI الحالية |
| 2 | `ALREADY IMPLEMENTED + PROVEN` | `18e212875e299e6e7c7d9c3b0b1c5304e9e7e133` واختبارات Real MySQL |
| 3 | `COMPLETED + PROVEN` | `b1141d93cb61f3961950faf774737b57e1143b5e` واختبارات validation |
| 4 | `ALREADY IMPLEMENTED + PROVEN` | عقود PDO/transaction وReal MySQL integration الحالية |
| 5 | `ALREADY IMPLEMENTED + PROVEN` | Category lifecycle وordering/concurrency integration الحالية |
| 6 | `ALREADY IMPLEMENTED + PROVEN` | Content lifecycle وidentity/restore integration الحالية، وإثبات parent-state semantics على Real MySQL |
| 7 | `COMPLETED + PROVEN` | `2cdef539a82e3a9c410b38b33ff43eba12906c02` وmanagement query tests |
| 8 | `ALREADY IMPLEMENTED + PROVEN` | visible query service/read tests وancestor visibility integration |
| 9 | `COMPLETED + PROVEN` | `2cdef539a82e3a9c410b38b33ff43eba12906c02` والقوائم bounded/deterministic |
| 10 | `COMPLETED + PROVEN` | `ceeb53fa47f8956d9700fd277b41e0b58cc23af6` واختبارات concurrency |
| 11 | `ALREADY IMPLEMENTED + PROVEN` | exception hierarchy/factories والـfailure tests الحالية |
| 12 | `COMPLETED BY THIS BATCH` | إعادة audit كاملة لـ`composer.json` وCI/consumer Composer proof؛ fixes committed في Work Branch الحالية، مع latest/lowest وPHP 8.4/8.5 وCategory Quality Gate verification |
| 13 | `ALREADY IMPLEMENTED + PROVEN` | `.github/workflows/ci.yml` والـaggregate gate الحالية |
| 14 | `COMPLETED BY THIS BATCH` | documentation and package-presentation sweep |
| 15 | `COMPLETED + PROVEN` | `f29af0d65728c7c252b57f7b1def8e7a56d6b38d` وstandalone consumer |
| 16 | `COMPLETED BY THIS BATCH` | Final API freeze audit؛ لا Runtime gap blocking معروفة |
| 17 | `BLOCKED BY OWNER DECISION` | Implementation وverification gates جاهزة؛ Final RC activation تحتاج owner-approved release metadata، ولا إصدار فعلي |

Phase 4–6 لا تعاد عبر Runtime implementation لمجرد إعادة الإثبات؛ evidence
الحالية في Real MySQL tests هي proof المعتمد لها. وPhase 3/7/9/15 مغلقة
ومثبتة في Draft الحالية قبل Batch التوثيق هذه.

### 1.2 Current Draft correction — unified Category Content

The current Draft corrects the previous content-model assumption. Category is a
structural entity and does not store `name` or `description`. One package-owned
Category Content persistence concept supports both:

* `language_code = NULL`: one ordinary, unlocalized Content row per Category.
* non-NULL `language_code`: one localized Content row per language identity.

The Runtime uses `CreateCategoryContentCommand` and the other Content lifecycle
Commands directly; callers do not create a pseudo-localized row to represent
ordinary Category data. The database uses a stored generated identity column in
the unique key so MySQL enforces the NULL row cardinality as well as
`(category_id, language_code)` uniqueness for localized rows. No fallback or
semantic language policy is introduced; those remain Host responsibilities.

This correction supersedes the prior public inventory and schema wording in
this roadmap. Its implementation evidence is the current Content unit tests,
Real MySQL schema/PDO tests, query tests, and standalone consumer coverage.

### 1.3 Current Draft capability — Category Image Assignments

The current Draft also provides first-class Category Image Assignments. Category
owns the direct relationship to a host-provided `media_asset_id`, but does not
own Media, Platform, or Language lifecycle and creates no foreign key to those
host concepts. The immutable identity is
`(category_id, media_asset_id, language_code, platform)`, including soft-deleted
rows. `language_code` and `platform` are nullable exact scope dimensions; all
four combinations are supported, empty strings are invalid, and no fallback or
hardcoded platform enum exists.

The Runtime exposes typed create, exact-scope ordering, soft-delete, restore,
management-read, and consumer-read contracts. Consumer reads exclude deleted
assignments and require the complete Category ancestor chain to be visible.
Management criteria distinguish no scope filter from exact NULL/NULL scope.
The MySQL schema uses generated NULL-safe identity columns and a generated
Category-plus-scope ordering key so creation and movement continue to use the
shared `maatify/persistence` Ordering API. This capability is covered by unit,
schema, PDO, visibility, lifecycle, concurrency, and standalone consumer tests.

### 1.4 Current Draft capability — extensible Category Content Fields

The child implementation adds first-class Host-defined Category Content Fields
without changing the fixed `CategoryContent` name/description contract. A field
uses immutable identity `(category_id, field_key, language_code, platform)` and
one of the exact lowercase typed formats `text`, `html`, or `json`. The package stores values
in `LONGTEXT`, validates JSON syntax for JSON fields, and does not own the
semantic meaning of keys, HTML sanitization/rendering, editor behavior, or JSON
semantics.

All four exact language/platform scopes are supported with no fallback. Field
creation and movement use the shared Ordering API with category-plus-scope
locking, while management criteria distinguish an omitted scope from exact
NULL/NULL. Consumer reads exclude deleted fields, require an exact scope, and
apply complete ancestor visibility. Stable identity remains reserved across
soft deletion. Real MySQL tests cover formats, JSON validation, identity,
ordering, lifecycle, management/consumer reads, ancestor visibility, and
concurrent position allocation.

---

## 2. Package Identity & Standards Authority

الهوية النهائية للمكتبة:

```text
Repository: Maatify/category
Composer: maatify/category
Root Namespace: Maatify\Category\
Database Prefix: maa_category_
```

ممنوع إعادة استخدام `Maatify\Catalog\` أو `maa_catalog_` داخل Runtime.
المكتبة تتبع `php-engineering-standards`.

### Domain Ownership & Explicit Non-Goals
Category Domain معني بإدارة التسلسل الهرمي للفئات (Hierarchy) ومحتواها
(Category Content). لا يتدخل في المنتجات (Products)، ولا يدير واجهات الـHTTP
أو Admin UI مباشرة.

---

## 3. Engineering Milestones

تم دمج المراحل السابقة (0-17) إلى 4 معالم هندسية (Milestones) تمثل قدرات النظام الحقيقية بناءً على وضع المستودع الحالي.

### Milestone 1: Core Domain, Schema & Persistence (Completed)
**Engineering Capability:** تأسيس المعمارية، قواعد البيانات، وعقود الـPDO و Transactions، وتحديد حالة المالك/المحتوى.
**Included Legacy Phases:** Phase 0, 1, 2, 4
**Locked v1 Decisions & Implementation Status:**
* **PHP Requirement:** PHP 8.4+
* **Database Requirement:** MySQL 8.0.16+, InnoDB, `utf8mb4_unicode_ci`.
* **Schema Design:** `maa_category_categories`, `maa_category_category_contents`, `maa_category_category_content_fields`, and `maa_category_category_image_assignments`.
* **Self-Parent Protection:** enforced via `AFTER INSERT` and `BEFORE UPDATE` triggers (لا CHECK constraint).
* **Content Identity:** (category_id, language_code) uniquely identifies Content; `language_code = NULL` is the single unlocalized identity per Category.
* **Content Parent-State Contract:** Create requires parent non-deleted (inactive allowed). Update/soft-delete/restore depend on Content lifecycle only. Parent inactive or soft-deleted does not block those operations.
* **Image Assignment Parent-State Contract:** Create requires parent non-deleted (inactive allowed). Ordering/soft-delete/restore depend on the assignment lifecycle only; exact identity remains reserved after soft deletion.
* **Content Field Schema Contract:** `(category_id, field_key, language_code, platform)` is NULL-safe and reserved across soft deletion; `format` accepts exact lowercase `text`, `html`, or `json`, and JSON rows require valid JSON syntax.
* **Display Order:** No implicit default (like 0) in schema. `CreateCategoryCommand` has no `display_order`. Persistence/shared ordering determines next position.
**Completion Gates:**
* Schema creation tests pass.
* Trigger behaviors proven.
* PDO and Transaction contracts proven via Real MySQL Integration Tests.

---

### Milestone 2: Management & Mutation APIs (Completed)
**Engineering Capability:** اكتمال جميع عمليات الـCRUD للإدارة (Management) الخاصة بالفئات ومحتواها مع معالجة التزامن والأخطاء.
**Included Legacy Phases:** Phase 3, 5, 6, 7, 9, 10, 11
**Locked v1 Decisions & Implementation Status:**
* **Mutation Contract Strictness:** No generic "Update" DTOs; explicit Commands only (`MoveCategoryCommand`, `UpdateCategoryStatusCommand`, etc.).
* **Pagination & Search:** Pagination and Search are deferred. Bounded list contracts remain max 100.
* **Management Reads:** No public management get-by-code.
* **Category Ordering:** Ordered by `display_order, id`.
* **Content Ordering:** Ordered by `language_code, id`.
* **Soft Delete:** Content records manage their own soft-delete lifecycle independent of the Category.
* **Exception Boundaries:** Namespace is `Maatify\Category\Exception`. Package marker is `CategoryExceptionInterface`. No `CategoryException` base class.
* **Concurrency Verification:** Proven transaction locks and restore mechanisms.
**Completion Gates:**
* Management read models are robust.
* Command validations pass.
* Concurrency and ordering tests on Real MySQL pass.

---

### Milestone 3: Consumer Visibility Query Model (Completed)
**Engineering Capability:** توفير واجهات استعلام المستهلك النهائي مع تطبيق قواعد الظهور المتقدمة.
**Included Legacy Phases:** Phase 8
**Locked v1 Decisions & Implementation Status:**
* **Hierarchy Visibility:** Consumer lists exclude inactive or deleted ancestors (tree climbing visibility check).
* **Language Fallback:** The package does not resolve language fallback. Fallback/locale policy is Host-owned.
**Completion Gates:**
* Visible category reads properly filter out non-active tree segments.

---

### Milestone 4: v1.0.0 Readiness & Verification (Completed / Pending Owner Release)
**Engineering Capability:** التجهيز النهائي للمكتبة للإصدار؛ شامل استقلالية المكتبة، CI، مراجعة API، والوثائق.
**Included Legacy Phases:** Phase 12, 13, 14, 15, 16, 17
**Locked v1 Decisions & Implementation Status:**
* **Standalone Proof:** Package works purely via `composer require` without AdminKernel, Slim, PHP-DI or Catalog runtime dependency.
* **API Freeze:** All Public APIs reviewed, stale Catalog runtimes completely removed.
* **Release Process:** Phase 17 does *not* imply automated Tag, Release, Publish, or Merge. Final release is strictly owner-controlled.
**Completion Gates (Final Readiness):**
* `composer validate --strict`
* `composer audit`
* PHPStan level max (level 9/max).
* Unit & Real MySQL tests fully green.
* Final Clean Architecture Audit and Document sweeps complete.

---

## 4. Legacy Phase Mapping

توضح هذه الخريطة أين ذهبت المراحل (Phases) الـ 18 لتتبع المرجع التاريخي:

| Old Phase | Name | Mapped To |
|---|---|---|
| Phase 0 | Architecture & Standards Lock | Milestone 1 |
| Phase 1 | Package Foundation | Milestone 1 |
| Phase 2 | Schema & Core Domain Data | Milestone 1 |
| Phase 3 | Command & Validation Layer | Milestone 2 |
| Phase 4 | Persistence & Transaction Foundation | Milestone 1 |
| Phase 5 | Category Create & Mutation CRUD | Milestone 2 |
| Phase 6 | Category Content CRUD | Milestone 2 |
| Phase 7 | Management Read Model | Milestone 2 |
| Phase 8 | Consumer Visibility Query Model | Milestone 3 |
| Phase 9 | Pagination, Ordering & Query Hardening | Milestone 2 |
| Phase 10 | Concurrency & Invariant Hardening | Milestone 2 |
| Phase 11 | Exception & Failure Contract | Milestone 2 |
| Phase 12 | Package / Composer Compliance | Milestone 4 |
| Phase 13 | CI Compliance | Milestone 4 |
| Phase 14 | Documentation & Package Presentation | Milestone 4 |
| Phase 15 | Standalone Consumer Verification | Milestone 4 |
| Phase 16 | Final API Freeze Review | Milestone 4 |
| Phase 17 | Release Readiness | Milestone 4 |

---

## 5. Artifact Naming & Contract Rules (Frozen)

### 5.1 Commands (Mutation Contract)
تم توحيد عقود الإدخال (Mutation-input contract):
* `CreateCategoryCommand`
* `MoveCategoryCommand`
* `RestoreCategoryCommand`
* `SoftDeleteCategoryCommand`
* `UpdateCategoryStatusCommand`
* `UpdateCategoryDisplayOrderCommand`
* `CreateCategoryContentCommand`
* `UpdateCategoryContentCommand`
* `SoftDeleteCategoryContentCommand`
* `RestoreCategoryContentCommand`
* `CreateCategoryImageAssignmentCommand`
* `UpdateCategoryImageAssignmentDisplayOrderCommand`
* `SoftDeleteCategoryImageAssignmentCommand`
* `RestoreCategoryImageAssignmentCommand`

### 5.2 DTOs (Read Models)
* `CategoryDTO`
* `CategoryContentDTO`
* `CategoryCollectionDTO`
* `CategoryContentCollectionDTO`
* `CategoryImageAssignmentScopeDTO`
* `CategoryImageAssignmentDTO`
* `CategoryImageAssignmentCollectionDTO`
* `CategoryImageAssignmentListCriteriaDTO`

*(Mutation Commands ممنوع تسميتها DTO.)*

---

## 6. Definition of Done for v1.0.0

الآن، مكتبة Category جاهزة للإصدار الأول v1.0.0 ولم يعد هناك تطوير Runtime مطلوب.
يعتبر الإصدار الأخير مكتملًا ومستعدًا عندما تتحقق البوابات التالية:

* Architecture Locked.
* Commands / DTOs Separated & Correct.
* Category CRUD & Category Content CRUD Complete.
* Management Reads & Consumer Visibility Complete.
* Persistence & Concurrency Verified.
* Composer & CI Compliant.
* Documentation Complete.
* Standalone Consumer Verified.
* Public API Frozen.
* Readiness gates verified fully green on the exact current HEAD.

**ما الذي يتبقى قبل v1.0.0؟**
فقط موافقة مالك المشروع (Owner Approval) لإطلاق Final RC وتحديد بيانات الـRelease (Metadata/Tag/Date).
