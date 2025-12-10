<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class SupervisorController extends Controller
{
    /**
     * Get all supervisors with their permissions
     */
    public function index()
    {
        $supervisors = User::where('role', 'supervisor')
            ->orWhereHas('roles', function ($query) {
                $query->where('name', 'supervisor');
            })
            ->get()
            ->map(function ($supervisor) {
                return [
                    'id' => $supervisor->id,
                    'name' => $supervisor->name,
                    'email' => $supervisor->email,
                    'phone' => $supervisor->phone,
                    'created_at' => $supervisor->created_at,
                    'permissions' => $supervisor->getDirectPermissions()->pluck('name'),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $supervisors,
        ]);
    }

    /**
     * Get available permissions grouped by page
     */
    public function getPermissions()
    {
        // صلاحية واحدة لكل صفحة - تمنح وصول كامل للصفحة ومحتوياتها
        $pagePermissions = [
            [
                'page' => 'الدبلومات',
                'key' => 'diplomas',
                'description' => 'إدارة الدبلومات والمقررات والفصول والدروس',
            ],
            [
                'page' => 'بنك الأسئلة',
                'key' => 'questions',
                'description' => 'إدارة بنوك الأسئلة والاختبارات',
            ],
            [
                'page' => 'إدارة الطلاب',
                'key' => 'students',
                'description' => 'عرض وإدارة بيانات الطلاب',
            ],
            [
                'page' => 'المحاضرون',
                'key' => 'instructors',
                'description' => 'إدارة المحاضرين',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $pagePermissions,
        ]);
    }

    /**
     * Create a new supervisor
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'phone' => 'nullable|string|max:20',
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string',
        ], [
            'name.required' => 'الاسم مطلوب',
            'email.required' => 'البريد الإلكتروني مطلوب',
            'email.unique' => 'البريد الإلكتروني مستخدم مسبقاً',
            'password.required' => 'كلمة المرور مطلوبة',
            'password.min' => 'كلمة المرور يجب أن تكون 6 أحرف على الأقل',
            'permissions.required' => 'يجب اختيار صلاحية واحدة على الأقل',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Create user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone' => $request->phone,
            'role' => 'supervisor',
        ]);

        // Assign role using Role model directly
        $supervisorRole = Role::where('name', 'supervisor')->where('guard_name', 'sanctum')->first();
        if ($supervisorRole) {
            $user->roles()->attach($supervisorRole->id);
        }
        
        // Assign permissions using Permission models
        $permissionModels = Permission::whereIn('name', $request->permissions)
            ->where('guard_name', 'sanctum')
            ->get();
        $user->permissions()->sync($permissionModels->pluck('id'));

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء المشرف بنجاح',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'permissions' => $user->getDirectPermissions()->pluck('name'),
            ],
        ], 201);
    }

    /**
     * Update supervisor permissions
     */
    public function updatePermissions(Request $request, User $supervisor)
    {
        if ($supervisor->role !== 'supervisor' && !$supervisor->hasRole('supervisor')) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم ليس مشرفاً',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        // Sync permissions using Permission models
        $permissionModels = Permission::whereIn('name', $request->permissions)
            ->where('guard_name', 'sanctum')
            ->get();
        $supervisor->permissions()->sync($permissionModels->pluck('id'));

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الصلاحيات بنجاح',
            'data' => [
                'permissions' => $supervisor->getDirectPermissions()->pluck('name'),
            ],
        ]);
    }

    /**
     * Update supervisor password
     */
    public function updatePassword(Request $request, User $supervisor)
    {
        if ($supervisor->role !== 'supervisor' && !$supervisor->hasRole('supervisor')) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم ليس مشرفاً',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $supervisor->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح',
        ]);
    }

    /**
     * Delete a supervisor
     */
    public function destroy(User $supervisor)
    {
        if ($supervisor->role !== 'supervisor' && !$supervisor->hasRole('supervisor')) {
            return response()->json([
                'success' => false,
                'message' => 'المستخدم ليس مشرفاً',
            ], 400);
        }

        // Remove all roles and permissions
        $supervisor->roles()->detach();
        $supervisor->permissions()->detach();
        
        // Delete user
        $supervisor->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المشرف بنجاح',
        ]);
    }

    /**
     * Get current user permissions (for sidebar filtering)
     */
    public function myPermissions(Request $request)
    {
        $user = $request->user();
        
        // Admin has all permissions
        if ($user->role === 'admin' || $user->hasRole('admin')) {
            return response()->json([
                'success' => true,
                'role' => 'admin',
                'permissions' => ['*'], // All permissions
            ]);
        }

        return response()->json([
            'success' => true,
            'role' => $user->role,
            'permissions' => $user->getDirectPermissions()->pluck('name'),
        ]);
    }
}
