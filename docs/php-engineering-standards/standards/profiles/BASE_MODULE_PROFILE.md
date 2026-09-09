# Base Module Profile

## Profile Metadata

- **Profile ID:** `base-module`
- **Profile Version:** `1.0.0`
- **Purpose / Applicability:** موديول مستقل، Host-agnostic، قابل لإعادة الاستخدام والاستخراج كـ Composer Package.
- **Extends:** `composer-package`

## Required Standards

يضيف هذا Profile مباشرةً:

- [Module Building Standard](../modules/MODULE_BUILDING_STANDARD.md)

ويحصل على Required Standards الخاصة بـ `composer-package` عبر inheritance، دون إدراجها يدويًا مرة أخرى.

## Conditional Applicability

قواعد Persistence في Base Module تظل مشروطة بامتلاك الموديول Database/Persistence behavior، وفق ما تملكه المعايير الأصلية. لا يغير هذا Profile تلك الشروط.

## Resolved Dependency Behavior

عند التفعيل، يحل Adoption Resolver سلسلة `base-module → composer-package` ويأخذ Union للـ Standards المباشرة في السلسلة. لا يضيف Profile قواعد Package منسوخة.

عند تفعيل هذا Profile، يجب تثبيت ملفه محليًا ضمن `Pinned Adoption Control Set`؛ وتثبت كذلك ملفات Profiles الموروثة اللازمة للحل، بينما لا تُنسخ Profiles غير المفعلة.

## Scope Notes

يجب أن يحدد المشروع Scope الموديول، مثل `Modules/*` أو مسار موديول مستقل. لا يفترض هذا Profile أن كل Repository أو كل Module تنتمي إليه.

## Precedence Notes

تظل حدود Base Module وعلاقاتها مملوكة لـ [MODULE_BUILDING_STANDARD.md](../modules/MODULE_BUILDING_STANDARD.md)، وتظل قواعد الحزمة الموروثة مملوكة لـ `composer-package`. هذا الملف يعلن composition فقط.
