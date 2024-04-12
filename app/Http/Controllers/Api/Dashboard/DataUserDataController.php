<?php

namespace App\Http\Controllers\Api\Dashboard;

use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Events\EventNotification;
use App\Models\{User, Karyawan, Roles, Menu};
use App\Helpers\{WebFeatureHelpers};

class DataUserDataController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    private $helpers;

    public function __construct()
    {
        $this->helpers = new WebFeatureHelpers(null);
    }
    
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $minutes = 60;
            
            $user_login = Cache::remember('user:' . $user->email, $minutes, function () use ($user) {
                return User::select('id','name','photo','role','email','phone','is_login','expires_at','last_login')
                ->whereEmail($user->email)
                ->with(['roles:id,name', 'logins:id,user_token_login', 'karyawans:id,nama,level,alamat'])
                ->first();
            });

            $menus = Cache::remember('menus:' . $user_login->role, $minutes, function () use ($user_login) {
                return Menu::whereJsonContains('roles', $user_login->role)
                ->with([
                    'sub_menus' => function ($query) use ($user_login) {
                        $query->whereJsonContains('roles', $user_login->role)
                        ->with(['child_sub_menus' => function ($query) use ($user_login) {
                            $query->whereJsonContains('roles', $user_login->role);
                        }]);
                    }
                ])
                ->get();
            });

            $karyawans = Cache::remember('karyawans:' . $user->name, $minutes, function () use ($user) {
                return Karyawan::withTrashed()->whereNama($user->name)->get();
            });

            if (count($user_login->logins) === 0) {
                return response()->json([
                    'success' => false,
                    'not_login' => true,
                    'message' => 'Unauthenticated'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'User is login 🧑🏻‍💻',
                'data' => $user_login,
                'menus' => $menus,
                'karyawans' => $karyawans
            ], 200);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'nama' => 'required',
                'email' => 'required|email|unique:users',
                'password'  => [
                    'required',  Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                ],
                'role' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }


            $roleUser = Roles::findOrFail($request->role);
            $roleName = substr($roleUser->name, 0, 3);

            $lastRecord = Karyawan::where('kode', 'like', $roleName . '%')
            ->orderBy('kode', 'desc')
            ->first();

            $lastNumber = 0;

            if ($lastRecord) {
                $lastNumber = intval(substr($lastRecord->kode, strlen($roleName)));
            }


            $newNumber = $lastNumber + 1;
            $newCode = strtoupper($roleName) . sprintf('%03d', $newNumber);

            $initial = $this->helpers->initials($request->nama);
            $path = public_path().'/thumbnail_images/users/';
            $fontPath = public_path('fonts/Oliciy.ttf');
            $char = $initial;
            $newAvatarName = rand(12, 34353) . time() . '_avatar.png';
            $dest = $path . $newAvatarName;
            $createAvatar = WebFeatureHelpers::makeAvatar($fontPath, $dest, $char);

            $newUser = new User;
            $newUser->name = $request->nama;
            $newUser->photo = 'thumbnail_images/users/' . $newAvatarName;
            $newUser->role = $request->role;
            $newUser->email = $request->email;
            $newUser->password = Hash::make($request->password);
            $newUser->save();

            $userRole = Roles::findOrFail($newUser->role);
            $userKaryawan = new Karyawan;
            $userKaryawan->kode = $newCode;
            $userKaryawan->nama = $newUser->name;
            $userKaryawan->level = $userRole->name;
            $userKaryawan->save();

            $newUser->roles()->sync($userRole->id);
            $newUser->karyawans()->sync($newUser->id);

            $data_event = [
                'type' => 'add_data',
                'email' => $newUser->email,
                'role' => $newUser->role,
                'notif' => "{$newUser->name}, has been created ✨!"
            ];

            event(new EventNotification($data_event));

            $newUserCreated = User::whereId($newUser->id)
            ->with('roles')
            ->with('karyawans')
            ->get();

            return response()->json([
                'success' => true,
                'message' => 'New user has been created ✨!',
                'data'    => $newUserCreated
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $user = User::with('karyawans')
            ->with('roles')
            ->findOrFail($id);
            return response()->json([
                'success' => true,
                'message' => 'User detail 🧑🏻‍💻',
                'data' => $user
            ], 200);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
