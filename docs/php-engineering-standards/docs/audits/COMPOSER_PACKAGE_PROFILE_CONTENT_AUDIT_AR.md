# تقرير تدقيق محتوى Composer Package Profile

## 1. Baseline

* **الـ SHA المُدققة:** `306516ea5f8e92dffca17c6f6b72c184f9a338e1`
* **الملفات الأربعة وإصداراتها:**
  1. `drafts/PACKAGE_BUILDING_STANDARD.md` (يُعلن صراحة إصدار `v1`)
  2. `drafts/COMPOSER_PACKAGE_STANDARD.md` (يُعلن صراحة إصدار `v1`)
  3. `drafts/CI_WORKFLOW_STANDARD.md` (لا يعلن رقم إصدار واضحًا)
  4. `drafts/LIBRARY_PRESENTATION_STANDARD.md` (لا يعلن رقم إصدار واضحًا)
* **الغرض والملكية:**
  - `PACKAGE_BUILDING_STANDARD.md`: يملك القواعد الهندسية وتصميم الحدود والتبعيات (الـ Runtime Architecture).
  - `COMPOSER_PACKAGE_STANDARD.md`: يملك قواعد `composer.json`، سياسات الاعتمادات، وقواعد الـ Lock File (يستبعد صراحة governance-document formatting و release publication).
  - `CI_WORKFLOW_STANDARD.md`: يملك بنية بوابات الجودة (CI Pipelines) وGitHub Actions وأوامر التشغيل.
  - `LIBRARY_PRESENTATION_STANDARD.md`: يملك طريقة عرض المستودع وعلامات الجودة قبل النشر (README، CHANGELOG، Badges).

## 2. Ownership Matrix

* **Runtime architecture + package structure + canonical root Package Reference:** المالك هو `PACKAGE_BUILDING_STANDARD.md` (canonical root Package Reference يقع في القسم 3).
* **`composer.json` metadata/declarations/constraints/scripts/config/lock-file policy:** المالك هو `COMPOSER_PACKAGE_STANDARD.md` (أقسام 7، 10، 11 وغيرها).
* **CI verification and quality gates:** المالك هو `CI_WORKFLOW_STANDARD.md`.
* **README/release-facing presentation + governance-document identity:** المالك هو `LIBRARY_PRESENTATION_STANDARD.md` (أقسام 5، 6، 7، 14).
* **Release publication:** خارج نطاق الملفات الأربعة حاليًا (إلا إن وجد نص صريح).
* **Test architecture versus CI execution:** هندسة الاختبار يملكها `PACKAGE_BUILDING_STANDARD.md` (القسم 21)، أوامر تشغيل CI يملكها `CI_WORKFLOW_STANDARD.md` (القسم 8).

## 3. Contradictions and Duplication

### 3.1 سياسة `composer.lock`
* **الملفات والأقسام:** `COMPOSER_PACKAGE_STANDARD.md` (القسم 11) و `CI_WORKFLOW_STANDARD.md` (القسم 5).
* **المعنى الفعلي:** سياسة المنع هي سياسة داخلية لـ Maatify. `COMPOSER_PACKAGE_STANDARD.md` يمنع الـ Commit في Reusable Libraries، بينما CI Workflow ينظم أوامر الفحص بناءً على وجود الملف من عدمه.
* **الأثر العملي:** لا يوجد.
* **الملف المالك:** `COMPOSER_PACKAGE_STANDARD.md`.
* **نوع الإجراء:** No contradiction / Retain.

### 3.2 Required Files
* **الملفات والأقسام:** `PACKAGE_BUILDING_STANDARD.md` (القسم 3) و `LIBRARY_PRESENTATION_STANDARD.md` (القسم 6).
* **المعنى الفعلي:** `PACKAGE_BUILDING_STANDARD.md` يحدد ملفات هيكلة البكج الجذرية، بينما `LIBRARY_PRESENTATION_STANDARD.md` يحدد ملفات التقديم الخارجي. لا يوجد `SECURITY.md` داخل قوائم Package Building.
* **الأثر العملي:** قد يُفهم خطأ كتعارض إذا لم يفرق بين هيكل التطوير وهيكل النشر.
* **الملف المالك:** `LIBRARY_PRESENTATION_STANDARD.md` لملفات النشر، و`PACKAGE_BUILDING_STANDARD.md` للهيكلة الأساسية.
* **نوع الإجراء:** Cross-reference لبيان غرض كل قائمة.

### 3.3 Test Requirements versus CI Execution
* **الملفات والأقسام:** `PACKAGE_BUILDING_STANDARD.md` (القسم 21) و `CI_WORKFLOW_STANDARD.md` (القسم 8).
* **المعنى الفعلي:** Package Building يحدد التغطية والمعمارية، بينما CI يملك تنفيذ الأوامر.
* **الأثر العملي:** لا يوجد إذا تم احترام كل نطاق.
* **الملف المالك:** `CI_WORKFLOW_STANDARD.md` للتنفيذ، و `PACKAGE_BUILDING_STANDARD.md` للهندسة.
* **نوع الإجراء:** Cross-reference.

### 3.4 Composer Scripts versus CI Commands
* **الملفات والأقسام:** `COMPOSER_PACKAGE_STANDARD.md` (القسم 14) و `CI_WORKFLOW_STANDARD.md` (القسم 8).
* **المعنى الفعلي:** سكربتات Composer تعرّف أوامر محددة، ويشغلها CI.
* **الأثر العملي:** لا يوجد.
* **الملف المالك:** `COMPOSER_PACKAGE_STANDARD.md` للتعريف المرجعي و CI للتنفيذ.
* **نوع الإجراء:** No contradiction / Retain مع إمكانية توضيح الـ Cross-reference.

### 3.5 PHP/PHPStan Compatibility Claims
* **الملفات والأقسام:** `PACKAGE_BUILDING_STANDARD.md` (القسم 21) و `CI_WORKFLOW_STANDARD.md` (القسم 7 والقسم 8).
* **المعنى الفعلي:** Package Building يطلب خلو الكود المعماري من أخطاء PHPStan في أقصى مستوى. CI Workflow ينظم التنفيذ على الـ matrix المحددة ولا يفرض دعم كل الـ minors بشكل معماري مطلق.
* **الأثر العملي:** لا يوجد.
* **الملف المالك:** `CI_WORKFLOW_STANDARD.md` للـ Matrix التنفيذية.
* **نوع الإجراء:** No contradiction / Retain.

### 3.6 Release-ready and Pre-tag Badge Requirements
* **الملفات والأقسام:** `LIBRARY_PRESENTATION_STANDARD.md` (القسم 8 والقسم 14.2).
* **المعنى الفعلي:** سياسة الـ Badges تخص تحضير العرض، وليست تعارضاً تقنياً مع CI.
* **الأثر العملي:** لا يوجد.
* **الملف المالك:** `LIBRARY_PRESENTATION_STANDARD.md`.
* **نوع الإجراء:** No contradiction / Retain.

### 3.7 Package Reference Ownership
* **الملفات والأقسام:** `PACKAGE_BUILDING_STANDARD.md` (القسم 3).
* **المعنى الفعلي:** مالك المرجعية الهندسية هو `PACKAGE_BUILDING_STANDARD.md`.
* **الأثر العملي:** لا يوجد.
* **الملف المالك:** `PACKAGE_BUILDING_STANDARD.md`.
* **نوع الإجراء:** Retain.

### 3.8 Dependency and Shared-package Requirements
* **الملفات والأقسام:** `PACKAGE_BUILDING_STANDARD.md` (القسم 2) و `COMPOSER_PACKAGE_STANDARD.md` (القسم 10).
* **المعنى الفعلي:** القرار المعماري بالتبعية ملك لـ Package Building، وطريقة إعلانها ملك لـ Composer Standard.
* **الأثر العملي:** لا يوجد.
* **الملف المالك:** كلٌ في تخصصه.
* **نوع الإجراء:** No contradiction / Retain.

### 3.9 Rules repeated with different MUST/SHOULD strength
* **الملفات والأقسام:** لا يوجد.
* **المعنى الفعلي:** لا توجد حالة فعلية مرصودة لاختلاف MUST/SHOULD بين الملفات الأربعة في الـ baseline المدققة.
* **الأثر العملي:** لا يوجد.
* **الملف المالك:** الأصل حسب الـ Matrix.
* **نوع الإجراء:** No contradiction / no action required.

## 4. Overbroad or Risky Rules

يجب التدقيق في القواعد التالية لضمان التطبيق السليم:

* **إلزامات `maatify/exceptions` و `maatify/shared-common` و `maatify/persistence` (`PACKAGE_BUILDING_STANDARD.md` الأقسام 2، 7، 11):**
  - **التصنيف:** هذا شرط معماري لضمان الوحدة (Policy)، ولكنه يتطلب تحققاً من وجود API مستقرة فعلياً قبل تطبيقه كإلزام تنفيذي، مع التفرقة بين الشرط المعماري وتوفر الأداة.
* **`PDO-based` كشرط مطلق لكل Package، و`schema/` فقط عند امتلاك persistence (`PACKAGE_BUILDING_STANDARD.md` الأقسام 1 و3 و6):**
  - **التصنيف:** يتطلب قرار مالك لتحديد الـ Applicability: هل الـ Profile مصمم لكل مكتبات PHP القابلة لإعادة الاستخدام عمومًا، أم مخصص حصراً للحزم التي تملك طبقة persistence؟
* **Mandatory test suites (`CI_WORKFLOW_STANDARD.md` القسم 8):**
  - **التصنيف:** سليمة؛ مشروطة بنص صريح `where applicable` (تشغل الـ suites المنفصلة فقط إذا وجدت).
* **PHP minors داخل Composer constraint (`COMPOSER_PACKAGE_STANDARD.md` القسم 10.3 و `CI_WORKFLOW_STANDARD.md` القسم 7):**
  - **التصنيف:** سليمة؛ يوجد documented exceptions للـ intermediate minors، وليست مطلقة لدعم كل minor في كل الحالات بشكل عشوائي.
* **المنع المطلق لـ PHPStan baseline و`ignoreErrors` (`PACKAGE_BUILDING_STANDARD.md` القسم 21):**
  - **التصنيف:** تُصنف كقاعدة Canonical للحزم الجديدة أو المُعاد بناؤها. مسار الـ Legacy migration يُعتبر نطاقاً منفصلاً ولا يُبرر تخفيف هذا المعيار الأساسي.
* **Integration infrastructure (`CI_WORKFLOW_STANDARD.md` القسم 11):**
  - **التصنيف:** سليمة؛ مشروطة صراحة بامتلاك الحزمة لـ persistence أو external-service behavior.
* **Immutable full-SHA pinning للأفعال الخارجية (`CI_WORKFLOW_STANDARD.md` القسم 12):**
  - **التصنيف:** Policy أمنية داخل المعيار (سليمة كمبدأ)، وتُفصل عن ادعاءات سلوك GitHub التي تتطلب Verification.
* **Packagist/download badges قبل أول Tag (`LIBRARY_PRESENTATION_STANDARD.md` القسم 8):**
  - **التصنيف:** سليمة؛ المعيار حسم أنها تنطبق فقط عندما يكون Packagist هو الـ registry المختار أو النشر الفوري جزء من الخطة (ليس قراراً لفتح باب الـ Private Libraries).
* **Canonical root Package Reference واحد (`PACKAGE_BUILDING_STANDARD.md` القسم 3):**
  - **التصنيف:** سليمة ومقصودة؛ قاعدة غرضها توحيد التوثيق المعماري للمشروع وليست قراراً يخضع للتخفيف حسب حجم الحزمة.

## 5. Project and Package Leakage

تم رصد تسرب أمثلة تطبيقية حقيقية تتطلب التحييد (Neutralization) أو نقلها لـ `Non-normative implementation example`:

* **في `PACKAGE_BUILDING_STANDARD.md`:**
  - `Maatify\EventLogging\` (القسم 4) -> مثال تطبيقي لتحديد الـ Namespaces، يجب استبداله باسم محايد.
  - `event-logging` (القسم 14) -> مثال تطبيقي لفصل الـ Admin vs Customer، يجب تحييده.
  - `AuthoritativeAudit` (القسم 14) -> مثال تطبيقي، يجب تحييده.
  - `BehaviorTrace` (القسم 14) -> مثال تطبيقي، يجب تحييده.
  - استخدام أسماء تطبيقات حقيقية في سياق منع الـ Namespaces مثل `Athar` و `EP4N` (القسم 4) -> يجب تحييدها.
* **ملاحظة:** الإشارة إلى إمكانيات `maatify/persistence` (مثل `display_order` في القسم 16، و Ordering/Pagination في القسم 11) تُعتبر dependency/repository reference معماري يتطلب External verification لاستقراره، وليس "تسرب مثال" يجب إزالته لمجرد كونه حقيقياً.

## 6. External Contract Claims

الادعاءات التالية تحتاج إلى تحقق خارجي (External Verification) للتأكد من مطابقتها للواقع المعماري الخارجي وسلوك الأدوات.
**ملاحظة هامة جداً:** هذه الـ PR التدقيقية لم تنفذ التحقق الخارجي بنفسها، ولذلك كل هذه الادعاءات تظل في حالة `Pending Verification` ولا تُعتبر نتائج مثبتة أو جاهزة للتطبيق.

| الادعاء المزعوم / API | المصدر (الملف والقسم) | نوع الادعاء | التحقق الخارجي | الإجراء المقترح |
| --- | --- | --- | --- | --- |
| وجود واستقرار hierarchy/API في `maatify/exceptions` | `PACKAGE_BUILDING_STANDARD.md` (القسم 7) | ادعاء عن API معيارية/مستودع خارجي | يحتاج تحققاً | Block pending verification |
| وجود واستقرار Clock/Date-Time contracts في `maatify/shared-common` | `PACKAGE_BUILDING_STANDARD.md` (القسم 2) | ادعاء عن API معيارية/مستودع خارجي | يحتاج تحققاً | Block pending verification |
| وجود واستقرار Ordering و Pagination API في `maatify/persistence` | `PACKAGE_BUILDING_STANDARD.md` (القسم 11) | ادعاء عن API خارجية منشورة ومساراتها | يحتاج تحققاً | Block pending verification |
| تفاصيل الـ Pagination deferring وحوكمة `PERSISTENCE_PACKAGE_REFERENCE.md` أو `v1.1.0` | `PACKAGE_BUILDING_STANDARD.md` (القسم 11) | ادعاء عن خطة الإصدار لـ `maatify/persistence` | يحتاج تحققاً | Block pending verification |
| تفاصيل سلوك `display_order` integration | `PACKAGE_BUILDING_STANDARD.md` (القسم 16) | ادعاء عن API مستقرة | يحتاج تحققاً | Block pending verification |
| سلوك تقني لـ Composer (Root-only fields وسياسات `composer.json`) | `COMPOSER_PACKAGE_STANDARD.md` (القسم 3.1 وأقسام أخرى) | ادعاء عن سلوك أداة قياسية فني | يحتاج تحققاً لتأكيد الدعم | Block pending verification |
| أوامر مثل `composer check-platform-reqs` و `composer audit` | `CI_WORKFLOW_STANDARD.md` (القسمين 5 و 9) | ادعاء عن سلوك أوامر Composer | يحتاج تحققاً لتأكيد الدعم | Block pending verification |
| Packagist badge endpoints وحالة pre-tag rendering | `LIBRARY_PRESENTATION_STANDARD.md` (القسم 8) | ادعاء عن سلوك موقع خارجي | يحتاج تحققاً | Block pending verification |
| سلوك GitHub في الـ required-check/path-filter، وسلوك الأفعال مع Immutable SHA | `CI_WORKFLOW_STANDARD.md` (الأقسام 3، 4، 12) | ادعاء عن سلوك GitHub Actions التقني | يحتاج تحققاً | Block pending verification |

## 7. Adoption Readiness per File

بناءً على الأدلة والوضع الحالي للتحقق:

* **`drafts/PACKAGE_BUILDING_STANDARD.md`**: `BLOCKED BY EXTERNAL VERIFICATION`
  (تم حسم قرار PDO-only ليكون مشروطًا، ولكن ما زال يحتاج لـ External Verification لادعاءات الـ APIs المعيارية، وتحييد الأمثلة التطبيقية).
* **`drafts/COMPOSER_PACKAGE_STANDARD.md`**: `BLOCKED BY EXTERNAL VERIFICATION`
  (إلى أن يتم التحقق الخارجي من ادعاءات السلوك التقني لأداة Composer نفسها، وليس بسبب سياسة Maatify الداخلية في منع Commit للـ lock file).
* **`drafts/CI_WORKFLOW_STANDARD.md`**: `BLOCKED BY EXTERNAL VERIFICATION`
  (إلى أن تتحقق ادعاءات GitHub Actions و required checks فنياً).
* **`drafts/LIBRARY_PRESENTATION_STANDARD.md`**: `BLOCKED BY EXTERNAL VERIFICATION`
  (إلى أن تتحقق روابط وسلوك Packagist و GitHub/assets فنياً، وليس بسبب أي قرارات مفتوحة حول الـ Private Libraries).

## 8. Owner Decisions

القرارات المحسومة من مالك المشروع:

1. **الـ Applicability لشرط الـ PDO المطلق (`PACKAGE_BUILDING_STANDARD.md` الأقسام 1 و3 و6):**
   * **الحالة:** تم حسم هذا القرار بوضوح في وثيقة القرارات الإدارية، وهو مُغلق ومُعتمد.
   * **القرار:** يُعتبر هذا الشرط يتسم بالـ Conditional Applicability؛ وينطبق فقط متى ما تواجدت طبقة persistence.
   * **أثر القرار:** هذا يسمح بتطبيق `PACKAGE_BUILDING_STANDARD.md` كـ Profile عام على جميع حزم PHP، بحيث تطبق قواعد `PDO`، `schema/`، وإدارة `migrations` واختبارات قاعدة البيانات وغيرها، فقط إذا كانت الحزمة تمتلك Data Persistence Behavior.

## 9. Recommended Execution Order

يجب أن يتم التنفيذ بالترتيب التالي:

1. **External verification للادعاءات المحددة** (التحقق الخارجي من استقرار حزم `maatify/*` وسلوكيات GitHub/Composer/Packagist التقنية).
2. **تصحيح منسق للملفات** بناءً على ownership الثابت، وCross-references الموضحة، وتحييد الأمثلة التطبيقية.
3. **إعادة تقييم readiness** لتحديث الحالات من BLOCKED إلى جاهزية الاعتماد الفعلي.
4. **اعتماد مجموعة Composer Package Profile** كوحدة متسقة.
