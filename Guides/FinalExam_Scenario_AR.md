# مرجع الواجهة الأمامية: سيناريو الامتحان النهائي (كورس 1)

هذا المستند يشرح بالتفصيل جميع النقاط التي نُفذت في السيناريو، مع تحديد الـ Endpoints، وهيكل الطلب (Body)، والاستجابات (Response) المتوقعة، ليكون مرجعًا جاهزًا للعمل على الواجهة الأمامية.

## الأساسيات

- قاعدة العنوان (Base URL): `http://127.0.0.1:9000`
- جميع الطلبات المحمية تتطلب ترويسة (Header): `Authorization: Bearer <TOKEN>`
- الترويسات العامة:
  - `Accept: application/json`
  - `Content-Type: application/json` (عند وجود Body)

## 1) المصادقة (تسجيل الدخول)

- Endpoint: `POST /api/login`
- Body:
```json
{
  "login": "admin@ofuq.academy",
  "password": "admin123"
}
```
- Response (مثال):
```json
{
  "user": { "id": 1, "email": "admin@ofuq.academy", "role": "admin" },
  "token": "<PLAINTEXT_TOKEN>"
}
```

مثال للطالب:
```json
{
  "login": "student@ofuq.academy",
  "password": "student123"
}
```

## 2) الاشتراك في الكورس

- Endpoint: `POST /api/courses/1/enroll`
- Headers: `Authorization: Bearer <STUDENT_TOKEN>`
- Body: لا يوجد
- Response (مثال):
```json
{
  "success": true,
  "data": {
    "course_id": 1,
    "status": "in_progress"
  }
}
```

## 3) إدارة الدروس (عرض/إكمال)

عرض تفاصيل الدرس:
- Endpoint: `GET /api/lessons/{lesson_id}`
- Headers: `Authorization: Bearer <STUDENT_TOKEN>`
- Response (مثال لدرس 1):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "What is Media?",
    "content": "...",
    "video_url": "...",
    "is_visible": true
  }
}
```

وضع الدرس كمكتمل:
- Endpoint: `POST /api/lessons/{lesson_id}/complete`
- Headers: `Authorization: Bearer <STUDENT_TOKEN>`
- Body: لا يوجد
- Response (مثال):
```json
{
  "success": true,
  "message": "Lesson completed",
  "data": {
    "lesson_id": 1,
    "status": "completed"
  }
}
```

## 4) إدارة بنك أسئلة الامتحان النهائي (أدمن)

### اختيار مقرر لإضافة أسئلة (قائمة المقررات للأدمن)

قبل إضافة الأسئلة، تحتاج لاختيار المقرر الذي ستضيف له أسئلة الامتحان النهائي.

- Endpoint: `GET /api/admin/courses`
- Headers: `Authorization: Bearer <ADMIN_TOKEN>`
- Query params الشائعة: `search`, `page`, `per_page`
- Response (شكل نموذجي):
```json
{
  "data": [
    {
      "id": 1,
      "title": "Media Fundamentals 101",
      "cover_image_url": "https://...",
      "is_published": true,
      "status": "published",
      "chapters_count": 3,
      "lessons_count": 12,
      "reviews_count": 5,
      "instructor": { "id": 1, "name": "John Doe" },
      "category": { "id": 3, "name": "Media" }
    }
  ],
  "pagination": { "current_page": 1, "per_page": 10, "total": 20, "last_page": 2 }
}
```

عرض تفاصيل مقرر محدد (قد تحتاجه لعرض محتوى المقرر في نفس شاشة الإدارة):
- Endpoint: `GET /api/admin/courses/{id}`
- Headers: `Authorization: Bearer <ADMIN_TOKEN>`
- Response (ملخّص):
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "Media Fundamentals 101",
    "chapters": [ /* ChapterResource */ ],
    "reviews": [ /* ReviewResource */ ],
    "instructor": { /* InstructorResource */ },
    "category": { "id": 3, "name": "Media" },
    "is_published": true,
    "status": "published"
  }
}
```

مهم: `CourseResource` لا يُظهر حقل `finalExam` أو `quiz_id` مباشرة. للحصول على معرف كويز الامتحان النهائي للمقرر، استخدم:
- Endpoint: `GET /api/courses/{course}/final-exam/meta` (متاح لأي مستخدم مصادق، بما فيهم الأدمن)
- Headers: `Authorization: Bearer <ADMIN_TOKEN>`
- Response (مثال):
```json
{
  "success": true,
  "data": {
    "quiz_id": 1,
    "questions_pool_count": 5,
    "has_sufficient_question_bank": true,
    "attempts_total": 0,
    "attempts_today": 0,
    "eligible_to_start": true,
    "next_lesson_id": null,
    "last_attempt_at": null,
    "next_allowed_at": null,
    "retake_cooldown_seconds": 900,
    "max_attempts_per_day": 2,
    "max_attempts_total": 5,
    "is_allowed_now": true
  }
}
```

بعد الحصول على `quiz_id`، أكمل بالخطوات أدناه لإضافة الأسئلة لبنك الامتحان النهائي.

الحصول على معرف كويز الامتحان النهائي من الميتا (طالب):
- Endpoint: `GET /api/courses/1/final-exam/meta`
- Headers: `Authorization: Bearer <STUDENT_TOKEN>`
- Response (مثال):
```json
{
  "success": true,
  "data": {
    "quiz_id": 1,
    "questions_pool_count": 5,
    "has_sufficient_question_bank": true,
    "attempts_total": 1,
    "attempts_today": 1,
    "eligible_to_start": true,
    "next_lesson_id": null,
    "last_attempt_at": "2025-11-08T10:20:31Z",
    "next_allowed_at": "2025-11-08T10:35:31Z",
    "retake_cooldown_seconds": 900,
    "max_attempts_per_day": 2,
    "max_attempts_total": 5,
    "is_allowed_now": false
  }
}
```

إضافة سؤال جديد للامتحان النهائي (أدمن):
- Endpoint: `POST /api/admin/quizzes/{quiz_id}/questions`
- Headers: `Authorization: Bearer <ADMIN_TOKEN>`
- Body (مثال):
```json
{
  "question": "Which is a form of digital media?",
  "options": ["Newspaper", "Podcast", "Billboard", "Postcard"],
  "correct_answer": "1"
}
```
- Response (مثال):
```json
{
  "id": 2,
  "question": "Which is a form of digital media?",
  "options": ["Newspaper", "Podcast", "Billboard", "Postcard"],
  "correct_answer": 1
}
```

عرض قائمة أسئلة الكويز (أدمن):
- Endpoint: `GET /api/admin/quizzes/{quiz_id}/questions`
- Headers: `Authorization: Bearer <ADMIN_TOKEN>`
- Response (مثال مختصر):
```json
{
  "data": [
    { "id": 1, "question": "What is media?", "correct_answer": 0 },
    { "id": 2, "question": "Which is a form of digital media?", "correct_answer": 1 },
    { "id": 3, "question": "Primary goal of media ethics?", "correct_answer": 1 },
    { "id": 4, "question": "Which term refers to audience analysis?", "correct_answer": 1 },
    { "id": 5, "question": "Video bitrate measures?", "correct_answer": 1 }
  ]
}
```

## 5) بيانات الميتا للامتحان النهائي (طالب)

- Endpoint: `GET /api/courses/1/final-exam/meta`
- Headers: `Authorization: Bearer <STUDENT_TOKEN>`
- مخرجات مهمة للاستخدام في الواجهة الأمامية:
  - `quiz_id`: معرف كويز الامتحان النهائي للكورس.
  - `questions_pool_count`: حجم بنك الأسئلة.
  - `has_sufficient_question_bank`: جاهزية البنك لبدء الامتحان.
  - `attempts_total` و`attempts_today`: عدد المحاولات السابقة (إجمالي واليوم).
  - `eligible_to_start`: صلاحية البدء (يتأثر بإكمال الكورس أو صلاحيات الأدمن).
  - `next_lesson_id`: إن لم يكن مؤهلاً للبدء، هذا يحدد الدرس التالي المطلوب إكماله.
  - `last_attempt_at` و`next_allowed_at`: وقت آخر محاولة ووقت السماح التالي إذا كانت هناك تهدئة/حد يومي.
  - `retake_cooldown_seconds` و`max_attempts_per_day` و`max_attempts_total`: قيم سياسة السيرفر.
  - `is_allowed_now`: جاهزية البدء فعليًا وفق السياسة + شرط الإكمال.

## 6) بدء الامتحان النهائي (طالب)

- Endpoint: `POST /api/courses/1/final-exam/start`
- Headers: `Authorization: Bearer <STUDENT_TOKEN>`
- Body: لا يوجد
- Response (نجاح):
```json
{
  "success": true,
  "data": {
    "attempt_id": 10,
    "questions": [
      {
        "id": 2,
        "question": "Which is a form of digital media?",
        "options": ["Newspaper", "Podcast", "Billboard", "Postcard"]
      },
      { "id": 3, "question": "Primary goal of media ethics?", "options": ["Maximize profit","Ensure truthful communication","Enforce censorship","Ignore privacy"] },
      { "id": 4, "question": "Which term refers to audience analysis?", "options": ["SEO","Demographics","Syntax","Metaverse"] },
      { "id": 5, "question": "Video bitrate measures?", "options": ["Frame width","Bits per second","Audio channels","Aspect ratio"] },
      { "id": 1, "question": "What is media?", "options": ["Channels of communication","Financial markets","Biological cells","Legal documents"] }
    ]
  }
}
```

- Response (غير مؤهل للبدء):
```json
{
  "success": false,
  "message": "Course not completed",
  "next_lesson_id": 2
}
```

- Response (429 — تهدئة/حدود محاولات):
```json
{
  "code": "EXAM_RETRY_COOLDOWN",
  "message": "لا يمكنك البدء الآن بسبب فترة التهدئة. يرجى المحاولة لاحقًا.",
  "next_allowed_at": "2025-11-08T10:35:31Z",
  "cooldown_seconds": 900,
  "remaining_attempts_today": 1,
  "remaining_attempts_total": 2
}
```
أكواد محتملة: `EXAM_RETRY_COOLDOWN`, `EXAM_MAX_ATTEMPTS_PER_DAY`, `EXAM_MAX_ATTEMPTS_TOTAL`.

## 6.1) استرجاع المحاولة النشطة (طالب)

- Endpoint: `GET /api/courses/{course_id}/final-exam/attempt/active`
- Headers: `Authorization: Bearer <STUDENT_TOKEN>`
- Response (200):
```json
{
  "success": true,
  "data": {
    "attempt_id": 12,
    "quiz_id": 1,
    "questions": [
      { "id": 2, "question": "...", "options": ["..."] },
      { "id": 5, "question": "...", "options": ["..."] }
    ]
  }
}
```
- Response (404):
```json
{ "success": false, "message": "No active attempt found." }
```

ملاحظة: عند وجود محاولة `in_progress`، الواجهة تعيد استخدامها بدل إنشاء محاولة جديدة، ما يحل مشاكل الضغط المزدوج/التحديث.

## 6.2) إلغاء المحاولة (اختياري)

- Endpoint: `POST /api/courses/{course_id}/final-exam/cancel/{attempt_id}`
- Headers: `Authorization: Bearer <STUDENT_TOKEN>`
- Response (200):
```json
{
  "success": true,
  "data": { "attempt_id": 12, "quiz_id": 1, "status": "canceled" }
}
```
- Response (409):
```json
{ "success": false, "message": "Attempt is not active and cannot be canceled." }
```

## ملاحظة حول كفاية بنك الأسئلة

في حال عدم كفاية الأسئلة للامتحان النهائي، يُعاد كود الحالة `422` بالشكل:
```json
{
  "code": "question_bank_insufficient",
  "message": "بنك الأسئلة غير كافٍ للاختبار النهائي لهذا المقرر."
}
```

## 7) إرسال إجابات الامتحان النهائي (طالب)

- Endpoint: `POST /api/courses/1/final-exam/submit/{attempt_id}`
- Headers: `Authorization: Bearer <STUDENT_TOKEN>`
- Body (مثال):
```json
{
  "answers": [
    { "question_id": 2, "selected_indices": [1] },
    { "question_id": 3, "selected_indices": [1] },
    { "question_id": 4, "selected_indices": [1] },
    { "question_id": 5, "selected_indices": [1] },
    { "question_id": 1, "selected_indices": [0] }
  ],
  "time_taken": 42
}
```
- Response (مثال حقيقي):
```json
{
  "score": 100,
  "passed": true,
  "correct_answers": 5,
  "total_questions": 5,
  "passing_score": 50
}
```

## 8) تقدم الكورس (طالب)

- Endpoint: `GET /api/courses/1/progress`
- Headers: `Authorization: Bearer <STUDENT_TOKEN>`
- Response (مثال حقيقي):
```json
{
  "course_progress": {
    "overall_progress": 100,
    "status": "completed",
    "completed_at": "2025-11-08 10:15:27",
    "lessons": [
      {
        "lesson_id": 1,
        "lesson_title": "What is Media?",
        "status": "completed",
        "quiz_passed": false,
        "started_at": "2025-11-08T10:11:05.000000Z",
        "completed_at": "2025-11-08T10:11:05.000000Z"
      },
      {
        "lesson_id": 2,
        "lesson_title": "Media History",
        "status": "completed",
        "quiz_passed": false,
        "started_at": "2025-11-08T10:11:21.000000Z",
        "completed_at": "2025-11-08T10:11:21.000000Z"
      },
      {
        "lesson_id": 3,
        "lesson_title": "Media Ethics",
        "status": "completed",
        "quiz_passed": false,
        "started_at": "2025-11-08T10:11:22.000000Z",
        "completed_at": "2025-11-08T10:11:22.000000Z"
      }
    ]
  }
}
```

## 9) محاولاتي في الامتحانات (طالب)

- Endpoint: `GET /api/my-tests`
- Headers: `Authorization: Bearer <STUDENT_TOKEN>`
- Response (مثال حقيقي):
```json
{
  "success": true,
  "data": [
    {
      "course_id": 1,
      "course_title": "Media Fundamentals 101",
      "status": "completed",
      "progress_percentage": 100,
      "eligible_to_start": true,
      "attempts_count": 1,
      "last_score": "100.00",
      "exam_status": "passed"
    }
  ]
}
```

## 10) الشهادات

شهادة الكورس (طالب):
- Endpoint: `GET /api/courses/1/certificate`
- Headers: `Authorization: Bearer <STUDENT_TOKEN>`
- Response (مثال حاليًا):
```json
{ "success": false }
```

قائمة شهاداتي (طالب):
- Endpoint: `GET /api/my-certificates`
- Headers: `Authorization: Bearer <STUDENT_TOKEN>`
- Response (مثال):
```json
{
  "success": true,
  "data": []
}
```

ملاحظة: بالرغم من أن حالة الكورس `completed` والامتحان النهائي `passed`، لا يتم إصدار شهادة تلقائيًا في السيناريو الحالي. قد يتطلب الإصدار تنفيذًا إضافيًا عبر `CertificateController` أو منطقًا في `FinalExamController@submit`.

## ملاحظات عامة للواجهة الأمامية

- راقب `eligible_to_start` و`next_lesson_id` في ميتا الامتحان لإظهار توجيه مناسب قبل البدء.
- استخدم `quiz_id` من الميتا لعرض/إدارة بنك الأسئلة (للأدمن فقط).
- عند بدء الامتحان، اعتمد على قائمة `questions` المرتجعة، واحفظ `attempt_id` لإرسال الإجابات لاحقًا.
- في إرسال الإجابات، استخدم `selected_indices` كمؤشرات للإجابات ضمن مصفوفة `options` لكل سؤال.
- اعرض نتيجة الامتحان من رد `submit` مباشرة (`score`, `passed`).
- اعرض حالة الكورس من `GET /api/courses/{id}/progress` لإظهار الشريط العام ونقاط إتمام الدروس.
- اعرض ملخص المحاولات من `GET /api/my-tests` لإظهار آخر نتيجة وحالة النجاح.
- للتكامل مع الشهادات، قد نضيف زر "طلب الشهادة" إذا كانت غير تلقائية.