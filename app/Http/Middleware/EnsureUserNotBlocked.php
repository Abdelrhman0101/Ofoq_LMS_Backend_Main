<?php

namespace App\Http\Middleware;

use App\Models\BlockedUser;
use Closure;
use Illuminate\Http\Request;

class EnsureUserNotBlocked
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user) {
            $blocked = BlockedUser::where('user_id', $user->id)->first();
            if ($blocked && $blocked->is_blocked) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is blocked. Please contact support.',
                    'is_blocked' => true,
                ], 403);
            }
        }
        return $next($request);
    }
}