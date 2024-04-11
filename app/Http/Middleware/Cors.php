<?php

/**
 * @author: pujiermanto@gmail.com
 * @param CorsMiddleware
 * */

namespace App\Http\Middleware;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Closure;
use Auth;
use App\Models\{ApiKey, Login};

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle($request, Closure $next)
    {
        try {
            $api_key = $request->header('Sirmuh-Key');
            $check_api_onDb = ApiKey::whereToken($api_key)->first();

            if ($check_api_onDb !== null) {
                $response = $next($request);

                if ($response !== null) {
                    return $response
                    ->header('Access-Control-Allow-Origin', '*')
                    ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                    ->header('Access-Control-Allow-Headers', 'Authorization')
                    ->header('Access-Control-Allow-Headers', 'Content-Type');
                } else {
                // Jika $response null, Anda bisa mengembalikan respons yang sesuai
                    $userData = Auth::user();

                    Login::whereUserId($userData->id)->delete();

                    return response()->json([
                        'error' => true,
                        'message' => 'Internal Server Error, please login again 🔑'
                    ], 500);
                }
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'Access blocked!'
                ], 404);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
