# استراتيجية تنفيذ نظام الدبلومات (الأقسام) والامتحان النهائي والشهادات

## ملخص تنفيذي
هذه الاستراتيجية تحول الأقسام إلى دبلومات قابلة للعرض والشراء، تضيف تسجيل على مستوى القسم، امتحان نهائي شامل ببنك أسئلة مستقل، وشهادة موثّقة للدبلومة مع QR للتحقق. التنفيذ يتم على مراحل قصيرة قابلة للنشر تدريجيًا دون كسر المنظومة الحالية.

## قرارات موجِّهة
- يجب إنهاء جميع كورسات الدبلومة قبل بدء الامتحان النهائي.
- النجاح 50%، لا يوجد وقت محدد للامتحان، عدد الأسئلة المسحوبة 20.
- الشهادة تصدر على مستوى الدبلومة/القسم فقط.
- اعتماد خيار A لإعادة استخدام جداول `quizzes` و`certificates` مع توسعات مدروسة.

## المرحلة 1: الأقسام كدبلومات + واجهات عامة
المخرجات:
- توسيع جدول `category_of_course` بحقوق: `description`, `price`, `is_free`, `cover_image`, `slug`, `is_published`, `display_order`.
- تحديث موديل `CategoryOfCourse` بإضافات accessor للـ`cover_image_url` وعلاقات العدّادات.
- بناء موارد عامة: `CategoryResource` و`CategoryDetailsResource`.
- واجهات عامة:
  - `GET /api/categories` (ترقيم/بحث/فرز + عدّادات)
  - `GET /api/categories/{slug}` (تفاصيل + كورسات القسم)
- توافق أمامي: الحرص على `with('category')` في ردود الكورسات.
قبول:
- تعرض الصفحة العامة بطاقات دبلومات مع صورة/سعر/وصف وعدّاد الكورسات.
- صفحة التفاصيل تُظهر قائمة الكورسات المرتبطة مع بيانات القسم.

## المرحلة 2: التسجيل على مستوى الدبلومة
المخرجات:
- إنشاء جدول `user_category_enrollments` وموديل `UserCategoryEnrollment`.
- نقطة تسجيل: `POST /api/categories/{category}/enroll` (مجاني الآن، قابل للربط بالدفع لاحقًا).
- Job لإلحاق كل كورس جديد بالقسم إلى المسجّلين (إنشاء `UserCourse`).
- قائمة دبلومات المستخدم: `GET /api/my-diplomas`.
قبول:
- عند التسجيل يُنشأ سجل دبلومة للمستخدم ويُضاف وصول الكورسات المرتبطة.
- تظهر الدبلومات في لوحة المستخدم مع حالة التقدم.

## المرحلة 3: الامتحان النهائي للدبلومة
المخرجات:
- توسيع `quizzes`: إضافة `category_id` و`is_final=true` للدبلومة.
- واجهات الإدارة لبنك الأسئلة:
  - `POST /api/admin/categories/{category}/final-exam` (إنشاء/تحديث كويز)
  - `POST /api/admin/final-exams/{quiz}/questions` (CRUD أسئلة)
- واجهات الطالب:
  - `POST /api/categories/{category}/final-exam/start` (شرط إنهاء كل الكورسات)
  - `POST /api/categories/{category}/final-exam/submit`
- منطق الامتحان: سحب 20 سؤالًا عشوائيًا، لا وقت محدد، النجاح 50%.
قبول:
- لا يمكن بدء الامتحان إلا بعد إكمال جميع الكورسات.
- تُسحب 20 سؤالًا ويُحسب النجاح بنسبة 50%.

## المرحلة 4: شهادة الدبلومة والتحقق بالـQR
المخرجات:
- جعل الشهادات polymorphic: `certifiable_type`=`category`, `certifiable_id`، إضافة `uuid`, `file_path`, `qr_path`.
- توليد PDF الشهادة وتخزينه تحت: `public/certificates/diplomas/{category_slug}/{user_id}_{uuid}.pdf`.
- توليد QR يحتوي `verification_url`.
- نقطة تحقق عامة: `GET /api/certificates/verify/{uuid}`.
قبول:
- تُنشأ شهادة عند نجاح الامتحان النهائي وتظهر في صفحة المستخدم.
- يمكن التحقق من الشهادة عبر الرابط أو مسح الـQR.

## المرحلة 5: الدفع (تهيئة)
المخرجات:
- جداول `orders`, `order_items`, `payments` دون تكامل مزوّد حالياً.
- ربط أمر الدفع بتفعيل `UserCategoryEnrollment`.
قبول:
- تسجيل مجاني يعمل الآن؛ الدفع قابل للإدراج لاحقًا دون كسر الواجهات.

## الاختبارات لكل مرحلة
- وحدات + تكامل: إنشاء/عرض الدبلومات، التسجيل وإنشاء `UserCourse`, بدء/تسليم الامتحان، إصدار الشهادة والتحقق.
- بيانات طرفية: حذف قسم يضبط `category_id=null` للكورسات، إضافة كورس جديد يلتحق تلقائيًا للمسجّلين.

## المخاطر والتخفيف
- تضخم سجلات `UserCourse`: استخدام Jobs ودُفعات، مراقبة الأداء.
- اتساق العدّادات: تحديث دوري أو حساب عند الطلب.
- توافق الواجهات: الحفاظ على سلوك `CourseResource` مع `with('category')`.

## خطة الإطلاق والتراجع
- نشر مرحلي لكل مرحلة مع فحوصات قبول.
- تراجع: إمكانية تعطيل نقاط الدبلومات دون التأثير على الكورسات القائمة.

# استراتية تنفيذ نظام الدبلومات

## تحديثات المرحلة 2 (التسجيل على مستوى الدبلومة)
- حالات التسجيل المعتمدة: `active` (مجاني/مفعّل) و`pending_payment` (مدفوع ينتظر التفعيل).
- نقطة التفعيل بعد الدفع: `POST /api/categories/{category}/enroll/activate` (محمي بـ `auth:sanctum`).
- رد `GET /api/my-diplomas` الآن يُضمّن الحقول: `id, status, enrolled_at, category` حيث `category` تُستخدم عبر `CategoryResource` مع `courses_count`.

### تدفق التسجيل (Enroll)
1. التحقق من أن الدبلوم (القسم) منشور `is_published=true` ويقبل الوصول.
2. تحديد إن كان الدبلوم مدفوعًا (`!is_free && price>0`) أم مجانيًا.
3. إنشاء `UserCategoryEnrollment` بالحالة:
   - مجاني: `active` ثم ربط كل الكورسات تحت الدبلومة للمستخدم (إنشاء `UserCourse`).
   - مدفوع: `pending_payment` بدون ربط كورسات حتى التفعيل.
4. إرجاع عدد الكورسات التي تم ربطها عند التسجيل المجاني.

### تدفق التفعيل (Activate)
1. التحقق من وجود دبلوم منشور ومعرّف بـ `slug` أو `id`.
2. جلب تسجيل المستخدم `UserCategoryEnrollment` لنفس الدبلوم.
3. رفض التفعيل إذا الحالة `active` أو ليست `pending_payment`.
4. تحديث الحالة إلى `active` ثم ربط كل كورسات الدبلوم للمستخدم عبر `UserCourse::firstOrCreate`؛ مع زيادة `students_count` لكل كورس جديد يُربط.
5. ردّ يحتوي `enrollment` و`enrolled_courses_count`.

### الجوب + المراقب (Auto-attach for new courses)
- `AttachCourseToActiveDiplomaEnrollmentsJob`: يقوم بإلحاق أي كورس منشور جديد إلى كل المسجّلين في الدبلومة بالحالة `active` فقط، بشكل آمن (idempotent) عبر `firstOrCreate`.
- `CourseObserver`:
  - عند الإنشاء: إذا كان `is_published=true` وله `category_id`، يتم جدولة الـ Job.
  - عند التحديث: إذا تغيّر `is_published` إلى true أو تغيّر `category_id`، تُجدول الـ Job.
- التسجيل في `AppServiceProvider::boot`: `Course::observe(CourseObserver::class);`

### تسجيل المسارات (Routes)
- تحت مجموعة `auth:sanctum`:
  - `POST /api/categories/{category}/enroll`
  - `POST /api/categories/{category}/enroll/activate`
  - `GET /api/my-diplomas`

### توافق أمامي (Frontend Contract)
- عند عرض دبلومات المستخدم، يعتمد الـ Frontend على:
  - `status`: يحدد ما إذا كان الوصول للكورسات مفعّلًا.
  - `enrolled_at`: تاريخ التسجيل.
  - `category`: تفاصيل الدبلوم عبر `CategoryResource` (يشمل `courses_count`).
- عند إتمام الدفع، يستدعي الـ Frontend `POST /api/categories/{category}/enroll/activate` لتفعيل الوصول وربط الكورسات.