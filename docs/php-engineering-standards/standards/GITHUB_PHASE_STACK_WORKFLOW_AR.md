# معيار GitHub Phase Stack Workflow

## بيانات المعيار

- **الإصدار:** `2.0.0`
- **اللغة المعتمدة:** العربية.
- **حالة الاعتماد:** يصبح معتمدًا عند دمجه في الفرع الافتراضي للمشروع.
- **الهدف:** تنظيم تنفيذ الـPhase عبر Phase Draft ووحدات عمل رأسية وDependency-Aware Execution Train، مع الحفاظ على traceability والمراجعة والاختبارات وجودة `main` دون فرض تسلسل إداري لا تدعمه dependencies فعلية.

---

# 1. قاعدة الـPhase الأساسية

**كل Phase ذات عمل فعلي (Executed Phase) يجب أن تمر عبر Phase Draft مكتملة الأركان، بحيث لا يدخل إلى `main` إلا عمل مكتمل ومراجع ومثبت بالأدلة كوحدة هندسية مفهومة.**

أما الـPhase التي يثبت Baseline Reconciliation أنها `No-op` بالكامل وفق §5، فلا تنشأ لها Phase Draft أو دورة فروع وPRs؛ تغلق تشغيليًا بأدلة ذلك الإثبات دون إسقاط Acceptance Criteria أو اختلاق تغيير.

يستخدم هذا المعيار نموذج:

```text
Dependency-Aware Phase Train
```

بدل فرض:

```text
Strict Sequential Stack
```

ولا يعني السماح بالتوازي تخفيف أي Quality Gate أو قبول Phase ناقصة.

## 1.1 الهيكل النموذجي

يكون الهيكل المفاهيمي:

```text
main
└── phase-draft
    ├── blueprint / planning       (إذا احتاجتها Phase)
    ├── execution wave 1
    │   ├── work-unit-a
    │   ├── work-unit-b
    │   └── work-unit-c
    ├── execution wave 2
    │   ├── work-unit-d
    │   └── work-unit-e
    └── phase closure
        ├── required fixes         (إذا لزم تغيير)
        └── integration gates
```

الـWave تخطيط تشغيلي وليست ملفًا دائمًا إلزاميًا. ولا تصبح الـVerification أو Final Review أو Gate Component لمجرد وجودها في هذا الهيكل؛ ينشأ لها Branch وPR فقط إذا نتج عنها تغيير مستودع مستقل ذي معنى.

---

# 2. Phase Draft

## 2.1 إنشاء الـ Phase Draft للـPhase ذات العمل الفعلي

1. تبدأ كل Phase ذات عمل فعلي من أحدث حالة فعلية ومعتمدة لـ`main`.
2. ينشأ **Draft Branch واحد للـPhase بالكامل**.
3. يمثل الـDraft Branch نقطة تجميع وتحقق للـPhase، ولا يستخدم للتطوير العشوائي أو الـCommits المباشرة.
4. تكون PRs الخاصة بوحدات العمل موجهة إلى الـPhase Draft وليس إلى `main`.
5. لا يدخل `main` إلا الـPhase Draft بعد اكتمال جميع وحداتها وGates المطلوبة.

إذا أثبت Baseline Reconciliation أن الـPhase `No-op` بالكامل، فلا تنفذ هذه الخطوات؛ يطبق مسار الإثبات والإغلاق التشغيلي في §5 بدل إنشاء Draft أو Branch أو PR.

## 2.2 Work Units ذات التغيير

1. كل Work Unit أو Component ينتج تغييرًا في المستودع يجب أن يملك Branch مستقلة وPR موجهة إلى الـPhase Draft.
2. ينشأ Branch الـWork Unit من أحدث Draft HEAD متاح عند بدء موجتها أو عند تفويضها، مع تثبيت الـBaseline في التوجيه والتقرير.
3. يمنع إجراء تعديلات أو إضافة Commits مباشرة على الـPhase Draft؛ فهي نقطة تجميع فقط.
4. تظل PR مفتوحة حتى اكتمال التنفيذ والمراجعة والتصحيحات وComponent Gate المطلوبة.
5. لا يدخل إلى الـDraft جزء سليم من Work Unit غير مكتملة. إذا تعثرت Work Unit، تطبق قواعد الاستعادة دون تقسيم acceptance الخاصة بها إلى مكوّن بديل.

## 2.3 الدمج المنظم مع التوازي

يجوز تنفيذ عدة Work Units بالتوازي، لكن دمجها إلى الـPhase Draft يظل منظمًا:

1. لا تدمج عدة Components إلى Draft بصورة عمياء.
2. يجب اعتماد كل Component واجتياز Component Gate قبل دمجها.
3. تتم عمليات الدمج إلى Draft واحدة تلو الأخرى حتى تظل حالة Draft معروفة بعد كل دمج.
4. قبل دمج Component مبنية على Draft أقدم، يتحقق المساعد القائد من توافقها مع أحدث Draft HEAD، ومن عدم تغير assumptions أو الملفات المشتركة.
5. إذا كانت المزامنة مطلوبة، يحدد التوجيه طريقة غير معيدة لكتابة التاريخ، مثل تنفيذ local `git merge` مصرح به لأحدث Draft في Branch الـComponent بCommit جديدة أو إنشاء Branch/PR بديلة من أحدث Draft عند الحاجة. يعاد تشغيل checks والمراجعة المتأثرة بعد المزامنة.
6. يمنع استخدام `git commit --amend` أو force-push لإخفاء تاريخ التصحيحات أو حل تعارض الـBaseline.

عند اكتمال واعتماد Component، يتم **GitHub Squash Merge عبر Component PR إلى الـPhase Draft** وفق صلاحيات Git المعتمدة. لا يجوز دمج Component غير مكتملة أو تمرير تعارض لمجرد أن تنفيذها بدأ في Wave سابقة.

---

# 3. Dependency-Aware Execution

## 3.1 dependency graph وExecution Waves

قبل التفويض، يعيد المساعد القائد بناء dependency graph ويثبت، لكل Work Unit:

- dependencies التنفيذية.
- الملفات والـownership.
- Public Contract أو assumptions المشتركة.
- Acceptance Criteria المستقلة.
- Gate المطلوبة قبل الانتقال إلى Work Unit تعتمد عليها.

تتكون الـPhase من Execution Waves. يمكن تنفيذ Work Units داخل نفس الـWave بالتوازي إذا أثبت المساعد القائد قبل التفويض:

- عدم وجود dependency تنفيذية مباشرة تتطلب الترتيب.
- عدم وجود تعارض متوقع في Public Contract.
- عدم وجود overlap خطير في الملفات أو ownership.
- عدم اعتماد Work Unit على ناتج غير مدمج من أخرى.
- استقلال Acceptance Criteria وحدود الملفات والمسؤولية.

إذا وجدت dependency أو overlap مؤثر، تنفذ الوحدات المعنية sequential. التوازي ليس إلزاميًا، لكنه ممنوع أن يكون محظورًا عالميًا، ولا ينتقل التنفيذ إلى Wave تالية إلا بعد اجتياز dependencies الفعلية وGates المطلوبة لها.

---

# 4. تعريف Work Unit ومكوناتها

## 4.1 Vertical Work Unit

الأصل أن تكون Work Unit وحدة هندسية كاملة ذات معنى، وليست نوع Artifact منفصلًا. عندما يكون ذلك منطقيًا، تشمل Work Unit الخاصة بـFeature أو Gap واحدة:

```text
Runtime
+ tests الخاصة بها
+ التوثيق المتأثر مباشرة
+ verification الخاص بالتغيير
```

يمنع افتراضيًا تقسيم Gap واحدة إلى Runtime PR وTests PR وDocumentation PR وVerification PR إذا كانت كلها تخص التغيير نفسه ويمكن مراجعتها كوحدة واحدة.

يجوز الفصل عند وجود سبب هندسي حقيقي، مثل:

- ownership مستقل.
- dependency مستقلة.
- cross-cutting verification.
- Documentation Sweep عامة للـPhase.
- تغيير واسع يحتاج isolation حقيقيًا.

## 4.2 Verification كـGate

Verification نشاط أو Gate، وليست Component افتراضية.

إذا انتهت Verification بنتيجة `PASSED` ولم تنتج تغييرًا في المستودع:

```text
لا Branch
لا Commit
لا PR
```

يسجل المساعد القائد evidence في التقرير أو المكان التشغيلي المناسب. وإذا كشفت Verification عن تغيير، ينفذ داخل Work Unit المفتوحة إن كانت ما زالت قيد المراجعة، أو داخل Consolidated Required-Fix Component عند Phase Closure.

لا تنشأ PR فقط لتسجيل أن الاختبارات نجحت.

## 4.3 Final Review كـGate

Final Review نشاط قبول ومراجعة، وليست Component افتراضية.

إذا لم تنتج Final Review تغييرًا في المستودع، تسجل نتيجتها كـGate evidence فقط. وإذا كشفت عن تغييرات، تطبق قواعد Work Unit أو Consolidated Required Fixes، ولا تنشأ سلسلة PRs منفصلة لكل ملاحظة صغيرة.

## 4.4 Consolidated Required Fixes

تجمع findings المتوافقة الناتجة من نفس Review Pass داخل **Consolidated Required-Fix Component** واحدة متى كان ذلك آمنًا ومتماسكًا.

لا تجمع مشاكل غير مترابطة إذا جعل ذلك PR غير قابلة للمراجعة، لكن يمنع إنشاء سلسلة:

```text
fix-1
fix-2
fix-3
docs-fix
status-fix
wording-fix
```

لمجرد أن findings اكتشفت منفردة. إذا كان finding يخص Work Unit مفتوحة، يعالج فيها بدل إنشاء Component إضافية.

---

# 5. No-op وBaseline Reconciliation

## 5.1 No-op Component وPhase Prohibition

إذا كانت Acceptance Criteria لمكوّن أو Phase موجودة بالفعل ومثبتة في الـBaseline الحالي:

```text
لا يعاد تنفيذها
لا ينشأ Branch فارغ
لا تنشأ PR Verification شكلية
```

تصنف الحالة بناءً على الأدلة الفعلية كـ`ALREADY IMPLEMENTED / VERIFIED` أو تصنيف أدق مناسب، ولا تنشأ Component لا تضيف تغييرًا أو دليلًا مطلوبًا.

### Phase مثبتة بالكامل كـNo-op

لا تصنف Phase بأنها `No-op` لمجرد وجود Implementation. يجب أن يثبت Baseline Reconciliation أن **جميع Acceptance Criteria الخاصة بالPhase نفسها** هي:

```text
ALREADY IMPLEMENTED + PROVEN
```

على Baseline معتمدة، وألا يوجد في نطاقها:

- repository change.
- missing proof.
- unresolved decision.
- required verification جديدة.
- documentation change.
- contract gap.

عند تحقق هذه الشروط، تعتبر Phase `Execution No-op` مثبتة بالأدلة، ولا ينشأ لها:

```text
Phase Draft
Branch
PR
empty commit
status-only documentation PR
owner merge ceremony
```

لا يجوز استخدام هذا المسار لإسقاط Acceptance Criteria أو تجاوز دليل مطلوب أو تغيير تاريخ المشروع.

## 5.2 Baseline Reconciliation

في المشاريع ذات Roadmap طويلة أو Legacy Implementation أو Extracted Module/Library أو Migration Baseline، تنفذ Baseline Reconciliation مرة واحدة على النطاق المتبقي عندما يكون ذلك أوفر من إعادة Discovery لكل Phase.

التصنيفات الممكنة:

```text
ALREADY IMPLEMENTED + PROVEN
IMPLEMENTED BUT MISSING PROOF
PARTIAL / GAP
NOT IMPLEMENTED
BLOCKED BY DECISION
```

هدفها عدم إعادة بناء الموجود، وكشف الـGaps مبكرًا، وإغلاق الـPhases المثبتة كـNo-op بالأدلة دون إنشاء دورة تنفيذ شكلية، وبناء dependency graph واقعية للعمل المتبقي. Baseline Reconciliation Activity تحليلية وليست PR أو طبقة Approval إلزامية بحد ذاتها.

إذا أثبتت Reconciliation أن عدة Phases متتابعة كلها `No-op` بالكامل، يجوز إغلاقها تشغيليًا دفعة واحدة في Evidence/Reconciliation Record واحد، بشرط الاحتفاظ بإثبات Acceptance Criteria لكل Phase وعدم إسقاط أي منها أو تغيير تاريخ المشروع كذبًا.

## 5.3 Phase / Roadmap Compaction

داخل Roadmap معتمدة، يجوز للمساعد القائد **اقتراح** Execution Compaction عندما تكون عدة Phases متتابعة موجودة بالفعل جزئيًا أو كليًا، أو شديدة الترابط، أو لا تمثل Boundaries هندسية مستقلة أثناء التنفيذ. إذا كان الدمج المقترح يغير Scope أو Phase Boundaries المعتمدة، فلا ينفذه المساعد القائد من نفسه؛ يعرض الأدلة والبدائل والأثر على المالك، ولا يصبح نافذًا إلا بعد اعتماد المالك.

بعد الاعتماد، يمكن تنفيذ الـPhases ذات العمل الفعلي كـExecution Train أو Phase أوسع وفق هذا المعيار. أما الـPhases المثبتة كـNo-op فتظل مسار Evidence-only وفق §5 ولا تتحول إلى Draft أو PR شكلية.

لا يجوز أن يؤدي ذلك إلى:

- حذف Acceptance Criteria.
- إسقاط Quality Gates.
- الادعاء باكتمال شيء غير مثبت.
- تغيير Architecture أو Public Contract أو Scope مؤثر دون اعتماد مالك المشروع.

يبقى الفصل المفاهيمي في الـRoadmap ممكنًا، بينما تصبح Execution Units أقل وأكثر منطقية.

---

# 6. Tiered Verification وCI

## 6.1 Component Gate

تشغل كل Work Unit checks الكافية لاكتشاف Regression المرتبط بها، إضافة إلى Static/General Gates المطلوبة التي تكون تكلفتها معقولة لنطاقها وProfile المشروع.

لا تضطر Documentation-only Work Unit صغيرة إلى تكرار Expensive Integration Matrix بلا سبب، إلا إذا أثبت معيار آخر أن هذا Check إلزامي لهذا النوع من التغيير. لا يجوز في المقابل تخطي Check مرتبطة مباشرة بالسلوك أو العقد المتغير.

## 6.2 Phase Integration Gate

بعد دمج جميع Work Units المطلوبة إلى أحدث Phase Draft، تشغل Full Required Verification للـPhase بحسب Profile المشروع ومعايير CI وTesting، وتشمل عند انطباقها:

- Full Test Suite.
- PHPStan أو Static Analysis.
- Latest وLowest Dependencies.
- Real Service/Database Integration.
- System/E2E Regression Protection.
- Composer/Package Checks.
- Workflow Checks.

تظل CI بواباتها مستقرة وFail-Closed وفق `CI_WORKFLOW_STANDARD.md`، ويظل Testing Standard هو المرجع لتغطية السلوك وSystem/E2E. لا تعتبر Phase جاهزة لـ`main` قبل نجاح Phase Integration Gate وجميع Gates الأخرى المطلوبة.

## 6.3 Quality Invariant

هذا التغيير لا يلغي:

- Phase Draft.
- Review.
- Testing.
- Quality Gates.
- Regression Protection.
- شرط أن `main` لا يستقبل Phase ناقصة أو غير مثبتة.

إنه يزيل Serial Bureaucracy فقط، ولا يزيل الأدلة أو المراجعة أو التحقق.

---

# 7. Standards Freeze أثناء Active Execution Train

عند بدء Phase أو Execution Train على Standards Snapshot مثبتة:

- تظل Snapshot هي الـBaseline طوال الـTrain.
- لا يفرض تحديث Upstream Standards Refresh فوريًا أو تلقائيًا.
- لا يحدث Refresh أثناء Phase نشطة إلا إذا طلبه المالك صراحة، أو وجد Security/Correctness Blocker مؤثر، أو كان التغيير الجديد مطلوبًا لإكمال Phase بشكل صحيح.
- تنتظر التحديثات غير الضرورية Boundary مناسبة بين Phases أو Trains.

---

# 8. اكتمال الـPhase والدمج إلى `main`

## 8.1 شروط اكتمال الـExecuted Phase ذات العمل الفعلي

لا تعتبر Phase مكتملة إلا بعد:

1. اكتمال كل Work Unit مطلوبة أو إثبات No-op لها.
2. اجتياز Component Gates والتصحيحات اللازمة.
3. اكتمال Documentation المرتبطة مباشرة أو إثبات عدم الحاجة إليها.
4. اجتياز Phase Integration Gate وRegression Protection المطلوبة.
5. مراجعة Phase Draft وFinal Review كـGate، سواء أنتجت تغييرًا أم سجلت evidence فقط.
6. عدم وجود Public Contract أو Architecture أو Scope غير معتمد.

تنطبق هذه البوابات على الـPhase التي تحتوي عملًا فعليًا. أما الـPhase المثبتة بالكامل كـ`Execution No-op`، فتغلق فقط وفق Evidence شروط §5، ولا تنشئ Draft أو Phase Integration Gate أو PR أو Merge.

## 8.2 الدمج النهائي

1. يمنع دمج أي Work Unit أو Verification أو Documentation أو Fix مباشرة إلى `main`.
2. بعد اكتمال Phase Draft وكل Gates، يكون الـDraft نفسه جاهزًا للدمج.
3. يظل مالك المشروع صاحب القرار النهائي في **GitHub Squash Merge للـPhase Draft إلى `main`**.
4. كل Commit ناتجة عن دمج Phase Draft في `main` تمثل Phase مكتملة وقابلة للفهم والمراجعة.

لا يوجد Phase-to-main Merge أو owner merge ceremony للـPhase المثبتة كـ`Execution No-op`، لأنها لا تنشئ Draft أو Commit أو PR أصلًا.

## 8.3 Git History المستهدف

يظل تاريخ `main` نظيفًا ومفاهيميًا:

```text
Phase A — complete
Phase B — complete
Phase C — complete
```

ولا يتحول إلى سجل تفصيلي لكل Work Unit أو Gate داخل Phase.

---

# 9. الصلاحيات والتوافق

- يظل مالك المشروع صاحب القرار النهائي في الهدف، والأولوية، والـArchitecture الجوهرية، وقرارات Public Contract الجوهرية، وتوسيع Scope المؤثر، ودمج Phase Draft إلى `main`، وTag، وRelease، وPublishing.
- بعد اعتماد Scope الـPhase من المالك، يملك المساعد القائد **Standing Execution Authority داخل الـPhase**، عندما تكون الأدوات والصلاحيات متاحة، لإدارة دورة التنفيذ دون الرجوع للمالك عند كل Micro-step، بما يشمل تقسيم Work Units، وتحديد Dependency Waves، واختيار المنفذين، وتشغيل الوحدات المستقلة بالتوازي، وفتح وإدارة PRs، ومراجعتها، وطلب Fixes، وإعادة Verification، واعتماد Component، و**GitHub Squash Merge للـComponent PR إلى Phase Draft**.
- لا تسمح Standing Execution Authority للمساعد القائد بتغيير Architecture أو Policy أو Public Contract جوهري، أو توسيع Scope مؤثر، أو الدمج إلى `main`، أو Tag/Release/Publish من نفسه.
- لا يحصل المنفذ تلقائيًا على Merge Authority لمجرد أن المساعد القائد يملك إدارة الـPhase. يظل Merge إلى `main` للمالك، ويظل تنفيذ المنفذ محصورًا في التكليف المحدد.
- تطبق صلاحيات Git التفصيلية وقواعد Amend وForce Push وStaging من `AI_COLLABORATION_WORKFLOW_AR.md` دون تعارض مع هذا المعيار.

---

# 10. سجل تغييرات المعيار

## `2.0.0`

- استبدال Strict Sequential Stack بنموذج Dependency-Aware Phase Train وExecution Waves.
- اعتماد Vertical Work Units، وتحويل Verification وFinal Review إلى Gates ما لم تنتجا تغييرًا مستقلًا.
- اعتماد Consolidated Required Fixes ومنع No-op Components وBaseline Reconciliation وPhase/Roadmap Compaction.
- اعتماد Component Gate وPhase Integration Gate مع الحفاظ على Full Required Verification قبل `main`.
- إضافة Standards Freeze أثناء Active Execution Train.
- تثبيت Standing Execution Authority للمساعد القائد داخل Phase بعد اعتماد Scope، مع إبقاء سلطة الدمج إلى `main` للمالك.

## `1.0.0`

- الإصدار الأول لنظام Phase Stack ودورة فروع الـPhase Draft والـComponents والدمج النهائي.
