<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use App\Events\{EventNotification};
use App\Helpers\{WebFeatureHelpers};
use App\Http\Resources\{ResponseDataCollect, RequestDataCollect};
use App\Models\{User, Karyawan, Roles, Menu};
use Auth;

class DataKaryawanController extends Controller
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
       $keywords = $request->query('keywords');
       $kode = $request->query('kode');
       $sortName = $request->query('sort_name');
       $sortType = $request->query('sort_type');

       if($keywords) {
        $karyawans = Karyawan::whereNull('deleted_at')
        ->whereNotIn('level', ['ADMIN', 'MASTER'])
        ->where('nama', 'like', '%'.$keywords.'%')
        ->select('id', 'nama', 'kode', 'level')
        ->with(['users:email,is_login'])
        ->orderByDesc('id', 'DESC')
        ->paginate(10);
    } else if($kode) {
        $karyawans = Karyawan::whereNull('deleted_at')
        ->select('id', 'nama', 'kode', 'level')
        ->where('kode', $kode)
        ->with(['users:email,is_login'])
        ->whereNotIn('level', ['ADMIN', 'MASTER'])
        ->orderByDesc('id', 'DESC')
        ->paginate(10);
    } else {
        if($sortName && $sortType) {
            $karyawans =  Karyawan::whereNull('deleted_at')
            ->select('id', 'nama', 'kode', 'level')
            ->with(['users:email,is_login'])
            ->whereNotIn('level', ['ADMIN', 'MASTER'])
            ->orderBy($sortName, $sortType)
            ->paginate(10);
        } else {
         $karyawans =  Karyawan::whereNull('deleted_at')
         ->select('id', 'nama', 'kode', 'level')
         ->with(['users:email,is_login'])
         ->whereNotIn('level', ['ADMIN', 'MASTER'])
         ->orderByDesc('id', 'DESC')
         ->paginate(10);
     }
 }

 return new ResponseDataCollect($karyawans);
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
            'jabatan' => 'required'
        ]);

           if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
            // $generateName = str_replace('', '', strtolower($request->nama));
        $generateName = str_replace([' ', 'bapak', 'ibu', 'pak', 'bu','om'], '', strtolower(trim($request->nama)));
        $emailByName = $generateName . '@sirmuh.com';
        $roleUser = Roles::findOrFail($request->jabatan);
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
        $newUser->role = $request->jabatan;
        $newUser->email = $emailByName;
        $newUser->password = Hash::make($roleUser->name."@123654");
        $newUser->save();

        $userRole = Roles::findOrFail($newUser->role);
        $userKaryawan = new Karyawan;
        $userKaryawan->kode = $newCode;
        $userKaryawan->nama = $newUser->name;
        $userKaryawan->level = $userRole->name;
        $userKaryawan->save();

        $newUser->roles()->sync($userRole->id);
        $newUser->karyawans()->sync($newUser->id);

        $userOnNotif = Auth::user();
        $data_event = [
            'type' => 'add_data',
            'email' => $newUser->email,
            'role' => $newUser->role,
            'notif' => "{$newUser->name}, has been created ✨!",
            'user' => $userOnNotif
        ];

        event(new EventNotification($data_event));

        $newUserCreated = User::whereId($newUser->id)
        ->with('roles')
        ->with('karyawans')
        ->get();

        return response()->json([
            'success' => true,
            'message' => 'New karyawan has been created ✨!',
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
            $karyawan = Karyawan::with('users')
            ->findOrFail($id);
            return response()->json([
                'success' => true,
                'message' => "Detail data karyawan {$karyawan->nama}✨!",
                'data' => $karyawan
            ]);
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
        try {
            $user = Auth::user();

            $userRole = Roles::findOrFail($user->role);

            if($userRole->name === "MASTER" || $userRole->name === "ADMIN") {
                $roles = Roles::findOrFail($request->jabatan);
                $update_karyawan = Karyawan::whereNull('deleted_at')
                ->findOrFail($id);
                $update_karyawan->nama = $request->nama ? $request->nama : $update_karyawan->nama;
                $update_karyawan->level = $roles->name;
                $update_karyawan->save();

                if($update_karyawan) {
                    $userOnNotif = Auth::user();
                    $data_event = [
                        'routes' => 'karyawan',
                        'alert' => 'success',
                        'type' => 'add-data',
                        'notif' => "{$update_karyawan->nama}, berhasil diupdate 🤙!",
                        'data' => $update_karyawan->nama,
                        'user' => $userOnNotif
                    ];

                    event(new EventNotification($data_event));

                    $updateDataKaryawan = Karyawan::findOrFail($update_karyawan->id);
                    return response()->json([
                        'success' => true,
                        'message' => "Karyawan dengan nama {$updateDataKaryawan->nama}, successfully added✨!",
                        'data' => $updateDataKaryawan
                    ]);
                }
            }else {
                return response()->json([
                    'error' => true,
                    'message' => "Hak akses tidak di ijinkan 📛"
                ]);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $user = Auth::user();

            $userRole = Roles::findOrFail($user->role);
            
            if($userRole->name === "MASTER" || $userRole->name === "ADMIN") {                
                $karyawan = Karyawan::whereNull('deleted_at')
                ->findOrFail($id);
                $karyawan->delete();
                $data_event = [
                    'alert' => 'error',
                    'routes' => 'karyawan',
                    'type' => 'removed',
                    'notif' => "{$karyawan->nama}, has move to trash, please check trash!",
                    'user' => Auth::user()
                ];

                event(new EventNotification($data_event));

                return response()->json([
                    'success' => true,
                    'message' => "Data supplier {$karyawan->nama} has move to trash, please check trash"
                ]);
            } else {
                return response()->json([
                    'error' => true,
                    'message' => "Hak akses tidak di ijinkan 📛"
                ]);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function update_password_user_karyawan(Request $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'new_password'  => [
                    'required', 'confirmed', Password::min(8)
                    ->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                ]
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $user = User::findOrFail($id);


            // if (!Hash::check($request->current_password, $user->password)) {
            //     return response()->json([
            //         'error' => true,
            //         'message' => 'The current password is incorrect!!'
            //     ]);
            // }

            $user->password = Hash::make($request->new_password);
            $user->save();

            $data_event = [
                'type' => 'change-password',
                'notif' => "Your password, has been changes!",
            ];

            event(new EventNotification($data_event));

            $user_has_update = User::with('karyawans')
            ->with('roles')
            ->findOrFail($user->id);

            return response()->json([
                'success' => true,
                'message' => "Your password successfully updates!",
                'data' => $user_has_update
            ]);

        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
