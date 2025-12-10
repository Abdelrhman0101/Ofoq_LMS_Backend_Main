<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            // الدبلومات
            'diplomas.view',
            'diplomas.create',
            'diplomas.update',
            'diplomas.delete',
            
            // بنك الأسئلة
            'questions.view',
            'questions.create',
            'questions.update',
            'questions.delete',
            
            // المحاضرين
            'instructors.view',
            'instructors.create',
            'instructors.update',
            'instructors.delete',
            
            // إدارة الطلاب
            'students.view',
            'students.block',
            'students.update',
            
            // الدورات
            'courses.view',
            'courses.create',
            'courses.update',
            'courses.delete',
            'courses.publish',
            
            // الفصول
            'chapters.view',
            'chapters.create',
            'chapters.update',
            'chapters.delete',
            
            // الدروس
            'lessons.view',
            'lessons.create',
            'lessons.update',
            'lessons.delete',
            
            // الشهادات
            'certificates.view',
            'certificates.generate',
            'certificates.revoke',
            
            // النسخ الاحتياطي
            'backups.view',
            'backups.create',
            'backups.restore',
            'backups.delete',
            
            // الإحصائيات
            'stats.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'sanctum']);
        }

        // Create Roles and assign permissions
        
        // Admin: كل الصلاحيات
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'sanctum']);
        $adminRole->givePermissionTo(Permission::all());

        // Supervisor: لا صلاحيات افتراضية - تُعطى للمستخدم مباشرة عند الإنشاء
        Role::firstOrCreate(['name' => 'supervisor', 'guard_name' => 'sanctum']);

        // Student: لا صلاحيات إدارية
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'sanctum']);

        // Sync existing users based on their 'role' column
        $this->syncExistingUsers();
    }

    /**
     * Sync existing users with Spatie roles based on their 'role' column.
     */
    private function syncExistingUsers(): void
    {
        // Get roles with sanctum guard
        $adminRole = Role::where('name', 'admin')->where('guard_name', 'sanctum')->first();
        $studentRole = Role::where('name', 'student')->where('guard_name', 'sanctum')->first();

        // Assign 'admin' role to existing admin users
        if ($adminRole) {
            User::where('role', 'admin')->each(function ($user) use ($adminRole) {
                if (!$user->roles()->where('id', $adminRole->id)->exists()) {
                    $user->roles()->attach($adminRole->id);
                }
            });
        }

        // Assign 'student' role to existing student users
        if ($studentRole) {
            User::where('role', 'student')->each(function ($user) use ($studentRole) {
                if (!$user->roles()->where('id', $studentRole->id)->exists()) {
                    $user->roles()->attach($studentRole->id);
                }
            });
        }
    }
}
