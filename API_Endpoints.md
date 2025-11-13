# Project API Endpoints

This document provides a comprehensive reference for all the API endpoints available in the project.

## Admin Endpoints

These endpoints are for managing the application's content and are restricted to users with the 'admin' role.

### Courses

#### `GET /api/admin/courses`
- **Description**: Retrieve a list of all courses.
- **Authorization**: Admin only.
- **Success Response**:
  ```json
  {
    "data": [
      {
        "id": 1,
        "title": "Sample Course",
        "description": "This is a sample course.",
        "created_at": "2023-10-27T10:00:00.000000Z",
        "updated_at": "2023-10-27T10:00:00.000000Z"
      }
    ]
  }
  ```

#### `GET /api/admin/courses/{id}`
- **Description**: Retrieve a single course by its ID.
- **Authorization**: Admin only.
- **Success Response**:
  ```json
  {
    "data": {
      "id": 1,
      "title": "Sample Course",
      "description": "This is a sample course.",
      "created_at": "2023-10-27T10:00:00.000000Z",
      "updated_at": "2023-10-27T10:00:00.000000Z"
    }
  }
  ```

#### `POST /api/admin/courses`
- **Description**: Create a new course.
- **Authorization**: Admin only.
- **Request Body**:
  ```json
  {
    "title": "New Course Title",
    "description": "Description for the new course."
  }
  ```
- **Success Response**:
  ```json
  {
    "data": {
      "id": 2,
      "title": "New Course Title",
      "description": "Description for the new course.",
      "created_at": "2023-10-27T11:00:00.000000Z",
      "updated_at": "2023-10-27T11:00:00.000000Z"
    }
  }
  ```

#### `PUT /api/admin/courses/{id}`
- **Description**: Update an existing course.
- **Authorization**: Admin only.
- **Request Body**:
  ```json
  {
    "title": "Updated Course Title"
  }
  ```
- **Success Response**:
  ```json
  {
    "data": {
      "id": 1,
      "title": "Updated Course Title",
      "description": "This is a sample course.",
      "created_at": "2023-10-27T10:00:00.000000Z",
      "updated_at": "2023-10-27T12:00:00.000000Z"
    }
  }
  ```

#### `DELETE /api/admin/courses/{id}`
- **Description**: Delete a course.
- **Authorization**: Admin only.
- **Success Response**: `204 No Content`

### Chapters

#### `POST /api/admin/courses/{course_id}/chapters`
- **Description**: Add a new chapter to a course.
- **Authorization**: Admin only.
- **Request Body**:
  ```json
  {
    "title": "New Chapter Title"
  }
  ```
- **Success Response**:
  ```json
  {
    "data": {
      "id": 1,
      "course_id": 1,
      "title": "New Chapter Title",
      "created_at": "2023-10-27T13:00:00.000000Z",
      "updated_at": "2023-10-27T13:00:00.000000Z"
    }
  }
  ```

#### `PUT /api/admin/chapters/{id}`
- **Description**: Update an existing chapter.
- **Authorization**: Admin only.
- **Request Body**:
  ```json
  {
    "title": "Updated Chapter Title"
  }
  ```
- **Success Response**:
  ```json
  {
    "data": {
      "id": 1,
      "course_id": 1,
      "title": "Updated Chapter Title",
      "created_at": "2023-10-27T13:00:00.000000Z",
      "updated_at": "2023-10-27T14:00:00.000000Z"
    }
  }
  ```

#### `DELETE /api/admin/chapters/{id}`
- **Description**: Delete a chapter.
- **Authorization**: Admin only.
- **Success Response**: `204 No Content`

### Lessons

#### `POST /api/admin/chapters/{chapter_id}/lessons`
- **Description**: Add a new lesson to a chapter.
- **Authorization**: Admin only.
- **Request Body**:
  ```json
  {
    "title": "New Lesson Title",
    "content": "Lesson content goes here."
  }
  ```
- **Success Response**:
  ```json
  {
    "data": {
      "id": 1,
      "chapter_id": 1,
      "title": "New Lesson Title",
      "content": "Lesson content goes here.",
      "created_at": "2023-10-27T15:00:00.000000Z",
      "updated_at": "2023-10-27T15:00:00.000000Z"
    }
  }
  ```

#### `PUT /api/admin/lessons/{id}`
- **Description**: Update an existing lesson.
- **Authorization**: Admin only.
- **Request Body**:
  ```json
  {
    "title": "Updated Lesson Title"
  }
  ```
- **Success Response**:
  ```json
  {
    "data": {
      "id": 1,
      "chapter_id": 1,
      "title": "Updated Lesson Title",
      "content": "Lesson content goes here.",
      "created_at": "2023-10-27T15:00:00.000000Z",
      "updated_at": "2023-10-27T16:00:00.000000Z"
    }
  }
  ```

#### `DELETE /api/admin/lessons/{id}`
- **Description**: Delete a lesson.
- **Authorization**: Admin only.
- **Success Response**: `204 No Content`

### Quizzes & Questions

#### `POST /api/admin/chapters/{chapter_id}/quiz`
- **Description**: Create a new quiz for a chapter.
- **Authorization**: Admin only.
- **Request Body**:
  ```json
  {
    "title": "New Quiz Title"
  }
  ```
- **Success Response**:
  ```json
  {
    "data": {
      "id": 1,
      "chapter_id": 1,
      "title": "New Quiz Title",
      "created_at": "2023-10-27T17:00:00.000000Z",
      "updated_at": "2023-10-27T17:00:00.000000Z"
    }
  }
  ```

#### `POST /api/admin/quiz/{quiz_id}/questions`
- **Description**: Add a new question to a quiz.
- **Authorization**: Admin only.
- **Request Body**:
  ```json
  {
    "question": "What is 2+2?",
    "options": ["3", "4", "5"],
    <!-- "correct_answer": "4" -->
  }
  ```
- **Success Response**:
  ```json
  {
    "data": {
      "id": 1,
      "quiz_id": 1,
      "question": "What is 2+2?",
      "options": ["3", "4", "5"],
      "correct_answer": "4",
      "created_at": "2023-10-27T18:00:00.000000Z",
      "updated_at": "2023-10-27T18:00:00.000000Z"
    }
  }
  ```

#### `PUT /api/admin/questions/{id}`
- **Description**: Update an existing question.
- **Authorization**: Admin only.
- **Request Body**:
  ```json
  {
    "question": "What is the capital of France?",
    "options": ["Berlin", "Madrid", "Paris"],
    "correct_answer": "Paris"
  }
  ```
- **Success Response**:
  ```json
  {
    "data": {
      "id": 1,
      "quiz_id": 1,
      "question": "What is the capital of France?",
      "options": ["Berlin", "Madrid", "Paris"],
      "correct_answer": "Paris",
      "created_at": "2023-10-27T18:00:00.000000Z",
      "updated_at": "2023-10-27T19:00:00.000000Z"
    }
  }
  ```

#### `DELETE /api/admin/questions/{id}`
- **Description**: Delete a question.
- **Authorization**: Admin only.
- **Success Response**: `204 No Content`

## Authentication Endpoints

These endpoints handle user authentication and profile management.

### Registration & Login

#### `POST /api/auth/signup`
- **Description**: Register a new student account.
- **Authorization**: Public (no authentication required).
- **Request Body**:
  ```json
  {
    "name": "Ahmed Ali",
    "email": "ahmed@example.com",
    "password": "secret123",
    "password_confirmation": "secret123"
  }
  ```
- **Success Response**:
  ```json
  {
    "user": {
      "id": 5,
      "name": "Ahmed Ali",
      "email": "ahmed@example.com",
      "role": "student"
    },
    "token": "SANCTUM_TOKEN"
  }
  ```

#### `POST /api/auth/login`
- **Description**: Login for admin or student.
- **Authorization**: Public (no authentication required).
- **Request Body**:
  ```json
  {
    "email": "ahmed@example.com",
    "password": "secret123"
  }
  ```
- **Success Response**:
  ```json
  {
    "user": {
      "id": 5,
      "name": "Ahmed Ali",
      "email": "ahmed@example.com",
      "role": "student"
    },
    "token": "SANCTUM_TOKEN"
  }
  ```

#### `POST /api/auth/logout`
- **Description**: Logout current user.
- **Authorization**: Authenticated user (auth:sanctum).
- **Success Response**:
  ```json
  {
    "message": "Successfully logged out"
  }
  ```

### Password Reset

#### `POST /api/auth/forgot-password`
- **Description**: Send password reset OTP to email.
- **Authorization**: Public (no authentication required).
- **Request Body**:
  ```json
  {
    "email": "ahmed@example.com"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Password reset OTP sent to your email"
  }
  ```

#### `POST /api/auth/reset-password`
- **Description**: Reset password using OTP.
- **Authorization**: Public (no authentication required).
- **Request Body**:
  ```json
  {
    "email": "ahmed@example.com",
    "otp": "123456",
    "password": "newpassword123",
    "password_confirmation": "newpassword123"
  }
  ```
- **Success Response**:
  ```json
  {
    "message": "Password reset successfully"
  }
  ```

### Profile Management

## User Profile

### `GET /api/user/profile`
- Description: Retrieve current authenticated user's profile, including enrollment fields.
- Authorization: Authenticated (Sanctum).
- Success Response:
  ```json
  {
    "user": {
      "id": 1,
      "name": "Ahmed Ali",
      "email": "ahmed@example.com",
      "role": "student",
      "profile_picture": "profile_pictures/abc.jpg",
      "qualification": "ماجستير",
      "media_work_sector": "جهة حكومية",
      "date_of_birth": "1995-05-10",
      "previous_field": "ادارة مؤسسة",
      "created_at": "2025-10-24T11:00:00.000000Z",
      "updated_at": "2025-10-24T11:00:00.000000Z"
    }
  }
  ```

### `PUT /api/user/profile`
- Description: Update the authenticated user's profile fields.
- Authorization: Authenticated (Sanctum).
- Request Body (all fields optional):
  - `name`: string, max 255
  - `profile_picture`: string, max 255
  - `qualification`: string, max 255
  - `media_work_sector`: string, max 255
  - `date_of_birth`: date (YYYY-MM-DD)
  - `previous_field`: string, max 255
- Success Response: Same shape as GET `/api/user/profile`.

```json
{
    "user": {
      "id": 5,
      "name": "Ahmed Ali",
      "email": "ahmed@example.com",
      "role": "student",
      "profile_picture": null,
      "created_at": "2023-10-27T10:00:00.000000Z",
      "updated_at": "2023-10-27T10:00:00.000000Z"
    }
  }
  ```

#### `PUT /api/profile`
- **Description**: Update current user profile.
- **Authorization**: Authenticated user (auth:sanctum).
- **Request Body**:
  ```json
  {
    "name": "Ahmed Ali Updated",
    "profile_picture": "https://example.com/profile.jpg"
  }
  ```
- **Success Response**:
  ```json
  {
    "user": {
      "id": 5,
      "name": "Ahmed Ali Updated",
      "email": "ahmed@example.com",
      "role": "student",
      "profile_picture": "https://example.com/profile.jpg",
      "created_at": "2023-10-27T10:00:00.000000Z",
      "updated_at": "2023-10-27T12:00:00.000000Z"
    }
  }
  ```

## Authorization & Middleware

### Middleware Usage

- **auth:sanctum**: Ensures the user is authenticated with a valid token.
- **role:admin**: Restricts access to admin users only.
- **role:student**: Restricts access to student users only.

### Default Users

The system comes with default users for testing:

- **Admin User**:
  - Email: `admin@ofoq.com`
  - Password: `admin123`
  - Role: `admin`

- **Test Student**:
  - Email: `student@ofoq.com`
  - Password: `student123`
  - Role: `student`

## Public Endpoints

### Lesson Status

#### `GET /api/lessons/{lesson}/status`
- Description: Check a specific lesson status for the authenticated user using a token.
- Authorization: Public, requires Sanctum token via query or Bearer.
- Query Parameters:
  - `token` (string, optional if using Bearer): Sanctum personal access token.
- Path Parameters:
  - `lesson` (integer): Lesson ID.
- Success Response (200):
  ```json
  {
    "status": "completed | in_progress | not_enrolled",
    "lesson_id": 123,
    "course_id": 45,
    "diploma_id": 7,
    "user_id": 9
  }
  ```
- Error Responses:
  - `401` Missing token: `{ "message": "Missing token" }`
  - `401` Invalid token: `{ "message": "Invalid token" }`