# معيار اعتماد وتوزيع معايير Maatify

## بيانات المعيار

- **الإصدار:** `1.0.0`
- **اللغة المعتمدة:** العربية.
- **حالة الاعتماد:** يصبح معتمدًا عند دمجه في الفرع الافتراضي للمشروع.
- **النطاق:** آلية اختيار المعايير وتثبيتها وتوزيعها وتفعيلها داخل المشاريع التابعة لمنظومة Maatify.
- **الهدف:** استبدال نسخ المستودع الكامل باعتماد انتقائي مثبت وقابل للتتبع، مع الحفاظ على المصدر المركزي وسلامة الروابط والترقية القابلة للمراجعة.

هذا الملف هو **المصدر الوحيد للحقيقة لآلية Adoption**. لا يملك قواعد هندسية تخص PHP أو Composer أو Modules أو CI أو Testing؛ تلك القواعد تظل مملوكة للـ Standards المشار إليها في ملفات Profiles.

## 1. المصدر المركزي وحدود الملكية

المستودع:

```text
Maatify/php-engineering-standards
```

هو المصدر المركزي الوحيد لتأليف المعايير وProfiles. المشروع التابع يحتفظ بنسخ محلية لأغراض الاعتماد والتثبيت، لكنه لا يجعل النسخة المحلية أو ملف الـ Manifest مصدرًا منافسًا للقواعد الأصلية.

يجب أن يملك كل موضوع معياري ملفًا واحدًا. Profile هو **composition manifest** يحدد ما يجتمع وما ينطبق، ولا يعيد كتابة القواعد المملوكة للـ Standard.

## 2. منع Full Repository Snapshot

يجب ألا يعتمد أي مشروع على نسخ مجلد:

```text
standards/
```

بالكامل كإجراء افتراضي أو احتياطي. لا يجوز استخدام نمط `all standards just in case`، ولا يجعل وجود Standard في المستودع المركزي تلك الـ Standard منطبقة على كل مشروع.

يجب أن يحتوي المشروع فقط على **Pinned Adoption Files**: الـ Pinned Adoption Control Set الإلزامية، والـ Pinned Applicable Standards Set الناتجة عن Profiles المفعلة وdependencies الخاصة بها، إضافة إلى Additional Standards المصرح بها صراحةً. لا تدخل ملفات أخرى لمجرد وجودها في المستودع المركزي.

## 3. طبقات الاعتماد: Control Plane وApplicable Engineering Standards

يستخدم كل consuming repository يعتمد هذا النظام طبقتين مختلفتين من الملفات المنسوخة من upstream، وسجلًا محليًا للنتيجة. لا تجعل هذه الطبقات Adoption Standard جزءًا من Required Standards الهندسية لأي Profile.

### A. Pinned Adoption Control Set

هذا الـ Control Set **إلزامي** لكل consuming repository يستخدم نظام Selective Adoption. يجب أن يحتوي، من upstream pinned commit، على:

```text
standards/STANDARDS_ADOPTION_STANDARD_AR.md
active Profile manifests
all inherited Profile manifests required to resolve those active Profiles
```

يجب ألا يحتوي Control Set على Profile غير مستخدمة أو غير موروثة من Profile مفعلة.

إذا كان الـ Active Profile هو:

```text
project-aware-slim-module
```

فإن Profile manifests المحلية المطلوبة هي:

```text
PROJECT_AWARE_SLIM_MODULE_PROFILE.md
SLIM_MODULE_PROFILE.md
BASE_MODULE_PROFILE.md
COMPOSER_PACKAGE_PROFILE.md
```

ولا يلزم نسخ:

```text
REPOSITORY_GOVERNANCE_PROFILE.md
```

إلا إذا كان مفعّلًا أيضًا أو دخل في inheritance مطلوبة.

وجود Adoption Standard وProfile manifests في Control Set لا يجعل Adoption Standard capability هندسية، ولا يضيفها إلى `Required Standards` لأي Profile.

### B. Pinned Applicable Standards Set

هو مجموعة الـ Standards الهندسية الناتجة عن:

```text
Active Profiles
+ inherited Profiles
+ Explicit Additional Standards
```

بعد تطبيق Scope applicability وأخذ Union للـ Activations المنطبقة. هذه المجموعة هي التي تحدد القواعد الهندسية المطلوب قراءتها وتطبيقها على Scope المهمة.

### C. Local Resolver Record

```text
STANDARDS_MANIFEST.md
```

هو ملف محلي يولده المشروع ويسجل نتيجة adoption والعلاقة بين Control Set وApplicable Standards Set. ليس ملفًا منسوخًا من upstream، وليس Standard أو Profile، ولا يدخل في Required Standards أو في Pinned Adoption Control Set.

وعليه:

```text
Pinned Adoption Files
=
Pinned Adoption Control Set
+
Pinned Applicable Standards Set

Local metadata
=
STANDARDS_MANIFEST.md
```

## 4. Selective Pinned Adoption

عند إنشاء أو تحديث Adoption Set:

1. تكون Adoption Standard وProfile manifests والـ Applicable Engineering Standards نسخًا محلية ومثبتة على Adoption Commit محدد.
2. تأتي جميع الملفات المنسوخة من upstream افتراضيًا من **نفس exact upstream commit** حفاظًا على consistency بين Control Plane والروابط والقواعد.
3. يمنع الاعتماد على floating `main` أو أي مرجع متحرك بدل commit محدد.
4. يسجل المشروع في Manifest المستودع upstream والـ commit والـ Profiles والـ Scopes والـ Control Set والـ Applicable Standards وإصداراتها المرتبطة بها.
5. تتم الترقية عبر تغيير reviewed يعيد حل Profiles المستخدمة فقط.
6. لا تنسخ الترقية Standards غير المنطبقة على Scopes المشروع.

لا يجوز جمع Standards من commits مختلفة إلا بقرار صريح موثق من مالك المشروع، ويجب تسجيل هذا الاستثناء في Manifest مع سببه ونطاقه.

## 5. Profile Composition Manifests

كل Profile مستقل ويبدأ افتراضيًا بالإصدار `1.0.0`. يجب أن يوضح ملف Profile، على الأقل:

- `Profile ID` فريدًا.
- `Profile Version` منفصلًا عن إصدارات Standards.
- `Purpose / Applicability`.
- `Extends`، أو `None` إذا لم يرث Profile آخر.
- `Required Standards` المباشرة التي يضيفها Profile.
- `Conditional Applicability`، مع الإحالة إلى Standard المالكة للشرط.
- `Resolved dependency behavior`.
- `Scope notes`.
- `Precedence notes`.

Profiles لا تنقل القواعد الهندسية إلى ملفاتها ولا تنشئ مصدرًا موازيًا للحقيقة. كل Profile يعلن الاعتمادات المباشرة فقط؛ أما الاعتمادات الموروثة فتدخل في الحل عبر Transitive Resolution.

### 5.1 Profile Versioning

يُفصل إصدار Profile عن إصدار كل Standard:

- **Patch:** تغيير صياغة أو metadata لا يغير composition.
- **Minor:** إضافة قدرة اختيارية أو شرطية متوافقة لا تغير الالتزامات القائمة.
- **Major:** تغيير Required Standards أو inheritance أو composition بما يغير عقد الاعتماد.

ملف Profile المفعّل، وكل ملف Profile موروث لازم لحل inheritance، جزء إلزامي من `Pinned Adoption Control Set` ويجب تثبيته محليًا. Profiles غير المفعلة وغير الموروثة لا تُنسخ. لا يعني ذلك إضافة Adoption Standard إلى `Required Standards`؛ فـ Required Standards تظل خاصة بالـ engineering applicability.

## 6. Scope-Aware Profile Activation

كل Profile Activation في المشروع يجب أن تحدد Scope صريحًا. يمكن أن يكون Scope:

```text
/
Modules/*
Modules/*Slim
Modules/SpecificFeature
```

أو قائمة paths محددة. Scope هو نطاق التطبيق، وليس نوعًا حصريًا للمستودع.

يجوز للمشروع تفعيل عدة Profiles في Repository واحدة، كما يجوز أن تنطبق عدة Activations على الملف نفسه. لا يعني Scope الأكثر تحديدًا إلغاء Scope أوسع تلقائيًا؛ بل تُجمع Profiles المنطبقة، ما لم يوجد استثناء مشروع موثق بسلطة مناسبة.

عند تنفيذ مهمة، يجب على الوكيل:

1. تحديد الملفات والمسارات المتأثرة بالمهمة.
2. مطابقة هذه المسارات مع Profile Activations.
3. حل Profiles المنطبقة على تلك Scopes.
4. أخذ Union للـ Standards المطلوبة من الحل.
5. قراءة وتطبيق Standards الموجودة في هذا الـ Resolved Set فقط.
6. تطبيق التعليمات المحلية والاستثناءات الموثقة وفق precedence المشروع.

إذا عبرت المهمة أكثر من Scope، يستخدم الوكيل Union للـ Profiles المنطبقة على جميع المسارات المتأثرة.

## 7. Transitive Profile Resolution

يجب أن يكون inheritance صريحًا وقابلًا للحل دون دورات. إذا كان:

```text
project-aware-slim-module
    extends slim-module
slim-module
    extends base-module
base-module
    extends composer-package
```

فإن الـ Resolved Adoption Set يتضمن Required Standards المباشرة لكل Profile في السلسلة، بترتيب dependency، دون إلزام المشروع بإدراجها مكررًا.

يجب على الـ resolver المفاهيمي أو عملية المراجعة التحقق من:

- وجود كل Profile مذكور في `Extends`.
- عدم وجود inheritance cycle.
- وجود كل Required Standard المشار إليها فعليًا.
- عدم إسقاط Standard موروثة بسبب تفعيل Profile أكثر تحديدًا.

لا يغير Profile القاعدة التي تملكها Standard أخرى. أي استثناء أو override يجب أن يكون explicit وموثقًا في Manifest ومسنودًا بقرار أو تعليمات مشروع ذات أولوية مناسبة.

## 8. Local Directory Layout وسلامة الروابط

عند نسخ Pinned Adoption Files إلى المشروع، يجب الحفاظ على البنية النسبية اللازمة لسلامة الروابط بين الملفات المختارة. المثال النهائي التالي يوضح أن Adoption Standard وProfile manifests المفعلة والموروثة جزء من النسخة المحلية، بينما تبقى المعايير الهندسية انتقائية:

```text
docs/php-engineering-standards/
├── STANDARDS_MANIFEST.md
└── standards/
    ├── STANDARDS_ADOPTION_STANDARD_AR.md
    ├── profiles/
    │   ├── <active profiles>
    │   └── <inherited profiles>
    ├── ai/
    │   └── <if applicable>
    ├── modules/
    │   └── <resolved applicable standards>
    ├── packages/
    │   └── <resolved applicable standards>
    └── testing/
        └── <if resolved>
```

تكون `<active profiles>` و`<inherited profiles>` أسماء الملفات المحلية الفعلية، ولا تعني placeholders لنسخ كل محتوى مجلد `profiles/`.

لا تُنسخ:

```text
unused profiles
unused engineering standards
docs/audits/
docs/decisions/
```

لا يشترط الاعتماد نسخ Folders فارغة، ولا تدخل الملفات التاريخية في Adoption Set. تظل `docs/audits/` و`docs/decisions/` في المستودع المركزي للتاريخ والحوكمة فقط.

## 9. عقد `STANDARDS_MANIFEST.md`

يجب أن يحتفظ كل مشروع تابع بملف:

```text
docs/php-engineering-standards/STANDARDS_MANIFEST.md
```

أو بالاسم نفسه تحت local standards root المعتمد للمشروع. الـ Manifest هو inventory وresolver record، وليس Standard جديدة. يجب أن يسجل على الأقل:

```text
Upstream Repository
Adoption Commit
Adoption Date أو metadata مناسبة وفق سياسة المشروع
Pinned Adoption Control Set
Active Profiles
Profile Version لكل Profile Activation
Scope لكل Profile Activation
Resolved Standards
Version لكل Standard
Explicit Additional Standards إن وجدت
Explicit Exceptions/Overrides إن وجدت
```

يجب أن تجعل البيانات المسجلة قابلة لمراجعة العلاقة بين كل Profile Activation وScope والـ Control Set والـ Applicable Standards الناتجة عنها. لا تضع في Manifest القواعد الهندسية الكاملة؛ استخدم روابط إلى الملفات المملوكة لها.

## 10. تكامل `AGENTS.md` في المشروع التابع

يكفي أن يشير `AGENTS.md` في المشروع التابع إلى `STANDARDS_MANIFEST.md` وإلى هذا Adoption Standard، بدل سرد كل Standard يدويًا. عند بدء مهمة، يلتزم الوكيل بمسار resolution المناسب لنوع المهمة.

### 10.1 Normal Engineering Task

في المهمة الهندسية العادية:

1. يقرأ الوكيل `STANDARDS_MANIFEST.md`.
2. يحدد Scope الملفات المتأثرة.
3. يحدد Profile Activations المنطبقة المسجلة محليًا.
4. يستخدم الـ Applicable Standards Set المسجلة في Manifest.
5. يمكنه التحقق من composition عبر Profile manifests المحلية المثبتة.
6. يقرأ Applicable Standards فقط.

لا يعاد بناء adoption من upstream في كل Task، ولا يحتاج الوكيل الاتصال بـ upstream لإتمام مهمة عادية.

### 10.2 Adoption / Upgrade / Manifest Validation

في مهمة Adoption أو Upgrade أو Manifest Validation، يعاد resolution فعليًا من:

```text
locally pinned active/inherited Profile manifests
```

ثم يتم التحقق من أن النتيجة تطابق `STANDARDS_MANIFEST.md`، بما في ذلك Control Set وApplicable Standards Set. وعند Upgrade فقط يتم جلب exact upstream commit الجديد ومراجعة أثره، مع إعادة حل Profiles المفعلة فقط.

## 11. Additional Standards

يجوز للمشروع إضافة Standard مستقلة خارج Profiles عندما تنطبق فعليًا على Scope محدد. يجب تسجيلها تحت `Explicit Additional Standards` في Manifest، وتظل مثبتة إلى نفس Adoption Commit افتراضيًا.

لا يجوز استخدام Additional Standards كطريقة غير مباشرة للعودة إلى نسخ كل محتوى `standards/`. يجب أن يكون لكل إضافة سبب applicability ومسار أو Scope واضح.

## 12. الترقية والمراجعة والتتبع

تتم ترقية الاعتماد عبر reviewed change تسجل:

- الـ Adoption Commit الجديد.
- Profiles والإصدارات المستخدمة بعد الترقية.
- إعادة حل Scopes والـ Control Set والـ Applicable Standards Set.
- أي Standard أضيفت أو أزيلت ولماذا.
- أثر الروابط النسبية والاستثناءات.

الترقية لا تعني Refresh شاملًا للمستودع. يعاد Resolve للـ Profiles المفعلة فقط، وتبقى Standards غير المنطبقة خارج النسخة المحلية.

## 13. Adoption Precedence

هذا المعيار يحدد applicability وcomposition، ولا يلغي هرم الأولوية العام في `AI_COLLABORATION_WORKFLOW_AR.md`. عند وجود تداخل:

1. تحدد Profile Activations الـ Union للـ Standards؛ ولا يحذف Profile Standard موروثة لمجرد أن Scope آخر أكثر تحديدًا.
2. تظل القاعدة التفصيلية مملوكة للـ Standard الأصلية، وليس لملف Profile أو Manifest.
3. تطبق تعليمات `AGENTS.md` الخاصة بالمشروع وفق أولوية المشروع، وتكون أي استثناءات أو overrides المؤثرة على الاعتماد صريحة ومسجلة.
4. تبقى تعليمات المالك الحالية والقرارات المعتمدة أعلى من عقد الاعتماد وفق هرم المسؤولية المعمول به.

عند تعارض غير محسوم بين مصادر ذات أولوية متقاربة، لا يُحسم بالتخمين؛ يجب تسجيله ورفعه للمالك.

## 14. Invariants

يجب أن تظل الحقائق التالية صحيحة عند اعتماد أي مشروع:

```text
Central canonical source: YES
Adoption Standard present locally: YES
Active Profile manifests pinned locally: YES
Inherited Profile manifests pinned locally: YES
Unused Profile manifests copied: NO
Applicable Standards only: YES
Ordinary task requires upstream network: NO
Manifest independently auditable against pinned Profiles: YES
Same upstream commit by default: YES
Floating main: NO
Full repository snapshot: NO
Historical audits and decisions copied to consumers: NO
Underlying standards remain source of truth: YES
```

## 15. Adoption Review Checklist

قبل قبول Adoption أو Upgrade، يجب التحقق من:

- [ ] وجود Upstream Repository وAdoption Commit ثابت.
- [ ] وجود Adoption Standard محليًا ضمن Pinned Adoption Control Set.
- [ ] وجود كل Active Profile manifest محليًا ومثبتًا.
- [ ] وجود كل inherited Profile manifest لازمة للحل محليًا ومثبتة.
- [ ] عدم نسخ Profile غير مستخدمة.
- [ ] عدم استخدام floating `main`.
- [ ] عدم نسخ `standards/` بالكامل افتراضيًا أو احتياطيًا.
- [ ] وجود Profile ID وVersion وScope لكل Activation.
- [ ] سلامة inheritance وعدم وجود cycle.
- [ ] وجود كل Required Standard في الـ Pinned Applicable Standards Set.
- [ ] تطبيق resolution عابر للـ Profiles دون تكرار يدوي.
- [ ] تطابق Control Set وApplicable Standards Set مع Manifest.
- [ ] عدم حاجة المهمة الهندسية العادية إلى upstream network.
- [ ] الحفاظ على relative links والبنية اللازمة لها.
- [ ] تسجيل Additional Standards والاستثناءات صراحةً.
- [ ] عدم إدخال `docs/audits/` أو `docs/decisions/` في Adoption Set.
- [ ] قراءة الوكيل للـ Standards المنطبقة فقط على Scope المهمة.
