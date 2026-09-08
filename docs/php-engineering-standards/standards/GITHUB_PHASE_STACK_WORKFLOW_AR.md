# معيار GitHub Phase Stack Workflow

## بيانات المعيار

- **الإصدار:** `1.0.0`
- **اللغة المعتمدة:** العربية.
- **حالة الاعتماد:** يصبح معتمدًا عند دمجه في الفرع الافتراضي للمشروع.
- **الهدف:** توحيد وتبسيط دورة حياة فروع Git وطلبات السحب (Pull Requests) لأي عمل (Library أو Module) وفق نظام يضمن عدم دمج أي مرحلة (Phase) غير مكتملة الأركان إلى `main`.

---

# 1. قاعدة الـ Phase Stack الأساسية

**كل مرحلة (Phase) يجب أن تُنفذ بنظام Phase Stack كامل، بحيث لا يدخل إلى `main` إلا مرحلة مكتملة الأركان كوحدة واحدة قابلة للفهم والمراجعة ككتلة هندسية واحدة.**

## 1.1 الهيكل النموذجي المطلوب

يجب أن يكون ترتيب فروع الـ Git كالتالي:

```text
main
└── phase-draft
    ├── blueprint / planning        (إذا كانت المرحلة تحتاجه)
    ├── work-unit-1
    ├── work-unit-2
    ├── ...
    ├── verification
    ├── documentation
    └── final-review / required-fixes
```

---

# 2. دورة حياة الـ Phase ومكوناتها (Components)

## 2.1 إنشاء الـ Phase Draft

1. كل Phase تبدأ من أحدث حالة فعلية ومعتمدة لـ `main`.
2. يتم إنشاء **Draft Branch واحد للـ Phase بالكامل** (مثل `phase-draft`).
3. يمثل الـ Draft Branch نقطة تجميع للمرحلة كاملة، ولا يُستخدم للتطوير أو التعديل المباشر عليه.

## 2.2 تنفيذ مكونات الـ Phase (Components)

1. **كل مكوّن أو جزء** يقوم بإحداث تغيير في المستودع ضمن المرحلة (سواء كان Blueprint، Work Unit، Verification، Documentation، Final Review، أو Required Fixes) يجب أن يكون له **Branch مستقل** منشأ دائمًا من **أحدث HEAD للـ Draft**.
2. الـ Pull Request (PR) الخاصة بكل مكوّن يجب أن يكون الـ Base الخاص بها هو الـ **Phase Draft** وليس `main`.
3. **يُمنع منعًا باتًا** إجراء أي تعديلات أو إضافة أي Commits (بما في ذلك Required Fixes) بشكل مباشر على الـ Phase Draft؛ لأن الـ Draft يُعتبر نقطة تجميع (Accumulation Point) فقط ولا يُستخدم للتطوير المباشر.
4. يظل كل مكوّن مفتوحًا في الـ PR الخاصة به من بداية تنفيذه وحتى:
   - اكتمال التنفيذ بالكامل بناءً على الـ acceptance criteria الخاصة به.
   - المراجعة.
   - إتمام كافة التصحيحات (Required Fixes) المطلوبة.
   - اجتياز جميع عمليات الـ Verification المطلوبة لذلك المكوّن.
5. **منع التوازي (Strict Sequential Stack):** يجب تنفيذ الـ Stack بشكل تسلسلي صارم. المكوّن $N$ يمر بكامل دورته (تنفيذ → مراجعة → تصحيحات → Verification → Squash Merge إلى الـ Phase Draft)، **وبعدها فقط** يُسمح بإنشاء المكوّن $N+1$ من أحدث Draft HEAD. لا يوجد أي استثناء لتشغيل مكونات (Work Units) على التوازي في هذا المعيار.

## 2.3 دمج الأجزاء (Merge)

1. عند اكتمال واعتماد المكوّن (Component)، يتم عمل **Squash Merge** له إلى الـ Phase Draft.
2. لا يدخل إلى الـ Phase Draft إلا مكوّن مكتمل بالكامل. إذا تعطلت الـ Session أثناء العمل على المكوّن (Component)، فلا يجوز دمج الجزء السليم منه منفردًا، ولا يُنشأ مكوّن جديد لاستكمال المتبقي. في حال الفشل التام للاستكمال في نفس الـ branch، تُغلق الـ PR الفاشلة وتبدأ محاولة بديلة لنفس المكوّن من أحدث Draft HEAD المعتمد.
3. بعد دخول المكوّن كاملًا إلى الـ Phase Draft، يجب إنشاء المكوّن (Component) التالي من أحدث HEAD للـ Draft، وهكذا بالترتيب حتى تكتمل جميع مكونات الـ Stack.

---

# 3. مكونات الـ Phase Stack

مكونات الـ Stack تختلف حسب طبيعة المرحلة، ولكن قد تشمل:

- **Blueprint / Planning:** (ليس إلزاميًا لكل Phase).
  - إذا كانت المرحلة تحتاج Blueprint، يكون جزءًا من نفس الـ Stack.
  - إذا لم تكن تحتاجه، تبدأ المرحلة من أول Work Unit مناسب.
  - **ممنوع** إنشاء Blueprint شكلي لمجرد استكمال التسلسل.
- **Work Units (وحدات العمل الفعلية).**
- **Runtime implementation.**
- **Tests.**
- **Verification / Static Analysis / Quality Gates.**
- **Documentation (و/أو Documentation sweep).**
- **Final review.**
- **Required fixes (الناتجة من المراجعة).**

---

# 4. اكتمال ودمج الـ Phase

## 4.1 شروط اكتمال الـ Phase

1. **الـ Runtime implementation وحده لا يعني اكتمال الـ Phase.**
2. لا تعتبر الـ Phase مكتملة إلا بعد إتمام جميع العناصر المطلوبة لها من البداية للنهاية، بما في ذلك التوثيق (Documentation)، والتحقق (Verification)، والمراجعة النهائية (Final Review) عند انطباقها.
3. **ممنوع** اعتبار الـ Phase مكتملة أو جاهزة للدمج النهائي إذا لم تتوفر فيها حماية الانحدار (Regression Protection) عبر اختبارات النظام / E2E، وفقاً لما ينص عليه [معيار الاختبارات (Testing Standard)](testing/TESTING_STANDARD.md).

## 4.2 الدمج النهائي إلى `main`

1. **ممنوع دمج أي جزء من أجزاء الـ Phase مباشرة إلى `main`**، بما في ذلك (Work Units, Verification, Documentation, Fixes, Final review changes).
2. بعد اكتمال جميع أجزاء الـ Phase واجتياز الـ required gates، يصبح الـ **Phase Draft** نفسه جاهزًا للدمج.
3. يتم عمل **Squash Merge للـ Phase Draft إلى `main`**.
4. كل Commit ناتجة عن دمج Phase Draft في `main` يجب أن تمثل Phase مكتملة وقابلة للفهم والمراجعة كوحدة هندسية واحدة.

## 4.3 Git History المستهدف

الهدف النهائي هو أن يكون تاريخ الـ Git (Git History) على `main` نظيفًا ومفاهيميًا بهذا الشكل:

```text
Phase A — complete
Phase B — complete
Phase C — complete
```

**وليس:**

```text
Blueprint
WU1
Fix WU1
WU2
Tests
Docs
Fix docs
...
```

5. بعد دمج الـ Phase إلى `main`، يبدأ أي Phase لاحق من **أحدث `main` الناتج**، ولا يعتمد أبدًا على Draft قديم أو Branch جانبية سابقة.

---

# 5. التوافق والصلاحيات

- **Merge Authority:** يظل مالك المشروع هو صاحب قرار الدمج النهائي واعتماد أي Phase Draft للدمج في `main` وفق ما هو مقرر في معايير المشروع.
- **تصحيحات المسار (Fixes):**
  - أي تصحيحات (Required Fixes) على مكوّن قيد المراجعة يجب أن تكون Commits جديدة مستقلة داخل نفس الـ PR الخاصة به، ولا يُستخدم الـ Force Push أو `git commit --amend` وفق قواعد Git السارية (مثال: `AI_COLLABORATION_WORKFLOW_AR.md`).
  - **ممنوع قطعيًا** إضافة Fix Commits بشكل مباشر على الـ Phase Draft لغرض معالجة ملاحظات (Final Review) أو غيره. أي تصحيح مطلوب للـ Phase Draft نفسه يجب أن يُنفذ كمكوّن جديد (Required Fixes Component) له Branch مستقل منشأ من أحدث Draft HEAD، ويُفتح له PR إلى الـ Draft، ثم يتم عمل Squash Merge له كأي مكوّن آخر.
