<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponses;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

// This is the middleware implementation for only the Projekt-Koordinator role.
class AdminMiddleware
{
    use ApiResponses;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check() || Auth::user()->role_id != 2) {
            return $this->error(trans('messages.auth.admin_required'), null);
        }

        return $next($request);
    }
}
