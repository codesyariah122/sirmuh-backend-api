<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Models\{User, Login};
use Auth;

class LoginController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        //set validation
        $validator = Validator::make($request->all(), [
            'email'     => 'required',
            'password'  => 'required'
        ]);

        //if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        //get credentials from request
        $credentials = $request->only('email', 'password');
        $user = User::whereNull('deleted_at')
        ->where('email', $request->email)
        ->get();

        $token = $user[0]->createToken($user[0]->name)->accessToken;
        
        $user = User::whereNull('deleted_at')
        ->where('email', $request->email)
        ->get();
        $user_login = User::findOrFail($user[0]->id);
        $user_id = $user_login->id;

        if ($request->remember_me) {
            $dates = Carbon::now()->addDays(7);
            $user_login->expires_at = $dates;
            $user_login->remember_token = $token;
        } else {
            $user_login->expires_at = Carbon::now()->addRealMinutes(60);
        }

        $user_login->last_login = Carbon::now();
        $user_login->is_login = 1;
        $user_login->save();

        $logins = new Login;
        $logins->user_id = $user_id;
        $logins->user_token_login = $token;
        $logins->save();
        $login_id = $logins->id;

        // sync pivot table
        $user[0]->logins()->sync($login_id);

        $userIsLogin = User::whereId($user_login->id)
        ->with('roles')
        ->with('logins')
        ->get();
        //if auth success
        $brandName = env('APP_NAME');
        return response()->json([
            'success' => true,
            'message' => "Login success, welcome in $brandName 🚀",
            // 'user'    => auth()->guard('api')->user(),
            'user'    => $userIsLogin,
            // 'token'   => $token
        ], 200);
    }

    public function logout(Request $request)
    {
        try {
            $user = User::findOrFail($request->user()->id);
            $user->is_login = 0;
            $user->expires_at = null;
            $user->remember_token = null;
            $user->save();

            $removeToken = $request->user()->tokens()->delete();
            $delete_login = Login::whereUserId($user->id);
            $delete_login->delete();

            if ($removeToken) {
                $userIsLogout = User::whereId($user->id)
                ->select('users.id', 'users.name', 'users.email', 'users.is_login', 'users.expires_at', 'users.last_login')
                ->with(['roles' => function ($query) {
                    $query->select('roles.id', 'roles.name');
                }])
                ->get();
                return response()->json([
                    'success' => true,
                    'message' => 'Logout Success 🔐',
                    'data' => $userIsLogout
                ]);
            }
            $tableLogin = with(new Login)->getTable();
            DB::statement("ALTER TABLE $tableLogin AUTO_INCREMENT = 1;");
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
