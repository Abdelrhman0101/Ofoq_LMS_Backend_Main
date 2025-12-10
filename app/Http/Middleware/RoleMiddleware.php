<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Check if user has any of the required roles using Spatie
        if ($user->hasAnyRole($roles)) {
            return $next($request);
        }

        // Fallback: check the legacy 'role' column for backward compatibility
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        return response()->json(['message' => 'Unauthorized - Insufficient permissions'], 403);
    }
}
