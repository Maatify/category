# تقرير التحقق الخارجي لأدوات Composer Package Profile

## 1. المصادر

*   **Composer Documentation:** `https://getcomposer.org/doc/` (تاريخ الاطلاع: 2026-07-18، أحدث إصدار مستقر رسمي: 2.10.2).
    *   مستند Composer CLI: `https://getcomposer.org/doc/03-cli.md`
    *   مستند Composer Schema: `https://getcomposer.org/doc/04-schema.md`
    *   مستند Composer Audit: `https://getcomposer.org/doc/03-cli.md#audit`
*   **GitHub Actions Documentation:** (تاريخ الاطلاع: 2026-07-18).
    *   مستند Path Filters: `https://docs.github.com/en/actions/using-workflows/workflow-syntax-for-github-actions#onpushpull_requestpull_request_targetpathspaths-ignore`
    *   مستند Security Hardening: `https://docs.github.com/en/actions/security-guides/security-hardening-for-github-actions`
    *   مستند Status Checks: `https://docs.github.com/en/pull-requests/collaborating-with-pull-requests/collaborating-on-repositories-with-code-quality-features/troubleshooting-required-status-checks`
    *   مستند Relative Links: `https://docs.github.com/en/repositories/managing-your-repositorys-settings-and-features/customizing-your-repository/about-readmes#relative-links-and-image-paths-in-readme-files`
*   **Packagist & Shields.io Documentation:** (تاريخ الاطلاع: 2026-07-18).
    *   Shields.io Packagist Endpoint `v`: `https://shields.io/badges/packagist-version`
    *   Shields.io Packagist Endpoint `php-v`: `https://shields.io/badges/packagist-php-version-support`
    *   Shields.io Packagist Endpoint `l`: `https://shields.io/badges/packagist-license`
    *   Shields.io Packagist Endpoint `dm`: `https://shields.io/badges/packagist-downloads`
    *   Shields.io Packagist Endpoint `dt`: `https://shields.io/badges/packagist-downloads`
    *   Shields.io Static Badge (Install): `https://shields.io/badges/static-badge`
*   **تجارب قابلة لإعادة التنفيذ (التاريخ: 2026-07-18):**
    *   **إصدار Composer المحلي في بيئة الاختبار:**
        *   الأمر: `composer --version; echo "Exit Code: $?"`
        *   المخرجات: `Composer version 2.9.5 2026-01-29 11:40:53`, Exit Code: `0`
    *   **شعار Maatify:**
        *   الأمر: `curl -I -s https://www.maatify.dev/assets/img/img/maatify_logo_white.svg; echo "Exit Code: $?"`
        *   المخرجات: `HTTP/2 200`, `content-type: image/svg+xml`, Exit Code: `0`.
    *   **حزمة غير موجودة في Shields:**
        *   الأمر: `curl -i -s https://img.shields.io/packagist/v/maatify/non-existent-package | head -n 1 && curl -s https://img.shields.io/packagist/v/maatify/non-existent-package | grep -o 'not found'`
        *   المخرجات: `HTTP/2 200` و `not found`. (الـ Exit code الخاص بـ curl هو `0`). الشارة موجودة وترد بـ HTTP 200 لكنها تظهر بصريًا "packagist: not found".

## 2. نطاق التحقق ونتائجه

### 2.1 Composer

*   **Root-only fields:** الادعاء بأن الحقول مثل `minimum-stability` و `prefer-stable` و `require-dev` هي Root-only وتؤثر فقط عندما تكون الحزمة هي الجذر متطابق مع التوثيق الرسمي لـ Composer.
*   **حقل `version`:** اشتقاق الإصدارات من VCS tags بدلاً من كتابة الحقل هو السلوك الموصى به لـ Composer و Packagist لتجنب التعارض.
*   **أوامر التحقق والتشغيل:**
    *   `composer validate --strict` ينجح في فرض القيود الصارمة ويفشل على الـ Warnings والـ Errors.
    *   `composer dump-autoload --optimize --strict-psr` يفرض توافق PSR-4 الحقيقي.
    *   `composer install` مقابل `composer update`: `install` يقرأ من `composer.lock` إن وجد، بينما `update` يقوم بحل التبعيات من جديد. وجود `composer.lock` يفرض إصدارات محددة مما يخالف هدف اختبار التوافق في المكتبات المستقلة.
    *   `composer check-platform-reqs` يتحقق بالفعل من توافق امتدادات ومتطلبات الـ Platform الحقيقية للحزم المثبتة مقابل بيئة التشغيل.
    *   `composer audit --no-interaction` يعيد `exit 0` عند عدم وجود مشاكل (issues)، ويعيد `1` عند وجود حزم تطابق سياسات المعتمديات أو عند فقدان حزم مطلوبة. السلوك تجاه الحزم الـ `abandoned` يعتمد على إعداد `audit.abandoned` والذي قد يكون `ignore` أو `report` أو `fail` (وضع `report` لا يفرض non-zero exit code).
    *   `--prefer-lowest` و `--prefer-stable`: أوامر صحيحة لاختبار إمكانية تثبيت الحد الأدنى، لكنها لا تضمن عمل الحزمة برمجيًا لكل إصدار في النطاق.
*   **الحقول الوصفية الاختيارية:** حقول مثل `readme`, `support`, `funding`, `archive`, `abandoned` مدعومة رسمياً من Composer وتلعب دوراً أساسياً في عرض الحزمة على Packagist وتقديم الروابط للمستخدمين.
*   **`composer.lock`:** أداة Composer نفسها لا تمنع Commit لـ `composer.lock` في المكتبات، بل هذه سياسة (Internal Policy) لضمان توافق الاعتمادات مع بيئات المستخدمين النهائيين.

### 2.2 GitHub Actions

*   **أثر `paths` و `paths-ignore`:** إذا تخطى فلتر المسارات Workflow وكان الأخير مطلوباً (Required Check)، فإن حالته ستظل `Pending` وتمنع الدمج. لذلك يعد تصميم `Model B` (تفعيل دائم مع بوابات داخلية) صحيحاً تقنياً لحل هذه المشكلة.
*   **سلوك jobs المشروطة و conclusions:** الـ jobs المشروطة بـ `if: always()` تسمح للبوابة النهائية بالعمل دائماً. نتيجة الـ jobs المعتمد عليها (`needs.*.result`) يمكن أن تكون `success`, `failure`, `cancelled`, أو `skipped`. يجب فحص هذه الحالات بشكل صريح في الـ gate job لتحديد النجاح النهائي كجزء من Required Check.
*   **أسماء matrix jobs:** GitHub يحدد الـ required checks بناءً على الـ context/name الخاص بالـ check، وتولّد الـ matrix وظائف (jobs) متعددة. فبدلًا من الاعتماد المباشر على matrix child jobs الذي قد يؤدي إلى Ambiguity عند تغيير الـ parameters، يُعد إلزام استخدام stable aggregate gate سياسة تشغيلية لـ Maatify مدعومة بهذه القيود.
*   **صلاحيات `permissions`:** إعداد `contents: read` ليس Default مطلقًا (قد يكون read أو write حسب إعدادات المستودع). فرضه هو سياسة داخلية لـ Maatify (Least Privilege Policy).
*   **مخاطر `pull_request_target`:** استخدامه يشكل خطرًا عند عمل Checkout وتنفيذ كود PR غير موثوق بصلاحيات عالية.
*   **pinning:** تثبيت الـ Actions إلى Full commit SHA مدعوم ويوصى به أمنيًا، ولكن إلزامه كقاعدة هو Maatify Policy.
*   **`timeout-minutes` و `cancel-in-progress`:** مدعومة بفعالية لتجنب هدر وقت التشغيل. إلزامها هو سياسة داخلية.

### 2.3 Packagist / Shields / GitHub Rendering

*   **Packagist badge endpoints:** صيغ `https://img.shields.io/packagist/...` مدعومة ومستقرة.
*   **URL encoding:** تمرير مسار الحزمة داخل رابط مثل `Install-composer%20require%20...` يتطلب ترميز الـ slash كـ `%2F` ليعمل بشكل صحيح.
*   **سلوك badges قبل التوفر أو التاج:**
    *   قبل وجود الحزمة على المستودع/Packagist: الـ badge response تعرض نص `packagist: not found` مع HTTP status 200.
    *   قبل أول Stable Tag: غامض؛ Shields يستبعد pre-releases افتراضيًا. يجب تحديد `include_prereleases` لرؤية الـ dev tags.
*   **الرابط الخارجي لشعار Maatify:** يعيد `200 OK` ونوعه `image/svg+xml`. هذا يثبت التوفر وصيغة العرض، ولا يعني أنه "آمن" مطلقًا في كل وقت.
*   **الروابط النسبية في GitHub README:** الروابط النسبية للملفات (مثل `CHANGELOG.md` أو `docs/`) تُدعم وتُحول بشكل صحيح من قِبل GitHub، مما يجعل استخدامها لربط الوثائق الداخلية آمناً.

## 3. جدول Traceability

| الادعاء أو القاعدة | الملف والقسم | النص الحالي أو معناه الدقيق | المصدر الرسمي أو التجربة | النتيجة الفعلية | التصنيف | الإجراء المقترح |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Composer** | | | | | | |
| حقل `require-dev` هو Root-only | `COMPOSER_PACKAGE_STANDARD.md` (القسم 3.1) | Composer fields are root-only... | Composer Schema Docs | Composer يتجاهله عند حل التبعيات كـ dependency. | `VERIFIED BY OFFICIAL DOCUMENTATION` | Retain |
| حقل `autoload-dev` هو Root-only | `COMPOSER_PACKAGE_STANDARD.md` (القسم 3.1) | Composer fields are root-only... | Composer Schema Docs | Composer يتجاهله عند حل التبعيات كـ dependency. | `VERIFIED BY OFFICIAL DOCUMENTATION` | Retain |
| حقول `repositories`, `config`, `scripts`, `minimum-stability`, `prefer-stable` هي Root-only | `COMPOSER_PACKAGE_STANDARD.md` (القسم 3.1) | Composer fields are root-only... | Composer Schema Docs | Composer يتجاهلها عند حل اعتمادات المستهلكين (dependencies). | `VERIFIED BY OFFICIAL DOCUMENTATION` | Retain |
| منع إدراج حقل `version` | `COMPOSER_PACKAGE_STANDARD.md` (القسم 12) | The version field MUST NOT be committed... | Composer Schema Docs | Composer يسمح بالحقل ويوصي بتجنبه إذا أمكن. المنع بـ `MUST NOT` هو سياسة لـ Maatify. | `INTERNAL POLICY — NOT AN EXTERNAL CLAIM` | Convert to internal policy wording |
| نجاح `composer validate --strict` | `COMPOSER_PACKAGE_STANDARD.md` (القسم 13, 29) | MUST pass `validate --strict` | Composer CLI Docs | `--strict` ينجح ويعيد non-zero exit code عند وجود أخطاء أو Warnings. | `VERIFIED BY OFFICIAL DOCUMENTATION` | Retain |
| أمر `composer dump-autoload --optimize --strict-psr` | `COMPOSER_PACKAGE_STANDARD.md` (القسم 13) | `dump-autoload --optimize --strict-psr` | Composer CLI Docs | يعيد non-zero exit code عند اكتشاف أخطاء PSR-4 مطابقة للواقع. | `VERIFIED BY OFFICIAL DOCUMENTATION` | Retain |
| `composer install` مقابل `composer update` مع lock | `CI_WORKFLOW_STANDARD.md` (القسم 5) | `install` vs `update` mode based on lock | Composer CLI Docs | `install` يستخدم الـ lock، `update` يحل تبعيات جديدة. | `VERIFIED BY OFFICIAL DOCUMENTATION` | Retain |
| أمر `composer check-platform-reqs` | `CI_WORKFLOW_STANDARD.md` (القسم 5) | `composer check-platform-reqs` | Composer CLI Docs | يتحقق من متطلبات البيئة للحزم المثبتة مقابل الـ Real Platform. | `VERIFIED BY OFFICIAL DOCUMENTATION` | Retain |
| `composer audit --no-interaction` | `CI_WORKFLOW_STANDARD.md` (القسم 9) | `composer audit --no-interaction` | Composer CLI Docs | يعيد `0` عند عدم وجود مشاكل، ويعيد `1` إذا طابقت حزم سياسات الاعتمادات (مثل الثغرات) أو فُقدت. وضع `abandoned` يعتمد على السياسة (إعداد `report` لا يفشل). | `PARTIALLY VERIFIED` | Clarify (توضيح سياسة abandoned) |
| اختبار `--prefer-lowest` و `--prefer-stable` | `CI_WORKFLOW_STANDARD.md` (القسم 6) | lowest-supported dependencies... `update --prefer-lowest` | Composer CLI Docs | يثبت إمكانية التثبيت للإصدارات القديمة، ولكنه لا يضمن الدعم البرمجي لكل الـ bounds دون فحص. | `PARTIALLY VERIFIED` | Clarify (توضيح حدود الاختبار) |
| سياسة `minimum-stability` و `prefer-stable` | `COMPOSER_PACKAGE_STANDARD.md` (القسم 24) | MUST use `stable` as minimum stability | Composer Schema Docs | Composer يتيح كل الخيارات، هذا الإلزام هو سياسة داخلية لـ Maatify. | `INTERNAL POLICY — NOT AN EXTERNAL CLAIM` | Convert to internal policy wording |
| حقل `readme` | `COMPOSER_PACKAGE_STANDARD.md` (القسم 10) | Optional Metadata field | Composer Schema Docs | موثق ومدعوم. | `VERIFIED BY OFFICIAL DOCUMENTATION` | Retain |
| حقل `support` | `COMPOSER_PACKAGE_STANDARD.md` (القسم 11) | Optional Metadata field | Composer Schema Docs | موثق ومدعوم. | `VERIFIED BY OFFICIAL DOCUMENTATION` | Retain |
| حقل `funding` | `COMPOSER_PACKAGE_STANDARD.md` (القسم 19) | Optional Metadata field | Composer Schema Docs | موثق ومدعوم. | `VERIFIED BY OFFICIAL DOCUMENTATION` | Retain |
| حقل `archive` | `COMPOSER_PACKAGE_STANDARD.md` (القسم 19) | Optional Metadata field | Composer Schema Docs | موثق ومدعوم. | `VERIFIED BY OFFICIAL DOCUMENTATION` | Retain |
| حقل `abandoned` | `COMPOSER_PACKAGE_STANDARD.md` (القسم 19) | Optional Metadata field | Composer Schema Docs | موثق ومدعوم. | `VERIFIED BY OFFICIAL DOCUMENTATION` | Retain |
| منع `composer.lock` في المكتبات | `COMPOSER_PACKAGE_STANDARD.md` (القسم 25) | `composer.lock` MUST NOT be committed. | Composer Basic Usage Docs | Composer لا يمنع ذلك تقنيًا. المنع هو سياسة متبعة لتجنب فرض إصدارات محددة. | `INTERNAL POLICY — NOT AN EXTERNAL CLAIM` | Convert to internal policy wording |
| **GitHub Actions** | | | | | | |
| skipped workflow بسبب `paths` filter | `CI_WORKFLOW_STANDARD.md` (القسم 3) | A directly required workflow MUST NOT disappear... | GitHub Troubleshooting Required Checks | Workflow المتخطي يظل عالقاً كـ `Pending`. | `VERIFIED BY OFFICIAL DOCUMENTATION` | Retain |
| skipped conditional job وتأثيره | `CI_WORKFLOW_STANDARD.md` (القسم 16) | inspect every required upstream job | GitHub Actions Context Docs | البوابة تحتاج للتعامل مع الـ skipped jobs بشكل صريح، وإلا قد تتأثر النتيجة. | `VERIFIED BY OFFICIAL DOCUMENTATION` | Retain |
| سلوك `if: always()` | `CI_WORKFLOW_STANDARD.md` (القسم 16) | `if: always()` | GitHub Actions Context Docs | يسمح بتشغيل البوابة دائمًا بغض النظر عن فشل ما سبقها. | `VERIFIED BY OFFICIAL DOCUMENTATION` | Retain |
| قراءة conclusions باستخدام `needs.*.result` | `CI_WORKFLOW_STANDARD.md` (القسم 16) | inspect every required upstream job | GitHub Actions Context Docs | البوابة يمكنها قراءة حالات `success`, `failure`, `cancelled`, `skipped`. | `VERIFIED BY OFFICIAL DOCUMENTATION` | Retain |
| منع الاعتماد على Matrix jobs كـ Required Checks | `CI_WORKFLOW_STANDARD.md` (القسم 16) | MUST NOT require individual matrix child jobs directly | GitHub Actions Required Checks | الـ Required checks تعتمد على الاسم. إلزام Stable Gate هو سياسة Maatify (Operational Policy) لتجنب الـ Ambiguity لأسماء الـ Matrix. | `PARTIALLY VERIFIED` | Clarify / Convert (المنع هو سياسة مدعومة بالقيود التقنية) |
| صلاحية `permissions: contents: read` | `CI_WORKFLOW_STANDARD.md` (القسم 12) | Workflows MUST require least privilege: `contents: read` | GitHub Security Hardening | هذه ليست Default عام مطلق (يمكن أن تكون `write` افتراضياً). إلزامها كـ Baseline هو سياسة Maatify. | `INTERNAL POLICY — NOT AN EXTERNAL CLAIM` | Convert to internal policy wording |
| مخاطر `pull_request_target` | `CI_WORKFLOW_STANDARD.md` (القسم 12) | MUST NOT use `pull_request_target` to execute untrusted pull-request code. | GitHub Security Hardening | استخدامه خطير عند عمل checkout لكود غير موثوق به وتنفيذه بصلاحيات عالية. | `VERIFIED BY OFFICIAL DOCUMENTATION` | Retain |
| إلزام Full-SHA pinning لـ Actions | `CI_WORKFLOW_STANDARD.md` (القسم 12) | Every externally sourced GitHub Action MUST be pinned | GitHub Security Hardening | يوصى به أمنيًا، وإلزامه كقاعدة هو Maatify Policy. | `INTERNAL POLICY — NOT AN EXTERNAL CLAIM` | Convert to internal policy wording |
| إلزام Full-SHA pinning لـ Reusable Workflows | `CI_WORKFLOW_STANDARD.md` (القسم 12) | Every external reusable workflow MUST be pinned | GitHub Security Hardening | يوصى به أمنيًا، وإلزامه كقاعدة هو Maatify Policy. | `INTERNAL POLICY — NOT AN EXTERNAL CLAIM` | Convert to internal policy wording |
| إعداد `timeout-minutes` | `CI_WORKFLOW_STANDARD.md` (القسم 13) | Every required job MUST define an appropriate timeout-minutes. | GitHub Actions Workflow Syntax | الإعداد مدعوم تقنيًا. الإلزام هو سياسة Maatify. | `INTERNAL POLICY — NOT AN EXTERNAL CLAIM` | Convert to internal policy wording |
| إعداد `concurrency` و `cancel-in-progress` | `CI_WORKFLOW_STANDARD.md` (القسم 13) | MUST define... | GitHub Actions Workflow Syntax | الإعدادات مدعومة تقنياً بشكل كامل. الإلزام هو سياسة داخلية. | `INTERNAL POLICY — NOT AN EXTERNAL CLAIM` | Convert to internal policy wording |
| **Packagist / Shields / GitHub** | | | | | | |
| Endpoint `v` | `LIBRARY_PRESENTATION_STANDARD.md` (القسم 9) | `img.shields.io/packagist/v/...` | Shields.io Version Badge | Endpoint رسمي للنسخة. | `VERIFIED BY OFFICIAL DOCUMENTATION` | Retain |
| Endpoint `php-v` | `LIBRARY_PRESENTATION_STANDARD.md` (القسم 9) | `img.shields.io/packagist/php-v/...` | Shields.io PHP Badge | Endpoint رسمي لنسخة PHP. | `VERIFIED BY OFFICIAL DOCUMENTATION` | Retain |
| Endpoint `l` | `LIBRARY_PRESENTATION_STANDARD.md` (القسم 9) | `img.shields.io/packagist/l/...` | Shields.io License Badge | Endpoint رسمي للترخيص. | `VERIFIED BY OFFICIAL DOCUMENTATION` | Retain |
| Endpoint `dm` | `LIBRARY_PRESENTATION_STANDARD.md` (القسم 9) | `img.shields.io/packagist/dm/...` | Shields.io Downloads Badge | Endpoint رسمي للتحميلات الشهرية. | `VERIFIED BY OFFICIAL DOCUMENTATION` | Retain |
| Endpoint `dt` | `LIBRARY_PRESENTATION_STANDARD.md` (القسم 9) | `img.shields.io/packagist/dt/...` | Shields.io Downloads Badge | Endpoint رسمي لإجمالي التحميلات. | `VERIFIED BY OFFICIAL DOCUMENTATION` | Retain |
| URL Encoding للحزمة | `LIBRARY_PRESENTATION_STANDARD.md` (القسم 9) | `Install-composer%20require%20{ENCODED_COMPOSER_PACKAGE_NAME}` | Shields.io Static Badge Format | Shields يطلب URL Encoding لعدم كسر البنية (`/` يصبح `%2F`). | `VERIFIED BY OFFICIAL DOCUMENTATION` | Retain |
| حزمة غير موجودة في Shields | (مطلوب من الاختبارات) | لا تدعي توفر الحزمة | تجربة فعلية (2026-07-18) | HTTP status 200, Exit 0, الشارة تكتب `not found`. | `VERIFIED BY REPRODUCIBLE TEST` | Clarify (يجب عدم إضافتها كأنها متوفرة) |
| شارة الإصدار قبل Stable Tag (Pre-releases) | `LIBRARY_PRESENTATION_STANDARD.md` (القسم 8.4) | Inclusion before tag | Shields.io Query Params | Shields يستبعد الـ pre-releases افتراضياً ما لم يطلب `?include_prereleases`. الادعاء بأن الشارة ستظهر بشكل صحيح قبل الـ Tag يحتاج لضبط Parameter أو إيضاح أنه يعرض الإصدار المتاح كـ dev. | `AMBIGUOUS` | Clarify |
| رابط شعار Maatify | `LIBRARY_PRESENTATION_STANDARD.md` (القسم 7) | `maatify_logo_white.svg` | تجربة فعلية (2026-07-18) | يعيد `HTTP 200 OK` و Content-Type `image/svg+xml`, Exit 0. | `VERIFIED BY REPRODUCIBLE TEST` | Retain (يثبت فقط التوافر وصيغة الـ SVG في وقت الفحص) |
| دعم الروابط النسبية (Relative Links) | (مطلوب من الاختبارات) | (مذكور في التقرير) | GitHub Relative Links Docs | GitHub يدعم تحويل الروابط النسبية لملفات في الـ Repository داخل ملف README بشكل تلقائي. | `VERIFIED BY OFFICIAL DOCUMENTATION` | Retain |

## 4. الخلاصة

### 4.1 الادعاءات المثبتة
*   سلوكيات أدوات Composer كـ Validate, Autoload, Install/Update, و Platform-reqs موثقة بدقة.
*   الهيكلة المعمارية لـ GitHub Actions لحل مشاكل الـ Path Filtering وتجاوز وظائف Matrix مدعومة كحلول صحيحة من قِبل توثيق GitHub.
*   آليات عمل الروابط للصور الخارجية والشارات الخاصة بـ Packagist/Shields ودعم الروابط النسبية في GitHub مثبتة بالتوثيق الرسمي والتجارب.

### 4.2 الادعاءات القديمة أو غير الدقيقة
*   ادعاء عرض الشارات للإصدارات الـ Dev قبل الـ Stable Tag في Shields دون تحديد `include_prereleases` يعتبر غامضاً أو يحتاج لتوضيح، حيث إن السلوك الافتراضي يخفيها.
*   توضيح سلوك `composer audit` بخصوص الـ `abandoned` يجب أن يكون أكثر دقة لأنه يعتمد على إعداد السياسة ولا يخرج حتماً بـ failure.
*   أداة `--prefer-lowest` تختبر فقط أقدم نسخ مقبولة للحل من المعتمديات، ولا تضمن دعم كل نسخة بين القديم والحديث.

### 4.3 القواعد كسياسة داخلية (Maatify Policy)
*   منع `composer.lock`، منع حقل `version`، إلزام Full-SHA pinning، وتحديد `timeout-minutes`/concurrency، بالإضافة لتعيين `contents: read` كـ Baseline وإلزام الـ `minimum-stability` كـ `stable`، هي كلها سياسات هندسية/أمنية داخلية معتمدة من Maatify وليست قيوداً تقنية مفروضة قسراً من الأدوات الخارجية.

### 4.4 التعديلات المطلوبة لاحقًا
*   `COMPOSER_PACKAGE_STANDARD.md`: تحويل لغة منع `composer.lock` و`version` وسياسات Stability إلى `internal policy wording`.
*   `CI_WORKFLOW_STANDARD.md`: تحويل لغة الـ SHA pinning والـ Least Privilege (`contents: read`) وإعدادات الوقت إلى `internal policy wording`، وتوضيح مخرجات `composer audit` وفحص الـ Gate conclusions بدقة.
*   `LIBRARY_PRESENTATION_STANDARD.md`: توضيح آلية عرض الشارات ما قبل التاج والتنبيه من عدم إدراجها قبل إنشاء الحزمة فعلياً على Packagist (Clarify).

### 4.5 Readiness لكل ملف
*   `drafts/COMPOSER_PACKAGE_STANDARD.md`: **PARTIAL** (جاهز بشرط توضيح سياسات Maatify الداخلية).
*   `drafts/CI_WORKFLOW_STANDARD.md`: **PARTIAL** (جاهز بشرط توضيح سياسات Maatify الداخلية وضبط توثيق نتائج الـ audit).
*   `drafts/LIBRARY_PRESENTATION_STANDARD.md`: **PARTIAL** (يحتاج لتوضيح سياسات الشارات قبل الـ Release).

### 4.6 النتيجة النهائية للـ External Verification العام
**النتيجة النهائية هي PARTIAL.**
تظل بعض الادعاءات بحاجة لتفريق واضح بين السلوك التقني الموثق وبين السياسة الداخلية المفروضة من Maatify (مثل الـ SHA Pinning و lock file policy)، ويحتاج التعامل مع الشارات لحزم Packagist في مرحلة الـ Pre-release لضبط دقيق في الصياغة.
