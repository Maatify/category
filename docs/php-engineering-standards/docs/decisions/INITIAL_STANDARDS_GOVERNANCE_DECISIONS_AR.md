# قرارات حوكمة المعايير الأولية

## 1. Central Canonical + Pinned Local Copy

*   المستودع المركزي هو مصدر التأليف والحقيقة.
*   كل مشروع يحتفظ بنسخة محلية مثبتة على إصدار محدد.
*   تسجل النسخة المحلية:
    *   Upstream Repository: Maatify/php-engineering-standards
    *   Upstream Path: standards/ai/AI_COLLABORATION_WORKFLOW_AR.md
    *   Upstream Version: الإصدار المحدد في النسخة المنسوخة
*   لا يوجد اعتماد على floating `main`.
*   كل ترقية تتم عبر PR مستقلة ومراجعة أثر.

## 2. Profiles مستقلة مع Cross-references

*   كل Profile يحتفظ بملفه المستقل.
*   القاعدة يملكها ملف واحد، وباقي الملفات تحيل إليها بدل نسخها.
*   لا يتم استخراج Shared Core حاليًا.

## 3. Slim Admin داخل نفس المستودع

*   Profile متخصص واختياري.
*   التسلسل:
    *   Module Profile
    *   Slim Admin Profile
    *   Project-Aware Slim Profile
*   Project-Aware مرتبط بالـ host وليس مكتبة قابلة للاستخراج.

## 4. سياسة الأمثلة

*   الأمثلة المحايدة هي الأصل العام في المعايير المركزية.
*   الأمثلة الحقيقية لا تُستخدم إلا عندما تكون معتمدة صراحة بقرار مالك.
*   الأمثلة الحالية داخل `MODULE_SLIM_BUILDING_STANDARD.md` و`MODULE_PROJECT_AWARE_STANDARD.md` هي أمثلة مقصودة ومعتمدة لتوثيق السياسة الموحدة.
*   هذه الأمثلة المعتمدة لا تُعد تسربًا، ولا يلزم تعميمها أو نقلها أو حذفها، ولا تتغير إلا بقرار مالك صريح.
*   يُعد قرار قسم `## 7. اعتماد Module Profiles كسياسة موحدة` هو المرجع الحاكم لهذه الأمثلة.

## 5. Persistence Applicability Profile

*   ملف `PACKAGE_BUILDING_STANDARD` هو Profile عام لكل مكتبات PHP/Composer القابلة لإعادة الاستخدام.
*   قواعد مثل استخدام `PDO`، هيكل مجلد `schema/`، إدارة `migrations`، الـ `transaction handling`، `persistence integration`، وقواعد البيانات واختبارات التكامل التابعة لها، تنطبق جميعها فقط عندما تمتلك الحزمة وظائف Persistence أو Database behavior.
*   الحزم التي لا تملك وظائف Persistence لا تُلزم بـ `PDO` أو `schema/` أو بنية Database.
*   هذا القرار لا يخفف المتطلبات الصارمة على الحزم التي تمتلك Persistence، بل يحولها إلى Conditional Applicability صريحة يتم التفريق فيها بوضوح بين القواعد العامة لكل Composer Package والقواعد المشروطة بامتلاك Persistence.

## 6. Base Module Profile Scope

*   `MODULE_BUILDING_STANDARD` مخصص للموديولات القابلة لإعادة الاستخدام والاستخراج كـ Composer Package.
*   كل Base Module يجب أن تكون Standalone وHost-agnostic وقابلة للتثبيت عبر `composer require`.
*   قواعد PDO وSQL و`schema/` وmigrations وtransactions وPersistence تطبق فقط عندما يمتلك الموديول Database/Persistence behavior.
*   `MODULE_SLIM_BUILDING_STANDARD` هو Profile اختياري متخصص يلف Base Module واحدة لتوفير Admin HTTP/UI integration، ولا يكرر business logic الأساسية.
*   `MODULE_PROJECT_AWARE_STANDARD` هو Profile مرتبط بالـ Host وغير قابل للاستخراج، ويملك الاستثناءات الصريحة مثل cross-module JOINs وتجاوز قيد Slim الخاص بموديول واحدة.
*   Project-Aware لا يلغي باقي قواعد Base المنطبقة، ومنها قواعد PDO وPersistence عند امتلاك Database behavior، إلا بقرار مستقل موثق.
*   الموديولات غير القابلة للاستخراج لا توسّع نطاق Base Profile؛ تستخدم Project-Aware أو Profile مستقلًا يعتمد بقرار منفصل.
*   تظل Profiles مستقلة، وكل Profile يملك قواعده واستثناءاته مع Cross-references وprecedence واضحة.

## 7. اعتماد Module Profiles كسياسة موحدة

*   تم اعتماد المستندات الثلاثة لـ Module Profiles رسميًا في مسار `standards/modules/` وهي:
    *   `MODULE_BUILDING_STANDARD.md`
    *   `MODULE_SLIM_BUILDING_STANDARD.md`
    *   `MODULE_PROJECT_AWARE_STANDARD.md`
*   هذه المعايير ناتجة عن تطبيقات فعلية ناجحة في مشاريع Maatify.
*   يوثق هذا المستودع السياسة الموحدة، ولا يعيد التحقق من تنفيذات المشاريع الفردية.
*   محتوى هذه الملفات الثلاثة لا يتغير إلا في الحالات التالية:
    *   بقرار صريح من المالك لتغيير أو تحديث سياسة.
    *   لتصحيح روابط أو ترقيم أو cross-references.
*   الأمثلة الموجودة داخل معايير Slim وProject-Aware (مثل أمثلة AdminKernel والمشاريع الخاصة) مقصودة لتوثيق السياسة الموحدة، ولا تُعد تسربًا يجب تعميمه أو حذفه.

## 8. سياسة اختبار الكلاسات Final

وثّق هذا القرار أن المالك اعتمد القواعد التالية:

*   contracts/interfaces هي الحدود الافتراضية للـ Test Doubles.
*   `final` لا تُزال لأجل الاختبارات.
*   الـ real instance أو Fake للحدود التابعة هي البدائل الأولى.
*   إنشاء interface مرتبط بحاجة Runtime حقيقية.
*   `dg/bypass-finals` ليس معيارًا مركزيًا.
*   حلول PHPStan suppressions الخاصة بالملف القديم غير معتمدة مركزيًا.
