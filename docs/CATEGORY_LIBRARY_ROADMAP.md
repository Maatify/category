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

## 2. Package Identity

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

## 3. Standards Authority

المصدر التنفيذي لهذه الـRoadmap هو النسخة المحلية المثبتة من:

```text
Maatify/php-engineering-standards
```

والـsnapshot المعتمد حاليًا هو:

```text
4918da9f15feb1b336a822d53afe497a6fef885e
```

وتنطبق على هذه المكتبة الـProfiles التالية:

```text
standards/ai/AI_COLLABORATION_WORKFLOW_AR.md
standards/GITHUB_PHASE_STACK_WORKFLOW_AR.md
standards/modules/MODULE_BUILDING_STANDARD.md
standards/packages/PACKAGE_BUILDING_STANDARD.md
standards/packages/COMPOSER_PACKAGE_STANDARD.md
standards/packages/CI_WORKFLOW_STANDARD.md
standards/packages/LIBRARY_PRESENTATION_STANDARD.md
standards/testing/TESTING_STANDARD.md
```

تطبيق `MODULE_BUILDING_STANDARD.md` مقصود لأن `category` مصنفة كـBase Module قابل للاستخراج. وتطبق قواعد Persistence لأن المكتبة تملك schema وسلوك PDO/MySQL.

القواعد التفصيلية تظل مملوكة للملفات المرجعية أعلاه؛ هذه الوثيقة تسجل فقط قرارات Category الخاصة، وترتيب التنفيذ، وشروط القبول الخاصة بالحزمة.

لا يجوز استخدام `main` المتحرك من مستودع الـstandards كمصدر تنفيذ مباشر. أي ترقية للـsnapshot أو تعديل للـstandard تغيير مستقل يحتاج قرارًا ومراجعة منفصلين.

لا يجوز اعتبار implementation قديم أو architecture قديمة استثناءً تلقائيًا من الـStandards. وأي تعارض يجب أن يُحل صراحة قبل التنفيذ.

---

## 4. Standards Compliance Principle

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

### 4.1 Current Baseline and Target Contract

هذه الوثيقة تصف Target Contract وليست إثباتًا بأن كل ما في الفرع الحالي يطابقه.

عند إنشاء الـRoadmap كان baseline تنفيذ الحزمة هو:

```text
codex/phase-1-category-package
e3999d250d9c02fef7e05fbe5dd696ab71a7315d
```

كان baseline يتضمن legacy operation inputs، بينما العقد المستهدف هو أن تستقبل كل
mutation typed Commands. أُغلقت Migration Gap الخاصة بـPhase 1 بنقل هذه
العقود إلى Commands وتحديث المرجع والاختبارات؛ ولا يصبح العقد Public API
Frozen إلا في Phase 16 بعد توحيد الكود والمرجع والاختبارات.

---

## 5. Domain Ownership

### 5.1 Category

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

### 5.2 Category Translation

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

## 6. Explicit Non-Goals

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

## 7. Artifact Naming Contract

الأسماء والعقود الخاصة بـCategory في هذا القسم هي Target Inventory. قواعد
التسمية، `final readonly`، وصيغة الملفات مملوكة للـ
[PACKAGE_BUILDING_STANDARD.md](php-engineering-standards/standards/packages/PACKAGE_BUILDING_STANDARD.md)
ولا تعاد صياغتها هنا.

### 7.1 Commands

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

كل Mutation Intent في Category يستخدم أحد الـCommands أعلاه، ولا يستخدم DTO
بديلًا عنها. يظل الفصل بين input validation وBusiness Orchestration وSQL
والـframework wiring خاضعًا للـPackage وBase Module Standards.

---

### 7.2 DTOs

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

* تلتزم بالكامل بعقد DTO وCollection DTO الموجود في
  [PACKAGE_BUILDING_STANDARD.md](php-engineering-standards/standards/packages/PACKAGE_BUILDING_STANDARD.md)،
  بما في ذلك serialization وtyped iteration عند انطباقه.

ممنوع استخدام DTO كبديل لـCommand في Create/Update/Delete/Restore operations.

---

### 7.3 Enums

مثال:

```text
CategoryStatusEnum
CategoryDeletedStateEnum
```

ولا يتم إنشاء Enum إلا عند وجود finite domain state حقيقي.

---

### 7.4 Interfaces

كل Interface يتبع قواعد التسمية والـmarker contract في الـPackage وBase Module
Standards.

مثال:

```text
CategoryExceptionInterface
CategoryCommandServiceInterface
CategoryQueryServiceInterface
```

---

### 7.5 Exceptions

كل Exception Class والـhierarchy الخاصة بها تتبع الـPackage وBase Module
Standards، مع إبقاء exceptions الخاصة بـCategory ضمن الـinventory أعلاه.

---

## 8. Public Contract Rules

تفاصيل Public PHP Contract، DTO serialization، وRepository/Service return
types مملوكة للـ
[PACKAGE_BUILDING_STANDARD.md](php-engineering-standards/standards/packages/PACKAGE_BUILDING_STANDARD.md).
تطبيق Category الخاص هو أن الـmutations تستقبل Commands، والـreads تعيد DTOs
أو Collections typed، ولا تعتمد على associative arrays كعقد Domain.

---

## 9. Language Boundary

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

## 10. Phase Stack Execution Rule

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

هذه القواعد لا تمنح صلاحية Merge أو Tag أو Release. تظل صلاحية الدمج والاعتماد النهائي لمالك المشروع، وتطبق قيود عدم الـamend والـforce-push وقواعد Review/Verification من الـPhase Stack Standard.

---

## 11. Phase 0 — Architecture & Standards Lock

### الهدف

إغلاق كل القرارات التي قد تسبب إعادة تصميم Runtime بعد التنفيذ.

### Required Documents

يجب اعتماد:

```text
CATEGORY_PACKAGE_REFERENCE.md

docs/architecture/CATEGORY_ARCHITECTURE.md

docs/CATEGORY_LIBRARY_ROADMAP.md
```

الـPackage Reference هي المصدر الـcanonical الوحيد لعقد الحزمة بعد اعتماده، ويجب أن تظل متزامنة مع Target Contract. أثناء مراحل الـmigration لا تعتبر أي قائمة قديمة للـAPI عقدًا نهائيًا؛ تُحدّث في نفس الـPhase التي تغيّر السلوك العام.

الـArchitecture توثق التفاصيل المعمارية.

الـRoadmap تحدد التنفيذ المرحلي.

---

### Required Decisions

يجب إغلاق:

#### Package

* namespace.
* Composer identity.
* PHP support range.
* MySQL support contract.
* runtime dependencies.

#### Category

* code validation.
* code immutability.
* parent rules.
* status rules.
* ordering rules.
* nullable root scope (`parent_id IS NULL`) and sibling scope rules.
* creation-time position policy and its transaction/locking boundary.
* soft-delete rules.
* restore rules.
* timestamp rules.
* hierarchy rules.
* concurrency invariants.

#### Translation

* language code syntactic contract.
* name validation.
* description validation.
* translation identity.
* delete/restore semantics.
* behavior when the parent Category is inactive or soft-deleted for every Translation mutation.

#### Persistence

* transaction ownership.
* lock boundaries.
* ordering integration.
* `getNextPosition()` versus movement transaction ownership.
* pagination integration.
* exception conversion/propagation.

#### Query

* management مقابل consumer read boundary.
* اعتماد أو تأجيل `get by code` وSearch وPagination في `v1.0.0`.
* مصادر البيانات والـDTOs والـvisibility rules لكل read contract معتمد.

---

## 12. Phase 0 Translation Architecture Contract

Category translation architecture is a natural part of the Domain contract.
The base Category does not own a localized `name`; `name` and `description`
are owned by Category Translation.

The logical identity is `(category_id, language_code)`. The Package owns the
syntactic and storage validation of `language_code` required by its contract.
The Host owns semantic language validation and fallback/locale policy.

This contract is compatible with the Package Standard at snapshot
`4918da9f15feb1b336a822d53afe497a6fef885e`. No Translation standards blocker
is open.

---

## 13. Phase 1 — Package Foundation

### الهدف

إنشاء Composer Library مستقلة صحيحة.

### Required Root Files

```text
README.md
CHANGELOG.md
CATEGORY_PACKAGE_REFERENCE.md
composer.json
phpstan.neon
phpunit.xml.dist
LICENSE
SECURITY.md
CONTRIBUTING.md
CODE_OF_CONDUCT.md

src/
tests/
schema/
docs/
.github/workflows/
```

حسب applicability الفعلية للـStandards.

---

### Composer

هوية الحزمة هي:

```text
maatify/category
```

وتفاصيل metadata وautoload وstability وlock-file policy مملوكة لـ
[COMPOSER_PACKAGE_STANDARD.md](php-engineering-standards/standards/packages/COMPOSER_PACKAGE_STANDARD.md).

---

### Runtime Dependencies

حسب APIs المستخدمة فعليًا:

```text
maatify/exceptions
maatify/shared-common
maatify/persistence
```

كل Dependency تستخدم minimum stable version التي تحتوي على الـAPI المطلوب.
ولا تعتمد الحزمة على API غير منشورة أو غير مثبتة؛ تفاصيل dependency constraints
مملوكة للـComposer Standard.

---

### Namespace

```text
Maatify\Category\
```

---

### Exit Gate

* Composer valid.
* PSR-4 صحيح.
* package structure مطابق.
* no legacy Catalog runtime naming.
* docs foundation موجودة.

---

## 14. Phase 2 — Schema & Core Domain Data

### الهدف

تثبيت database contract والـdata types الأساسية.

### Tables

```text
maa_category_categories

maa_category_category_translations
```

---

### Category Schema

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

### Translation Schema

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

### Database Requirements

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
* `parent_id` nullable; `NULL` means root and each non-NULL value identifies the sibling scope.
* `display_order` has no schema-default `0`; creation receives a valid position from the shared Ordering API.

يجب تثبيت types وnullability وdefaults وindexes وconstraints والتعليقات لكل field في الـschema والـPackage Reference؛ لا يترك أي جزء من storage contract لقرار منفذ المرحلة.

---

### Translation Identity

الهوية المنطقية:

```text
(category_id, language_code)
```

ولا تتحرر بالـSoft Delete.

---

### Self-Parent Invariant

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

### MySQL

العقد الحالي المستهدف:

```text
MySQL 8.0.16+
```

ويجب إثباته باختبارات حقيقية.

### Creation Position Acceptance

إنشاء Root Category وChild Category يجب أن يحصل على position صالح داخل الـscope الصحيح عبر shared Ordering API، داخل transaction وبحدود الـlocking المعتمدة. يجب أن ينجح تحريك كل row مباشرة بعد إنشائه دون أي SQL normalization من الاختبار.

---

### DTOs

هذه المرحلة تنشئ Data DTOs فقط، مثل:

```text
CategoryDTO
CategoryTranslationDTO
CategoryCollectionDTO
CategoryTranslationCollectionDTO
```

ولا تنشئ mutation inputs؛ تبقى هذه المسؤولية ضمن طبقة Commands المحددة في
Phase 3.

---

### Enums

مثل:

```text
CategoryStatusEnum
```

---

### Exit Gate

Schema + Core DTOs + Enums مثبتة Unit/Integration، مع إثبات nullable root scope وcreation-time ordering للـroot والـchild.

---

## 15. Phase 3 — Command & Validation Layer

### الهدف

استكمال وتثبيت Command & Validation Layer وفق مسؤوليات Phase 3 المتبقية.
تم تقديم الـ10 typed mutation Commands الأساسية مبكرًا ضمن Phase 1 لإغلاق
Migration Gap الخاصة بـmutation inputs؛ لذلك لا تعيد Phase 3 إنشاءها من الصفر
ولا تعتبر مكتملة بمجرد وجود هذه Commands. تظل Phase 3 مسؤولة عن استكمال ما
تبقى من validation وdomain-contract completion وإثبات معايير القبول الخاصة
بها أدناه.

القوائم التالية هي inventory الـCommands الموجودة فعليًا والمستخدمة في
Runtime، وتُعرض هنا كـbaseline لهذه المرحلة.

### Category Commands

```text
CreateCategoryCommand

MoveCategoryCommand

UpdateCategoryStatusCommand

UpdateCategoryDisplayOrderCommand

SoftDeleteCategoryCommand

RestoreCategoryCommand
```

---

### Category Translation Commands

```text
CreateCategoryTranslationCommand

UpdateCategoryTranslationCommand

SoftDeleteCategoryTranslationCommand

RestoreCategoryTranslationCommand
```

---

### Command Rules

تستكمل Phase 3 حسم وتثبيت حدود validation الخاصة بكل Command وفق Architecture
Category. وما تبقى من contract completion يجب أن يثبت على الأقل:

* positive canonical IDs.
* non-empty required strings.
* allowed enum value.
* max storage lengths.
* directly detectable self-reference.

أما فصل input validation عن database checks وbusiness orchestration والـframework
wiring فيتبع [PACKAGE_BUILDING_STANDARD.md](php-engineering-standards/standards/packages/PACKAGE_BUILDING_STANDARD.md)
و[MODULE_BUILDING_STANDARD.md](php-engineering-standards/standards/modules/MODULE_BUILDING_STANDARD.md).

---

### Display Order Rule

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

إنشاء Category لا يقبل position من المستهلك ولا يعتمد على schema default؛ Service/Persistence integration تستدعي stable Ordering API للحصول على position داخل الـscope الصحيح قبل الحفظ.

---

### Generic Update

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

### Exit Gate

وجود الـCommands الأساسية واستخدامها في Runtime ليس وحده Exit Gate. لا تعتبر
Phase 3 مكتملة إلا بعد استكمال validation/domain-contract completion المتبقي
وإثبات acceptance criteria الخاصة بهذه الطبقة، مع بقاء كل mutation intent
مرتبطًا بـCommand مستقلة وواضحة.

---

## 16. Phase 4 — Persistence & Transaction Foundation

### الهدف

توفير Infrastructure قابلة للاستخدام بواسطة Services بدون business logic داخل repositories.

### Required Contracts

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

### PDO

تطبق قواعد PDO وSQL والـhost isolation كما هي مملوكة للـ
[PACKAGE_BUILDING_STANDARD.md](php-engineering-standards/standards/packages/PACKAGE_BUILDING_STANDARD.md)
و[MODULE_BUILDING_STANDARD.md](php-engineering-standards/standards/modules/MODULE_BUILDING_STANDARD.md).
الـJOINs والعلاقات هنا package-local فقط وفق حدود Category.

### Shared Ordering Boundary

`getNextPosition()` لا يبدأ transaction ولا يكتسب lock. عند استخدامه لإنشاء Category، يملك caller transaction وقفل الـscope المناسب، بما في ذلك scope الجذر ذي `parent_id IS NULL`.

حركة row تستخدم operation الحركة المستقرة من `maatify/persistence`، ولا يتم استدعاؤها داخل transaction نشطة إذا كان عقد الـAPI يمنع ذلك. لا تنسخ الحزمة shifting أو clamping أو locking أو transaction mechanics.

---

### Repository Return Contract

تتبع Command Repositories وServices طبقات الإرجاع وتحويل not-found المملوكة
للـ[PACKAGE_BUILDING_STANDARD.md](php-engineering-standards/standards/packages/PACKAGE_BUILDING_STANDARD.md)،
مع توثيق أي Category-specific public behavior في Package Reference.

---

### Transaction Boundary

تطبق transaction ownership وrollback وThrowable propagation كما هي مملوكة
للـPackage وBase Module Standards. Category-specific transaction boundaries
والـlocking المطلوبة لكل mutation موثقة في Phases 5 و6 و10.

---

### Exception Classification

تتبع exception hierarchy وwrapping/propagation وSQLSTATE classification قواعد
[PACKAGE_BUILDING_STANDARD.md](php-engineering-standards/standards/packages/PACKAGE_BUILDING_STANDARD.md)
ولا تعيد Category تعريفها هنا. أسماء failures الخاصة بـCategory تبقى ضمن
الـPackage Reference.

---

### Hydration

تتبع PDO hydration وtype narrowing وPHPStan max عقد الـPackage Standard؛ أي
storage-shape failure خاص بـCategory يوثق في Package Reference.

---

### Exit Gate

Persistence كاملة وقابلة للاختبار بمعزل عن Services.

---

## 17. Phase 5 — Category Create & Mutation CRUD

### الهدف

إكمال Category mutation lifecycle.

### Create

```text
CreateCategoryCommand
```

المطلوب:

* immutable code.
* uniqueness.
* optional parent.
* parent existence.
* `parent_id = NULL` للـroot، أو sibling scope يساوي `parent_id` للـchild.
* قفل الـscope داخل transaction قبل طلب position جديدة.
* الحصول على position عبر stable Ordering API، وليس عبر schema default أو SQL محلي.
* application-managed timestamps.
* ordering assignment عبر shared persistence capability.

يجب أن يتم assignment داخل transaction يملكها الـService/transaction boundary، بعد قفل scope الأشقاء المناسب؛ ويشمل ذلك scope الجذر ذي `parent_id IS NULL`. لا يمرر الـCommand قيمة `display_order` ولا يعتمد الإنشاء على schema default أو SQL محلي مكرر.

Acceptance: إنشاء Root وChild ينتج position صالحًا في الـscope الصحيح، ثم يمكن تحريك كل منهما فورًا عبر public mutation API. اختبارات MySQL لا تقوم بإعادة ضبط `display_order` يدويًا.

---

### Move

```text
MoveCategoryCommand
```

يمنع:

* self-parent.
* direct cycle.
* indirect cycle.

ويتعامل مع locking الكامل المطلوب لمنع race conditions.

---

### Update Status

```text
UpdateCategoryStatusCommand
```

الحالة Typed Enum.

لا توجد status strings حرة في public contract.

فحص وجود Category وحالتها lifecycle والكتابة يجب أن تتم داخل transaction واحدة مع locking boundary الموثقة؛ ممنوع تنفيذ unlocked existence/lifecycle read ثم write مستقل خارج transaction أو القفل المطلوب.

---

### Update Display Order

```text
UpdateCategoryDisplayOrderCommand
```

تستخدم:

```text
maatify/persistence
```

ولا يوجد local ordering engine.

---

### Delete

```text
SoftDeleteCategoryCommand
```

الحذف الطبيعي للمكتبة:

```text
Soft Delete
```

لا يمكن Soft Delete لـCategory لديها non-deleted child.

---

### Restore

```text
RestoreCategoryCommand
```

يعيد نفس identity.

لا ينشئ row بديلة.

لا يسمح بإعادة استخدام code نتيجة soft delete.

---

### Hard Delete

Hard Delete ليست جزءًا من Category Domain CRUD الطبيعي في `v1.0.0`.

لا يتم إضافتها لمجرد أن Generic Package Standard يذكر repository return contract لعمليات hard delete.

إضافتها مستقبلًا تحتاج Architecture decision صريح.

---

### Exit Gate

Category mutation side كاملة.

---

## 18. Phase 6 — Category Translation CRUD

### الهدف

إكمال lifecycle للTranslations.

التنفيذ القديم الذي كان يوفر Update Translation فقط لا يعتبر CRUD كاملة.

---

### Create

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

يتم إنشاء Translation من خلال `CategoryCommandServiceInterface` وـpackage-owned command repository فقط. لا يحتاج Runtime consumer إلى تنفيذ SQL أو إدارة lifecycle يدويًا.

---

### Read

Read side يتم تنفيذه في Query phases، لكن يجب أن يكون contract المطلوب معروفًا هنا.

---

### Update

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

### Soft Delete

```text
SoftDeleteCategoryTranslationCommand
```

الحذف لا يحرر:

```text
(category_id, language_code)
```

---

### Restore

```text
RestoreCategoryTranslationCommand
```

يعيد نفس row ونفس identity.

كل عمليات Translation mutation الأربع لها typed Command وService contract وRepository behavior واضح، وتنفذ existence/lifecycle checks والكتابة داخل transaction والـlocking المطلوبين. يجب أن تثبت اختبارات MySQL الحقيقية النجاح، الفشل، ثبات `(category_id, language_code)`، وإعادة الاستخدام عبر Restore دون إنشاء row بديلة.

---

### Parent Category State

قبل التنفيذ يجب أن تكون Architecture حاسمة بشأن:

* إنشاء Translation لـsoft-deleted Category.
* تعديل Translation لـsoft-deleted Category.
* حذف/استعادة Translation عندما تكون Category نفسها محذوفة.

ممنوع ترك هذه semantics ليقررها implementer أثناء كتابة الكود.

---

### Exit Gate

Translation mutation CRUD كاملة.

---

## 19. Phase 7 — Management Read Model

### الهدف

إكمال Read من CRUD للـmanagement/use-case APIs.

هذه queries لا تمثل consumer visibility.

---

### Category Reads

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

### Translation Reads

* Get Translation.
* List Translations for Category.
* Management translation listing.
* deleted-state handling.

---

### Criteria DTOs

Filters/criteria تستخدم DTOs مثل:

```text
CategoryListCriteriaDTO

CategoryTranslationListCriteriaDTO
```

وليس associative arrays.

---

### Deleted State

يجب أن تكون semantics صريحة:

```text
Active/non-deleted only
Includes deleted
Deleted only
```

ولا توجد method غامضة يعتمد معناها على assumption داخلي.

---

### Search

إذا تم اعتماد Search كجزء من Management API:

* تكون package-owned fields فقط.
* لا Product joins.
* لا Host joins.
* تستخدم criteria DTO.
* pagination contract واضح.

---

### Exit Gate

الإدارة تستطيع قراءة الحالة الفعلية للـCategory/Translations بدون استخدام Consumer Visibility queries.

---

## 20. Phase 8 — Consumer Visibility Query Model

### الهدف

توفير consumer-facing query contract منفصلة.

### Visible Category

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

### Required Reads

* Find visible Category by ID.
* List visible roots.
* List visible children.
* List visible translations for visible Category.

---

### Hierarchy Visibility

يجب إثبات:

* inactive Category hidden.
* deleted Category hidden.
* inactive ancestor hides descendants.
* deleted ancestor hides descendants.

---

### Language

المكتبة لا تقوم باختيار:

* current language.
* preferred language.
* fallback language.

إلا إذا تغير Architecture contract رسميًا.

---

### Exit Gate

Management reads وConsumer reads منفصلين تمامًا.

---

## 21. Phase 9 — Pagination, Ordering & Query Hardening

### الهدف

منع وجود list APIs غير production-ready أو implementation مكرر.

### Ordering

الترتيب deterministic.

بالنسبة للـCategory hierarchy:

```text
display_order, id
```

ما لم تعتمد Architecture ترتيبًا مختلفًا.

---

### Shared Ordering

كل row-position mutation في Category تستخدم stable API من `maatify/persistence`؛
تفاصيل الـAPI ومنع إعادة تنفيذ mechanics مملوكة للـPackage Standard. قرار
Category الخاص بالـscope والـtransaction موثق في Phase 4.

---

### Pagination

يجب أن يحسم Phase 0 ما إذا كانت Pagination جزءًا من `v1.0.0`، وأي lists تشملها. إذا لم تعتمد، يتم تسجيلها كـdeferred decision، وتظل كل القوائم غير المرقمة محدودة وموثقة بشكل production-safe؛ لا يترك القرار للـimplementer أثناء Phase 9.

إذا اعتمدت Management lists Pagination:

يتم استخدام stable Pagination capability من:

```text
maatify/persistence
```

ولا يتم بناء paginator محلي.

---

### Public Results

تلتزم Category في كل list contract بالـtyped DTO result والـCollections
المحددة في Package Reference. إذا احتاجت adapter لنتيجة shared persistence،
يظل thin adapter فقط ولا يعيد تنفيذ pagination mechanics وفق الـPackage
Standard.

---

### Exit Gate

كل list contract deterministic ومحدودة/موثقة بشكل production-safe، مع قرار صريح بشأن Pagination وSearch وGet-by-code قبل بدء التنفيذ.

---

## 22. Phase 10 — Concurrency & Invariant Hardening

### الهدف

إثبات صحة المكتبة تحت العمليات المتزامنة.

### Hierarchy Scenarios

اختبار Real MySQL لـ:

* concurrent moves.
* parent change races.
* self-parent.
* direct cycle.
* indirect cycle.
* ancestor-chain locks.

---

### Delete Scenarios

اختبار:

* child existing during delete.
* child being created أثناء delete.
* parent deletion race.
* no invalid parent/child lifecycle state.

---

### Ordering

اختبار:

* root ordering.
* sibling ordering.
* concurrent reorder.
* target timestamps.
* atomic movement.

---

### Restore

اختبار:

* Category code uniqueness.
* Translation logical identity uniqueness.
* same identity restoration.
* no replacement row behavior.

---

### Transaction Failure

إثبات:

* rollback.
* no partial write.
* original throwable preserved.
* lock/transaction cleanup.

اختبارات التزامن تستخدم PDO connections مستقلة، وحواجز/مهلات deterministic، وتتحقق من النتيجة النهائية دون deadlock غير منضبط. ويجب أن تثبت Integration suite cleanup، repeatability، وعدم بقاء tables/triggers/records/locks/transactions بعد التشغيل.

---

### Exit Gate

كل invariant حرجة مثبتة Integration tests حقيقية.

---

## 23. Phase 11 — Exception & Failure Contract

### الهدف

تثبيت failure surface قبل Stable API.

### Candidate Package Exceptions

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

### Package Marker

```text
CategoryExceptionInterface
```

ويمتد من:

```text
Throwable
```

وفق الـStandard.

---

### Required Audit

تنفذ مراجعة failure surface وفق exception contract في
[PACKAGE_BUILDING_STANDARD.md](php-engineering-standards/standards/packages/PACKAGE_BUILDING_STANDARD.md)،
ثم تثبت Package Reference فقط الـsemantic failures التي تملكها Category، وما
إذا كانت infrastructure failures تُمرر أو تُحوّل.

---

### Exit Gate

Failure contract كاملة داخل Package Reference.

---

## 24. Phase 12 — Package / Composer Compliance

### Composer Review

تنفذ مراجعة `composer.json` كاملة وفق
[COMPOSER_PACKAGE_STANDARD.md](php-engineering-standards/standards/packages/COMPOSER_PACKAGE_STANDARD.md)،
مع تثبيت هوية Category (`maatify/category` و`Maatify\\Category\\`) والـruntime
dependencies التي يثبتها Phase 0.

---

### Required Commands

تطبق أوامر التحقق وحالات latest/lowest المحددة في
[COMPOSER_PACKAGE_STANDARD.md](php-engineering-standards/standards/packages/COMPOSER_PACKAGE_STANDARD.md)
و[CI_WORKFLOW_STANDARD.md](php-engineering-standards/standards/packages/CI_WORKFLOW_STANDARD.md).

---

### Dependency Resolution

يجب إثبات latest-compatible وlowest-supported وفق الـComposer وCI Standards؛
ولا يكفي latest وحده.

---

### Exit Gate

المكتبة يمكن تثبيتها كمكتبة Composer حقيقية.

---

## 25. Phase 13 — CI Compliance

### الهدف

تحويل متطلبات الجودة إلى Gates فعلية.

### PHP Matrix

يتم تحديد PHP compatibility contract في Phase 0.

إذا كان Composer يعلن مثلًا:

```text
php ^8.4
```

يجب على CI تغطية كل released PHP minor التي تقع داخل الـsupport contract وفق CI Standard، وليس minimum/latest فقط إذا كان ذلك يخالف الـStandard.

---

### Required CI Areas

تطبق الـCI كامل Compliance Checklist في
[CI_WORKFLOW_STANDARD.md](php-engineering-standards/standards/packages/CI_WORKFLOW_STANDARD.md)،
مع تغطية latest/lowest dependency resolutions، وPHP minors المعتمدة، وReal
MySQL Integration، وFull Suite، وبقية checks المنطبقة على هذه المكتبة.

بنية Category الخاصة هي أن Jobs التحقق المطلوبة (`category-latest`,
`category-lowest`, و`workflow-lint`) تصب في aggregate gate واحد اسمه
`Category Quality Gate`. إعدادات Branch Protection/Ruleset تتطلب هذا الـgate
فقط، ولا تتطلب أسماء Jobs الـmatrix منفردة.

---

### Workflow Security

تتبع Workflow Security وpermissions وaction pinning وservice readiness
والـtimeouts قواعد [CI_WORKFLOW_STANDARD.md](php-engineering-standards/standards/packages/CI_WORKFLOW_STANDARD.md).
الـCategory-specific service هو MySQL بالإصدار الموثق في Phase 2.

---

### Exit Gate

كل required gates خضراء على نفس exact commit.

---

## 26. Phase 14 — Documentation & Package Presentation

### README

يجب أن يعرض README هوية `maatify/category`، والغرض والنطاق وnon-goals،
والـrequirements وطريقة التثبيت والاستخدام ومتطلب MySQL، مع روابط للـPackage
Reference والوثائق. تفاصيل البنية البصرية والـbadges مملوكة لـ
[LIBRARY_PRESENTATION_STANDARD.md](php-engineering-standards/standards/packages/LIBRARY_PRESENTATION_STANDARD.md).

ويجب أن تظل ملفات `SECURITY.md` و`CONTRIBUTING.md` و`CODE_OF_CONDUCT.md` و`LICENSE` موجودة ومتوافقة مع `LIBRARY_PRESENTATION_STANDARD.md` عند اعتبار الحزمة release-ready.

---

### Package Reference

```text
CATEGORY_PACKAGE_REFERENCE.md
```

هو المرجع الـcanonical الوحيد للـPublic Package Contract بعد اكتمال migration، وليس إثباتًا بأن baseline الحالي مكتمل.

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

### Detailed Docs

تحت:

```text
docs/
```

مثل:

```text
docs/architecture/CATEGORY_ARCHITECTURE.md

docs/CATEGORY_LIBRARY_ROADMAP.md

docs/DATABASE.md

docs/INTEGRATION.md

docs/EXCEPTIONS.md
```

عند الحاجة الفعلية.

---

### CHANGELOG

يتبع CHANGELOG بنية وحوكمة
[LIBRARY_PRESENTATION_STANDARD.md](php-engineering-standards/standards/packages/LIBRARY_PRESENTATION_STANDARD.md)،
مع إبقاء `[Unreleased]` قبل أول إصدار وبدء release history عند `1.0.0`.

---

### Exit Gate

كل documentation claims مطابقة للRuntime الفعلي.

---

## 27. Phase 15 — Standalone Consumer Verification

### الهدف

إثبات أن المكتبة مستقلة فعليًا عن المصدر الذي تم استخراجها منه.

يجب اختبار clean consumer installation.

### يجب إثبات

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

قبل نشر الحزمة على Packagist، يحدد الاختبار مصدر التثبيت صراحةً باستخدام VCS/path أو local package archive، ولا يفترض وجود نسخة منشورة. بعد النشر يضاف تحقق مستقل باستخدام `composer require maatify/category` من الـregistry المعتمد.

---

### Exit Gate

`maatify/category` تعمل خارج أي Maatify host repository.

---

## 28. Phase 16 — Final API Freeze Review

### الهدف

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

### Cleanup

قبل Stable:

* stale runtime removed.
* legacy Catalog naming removed.
* duplicate contracts removed.
* speculative APIs removed.

وتطبق بقية قواعد cleanup العامة من الـPackage وComposer Standards دون إعادة
نسخها هنا.

التعليقات وTODOs لا تحذف بشكل أعمى.

إذا كانت غير صحيحة يتم تعديلها أو التعامل معها صراحة وفق سياقها.

---

### Exit Gate

لا يوجد Public API معروف مسبقًا أنه يحتاج breaking redesign بعد الإصدار.

---

## 29. Phase 17 — Release Readiness

### Exact Release Candidate Verification

على exact candidate SHA يجب اجتياز كل Gates المنطبقة في Package وComposer وCI
وBase Module وPresentation Standards. والدليل الخاص بـCategory يجب أن يشمل
Real MySQL Integration، كل PHP minors المعتمدة، مراجعة API/Architecture،
documentation sweep، clean repository، وstandalone installation test.

---

### Release Files

* README final.
* Package Reference final.
* Architecture final.
* Roadmap updated with completed status.
* CHANGELOG release entry ready.
* Composer metadata final.

---

### Stable Release

بعد اجتياز كل ما سبق فقط تصبح المكتبة جاهزة لـ:

```text
v1.0.0
```

الجاهزية للإصدار ليست موافقة على الدمج أو إنشاء Tag أو النشر. أي Merge إلى `main` أو Tag أو Release أو Packagist publication يحتاج موافقة صريحة من مالك المشروع، ولا يجوز أن ينفذ تلقائيًا من CI أو من هذه الـRoadmap.

---

## 30. Final CRUD Matrix

### Category

#### Create

```text
CreateCategoryCommand
```

#### Read

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

#### Update

```text
MoveCategoryCommand

UpdateCategoryStatusCommand

UpdateCategoryDisplayOrderCommand
```

لا Generic Update بدون Domain meaning.

#### Delete

```text
SoftDeleteCategoryCommand
```

#### Restore

```text
RestoreCategoryCommand
```

---

## 31. Translation CRUD Matrix

### Create

```text
CreateCategoryTranslationCommand
```

### Read

* translation detail.
* list by Category.
* management list.
* visible translations.

### Update

```text
UpdateCategoryTranslationCommand
```

### Delete

```text
SoftDeleteCategoryTranslationCommand
```

### Restore

```text
RestoreCategoryTranslationCommand
```

---

## 32. Command / DTO Separation Checklist

### Commands

* [x] CreateCategoryCommand
* [x] MoveCategoryCommand
* [x] UpdateCategoryStatusCommand
* [x] UpdateCategoryDisplayOrderCommand
* [x] SoftDeleteCategoryCommand
* [x] RestoreCategoryCommand
* [x] CreateCategoryTranslationCommand
* [x] UpdateCategoryTranslationCommand
* [x] SoftDeleteCategoryTranslationCommand
* [x] RestoreCategoryTranslationCommand

### DTOs

* [ ] CategoryDTO
* [ ] CategoryTranslationDTO
* [ ] CategoryCollectionDTO
* [ ] CategoryTranslationCollectionDTO
* [ ] Query Criteria DTOs where needed
* [ ] Pagination/result DTOs where Category owns a stable public result contract

Mutation Commands ممنوع تسميتها DTO.

---

## 33. Migration From Previous Admin Implementation

الشغل السابق داخل:

```text
admin-control-panel
```

يعتبر implementation evidence فقط، وكذلك أي pre-command implementation موجود في baseline الحالي للحزمة.

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

### Resolved Mutation Contract

تم توحيد mutation-input contract في المكتبة الجديدة إلى:

```text
CreateCategoryCommand
MoveCategoryCommand
RestoreCategoryCommand
SoftDeleteCategoryCommand
UpdateCategoryStatusCommand
UpdateCategoryDisplayOrderCommand
CreateCategoryTranslationCommand
UpdateCategoryTranslationCommand
SoftDeleteCategoryTranslationCommand
RestoreCategoryTranslationCommand
```

وتستخدم الخدمات والعقود والـPDO adapters والاختبارات هذه Commands مباشرة، مع
بقاء DTOs مخصصة للبيانات ونتائج القراءة.

---

### Other Known Gaps

* Master Roadmap لم تكن موجودة.
* Translation CRUD لم تكن كاملة.
* Management Read Model لم تكن كاملة.
* standalone Composer verification لم تكن مكتملة.
* release gate لم تكن مكتملة.
* naming كان ما زال Catalog-centric.
* table prefix كان `maa_catalog_`.
* package namespace كان Catalog-oriented.
* Translation-only Domain contract موثق ومتوافق مع الـPackage Standard.

---

## 34. Definition of Done

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
