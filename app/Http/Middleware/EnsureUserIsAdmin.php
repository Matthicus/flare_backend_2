<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->is_admin) {
            // Redirect to login if not authenticated
            if (!$request->user()) {
                return redirect()->route('login');
            }
            
            // Show 403 if not admin
            abort(403, 'Unauthorized. Admin access required.');
        }

        return $next($request);
    }
}