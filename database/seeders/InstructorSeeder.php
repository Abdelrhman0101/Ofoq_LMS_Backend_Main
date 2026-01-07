<?php

namespace Database\Seeders;

use App\Models\Instructors;
use Illuminate\Database\Seeder;

class InstructorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Instructors::updateOrCreate(
            ['email' => 'instructor1@ofoq.com'],
            [
                'name' => 'الدكتور أحمد محمد',
                'title' => 'خبير تطوير البرمجيات',
                'bio' => 'أكثر من 10 سنوات خبرة في تطوير تطبيقات الويب والتدريب البرمجي.',
                'rating' => 5.0,
            ]
        );

        Instructors::updateOrCreate(
            ['email' => 'instructor2@ofoq.com'],
            [
                'name' => 'المهندسة سارة علي',
                'title' => 'متخصصة في تحليل البيانات',
                'bio' => 'خبيرة في استخدام Python و R لتحليل البيانات الضخمة.',
                'rating' => 4.8,
            ]
        );
    }
}
