<?php

/**
 * @author: pujiermanto@gmail.com
 * @param ForceJsonResponse
 * */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\{User, Login};
use Auth;

class ForceJsonResponse
{
    /**
     * Handle an incoming request.
     *
     * @author Puji Ermanto <puji.ermanto@gmail.com>
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $request->headers->set('Accept', 'application/json');
        if ($request->headers->get('Authorization')) {
            $token = explode(' ', $request->headers->get('Authorization'))[1];
            $login_check = Login::whereUserTokenLogin($token)->get();

            if (count($login_check) % 2 === 1) return $next($request);

            return response()->json([
                'error' => true,
                'message' => 'Forbidden Access!! OR Your token is stale',
            ], 403);
        } else {
            return $next($request);
        }
    }
}
