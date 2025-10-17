<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BlockedUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BlockedUserController extends Controller
{
    // List blocked users with optional filters
    public function index(Request $request)
    {
        $perPage = (int)($request->get('per_page', 15));
        $query = BlockedUser::query()->with('user');

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter currently blocked only
        if ($request->boolean('only_active', false)) {
            $query->where('is_blocked', true);
        }

        $blockedUsers = $query->orderByDesc('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $blockedUsers,
        ]);
    }

    // Block a user (boolean flag)
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'reason' => ['nullable', 'string'],
            'is_blocked' => ['sometimes', 'boolean'],
        ]);

        // Default to true if not provided
        $isBlocked = $validated['is_blocked'] ?? true;
        $blocked = BlockedUser::updateOrCreate(
            ['user_id' => $validated['user_id']],
            ['reason' => $validated['reason'] ?? null, 'is_blocked' => $isBlocked]
        );

        return response()->json([
            'success' => true,
            'message' => 'User block status updated successfully',
            'data' => $blocked,
        ], 201);
    }

    // Update block (change reason or toggle blocked flag)
    // public function update(Request $request, BlockedUser $blockedUser)
    // {
    //     $validated = $request->validate([
    //         'reason' => ['nullable', 'string'],
    //         'is_blocked' => ['required', 'boolean'],
    //     ]);

    //     $blockedUser->update($validated);

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Blocked user updated successfully',
    //         'data' => $blockedUser,
    //     ]);
    // }

    // Unblock user (set flag to false or delete record)
    public function destroy(BlockedUser $blockedUser)
    {
        $blockedUser->update(['is_blocked' => false, 'reason' => null]);

        return response()->json([
            'success' => true,
            'message' => 'User unblocked successfully',
        ]);
    }
}
