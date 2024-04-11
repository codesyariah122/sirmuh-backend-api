<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\{Hash, Validator, Http};
use App\Models\{User, Login, Menu};
use App\Events\{EventNotification, ForbidenLoginEvent, LogoutEvent, LoginEvent};
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
            // ->whereDoesntHave('roles', function($query) {
            //     $query->where('roles.id', 3)
            //     ->whereNull('roles.deleted_at');
            // })
            ->where('email', $request->email)
            ->with('roles')
            ->get();

            if(count($check_userRole) > 0) {
                $user = User::select('id','name','photo','role','email','phone','is_login','expires_at','last_login','password')
                ->whereNull('deleted_at')
                ->where('email', $request->email)
                ->with(['roles:id,name', 'logins:id,user_token_login'])
                ->first();

                if (!$user) {
                    return response()->json([
                        'not_found' => true,
                        'message' => 'Your email not registered !'
                    ]);
                } else {
                    if (!Hash::check($request->password, $user->password)) :
                        return response()->json([
                            'success' => false,
                            'message' => 'Your password its wrong'
                        ]);
                    else :
                        if ($this->forbidenIsUserLogin($user->is_login)) {
                            $last_login = Carbon::parse($user->last_login)->locale('id')->diffForHumans();
                            $login_data = Login::where('user_id', $user->id)
                            ->first();

                            if($login_data === NULL) {
                                $removeUserIsLogin = User::findOrFail($user->id);
                                $removeUserIsLogin->is_login = 0;
                                $removeUserIsLogin->expires_at = NULL;
                                $removeUserIsLogin->save();

                                return response()->json([
                                    'error' => true,
                                    'message' => 'Silahkan login ulang 🤦‍♂️💥'
                                ]);
                            }

                            $dashboard = env('DASHBOARD_APP');

                            $data_event = [
                                'type' => 'forbiden',
                                'alert' => 'error',
                                'notif' => "Seseorang, baru saja mencoba mengakses akun Anda!",
                                'emailForbaiden' => $user->email,
                                'token' => $user->logins[0]->user_token_login,
                                'user' => $user
                            ];

                            $users = User::select('id','name','photo','role','email','phone','is_login','expires_at','last_login')
                            ->with(['roles:id,name', 'logins:id,user_token_login', 'karyawans:id,nama,level'])
                            ->where('email', $request->email)
                            ->whereIsLogin($user->is_login)
                            ->firstOrFail();

                            $menus = Menu::whereJsonContains('roles', $users->role)
                            ->with([
                                'sub_menus' => function ($query) use ($users) {
                                    $query->whereJsonContains('roles', $users->role)
                                    ->with('child_sub_menus');
                                }])
                            ->get();

                            event(new ForbidenLoginEvent($data_event));

                            return response()->json([
                                'is_login' => true,
                                'message' => "Akun sedang digunakan {$last_login}, silahkan cek email anda!",
                                'quote' => 'Please check the notification again!',
                                'data' => $users,
                                'menus' => $menus
                            ]);
                        } else {
                            $token = $user->createToken($user->name)->accessToken;

                            $user_login = User::findOrFail($user->id);
                            $user_login->is_login = 1;

                            if ($request->remember_me) {
                                $dates = Carbon::now()->addDays(31);
                                $user_login->expires_at = $dates;
                                $user_login->remember_token = $user->createToken('RememberMe')->accessToken;
                            } else {
                                $user_login->expires_at = Carbon::now()->addRealDays(1);
                            }

                            $user_login->last_login = Carbon::now();

                            $user_login->save();
                            $user_id = $user_login->id;

                            $logins = new Login;
                            $logins->user_id = $user_id;
                            $logins->user_token_login = $token;
                            $logins->save();
                            $login_id = $logins->id;

                            $user->logins()->sync($login_id);

                            $userIsLogin = User::select('id','name','photo','role','email','phone','is_login','expires_at','last_login')
                            ->whereId($user_login->id)
                            ->with(['roles:id,name', 'logins:id,user_token_login', 'karyawans:id,nama,level'])
                            ->first();

                            $menus = Menu::whereJsonContains('roles', $userIsLogin->role)
                            ->with([
                                'sub_menus' => function ($query) use ($userIsLogin) {
                                    $query->whereJsonContains('roles', $userIsLogin->role)
                                    ->with('child_sub_menus');
                                }])
                            ->get();

                            $data_event = [
                                'alert' => 'success',
                                'type' => 'login',
                                'email' => $user->email,
                                'role' => $user->role,
                                'notif' => "{$user->name}, baru saja login!",
                                'data' => $userIsLogin,
                                'showNotif' => $request->email === $user->email ? false : true
                            ];

                            event(new LoginEvent($data_event));

                            return response()->json([
                                'success' => true,
                                'message' => 'Login Success!',
                                'data'    => $userIsLogin,
                                'menus' => $menus,
                                'remember_token' => $user_login->remember_token
                            ]);
                        }
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
            throw $th;
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

            $userLogout = User::whereNull('deleted_at')
            ->with(['logins:id,user_token_login'])
            ->where('id', $user->id)
            ->first();
            
            $data_event = [
                'alert' => 'info',
                'type' => 'logout',
                'notif' => "{$user->name}, telah logout!",
                'email' => $user->email,
                'user' => Auth::user(),
                'showNotif' => $user->email === $userLogout->email ? false : true
            ];

            event(new LogoutEvent($data_event));

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

    public function force_logout(Request $request)
    {
        try {
            $token = $request->token;
            $loginData = Login::where('user_token_login', $token)->first();
            $user = User::findOrFail($loginData->user_id);
            $user->is_login = 0;
            $user->expires_at = null;
            $user->remember_token = null;
            $user->save();
            $delete_login = Login::whereUserId($user->id);
            $delete_login->delete();

            $userLogout = User::whereNull('deleted_at')
            ->with(['logins:id,user_token_login'])
            ->where('id', $user->id)
            ->first();
            
            $data_event = [
                'alert' => 'info',
                'type' => 'logout',
                'notif' => "{$user->name}, telah logout!",
                'email' => $user->email,
                'user' => Auth::user(),
                'showNotif' => $user->email === $userLogout->email ? false : true
            ];

            event(new LogoutEvent($data_event));

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
            $tableLogin = with(new Login)->getTable();
            DB::statement("ALTER TABLE $tableLogin AUTO_INCREMENT = 1;");
        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => 'Token is not defined 🥺'
            ]);
        }
    }
}
