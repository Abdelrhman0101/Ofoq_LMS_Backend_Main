<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\CategoryOfCourse;

class CategoriesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'الإدارة الإعلامية',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'القيادة والتطوير',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'التسويق والعلاقات العامة',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'استشراف المستقبل',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'التخطيط الاستراتيجي',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'أخلاقيات الإعلام',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'اقتصاديات الإعلام',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'إدارة المعرفة',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'الإعلام الرقمي',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'النماذج التطبيقية',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'التطوير التنظيمي',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($categories as $category) {
            CategoryOfCourse::firstOrCreate(
                ['name' => $category['name']],
                $category
            );
        }

        $this->command->info('✅ تم إنشاء الفئات بنجاح!');
    }
}