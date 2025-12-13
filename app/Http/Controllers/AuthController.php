<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\BlockedUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new student
     */
    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:20|unique:users',
            'nationality' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        $validated = $validator->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'nationality' => $validated['nationality'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'student', // Only students can signup
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => array_filter([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'nationality' => $user->nationality,
                'role' => $user->role,
            ], fn($value) => !is_null($value)),
            'token' => $token
        ], 201);

    }

    /**
     * Login user (admin or student)
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (!Auth::attempt([$loginType => $request->login, 'password' => $request->password])) {
            throw ValidationException::withMessages([
                'login' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => array_filter([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'nationality' => $user->nationality,
                'role' => $user->role,
            ], fn($value) => !is_null($value)),
            'token' => $token
        ], 201);
    }

    /**
     * Logout user
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * Get user profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'profile_picture' => $user->profile_picture,
                'qualification' => $user->qualification,
                'media_work_sector' => $user->media_work_sector,
                'date_of_birth' => $user->date_of_birth,
                'previous_field' => $user->previous_field,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'profile_picture' => 'sometimes|string|max:255',
            'qualification' => 'sometimes|string|max:255',
            'media_work_sector' => 'sometimes|string|max:255',
            'date_of_birth' => 'sometimes|date',
            'previous_field' => 'sometimes|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($request->only([
            'name',
            'profile_picture',
            'qualification',
            'media_work_sector',
            'date_of_birth',
            'previous_field',
        ]));

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'profile_picture' => $user->profile_picture,
                'qualification' => $user->qualification,
                'media_work_sector' => $user->media_work_sector,
                'date_of_birth' => $user->date_of_birth,
                'previous_field' => $user->previous_field,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ]);
    }

    /**
     * Change password for authenticated user
     */
    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'كلمة المرور الحالية غير صحيحة'
            ], 400);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return response()->json([
            'message' => 'تم تغيير كلمة المرور بنجاح'
        ]);
    }

    /**
     * Send password reset OTP
     */
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // Here you would implement OTP generation and email sending
        // For now, we'll return a success message
        return response()->json([
            'message' => 'Password reset OTP sent to your email'
        ]);
    }

    /**
     * Reset password with OTP
     */
    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // Here you would verify the OTP
        // For now, we'll just update the password
        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'message' => 'Password reset successfully'
        ]);
    }

    /**
     * Update user profile picture
     */
    public function updateProfilePicture(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg|max:10240',
        ]);

        // Store the uploaded file in the public disk under profile_pictures folder
        $path = $request->file('profile_picture')->store('profile_pictures', 'public');

        // Get the currently authenticated user and update their profile_picture field
        $user = Auth::user();
        $user->profile_picture = $path;
        $user->save();

        // Return successful JSON response with the new path
        return response()->json([
            'message' => 'Profile picture updated successfully.',
            'path' => $path
        ]);
    }
}
