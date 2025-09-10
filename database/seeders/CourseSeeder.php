<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\User;
use Illuminate\Database\Seeder;

class CourseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a sample course
        $course = Course::create([
            'title' => 'Laravel for Beginners',
            'description' => 'A comprehensive course for learning Laravel from scratch.',
            'price' => 49.99,
            'is_free' => false,
        ]);

        // Create chapters for the course
        $chapter1 = Chapter::create([
            'course_id' => $course->id,
            'title' => 'Chapter 1: Introduction to Laravel',
            'order' => 1,
        ]);

        $chapter2 = Chapter::create([
            'course_id' => $course->id,
            'title' => 'Chapter 2: Core Concepts',
            'order' => 2,
        ]);

        // Create lessons for Chapter 1
        Lesson::create([
            'chapter_id' => $chapter1->id,
            'title' => 'Lesson 1.1: Setting up Your Environment',
            'content' => '...',
            'order' => 1,
        ]);

        Lesson::create([
            'chapter_id' => $chapter1->id,
            'title' => 'Lesson 1.2: Understanding the Directory Structure',
            'content' => '...',
            'order' => 2,
        ]);

        // Create lessons for Chapter 2
        Lesson::create([
            'chapter_id' => $chapter2->id,
            'title' => 'Lesson 2.1: Routing and Controllers',
            'content' => '...',
            'order' => 1,
        ]);

        Lesson::create([
            'chapter_id' => $chapter2->id,
            'title' => 'Lesson 2.2: Blade Templates',
            'content' => '...',
            'order' => 2,
        ]);

        // Create a quiz for Chapter 1
        $quiz1 = Quiz::create([
            'chapter_id' => $chapter1->id,
            'title' => 'Quiz for Chapter 1',
        ]);

        // Create questions for the quiz
        Question::create([
            'quiz_id' => $quiz1->id,
            'question' => 'What is Composer?',
            'options' => json_encode(['A package manager for PHP', 'A web server', 'A database']),
            'correct_answer' => 'A package manager for PHP',
        ]);

        Question::create([
            'quiz_id' => $quiz1->id,
            'question' => 'What is the purpose of the .env file?',
            'options' => json_encode(['To store environment-specific variables', 'To define routes', 'To write Blade templates']),
            'correct_answer' => 'To store environment-specific variables',
        ]);

        Question::create([
            'quiz_id' => $quiz1->id,
            'question' => 'Which command is used to start the Laravel development server?',
            'options' => json_encode(['php artisan serve', 'php start', 'laravel run']),
            'correct_answer' => 'php artisan serve',
        ]);

        Question::create([
            'quiz_id' => $quiz1->id,
            'question' => 'What is the name of Laravel\'s templating engine?',
            'options' => json_encode(['Blade', 'Twig', 'Smarty']),
            'correct_answer' => 'Blade',
        ]);

        Question::create([
            'quiz_id' => $quiz1->id,
            'question' => 'What is Eloquent?',
            'options' => json_encode(['An ORM (Object-Relational Mapper)', 'A validation library', 'A testing framework']),
            'correct_answer' => 'An ORM (Object-Relational Mapper)',
        ]);

        // Create a student and an admin user
        $student = User::factory()->create([
            'name' => 'Student User',
            'email' => 'student@ofoq.com',
        ]);

        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@ofoq.com',
        ]);

        // Assign the course to the student
        $student->courses()->attach($course->id);
    }
}
