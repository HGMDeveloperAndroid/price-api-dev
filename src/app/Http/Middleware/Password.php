<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class Password
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();
        if ($user->hasAnyRole(['Admin', 'Validator'])) {

            if ($user->default_password == $user->password) {
                return response()->json([
                    'error' => 'Unauthorized. Set your password first.',
                    'route' => route('setup-password')
                ], 401);
            }
        }
        return $next($request);
    }
}
