# Category Library Roadmap

## 1. Purpose

هذه الوثيقة هي الـMaster Implementation Roadmap لمكتبة:

```text
maatify/category
```

وهي المرجع التنفيذي الذي يحدد المراحل المطلوبة من بداية تأسيس المكتبة وحتى جاهزية أول إصدار Stable.

الهدف النهائي هو إنتاج مكتبة Category:

* Standalone.
* Reusable.
* Installable عبر Composer.
* Host-agnostic.
* Framework-neutral.
* متوافقة بالكامل مع Maatify Engineering Standards.
* مكتملة وظيفيًا من ناحية Category وCategory Translation lifecycle.
* مكتملة من ناحية CRUD.
* مكتملة من ناحية Persistence وConcurrency.
* موثقة ومختبرة وقابلة للإصدار.

هذه الوثيقة تمنع تنفيذ أي Phase مستقبلية اعتمادًا على التخمين أو تحديد Scope أثناء التنفيذ.

كل Phase يجب أن يكون نطاقها وAcceptance Criteria معروفين قبل بدء تنفيذها.

---

# 2. Package Identity

الهوية النهائية للمكتبة:

```text
Repository:
Maatify/category

Composer:
maatify/category

Root Namespace:
Maatify\Category\

Database Prefix:
maa_category_
```

ممنوع إعادة استخدام:

```text
Maatify\Catalog\
maa_catalog_
```

داخل Runtime الخاص بمكتبة Category.

Catalog هو مستهلك/aggregator محتمل للمكتبة، وليس مالكًا لها.

---

# 3. Standards Authority

المكتبة تخضع لأحدث نسخة معتمدة من:

```text
Maatify/php-engineering-standards
```

وبشكل خاص:

```text
standards/GITHUB_PHASE_STACK_WORKFLOW_AR.md

standards/modules/MODULE_BUILDING_STANDARD.md

standards/packages/PACKAGE_BUILDING_STANDARD.md
standards/packages/COMPOSER_PACKAGE_STANDARD.md
standards/packages/CI_WORKFLOW_STANDARD.md
standards/packages/LIBRARY_PRESENTATION_STANDARD.md
```

لا يجوز اعتبار implementation قديم أو architecture قديمة استثناءً تلقائيًا من الـStandards.

أي تعارض يجب أن يُحل صراحة قبل التنفيذ.

---

# 4. Standards Compliance Principle

الهدف ليس فقط:

```text
"الكود شغال"
```

ولا:

```text
"CRUD موجودة"
```

بل:

```text
Runtime
+ Architecture
+ Public API
+ Persistence
+ Tests
+ CI
+ Composer
+ Documentation
+ Consumer Verification
= Standard-Compliant Library
```

لا تعتبر المكتبة مكتملة طالما يوجد بند إلزامي في الـStandards غير مطبق أو غير محسوم.

---

# 5. Domain Ownership

## 5.1 Category

المكتبة تملك:

* Category identity.
* Stable Category code.
* Parent/child hierarchy.
* Category status.
* Category display order.
* Category timestamps.
* Soft delete.
* Restore.
* Cycle prevention.
* Hierarchy invariants.

## 5.2 Category Translation

المكتبة تملك:

* Translation identity.
* Category relationship.
* Language code storage.
* Name.
* Description.
* Translation timestamps.
* Soft delete.
* Restore.
* Translation logical identity.

---

# 6. Explicit Non-Goals

المكتبة لا تملك:

* Catalog Entity.
* Product.
* Variant.
* SKU.
* Barcode.
* Product Option.
* Option Value.
* Product ↔ Category mapping.
* Pricing.
* Inventory.
* Media.
* Cart.
* Order.
* Customer.
* Promotion.
* Discount.
* Shipping.
* Payment.
* HTTP.
* Slim.
* Controller.
* Route.
* Middleware.
* Permission.
* Twig.
* JavaScript.
* Admin UI.
* Customer UI.
* PHP-DI host bindings.

أي integration مع هذه المجالات يتم خارج المكتبة.

---

# 7. Artifact Naming Contract

هذه القاعدة Binding على كل Runtime جديد.

## 7.1 Commands

كل Mutation Input يجب أن يكون Command.

أمثلة:

```text
CreateCategoryCommand
MoveCategoryCommand
UpdateCategoryStatusCommand
UpdateCategoryDisplayOrderCommand
SoftDeleteCategoryCommand
RestoreCategoryCommand

CreateCategoryTranslationCommand
UpdateCategoryTranslationCommand
SoftDeleteCategoryTranslationCommand
RestoreCategoryTranslationCommand
```

كل Command:

* `final readonly`.
* Validation في constructor.
* لا يحتوي Business Orchestration.
* لا يحتوي SQL.
* لا يحتوي framework logic.
* اسمه ينتهي بـ `Command`.
* اسم الملف يطابق اسم الـClass.

---

# 7.2 DTOs

DTO تستخدم لتمثيل البيانات والنتائج، وليس Mutation Intent.

أمثلة:

```text
CategoryDTO
CategoryTranslationDTO
CategoryCollectionDTO
CategoryTranslationCollectionDTO
CategoryListCriteriaDTO
CategoryTranslationListCriteriaDTO
```

كل DTO:

* اسمها ينتهي بـ `DTO`.
* `final readonly`.
* تلتزم بعقد DTO الموجود في Package Standard.
* إذا كان الـStandard يتطلب `JsonSerializable` فيجب تطبيقه.

ممنوع استخدام DTO كبديل لـCommand في Create/Update/Delete/Restore operations.

---

# 7.3 Enums

كل Enum:

```text
*Enum
```

مثال:

```text
CategoryStatusEnum
CategoryDeletedStateEnum
```

ولا يتم إنشاء Enum إلا عند وجود finite domain state حقيقي.

---

# 7.4 Interfaces

كل Interface:

```text
*Interface
```

بما فيها Package Exception Marker.

مثال:

```text
CategoryExceptionInterface
CategoryCommandServiceInterface
CategoryQueryServiceInterface
```

---

# 7.5 Exceptions

كل Exception Class:

```text
*Exception
```

وتستخدم hierarchy مناسبة من:

```text
maatify/exceptions
```

---

# 8. Public Contract Rules

Public Service/Repository/Query contracts لا تعيد associative arrays.

ممنوع:

```text
array
array<string,mixed>
mixed
```

كـPublic Domain Data Contract إلا عندما يكون هناك سبب تقني داخلي صريح لا يمثل Domain API.

الـPublic Data تنتقل عبر DTOs.

الـPublic Mutations تستقبل Commands.

الـPublic Query Filters تستخدم typed DTOs/Enums عند الحاجة.

ملاحظة:

`JsonSerializable` داخل DTO لا يعني أن Service أو Repository تعيد array.

Serialization capability شيء، والـPublic PHP Contract شيء آخر.

---

# 9. Language Boundary

المكتبة تخزن:

```text
language_code VARCHAR(16)
```

لكن لا تملك:

* Host locale.
* Current user language.
* Preferred language.
* Fallback chain.
* Locale negotiation.

BCP-47 semantic validation وسياسة fallback تظل Host responsibility وفق Architecture المعتمدة، ما لم يتم تعديل هذا العقد صراحة.

المكتبة تملك فقط storage/domain validation الذي يتم اعتماده في Phase 0.

---

# 10. Phase Stack Execution Rule

كل Phase في هذه الـRoadmap تنفذ بنظام:

```text
main
└── phase-N-draft
    ├── blueprint
    ├── work-unit-1
    ├── work-unit-2
    ├── ...
    ├── verification
    ├── documentation
    └── final-review / required-fixes
```

القواعد:

1. Phase Draft تبدأ من أحدث `main`.
2. ممنوع التطوير مباشرة على Draft.
3. كل Component له Branch مستقل.
4. كل Branch تبدأ من أحدث Draft HEAD.
5. التنفيذ Sequential.
6. لا يبدأ Component جديد قبل اعتماد وSquash Merge السابق.
7. Component PR تستهدف Phase Draft.
8. Fixes تتم داخل نفس Component PR أو Required-Fix Component حسب حالة الـStack.
9. لا يدخل `main` إلا Phase مكتملة.
10. Phase Draft يتم Squash Merge إلى `main` بعد اكتمال Verification + Documentation + Final Review.

---

# 11. Phase 0 — Architecture & Standards Lock

## الهدف

إغلاق كل القرارات التي قد تسبب إعادة تصميم Runtime بعد التنفيذ.

## Required Documents

يجب اعتماد:

```text
/CATEGORY_PACKAGE_REFERENCE.md

/docs/architecture/CATEGORY_ARCHITECTURE.md

/docs/CATEGORY_LIBRARY_ROADMAP.md
```

الـPackage Reference هي stable package contract.

الـArchitecture توثق التفاصيل المعمارية.

الـRoadmap تحدد التنفيذ المرحلي.

---

## Required Decisions

يجب إغلاق:

### Package

* namespace.
* Composer identity.
* PHP support range.
* MySQL support contract.
* runtime dependencies.

### Category

* code validation.
* code immutability.
* parent rules.
* status rules.
* ordering rules.
* soft-delete rules.
* restore rules.
* timestamp rules.
* hierarchy rules.
* concurrency invariants.

### Translation

* language code syntactic contract.
* name validation.
* description validation.
* translation identity.
* delete/restore semantics.

### Persistence

* transaction ownership.
* lock boundaries.
* ordering integration.
* pagination integration.
* exception conversion/propagation.

---

# 12. Phase 0 Critical Standards Reconciliation — Translation Pattern

يجب حل هذه النقطة قبل الادعاء بأن المكتبة 100% Standard-Compliant.

الـCategory Architecture الحالية تعتمد:

```text
Category
+
Category Translation rows
```

ولا يوجد Base `name` داخل Category نفسها.

كما تستخدم:

```text
language_code
```

بينما Translation Pattern العام الموجود في Package Standard يحتوي assumptions مختلفة، منها Base value / translated value paths.

لذلك يجب في Phase 0 تحديد واحد فقط من الآتي عبر قرار رسمي:

### Option A

إثبات أن Translation Pattern العام غير منطبق على هذا Domain بالشكل الحرفي، وتوثيق سبب Architecture-specific applicability وفق الـStandards.

أو:

### Option B

تعديل Category Architecture لتصبح متوافقة مع الـTranslation contract الإلزامي إن ثبت أنه Binding على هذا النوع من المكتبات.

أو:

### Option C

تحديث الـEngineering Standard نفسه إذا ثبت أن الـPattern العام لا يصلح لكل Translation-only Domain.

ممنوع تجاهل التعارض.

ممنوع اعتبار المكتبة 100% Standard-Compliant قبل إغلاقه.

---

# 13. Phase 1 — Package Foundation

## الهدف

إنشاء Composer Library مستقلة صحيحة.

## Required Root Files

```text
README.md
CHANGELOG.md
CATEGORY_PACKAGE_REFERENCE.md
composer.json
phpstan.neon
phpunit.xml.dist
LICENSE

src/
tests/
schema/
docs/
.github/workflows/
```

حسب applicability الفعلية للـStandards.

---

## Composer

يجب أن يكون:

```text
maatify/category
```

بدون:

```text
version
composer.lock
```

في repository reusable library.

---

## Runtime Dependencies

حسب APIs المستخدمة فعليًا:

```text
maatify/exceptions
maatify/shared-common
maatify/persistence
```

كل Dependency تستخدم minimum stable version التي تحتوي على الـAPI المطلوب.

لا يتم الاعتماد على:

* branch.
* commit.
* unreleased API.
* docs-only proposed contract.

---

## Namespace

```text
Maatify\Category\
```

---

## Exit Gate

* Composer valid.
* PSR-4 صحيح.
* package structure مطابق.
* no legacy Catalog runtime naming.
* docs foundation موجودة.

---

# 14. Phase 2 — Schema & Core Domain Data

## الهدف

تثبيت database contract والـdata types الأساسية.

## Tables

```text
maa_category_categories

maa_category_category_translations
```

---

## Category Schema

الحقول الأساسية:

```text
id
parent_id
code
status
display_order
created_at
updated_at
deleted_at
```

---

## Translation Schema

الحقول الأساسية:

```text
id
category_id
language_code
name
description
created_at
updated_at
deleted_at
```

---

## Database Requirements

* InnoDB.
* utf8mb4.
* utf8mb4_unicode_ci.
* meaningful column comments.
* internal FK only.
* `ON DELETE RESTRICT`.
* `ON UPDATE RESTRICT`.
* unique Category code.
* unique translation logical identity.
* required indexes.
* application-owned timestamps.

---

## Translation Identity

الهوية المنطقية:

```text
(category_id, language_code)
```

ولا تتحرر بالـSoft Delete.

---

## Self-Parent Invariant

لا يستخدم:

```text
CHECK(parent_id <> id)
```

مع generated AUTO_INCREMENT identity بالشكل غير المدعوم في MySQL.

يتم تطبيق DB invariant عبر package-owned triggers حسب Architecture المعتمدة:

```text
AFTER INSERT
BEFORE UPDATE
```

مع Domain-level prevention أيضًا.

---

## MySQL

العقد الحالي المستهدف:

```text
MySQL 8.0.16+
```

ويجب إثباته باختبارات حقيقية.

---

## DTOs

هذه المرحلة تنشئ Data DTOs فقط، مثل:

```text
CategoryDTO
CategoryTranslationDTO
CategoryCollectionDTO
CategoryTranslationCollectionDTO
```

ولا تنشئ Mutation DTOs.

---

## Enums

مثل:

```text
CategoryStatusEnum
```

---

## Exit Gate

Schema + Core DTOs + Enums مثبتة Unit/Integration.

---

# 15. Phase 3 — Command & Validation Layer

## الهدف

إنشاء Mutation Input Contract الصحيح قبل Business Orchestration.

## Category Commands

```text
CreateCategoryCommand

MoveCategoryCommand

UpdateCategoryStatusCommand

UpdateCategoryDisplayOrderCommand

SoftDeleteCategoryCommand

RestoreCategoryCommand
```

---

## Category Translation Commands

```text
CreateCategoryTranslationCommand

UpdateCategoryTranslationCommand

SoftDeleteCategoryTranslationCommand

RestoreCategoryTranslationCommand
```

---

## Command Rules

كل Command:

```text
final readonly
```

ويطبق فقط validation يمكن معرفتها من input نفسه.

أمثلة validation التي قد تكون Command-owned بعد اعتماد Architecture:

* positive canonical IDs.
* non-empty required strings.
* allowed enum value.
* max storage lengths.
* directly detectable self-reference.

ولا يطبق:

* database existence.
* uniqueness.
* ancestor cycle lookup.
* child dependency.
* transaction rules.
* business orchestration.

هذه مسؤولية Service/Persistence layers.

---

## Display Order Rule

`display_order` لا يدخل في:

```text
CreateCategoryCommand
```

ولا generic Update Command.

تغيير position يتم فقط بواسطة:

```text
UpdateCategoryDisplayOrderCommand
```

واستخدام shared Ordering capability.

---

## Generic Update

لا يتم إنشاء:

```text
UpdateCategoryCommand
```

بشكل generic لمجرد إكمال CRUD.

لأن Category mutable state موزعة على operations مستقلة:

* Move.
* Status.
* Display Order.

والـcode immutable.

---

## Exit Gate

كل mutation intent له Command مستقلة وواضحة.

---

# 16. Phase 4 — Persistence & Transaction Foundation

## الهدف

توفير Infrastructure قابلة للاستخدام بواسطة Services بدون business logic داخل repositories.

## Required Contracts

حسب الـArchitecture النهائية:

```text
CategoryCommandRepositoryInterface

CategoryTranslationCommandRepositoryInterface

CategoryQueryReaderInterface

CategoryReadQueryInterface

CategoryTransactionInterface
```

وأي interfaces أخرى تكون لها replaceability requirement فعلية.

لا يتم إنشاء interfaces لمجرد الاختبارات.

---

## PDO

* PDO مباشرة.
* no ORM.
* no external query builder.
* unique named placeholders.
* package-local JOINs فقط.
* no Host FK.
* no Host JOIN.

---

## Repository Return Contract

Command Repository تتبع layer contract الخاص بالـPackage Standard.

مثل:

```text
create → int
mutation → bool
findById → ?DTO
```

الـService هي التي تحول Repository `false/null` إلى Domain NotFound عند الحاجة.

---

## Transaction Boundary

يجب استخدام transaction adapter/framework-neutral contract بحيث:

* transaction تبدأ مرة واحدة.
* commit صحيح.
* rollback فقط عند transaction نشطة.
* original Throwable لا يضيع.
* لا swallowing.
* لا blanket wrapping.

الـService تقوم Business Orchestration.

الـPDO transaction mechanics تبقى Infrastructure concern.

---

## Exception Classification

Known package-owned failures يمكن تحويلها إلى named package exceptions.

Unknown PDO/infrastructure failures لا يتم تحويلها تعسفيًا.

SQLSTATE `23xxx` لا يعني تلقائيًا duplicate.

Duplicate classification يجب أن تستخدم documented driver evidence.

---

## Hydration

* type narrowing.
* no blind `mixed` casts.
* PHPStan max.
* storage-shape failures تعامل حسب package exception contract.

---

## Exit Gate

Persistence كاملة وقابلة للاختبار بمعزل عن Services.

---

# 17. Phase 5 — Category Create & Mutation CRUD

## الهدف

إكمال Category mutation lifecycle.

## Create

```text
CreateCategoryCommand
```

المطلوب:

* immutable code.
* uniqueness.
* optional parent.
* parent existence.
* transaction/locking حيث يلزم.
* application-managed timestamps.
* ordering assignment عبر shared persistence capability.

---

## Move

```text
MoveCategoryCommand
```

يمنع:

* self-parent.
* direct cycle.
* indirect cycle.

ويتعامل مع locking الكامل المطلوب لمنع race conditions.

---

## Update Status

```text
UpdateCategoryStatusCommand
```

الحالة Typed Enum.

لا توجد status strings حرة في public contract.

---

## Update Display Order

```text
UpdateCategoryDisplayOrderCommand
```

تستخدم:

```text
maatify/persistence
```

ولا يوجد local ordering engine.

---

## Delete

```text
SoftDeleteCategoryCommand
```

الحذف الطبيعي للمكتبة:

```text
Soft Delete
```

لا يمكن Soft Delete لـCategory لديها non-deleted child.

---

## Restore

```text
RestoreCategoryCommand
```

يعيد نفس identity.

لا ينشئ row بديلة.

لا يسمح بإعادة استخدام code نتيجة soft delete.

---

## Hard Delete

Hard Delete ليست جزءًا من Category Domain CRUD الطبيعي في `v1.0.0`.

لا يتم إضافتها لمجرد أن Generic Package Standard يذكر repository return contract لعمليات hard delete.

إضافتها مستقبلًا تحتاج Architecture decision صريح.

---

## Exit Gate

Category mutation side كاملة.

---

# 18. Phase 6 — Category Translation CRUD

## الهدف

إكمال lifecycle للTranslations.

التنفيذ القديم الذي كان يوفر Update Translation فقط لا يعتبر CRUD كاملة.

---

## Create

```text
CreateCategoryTranslationCommand
```

المطلوب:

* category identity.
* language code.
* name.
* description.
* uniqueness.
* timestamps.

إذا كانت logical identity موجودة soft-deleted، لا يتم إنشاء identity بديلة بشكل صامت.

Restore هي operation مستقلة.

---

## Read

Read side يتم تنفيذه في Query phases، لكن يجب أن يكون contract المطلوب معروفًا هنا.

---

## Update

```text
UpdateCategoryTranslationCommand
```

المسموح:

```text
name
description
```

الممنوع:

```text
category_id
language_code
```

لأنهما جزء من logical identity.

---

## Soft Delete

```text
SoftDeleteCategoryTranslationCommand
```

الحذف لا يحرر:

```text
(category_id, language_code)
```

---

## Restore

```text
RestoreCategoryTranslationCommand
```

يعيد نفس row ونفس identity.

---

## Parent Category State

قبل التنفيذ يجب أن تكون Architecture حاسمة بشأن:

* إنشاء Translation لـsoft-deleted Category.
* تعديل Translation لـsoft-deleted Category.
* حذف/استعادة Translation عندما تكون Category نفسها محذوفة.

ممنوع ترك هذه semantics ليقررها implementer أثناء كتابة الكود.

---

## Exit Gate

Translation mutation CRUD كاملة.

---

# 19. Phase 7 — Management Read Model

## الهدف

إكمال Read من CRUD للـmanagement/use-case APIs.

هذه queries لا تمثل consumer visibility.

---

## Category Reads

على الأقل:

* Get Category by ID.
* Get Category by code إذا كان contract النهائي يحتاجه.
* List Categories.
* List roots.
* List direct children.
* Read deleted records عند الطلب الصريح.
* Filter by status.
* Filter by deleted state.

---

## Translation Reads

* Get Translation.
* List Translations for Category.
* Management translation listing.
* deleted-state handling.

---

## Criteria DTOs

Filters/criteria تستخدم DTOs مثل:

```text
CategoryListCriteriaDTO

CategoryTranslationListCriteriaDTO
```

وليس associative arrays.

---

## Deleted State

يجب أن تكون semantics صريحة:

```text
Active/non-deleted only
Includes deleted
Deleted only
```

ولا توجد method غامضة يعتمد معناها على assumption داخلي.

---

## Search

إذا تم اعتماد Search كجزء من Management API:

* تكون package-owned fields فقط.
* لا Product joins.
* لا Host joins.
* تستخدم criteria DTO.
* pagination contract واضح.

---

## Exit Gate

الإدارة تستطيع قراءة الحالة الفعلية للـCategory/Translations بدون استخدام Consumer Visibility queries.

---

# 20. Phase 8 — Consumer Visibility Query Model

## الهدف

توفير consumer-facing query contract منفصلة.

## Visible Category

Category مرئية عندما:

```text
deleted_at IS NULL
AND status = active
```

وكامل ancestor chain أيضًا:

```text
deleted_at IS NULL
AND status = active
```

---

## Required Reads

* Find visible Category by ID.
* List visible roots.
* List visible children.
* List visible translations for visible Category.

---

## Hierarchy Visibility

يجب إثبات:

* inactive Category hidden.
* deleted Category hidden.
* inactive ancestor hides descendants.
* deleted ancestor hides descendants.

---

## Language

المكتبة لا تقوم باختيار:

* current language.
* preferred language.
* fallback language.

إلا إذا تغير Architecture contract رسميًا.

---

## Exit Gate

Management reads وConsumer reads منفصلين تمامًا.

---

# 21. Phase 9 — Pagination, Ordering & Query Hardening

## الهدف

منع وجود list APIs غير production-ready أو implementation مكرر.

## Ordering

الترتيب deterministic.

بالنسبة للـCategory hierarchy:

```text
display_order, id
```

ما لم تعتمد Architecture ترتيبًا مختلفًا.

---

## Shared Ordering

كل row-position mutation تستخدم stable API من:

```text
maatify/persistence
```

ولا يتم نسخ:

* shifting SQL.
* locking engine.
* clamping.
* ordering transactions.

---

## Pagination

إذا كانت Management lists تحتاج pagination:

يتم استخدام stable Pagination capability من:

```text
maatify/persistence
```

ولا يتم بناء paginator محلي.

---

## Public Results

لا ترجع Public Query:

```text
array
```

بل typed DTO result مناسب.

إذا كانت persistence library تعيد result object خاص بها ويتم الحفاظ على Category public contract، يجوز thin adapter بدون إعادة تنفيذ pagination mechanics.

---

## Exit Gate

كل list contract deterministic ومحدودة/موثقة بشكل production-safe.

---

# 22. Phase 10 — Concurrency & Invariant Hardening

## الهدف

إثبات صحة المكتبة تحت العمليات المتزامنة.

## Hierarchy Scenarios

اختبار Real MySQL لـ:

* concurrent moves.
* parent change races.
* self-parent.
* direct cycle.
* indirect cycle.
* ancestor-chain locks.

---

## Delete Scenarios

اختبار:

* child existing during delete.
* child being created أثناء delete.
* parent deletion race.
* no invalid parent/child lifecycle state.

---

## Ordering

اختبار:

* root ordering.
* sibling ordering.
* concurrent reorder.
* target timestamps.
* atomic movement.

---

## Restore

اختبار:

* Category code uniqueness.
* Translation logical identity uniqueness.
* same identity restoration.
* no replacement row behavior.

---

## Transaction Failure

إثبات:

* rollback.
* no partial write.
* original throwable preserved.
* lock/transaction cleanup.

---

## Exit Gate

كل invariant حرجة مثبتة Integration tests حقيقية.

---

# 23. Phase 11 — Exception & Failure Contract

## الهدف

تثبيت failure surface قبل Stable API.

## Candidate Package Exceptions

حسب ما تثبته Architecture:

```text
CategoryInvalidArgumentException
CategoryNotFoundException
CategoryCodeAlreadyExistsException
CategoryCycleException
CategoryHasNonDeletedChildrenException

CategoryTranslationNotFoundException
CategoryTranslationAlreadyExistsException

CategoryPersistenceException
CategoryTransactionException
```

لا تعتبر القائمة إلزامية لمجرد وجودها هنا؛ Phase Blueprint يجب أن يثبت semantic necessity لكل exception.

---

## Package Marker

```text
CategoryExceptionInterface
```

ويمتد من:

```text
Throwable
```

وفق الـStandard.

---

## Required Audit

* no generic RuntimeException لpackage-owned semantic failure.
* no misleading error codes.
* no swallowed exception.
* no blind catch-all wrapping.
* no blanket SQLSTATE mapping.
* `previous` preserved عند wrapping.
* infrastructure propagation documented.

---

## Exit Gate

Failure contract كاملة داخل Package Reference.

---

# 24. Phase 12 — Package / Composer Compliance

## Composer Review

يجب التحقق من:

* canonical package name.
* correct description.
* correct repository metadata.
* `type: library`.
* PSR-4.
* direct dependencies.
* require vs require-dev.
* minimum PHP.
* alphabetic package maps.
* `sort-packages`.
* valid scripts.
* no committed `version`.
* no committed `composer.lock`.

---

## Required Commands

يجب اجتياز:

```text
composer validate --strict

composer dump-autoload --optimize --strict-psr

composer check-platform-reqs

composer audit --no-interaction --abandoned=fail
```

---

## Dependency Resolution

يجب اختبار:

```text
Latest-compatible
Lowest-supported
```

ولا يكفي اختبار latest فقط.

---

## Exit Gate

المكتبة يمكن تثبيتها كمكتبة Composer حقيقية.

---

# 25. Phase 13 — CI Compliance

## الهدف

تحويل متطلبات الجودة إلى Gates فعلية.

## PHP Matrix

يتم تحديد PHP compatibility contract في Phase 0.

إذا كان Composer يعلن مثلًا:

```text
php >=8.2
```

يجب على CI تغطية كل released PHP minor التي تقع داخل الـsupport contract وفق CI Standard، وليس minimum/latest فقط إذا كان ذلك يخالف الـStandard.

---

## Required CI Areas

* Composer strict validation.
* Latest dependency resolution.
* Lowest dependency resolution.
* platform check.
* syntax.
* PHPStan max.
* Unit tests.
* Regression tests حيث تنطبق.
* Real MySQL Integration.
* Full PHPUnit suite.
* Composer audit fail-closed.
* workflow lint.
* aggregate quality gate.

---

## Workflow Security

* minimum permissions.
* `contents: read`.
* immutable action pinning.
* no unsafe `pull_request_target`.
* explicit timeouts.
* concurrency handling.
* health checks.
* explicit DB image/version.

---

## Exit Gate

كل required gates خضراء على نفس exact commit.

---

# 26. Phase 14 — Documentation & Package Presentation

## README

يجب أن يوضح:

* purpose.
* installation.
* package scope.
* non-goals.
* requirements.
* basic usage.
* database requirement.
* links to Package Reference/docs.

---

## Package Reference

```text
/CATEGORY_PACKAGE_REFERENCE.md
```

هو المرجع stable الوحيد للـPublic Package Contract.

يشمل:

* Public Runtime API inventory.
* Commands.
* DTOs.
* Enums.
* Interfaces.
* Exceptions.
* CRUD.
* Read contracts.
* visibility.
* persistence.
* failure behavior.
* compatibility.
* package boundaries.

---

## Detailed Docs

تحت:

```text
/docs/
```

مثل:

```text
/docs/architecture/CATEGORY_ARCHITECTURE.md

/docs/CATEGORY_LIBRARY_ROADMAP.md

/docs/DATABASE.md

/docs/INTEGRATION.md

/docs/EXCEPTIONS.md
```

عند الحاجة الفعلية.

---

## CHANGELOG

يحتوي:

```text
[Unreleased]
```

وتبدأ release history عند:

```text
[1.0.0]
```

طبقًا للـPresentation Standard.

---

## Exit Gate

كل documentation claims مطابقة للRuntime الفعلي.

---

# 27. Phase 15 — Standalone Consumer Verification

## الهدف

إثبات أن المكتبة مستقلة فعليًا عن المصدر الذي تم استخراجها منه.

يجب اختبار clean consumer installation.

## يجب إثبات

* `composer require` يعمل.
* PSR-4 يعمل.
* لا AdminKernel dependency.
* لا `admin-control-panel` dependency.
* لا Catalog runtime dependency.
* لا Slim dependency.
* لا PHP-DI host dependency.
* لا Host namespace.
* لا Host database tables.
* schema تثبت منفردة.
* services/repositories قابلة للـhost wiring.
* runtime لا يحتاج ملفات خارج package.

---

## Exit Gate

`maatify/category` تعمل خارج أي Maatify host repository.

---

# 28. Phase 16 — Final API Freeze Review

## الهدف

مراجعة كل Public API قبل أول Stable Release.

يتم تدقيق:

* namespace.
* Commands.
* DTOs.
* Enum values.
* Interfaces.
* Exceptions.
* constructor signatures.
* method signatures.
* return types.
* table names.
* column names.
* database constraints.
* Composer dependencies.
* PHP constraint.
* MySQL contract.

---

## Cleanup

قبل Stable:

* dead code removed.
* unused imports removed.
* stale runtime removed.
* legacy Catalog naming removed.
* duplicate contracts removed.
* speculative APIs removed.

التعليقات وTODOs لا تحذف بشكل أعمى.

إذا كانت غير صحيحة يتم تعديلها أو التعامل معها صراحة وفق سياقها.

---

## Exit Gate

لا يوجد Public API معروف مسبقًا أنه يحتاج breaking redesign بعد الإصدار.

---

# 29. Phase 17 — Release Readiness

## Exact Release Candidate Verification

على exact candidate SHA:

* PHPStan max.
* Unit.
* Regression حيث تنطبق.
* Real MySQL Integration.
* Full tests.
* latest dependencies.
* lowest dependencies.
* all supported PHP minors.
* Composer strict validation.
* platform requirements.
* audit.
* workflow lint.
* clean repository.
* docs sweep.
* architecture review.
* API inventory.
* standalone installation test.

---

## Release Files

* README final.
* Package Reference final.
* Architecture final.
* Roadmap updated with completed status.
* CHANGELOG release entry ready.
* Composer metadata final.

---

## Stable Release

بعد اجتياز كل ما سبق فقط تصبح المكتبة جاهزة لـ:

```text
v1.0.0
```

---

# 30. Final CRUD Matrix

## Category

### Create

```text
CreateCategoryCommand
```

### Read

Management:

* by ID.
* by code when approved.
* roots.
* children.
* lists.
* deleted-state reads.

Consumer:

* visible by ID.
* visible roots.
* visible children.

### Update

```text
MoveCategoryCommand

UpdateCategoryStatusCommand

UpdateCategoryDisplayOrderCommand
```

لا Generic Update بدون Domain meaning.

### Delete

```text
SoftDeleteCategoryCommand
```

### Restore

```text
RestoreCategoryCommand
```

---

# 31. Translation CRUD Matrix

## Create

```text
CreateCategoryTranslationCommand
```

## Read

* translation detail.
* list by Category.
* management list.
* visible translations.

## Update

```text
UpdateCategoryTranslationCommand
```

## Delete

```text
SoftDeleteCategoryTranslationCommand
```

## Restore

```text
RestoreCategoryTranslationCommand
```

---

# 32. Command / DTO Separation Checklist

## Commands

* [ ] CreateCategoryCommand
* [ ] MoveCategoryCommand
* [ ] UpdateCategoryStatusCommand
* [ ] UpdateCategoryDisplayOrderCommand
* [ ] SoftDeleteCategoryCommand
* [ ] RestoreCategoryCommand
* [ ] CreateCategoryTranslationCommand
* [ ] UpdateCategoryTranslationCommand
* [ ] SoftDeleteCategoryTranslationCommand
* [ ] RestoreCategoryTranslationCommand

## DTOs

* [ ] CategoryDTO
* [ ] CategoryTranslationDTO
* [ ] CategoryCollectionDTO
* [ ] CategoryTranslationCollectionDTO
* [ ] Query Criteria DTOs where needed
* [ ] Pagination/result DTOs where Category owns a stable public result contract

Mutation Commands ممنوع تسميتها DTO.

---

# 33. Migration From Previous Admin Implementation

الشغل السابق داخل:

```text
admin-control-panel
```

يعتبر implementation evidence فقط.

تقريبًا:

```text
Old Phase 1
→ Schema + Foundation

Old Phase 2
→ Category mutations/persistence

Old Phase 3
→ Consumer visible query
```

لكن لا يتم نسخه كما هو.

---

## Known Migration Gaps

التنفيذ القديم استخدم أسماء مثل:

```text
CreateCategoryDTO
MoveCategoryDTO
RestoreCategoryDTO
SoftDeleteCategoryDTO
UpdateCategoryStatusDTO
UpdateCategoryDisplayOrderDTO
UpdateCategoryTranslationDTO
```

هذه mutation inputs يجب ألا تنتقل بهذه الأسماء إلى المكتبة الجديدة.

يجب تحويل contract الجديد إلى:

```text
CreateCategoryCommand
MoveCategoryCommand
RestoreCategoryCommand
SoftDeleteCategoryCommand
UpdateCategoryStatusCommand
UpdateCategoryDisplayOrderCommand
UpdateCategoryTranslationCommand
```

مع إضافة Translation Commands الناقصة.

---

## Other Known Gaps

* Master Roadmap لم تكن موجودة.
* Translation CRUD لم تكن كاملة.
* Management Read Model لم تكن كاملة.
* standalone Composer verification لم تكن مكتملة.
* release gate لم تكن مكتملة.
* naming كان ما زال Catalog-centric.
* table prefix كان `maa_catalog_`.
* package namespace كان Catalog-oriented.
* DTO contract يحتاج إعادة مطابقة مع الـStandard الحالي.
* Translation Pattern standards reconciliation لم يتم حسمه.

---

# 34. Definition of Done

Category Library لا تعتبر مكتملة لأن:

```text
CRUD موجودة
```

فقط.

ولا لأن:

```text
PHPStan = green
```

ولا لأن:

```text
Tests = green
```

بل تعتبر مكتملة عندما يتحقق:

```text
Architecture Locked

+ Commands Correct

+ DTO Contracts Correct

+ Category CRUD Complete

+ Translation CRUD Complete

+ Management Reads Complete

+ Consumer Visibility Complete

+ Persistence Correct

+ Concurrency Verified

+ Exceptions Stable

+ Composer Compliant

+ CI Compliant

+ Documentation Complete

+ Standalone Consumer Verified

+ Public API Frozen

+ Release Candidate Fully Green
```

عندها فقط يمكن اعتبار:

```text
maatify/category
```

مكتبة مكتملة ومتوافقة مع الـStandards وجاهزة لأول إصدار Stable.
