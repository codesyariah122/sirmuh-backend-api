<?php
/**
 * @author: pujiermanto@gmail.com
 * @param SessionExpired
 * */

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Login;
use App\Models\User;
use Auth;
use Carbon\Carbon;

class SessionExpired {
    protected $timeout = 1200;

    public function handle($request, $next){
        $user = Auth::user();
        $login = Login::whereUserId($user->id)->first();
        $user = User::whereId($user->id)->first();
        $token =  explode(' ',$request->headers->get('Authorization'))[1];
        $dt = Carbon::now();
        $now = $dt->format('Y-m-d H:i:s');

        if($token === $login->user_token_login) {

            if($now > $user->expires_at){

                $user->is_login = 0;
                $user->expires_at = NULL;
                $user->remember_token = null;
                $user->save();

                $removeToken = $request->user()->tokens()->delete();
                $delete_login = Login::whereUserId($user->id);
                $delete_login->delete();

                if ($removeToken) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Logout Success!',
                        'data' => $user
                    ]);
                }

            }else{
                return $next($request);
            }
            
        }

    }
}

