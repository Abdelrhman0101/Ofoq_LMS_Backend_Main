<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Course;
use App\Models\Chapter;
use App\Models\Lesson;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        $courseTitles = [
            'Python Mastery',
            'Java Programming',
            'PHP for Web Development',
            'JavaScript Essentials',
            'C# Fundamentals',
            'Ruby on Rails Bootcamp',
            'C++ Advanced',
            'Swift for iOS',
            'Kotlin for Android',
            'Go Programming'
        ];

        foreach ($courseTitles as $title) {
            $course = Course::create([
                'title'       => $title,
                'description' => "Comprehensive {$title} course with practical examples.",
                // 'level'       => ['Beginner', 'Intermediate', 'Advanced'][array_rand([0, 1, 2])],
                // 'duration'    => rand(10, 30) . 'h',
                'price'       => rand(49, 299),
            ]);

            // 🟢 5 Chapters لكل كورس
            for ($c = 1; $c <= 5; $c++) {
                $chapter = Chapter::create([
                    'title'       => "Chapter {$c} of {$title}",
                    // 'description' => "Details for Chapter {$c} of {$title}",
                    'order'       => $c,
                    'course_id'   => $course->id,
                ]);

                // 🟢 3 Lessons لكل Chapter
                for ($l = 1; $l <= 3; $l++) {
                    $lesson = Lesson::create([
                        'chapter_id'  => $chapter->id,
                        'title'       => "Lesson {$l} of Chapter {$c}",
                        'content'     => "Content for Lesson {$l} of {$title}",
                        'order'       => $l,
                        'attachments' => "attachment-{$c}-{$l}.zip",
                        'resources'   => [
                            [
                                'key'   => "doc-{$c}-{$l}",
                                'value' => "lesson-{$c}-{$l}.pdf",
                            ],
                            [
                                'key'   => "slides-{$c}-{$l}",
                                'value' => "lesson-{$c}-{$l}.pptx",
                            ],
                            [
                                'key'   => "video-{$c}-{$l}",
                                'value' => "lesson-{$c}-{$l}.mp4",
                            ],
                        ],
                    ]);

                    // 🟢 Quiz لكل Lesson
                    $lessonQuiz = $lesson->quiz()->create([
                        'title'          => "Quiz for {$lesson->title}",
                        'quizzable_type' => Lesson::class,
                        'quizzable_id'   => $lesson->id,
                    ]);

                    // 🟢 5 أسئلة لكل Quiz
                    $lessonQuiz->questions()->createMany([
                        [
                            'question'       => "What is covered in {$lesson->title}?",
                            'options'        => json_encode(['Basics', 'Intermediate', 'Advanced']),
                            'correct_answer' => 'Basics',
                        ],
                        [
                            'question'       => "Which programming language is this course about?",
                            'options'        => json_encode(['Python', 'Java', 'PHP', 'JavaScript']),
                            'correct_answer' => explode(' ', $title)[0],
                        ],
                        [
                            'question'       => "How many chapters does {$title} have?",
                            'options'        => json_encode(['3', '5', '7']),
                            'correct_answer' => '5',
                        ],
                        [
                            'question'       => "What type of resources are included in lessons?",
                            'options'        => json_encode(['PDF', 'Slides', 'Video', 'All of the above']),
                            'correct_answer' => 'All of the above',
                        ],
                        [
                            'question'       => "In which chapter is {$lesson->title} located?",
                            'options'        => json_encode(['Chapter 1', 'Chapter 2', 'Chapter 3', 'Chapter ' . $c]),
                            'correct_answer' => 'Chapter ' . $c,
                        ],
                    ]);
                }
            }
        }
    }
}
