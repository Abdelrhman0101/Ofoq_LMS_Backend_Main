<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Instructors;

class InstructorsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $instructors = [
            [
                'name' => 'د. عبد الرحمن البسيوني',
                'title' => 'خبير إعلامي',
                'bio' => 'الخبير الإعلامي بمعهد الإذاعة والتلفزيون',
                'image' => 'https://api.ofuq.academy/storage/inst/dr.abdelrhman-elbasweny.jpg',
                'email' => 'dr.abdelrhman.elbasweny@ofuq.academy',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'د.علاء صالح',
                'title' => 'استشاري تدريب وتطوير',
                'bio' => 'استشاري تدريب وتطوير قيادي',
                'image' => 'https://api.ofuq.academy/storage/inst/dr.alaa-saleh.jpg',
                'email' => 'dr.alaa.saleh@ofuq.academy',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'د. أشرف البهي',
                'title' => 'خبير تسويق وعلاقات عامة',
                'bio' => 'خبير التسويق والعلاقات العامة',
                'image' => 'https://api.ofuq.academy/storage/inst/dr.ashraf-elbahy.jpg',
                'email' => 'dr.ashraf.elbahy@ofuq.academy',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'د. أشرف الشامي',
                'title' => 'أستاذ الإعلام',
                'bio' => 'أستاذ الإعلام جامعة الأهرام الكندية',
                'image' => 'https://api.ofuq.academy/storage/inst/dr.ashraf-el-shamy.jpg',
                'email' => 'dr.ashraf.elshamy@ofuq.academy',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'الأستاذ الدكتور/ أشـــــــرف رجـــــــب الريـــــــدي',
                'title' => 'أستاذ تعليم الإعلام',
                'bio' => 'أستاذ تعليم الإعلام بجامعة المنيا - مدرب معتمد من المجلس الأعلى للجامعات بمجال تكنولوجيا المعلومات',
                'image' => 'https://api.ofuq.academy/storage/inst/dr.ashraf-ragab-elredy.jpg',
                'email' => 'dr.ashraf.ragab@ofuq.academy',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'د. فوزي عبدالرحمن',
                'title' => 'أستاذ الإعلام',
                'bio' => 'أستاذ الإعلام بمعهد البحوث والدراسات التابع لجامعة الدول العربية',
                'image' => 'https://api.ofuq.academy/storage/inst/dr.fawzy-abdelrhman.jpg',
                'email' => 'dr.fawzy.abdelrhman@ofuq.academy',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'د. حماد الرمحي',
                'title' => 'خبير اقتصاديات إعلامية',
                'bio' => 'خبير تحليل اقتصاديات المؤسسات الإعلامية',
                'image' => 'https://api.ofuq.academy/storage/inst/dr.hamad-elramhy.jpg',
                'email' => 'dr.hamad.elramhy@ofuq.academy',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'د. مطهر الريدة',
                'title' => 'محاضر بكلية الإعلام',
                'bio' => 'محاضر بكلية الإعلام جامعة الأزهر',
                'image' => 'https://api.ofuq.academy/storage/inst/dr.mathar-elredah.jpg',
                'email' => 'dr.mathar.elredah@ofuq.academy',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'د. محمد عبادي',
                'title' => 'خبير إعلام وسياسات دولية',
                'bio' => 'خبير الإعلام وتحليل السياسات الدولية',
                'image' => 'https://api.ofuq.academy/storage/inst/dr.mohamed-abady.jpg',
                'email' => 'dr.mohamed.abady@ofuq.academy',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'د. محمد عابدين',
                'title' => 'خبير إعلامي',
                'bio' => 'خبير إعلامي ورئيس تحرير سابق بقناة النيل الثقافية',
                'image' => 'https://api.ofuq.academy/storage/inst/dr.mohamed-abdeen.jpg',
                'email' => 'dr.mohamed.abdeen@ofuq.academy',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'د. محمد علوان',
                'title' => 'خبير تطوير تنظيمي',
                'bio' => 'خبير التطوير التنظيمي والإعلام القيادي',
                'image' => 'https://api.ofuq.academy/storage/inst/dr.mohamed-alwaan.jpg',
                'email' => 'dr.mohamed.alwaan@ofuq.academy',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'محمد المشتاوي',
                'title' => 'خبير إعلام رقمي',
                'bio' => 'خبير الإعلام الرقمي والتحول الذكي',
                'image' => 'https://api.ofuq.academy/storage/inst/dr.mohamed-sha\'t.jpg',
                'email' => 'mohamed.elmeshtawe@ofuq.academy',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($instructors as $instructor) {
            Instructors::create($instructor);
        }
    }
}