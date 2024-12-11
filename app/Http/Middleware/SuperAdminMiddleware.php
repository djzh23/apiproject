<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponses;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    use ApiResponses;
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (!Auth::check() || Auth::user()->role_id != 1) {
//            return response()->json(['success' => false, 'message' => 'Unauthorized', 'data' => null], 401);
            return $this->error(trans('messages.auth.superadmin_required'), null);
        }

        //            'message' => trans('messages.auth.superadmin_required'),
        return $next($request);
    }
}
