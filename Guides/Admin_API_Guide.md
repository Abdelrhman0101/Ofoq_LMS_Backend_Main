# دليل API الإدارة - خطة تنفيذية شاملة

## نظرة عامة
هذا الدليل يوضح جميع endpoints الخاصة بلوحة الإدارة مرتبة حسب السيناريو التنفيذي لإنشاء وإدارة المحتوى التعليمي.

---

## 🎯 السيناريو الأول: إعداد النظام الأساسي

### 1. إدارة الأقسام (Categories)
**الهدف:** إنشاء تصنيفات للكورسات

#### Endpoints:
- **GET** `/admin/categories` - عرض جميع الأقسام
- **POST** `/admin/categories` - إنشاء قسم جديد
- **DELETE** `/admin/categories/{id}` - حذف قسم

#### مثال على إنشاء قسم:
```json
POST /admin/categories
{
    "name": "البرمجة"
}
```

### 2. إدارة المدربين (Instructors)
**الهدف:** إضافة المدربين الذين سيقومون بتدريس الكورسات

#### Endpoints:
- **GET** `/admin/instructors` - عرض جميع المدربين (مع البحث والترقيم)
- **GET** `/admin/instructors/{instructor}` - عرض مدرب محدد
- **POST** `/admin/instructors` - إنشاء مدرب جديد
- **PUT** `/admin/instructors/{instructor}` - تحديث بيانات مدرب
- **DELETE** `/admin/instructors/{instructor}` - حذف مدرب

#### مثال على إنشاء مدرب:
```json
POST /admin/instructors
{
    "name": "أحمد محمد",
    "title": "مطور Full Stack",
    "bio": "خبرة 10 سنوات في تطوير الويب",
    "image": "file_upload"
}
```

---

## 🎯 السيناريو الثاني: إنشاء المحتوى التعليمي

### 3. إنشاء الكورس (Course Creation)
**الهدف:** إنشاء الكورس الأساسي

#### Endpoints:
- **GET** `/admin/courses` - عرض جميع الكورسات
- **GET** `/admin/courses/{id}` - عرض كورس محدد
- **POST** `/admin/courses` - إنشاء كورس جديد
- **PUT** `/admin/courses/{course}` - تحديث كورس
- **DELETE** `/admin/courses/{course}` - حذف كورس

#### Endpoints إضافية:
- **GET** `/admin/courses/details` - إحصائيات الكورسات
- **GET** `/admin/courses/unpublished` - الكورسات غير المنشورة

#### مثال على إنشاء كورس:
```json
POST /admin/courses
{
    "title": "تعلم Laravel من الصفر",
    "description": "كورس شامل لتعلم Laravel",
    "instructor_id": 1,
    "category_id": 1,
    "price": 299.99,
    "discount_price": 199.99,
    "is_free": false,
    "is_published": false,
    "cover_image": "file_upload"
}
```

### 4. إنشاء الوحدات (Chapters)
**الهدف:** تقسيم الكورس إلى وحدات منطقية

#### Endpoints:
- **GET** `/admin/courses/{course}/chapters` - عرض وحدات الكورس
- **GET** `/admin/chapters/{chapter}` - عرض وحدة محددة
- **POST** `/admin/courses/{course}/chapters` - إنشاء وحدة جديدة
- **PUT** `/admin/chapters/{chapter}` - تحديث وحدة
- **DELETE** `/admin/chapters/{chapter}` - حذف وحدة

#### مثال على إنشاء وحدة:
```json
POST /admin/courses/{course_id}/chapters
{
    "title": "مقدمة في Laravel",
    "description": "التعرف على إطار العمل Laravel"
}
```

### 5. إنشاء الدروس (Lessons)
**الهدف:** إضافة الدروس داخل كل وحدة

#### Endpoints:
- **GET** `/admin/chapters/{chapter}/lessons` - عرض دروس الوحدة
- **GET** `/admin/lessons/{lesson}` - عرض درس محدد
- **POST** `/admin/chapters/{chapter}/lessons` - إنشاء درس جديد
- **PUT** `/admin/lessons/{lesson}` - تحديث درس
- **DELETE** `/admin/lessons/{lesson}` - حذف درس

#### مثال على إنشاء درس:
```json
POST /admin/chapters/{chapter_id}/lessons
{
    "title": "تثبيت Laravel",
    "content": "شرح خطوات تثبيت Laravel"
}
```

---

## 🎯 السيناريو الثالث: إضافة التقييمات والاختبارات

### 6. إنشاء الاختبارات (Quizzes)
**الهدف:** إضافة اختبارات لتقييم الطلاب

#### Endpoints:
- **GET** `/admin/courses/{course}/quizzes` - عرض اختبارات الكورس
- **GET** `/admin/quizzes/{quiz}` - عرض اختبار محدد
- **POST** `/admin/courses/{course}/quizzes` - إنشاء اختبار جديد
- **PUT** `/admin/quizzes/{quiz}` - تحديث اختبار
- **DELETE** `/admin/quizzes/{quiz}` - حذف اختبار

#### مثال على إنشاء اختبار:
```json
POST /admin/courses/{course_id}/quizzes
{
    "title": "اختبار Laravel الأساسي",
    "description": "اختبار لقياس فهم أساسيات Laravel"
}
```

### 7. إنشاء الأسئلة (Questions)
**الهدف:** إضافة أسئلة للاختبارات

#### Endpoints:
- **GET** `/admin/quizzes/{quiz}/questions` - عرض أسئلة الاختبار
- **GET** `/admin/questions/{question}` - عرض سؤال محدد
- **POST** `/admin/quizzes/{quiz}/questions` - إنشاء سؤال جديد
- **PUT** `/admin/questions/{question}` - تحديث سؤال
- **DELETE** `/admin/questions/{question}` - حذف سؤال

#### مثال على إنشاء سؤال:
```json
POST /admin/quizzes/{quiz_id}/questions
{
    "question": "ما هو Laravel؟",
    "options": [
        "إطار عمل PHP",
        "لغة برمجة",
        "قاعدة بيانات",
        "خادم ويب"
    ],
    "correct_answer": "إطار عمل PHP"
}
```

---

## 🎯 السيناريو الرابع: إدارة المحتوى المميز

### 8. إدارة الكورسات المميزة (Featured Courses)
**الهدف:** تمييز كورسات معينة لعرضها في الصفحة الرئيسية

#### Endpoints:
- **POST** `/admin/featured-courses` - إضافة كورس للمميزة
- **DELETE** `/admin/featured-courses/{featuredCourse}` - إزالة كورس من المميزة

#### مثال على تمييز كورس:
```json
POST /admin/featured-courses
{
    "course_id": 1,
    "priority": 1,
    "is_active": true
}
```

---

## 📋 خطة التنفيذ المرحلية

### المرحلة الأولى: الإعداد الأساسي
1. ✅ إنشاء الأقسام الرئيسية
2. ✅ إضافة المدربين
3. ✅ التحقق من صحة البيانات

### المرحلة الثانية: إنشاء المحتوى
1. ✅ إنشاء الكورس الأساسي
2. ✅ تقسيم الكورس إلى وحدات
3. ✅ إضافة الدروس لكل وحدة
4. ✅ رفع الملفات والصور

### المرحلة الثالثة: التقييم
1. ✅ إنشاء الاختبارات
2. ✅ إضافة الأسئلة
3. ✅ تحديد الإجابات الصحيحة

### المرحلة الرابعة: النشر والتسويق
1. ✅ مراجعة المحتوى
2. ✅ نشر الكورس
3. ✅ إضافة للكورسات المميزة

---

## 🔧 ملاحظات تقنية مهمة

### Authentication & Authorization
- جميع endpoints تتطلب مصادقة Admin
- يتم استخدام middleware للتحقق من الصلاحيات
- Resource authorization مطبق على معظم Controllers

### File Uploads
- صور الكورسات: `courses/cover_images/`
- صور المدربين: `instructors/`
- الحد الأقصى لحجم الصورة: 2MB

### Validation Rules
- أسماء الأقسام: فريدة ومطلوبة
- بيانات المدربين: اسم وتخصص مطلوبان
- الكورسات المجانية: السعر يصبح 0 تلقائياً

### Response Format
جميع الاستجابات تتبع النمط التالي:
```json
{
    "success": true,
    "message": "رسالة النجاح",
    "data": {},
    "pagination": {} // للقوائم المرقمة
}
```

---

## 🚀 نصائح للتنفيذ

1. **ابدأ بالأساسيات**: أنشئ الأقسام والمدربين أولاً
2. **خطط المحتوى**: حدد هيكل الكورس قبل البدء
3. **اختبر تدريجياً**: تأكد من كل خطوة قبل الانتقال للتالية
4. **احفظ النسخ الاحتياطية**: خاصة عند رفع الملفات
5. **راجع الصلاحيات**: تأكد من وجود الصلاحيات المناسبة

---

## 📞 الدعم والمساعدة

في حالة مواجهة أي مشاكل:
1. تحقق من رسائل الخطأ في Response
2. راجع validation rules
3. تأكد من صحة البيانات المرسلة
4. تحقق من الصلاحيات والمصادقة

---

*تم إنشاء هذا الدليل لتسهيل عملية إدارة المحتوى التعليمي بطريقة منظمة وفعالة.*