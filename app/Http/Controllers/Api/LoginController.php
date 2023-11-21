<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\{Hash, Validator, Http};
use App\Models\{User, Login};
use App\Events\{EventNotification};
use Auth;

class LoginController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    private function forbidenIsUserLogin($isLogin)
    {
        return $isLogin ? true : false;
    }

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $check_userRole = User::whereNull('deleted_at')
            ->whereDoesntHave('roles', function($query) {
                $query->where('roles.id', 3)
                ->whereNull('roles.deleted_at');
            })
            ->where('email', $request->email)
            ->with('roles')
            ->get();

            if(count($check_userRole) > 0) {
                $user = User::whereNull('deleted_at')
                ->where('email', $request->email)
                ->get();

                if (count($user) === 0) {
                    return response()->json([
                        'not_found' => true,
                        'message' => 'Your email not registered !'
                    ]);
                } else {

                    if (!Hash::check($request->password, $user[0]->password)) :
                        return response()->json([
                            'success' => false,
                            'message' => 'Your password its wrong'
                        ]);
                    else :
                        if ($this->forbidenIsUserLogin($user[0]->is_login)) {
                            $last_login = Carbon::parse($user[0]->last_login)->locale('id')->diffForHumans();
                            $login_data = Login::whereUserId($user[0]->id)
                            ->firstOrFail();

                            $dashboard = env('DASHBOARD_APP');

                            $data_event = [
                                'notif' => "Seseorang, baru saja mencoba mengakses akun Anda!",
                                'emailForbaiden' => $user[0]->email,
                            ];

                            $users = User::with('logins')
                            ->with('roles')
                            ->whereIsLogin($user[0]->is_login)
                            ->firstOrFail();

                            event(new EventNotification($data_event));

                            return response()->json([
                                'is_login' => true,
                                'message' => "Akun sedang digunakan {$last_login}, silahkan cek email anda!",
                                'quote' => 'Please check the notification again!',
                                'data' => $users
                            ]);
                        }

                        $token = $user[0]->createToken($user[0]->name)->accessToken;

                        $user_login = User::findOrFail($user[0]->id);
                        $user_login->is_login = 1;

                        if ($request->remember_me) {
                            $dates = Carbon::now()->addDays(7);
                            $user_login->expires_at = $dates;
                            $user_login->remember_token = $user[0]->createToken('RememberMe')->accessToken;
                        } else {
                            $user_login->expires_at = Carbon::now()->addRealMinutes(60);
                        }

                        $user_login->last_login = Carbon::now();
                        $user_login->save();
                        $user_id = $user_login->id;

                        $logins = new Login;
                        $logins->user_id = $user_id;
                        $logins->user_token_login = $token;
                        $logins->save();
                        $login_id = $logins->id;

                        $user[0]->logins()->sync($login_id);

                        $userIsLogin = User::whereId($user_login->id)
                        ->with('roles')
                        ->with('logins')
                        ->get();


                        $data_event = [
                            'type' => 'login',
                            'email' => $user[0]->email,
                            'role' => $user[0]->role,
                            'notif' => "{$user[0]->name}, baru saja login!",
                            'data' => $userIsLogin
                        ];

                        event(new EventNotification($data_event));

                        return response()->json([
                            'success' => true,
                            'message' => 'Login Success!',
                            'data'    => $userIsLogin,
                            'remember_token' => $user_login->remember_token
                        ]);
                    endif;
                }
            } else {
                $user = User::whereNull('deleted_at')
                ->where('email', $request->email)
                ->get();

                if(count($user) === 0) {
                    return response()->json([
                        'error' => true,
                        'message' => 'User not registered!'
                    ]);
                }
            }
        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'messge' => $th->getMessage()
            ]);
        }
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
