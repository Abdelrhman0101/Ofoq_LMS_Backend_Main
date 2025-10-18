<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Delete a user (admin only). Cascades will remove related data.
     */
    public function destroy(User $user)
    {
        // Optional: prevent deleting admins
        if ($user->role === 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Deleting admin users is not allowed.'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully.'
        ]);
    }
}