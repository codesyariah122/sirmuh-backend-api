<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Exports\CampaignDataExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\ContextData;
use App\Models\{User, Roles, Bank, Barang, ItemPenjualan, SatuanBeli, SatuanJual};
use App\Events\{EventNotification};
use App\Helpers\{UserHelpers, WebFeatureHelpers};
use App\Http\Resources\ResponseDataCollect;
use Image;


class DataWebFiturController extends Controller
{

    public function web_data()
    {
        try {
            $my_context = new ContextData;
            $ownerInfo = $my_context->getInfoData('COD(O.t)');
            return response()->json([
                'message' => 'Owner data info',
                'data' => $ownerInfo
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function trash(Request $request)
    {
        try {
            $dataType = $request->query('type');
            switch ($dataType):
                case 'USER_DATA':
                $roleType = $request->query('roles');
                if($roleType === 'DASHBOARD') {
                    $deleted =  User::onlyTrashed()
                    ->where('role', '<', 3)
                    ->with('profiles', function($profile) {
                        return $profile->withTrashed();
                    })
                    ->with('roles')
                    ->paginate(10);
                } else {                
                    $deleted = User::onlyTrashed()
                    ->where('role', '>', 2)
                    ->with('profiles', function($profile) {
                        return $profile->withTrashed();
                    })
                    ->with('roles')
                    ->paginate(10);
                }
                break;

                case 'ROLE_USER':
                $deleted = Roles::onlyTrashed()
                ->with('users')
                ->paginate(10);
                break;

                case 'BANK_DATA':
                $deleted = Bank::onlyTrashed()
                ->paginate(10);
                break;

                case 'DATA_BARANG':
                $deleted = Barang::onlyTrashed()
                ->select('id', 'kode', 'nama', 'photo', 'kategori', 'satuanbeli', 'satuan', 'isi', 'toko', 'gudang', 'hpp', 'harga_toko', 'diskon', 'supplier', 'kode_barcode', 'tgl_terakhir', 'ada_expired_date', 'expired')
                ->paginate(10);
                break;

                default:
                $deleted = [];
                break;
            endswitch;

            return response()->json([
                'success' => true,
                'message' => 'Deleted data on trashed!',
                'data' => $deleted
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function restoreTrash(Request $request, $id)
    {
        try {
            $dataType = $request->query('type');

            switch ($dataType):
                case 'USER_DATA':
                $restored_user = User::withTrashed()
                ->with('profiles', function($profile) {
                    return $profile->withTrashed();
                })
                ->findOrFail($id);
                $restored_user->restore();
                $restored_user->profiles()->restore();
                $restored = User::findOrFail($id);
                $name = $restored->name;

                $data_event = [
                    'alert' => 'info',
                    'type' => 'restored',
                    'notif' => "{$name}, has been restored!",
                    'data' => $restored->deleted_at
                ];
                break;

                case 'ROLE_USER':
                $restored_role = Roles::with(['users' => function ($user) {
                    return $user->withTrashed()->with('profiles')->get();
                }])
                ->withTrashed()
                ->findOrFail(intval($id));


                $prepare_userToProfiles = User::withTrashed()
                ->where('role', intval($id))
                ->with(['profiles' => function ($query) {
                    $query->withTrashed();
                }])
                ->get();

                foreach ($prepare_userToProfiles as $user) {
                    foreach ($user->profiles as $profile) {
                        $profile->restore();
                    }
                }

                $restored_role->restore();
                $restored_role->users()->restore();


                $restored = Roles::with(['users' => function ($query) {
                    $query->with('profiles');
                }])
                ->findOrFail($id);
                $name = $restored->name;

                $data_event = [
                    'alert' => 'info',
                    'type' => 'restored',
                    'notif' => "{$name} has been restored!",
                    'data' => $restored->deleted_at
                ];
                break;

                case 'BANK_DATA':
                $restored_bank = Bank::onlyTrashed()
                ->findOrFail($id);
                $restored_bank->restore();
                $restored = Bank::findOrFail($id);
                $name = $restored->name;
                $data_event = [
                    'alert' => 'info',
                    'type' => 'restored',
                    'notif' => "Bank, {$name} has been restored!",
                    'data' => $restored->deleted_at
                ];
                break;

                case 'DATA_BARANG':
                $restored_barang = Barang::onlyTrashed()
                ->findOrFail($id);
                $restored_barang->restore();
                $restored = Barang::findOrFail($id);
                $name = $restored->nama;
                $data_event = [
                    'alert' => 'info',
                    'type' => 'restored',
                    'notif' => "Barang, {$name} has been restored!",
                    'data' => $restored->deleted_at
                ];
                break;

                default:
                $restored = [];
            endswitch;

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => $data_event['notif'],
                'data' => $restored
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function deletePermanently(Request $request, $id)
    {
        try {
            $dataType = $request->query('type');
            switch ($dataType):
                case 'USER_DATA':

                $deleted = User::onlyTrashed()
                ->with('profiles', function($profile) {
                    return $profile->withTrashed();
                })
                ->where('id', $id)
                ->firstOrFail();

                if ($deleted->profiles[0]->photo !== "" && $deleted->profiles[0]->photo !== NULL) {
                    $old_photo = public_path() . '/' . $deleted->profiles[0]->photo;
                    $file_exists = public_path() . '/' . $deleted->profiles[0]->photo;

                    if($old_photo && file_exists($file_exists)) {
                        unlink($old_photo);
                    }
                }

                $deleted->profiles()->forceDelete();
                $deleted->forceDelete();

                $message = "Data {$deleted->name} has permanently deleted !";

                $tableUser = with(new User)->getTable();
                $tableProfile = with(new Profile)->getTable();
                DB::statement("ALTER TABLE $tableUser AUTO_INCREMENT = 1;");
                DB::statement("ALTER TABLE $tableProfile AUTO_INCREMENT = 1;");


                $data_event = [
                    'alert' => 'error',
                    'type' => 'destroyed',
                    'notif' => "User {$deleted->name} has permanently deleted!",
                    'data' => $deleted->deleted_at
                ];

                break;

                case 'DATA_BARANG':
                $deleted = Barang::onlyTrashed()
                ->findOrFail($id);

                $file_path = $deleted->photo;
                
                if (Storage::disk('public')->exists($file_path)) {
                    Storage::disk('public')->delete($file_path);
                }

                $deleted->suppliers()->forceDelete();
                $deleted->forceDelete();

                $message = "Data barang, {$deleted->nama} has permanently deleted !";
                $data_event = [
                    'alert' => 'error',
                    'type' => 'destroyed',
                    'notif' => "Barang, {$deleted->nama} has permanently deleted!",
                    'data' => $deleted->deleted_at
                ];
                break;

                default:
                $deleted = [];
            endswitch;


            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $deleted
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => $th->getMessage(),
            ]);
        }
    }

    public function totalTrash(Request $request)
    {
        try {
            $type = $request->query('type');
            switch ($type) {
                case 'DATA_BARANG':
                $countTrash = Barang::onlyTrashed()
                ->get();
                break;
                default:
                $countTrash = [];
            }

            return response()
            ->json([
                'success' => true,
                'message' => $type . ' Trash',
                'data' => count($countTrash)
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ]);
        }
    }

    public function totalDataSendResponse($data)
    {
        return response()->json([
            'success' => true,
            'message' => $data['message'],
            'total' => $data['total'],
            'data' => isset($data['data']) ? $data['data'] : null,
        ], 200);
    }

    public function totalData(Request $request)
    {
        try {
            $type = $request->query('type');

            switch ($type) {
                case "TOTAL_USER":
                $totalData = User::whereNull('deleted_at')
                ->get();
                $totals = count($totalData);
                $user_per_role = new WebFeatureHelpers;
                $owner = $user_per_role->get_total_user('OWNER');
                $admin = $user_per_role->get_total_user('ADMIN');
                $kasir = $user_per_role->get_total_user('KASIR');
                $kasirGudang = $user_per_role->get_total_user('KASIR_GUDANG');
                $gudang = $user_per_role->get_total_user('GUDANG');
                $produksi = $user_per_role->get_total_user('PRODUKSI');
                $user_online = $user_per_role->user_online();
                $sendResponse = [
                    'type' => 'TOTAL_USER',
                    'message' => 'Total data user',
                    'total' => $totals,
                    'data' => [
                        'user_online' => $user_online,
                        'admin' => $admin,
                        'kasir' => $kasir,
                        'kasirGudang' => $kasirGudang,
                        'gudang' => $gudang,
                        'produksi' => $produksi
                    ]
                ];
                return $this->totalDataSendResponse($sendResponse);
                break;

                case "TOTAL_BARANG":
                $totalData = Barang::whereNull('deleted_at')
                ->get();
                $totals = count($totalData);
                $barangLimits = Barang::whereNull('deleted_at')
                ->orderBy('toko')
                // ->where('toko', 'NOT LIKE', '0.%') 
                ->where('toko', 'LIKE', '-%')
                ->limit(10)
                ->select('kode', 'nama', 'satuan', 'toko')
                ->get();
                $sendResponse = [
                    'type' => 'TOTAL_BARANG',
                    'message' => 'Total data barang',
                    'total' => $totals,
                    'data' => [
                        'barang_limits' => $barangLimits
                    ]
                ];
                return $this->totalDataSendResponse($sendResponse);
                break;

                default:
                $totalData = [];
            }
        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ]);
        }
    }


    public function initials($name)
    {
        preg_match('/(?:\w+\. )?(\w+).*?(\w+)(?: \w+\.)?$/', $name, $result);
        $initial = strtoupper($result[1][0] . $result[2][0]);
        return $initial;
    }

    public function upload_profile_picture(Request $request)
    {
        try {
            $user_id = $request->user()->id;

            $update_user = User::with('profiles')
            ->findOrFail($user_id);

            $user_photo = $update_user->profiles[0]->photo;
            
            $image = $request->file('photo');

            if ($image !== '' && $image !== NULL) {
                $nameImage = $image->getClientOriginalName();
                $filename = pathinfo($nameImage, PATHINFO_FILENAME);

                $extension = $request->file('photo')->getClientOriginalExtension();

                $filenametostore = Str::random(12) . '_' . time() . '.' . $extension;

                $thumbImage = Image::make($image->getRealPath())->resize(100, 100);
                $thumbPath = public_path() . '/thumbnail_images/users/' . $filenametostore;

                if ($user_photo !== '' && $user_photo !== NULL) {
                    $old_photo = public_path() . '/' . $user_photo;
                    unlink($old_photo);
                }

                Image::make($thumbImage)->save($thumbPath);
                $new_profile = Profile::findOrFail($update_user->profiles[0]->id);
                
                $new_profile->photo = "thumbnail_images/users/" . $filenametostore;
                $new_profile->save();

                $profile_has_update = Profile::with('users')->findOrFail($update_user->profiles[0]->id);

                $data_event = [
                    'type' => 'update-photo',
                    'notif' => "{$update_user->name} photo, has been updated!"
                ];

                event(new UpdateProfileEvent($data_event));

                return response()->json([
                    'success' => true,
                    'message' => 'Profile photo has been updated',
                    'data' => $profile_has_update
                ]);
            } else {
                return response()->json([
                    'error' => true,
                    'message' => 'please choose files!!'
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ]);
        }
    }

    public function update_user_profile(Request $request)
    {
        try {

            $username = $request->user()->profiles[0]->username;

            $prepare_profile = Profile::whereUsername($username)->with('users')->firstOrFail();

            $check_avatar = explode('_', $prepare_profile->photo);

            $user_id = $prepare_profile->users[0]->id;
            $update_user = User::findOrFail($user_id);

            // var_dump($check_avatar[2]); die;

            $update_user->name = $request->name ? $request->name : $update_user->name;
            $update_user->email = $request->email ? $request->email : $update_user->email;
            $update_user->phone = $request->phone ? $request->phone : $update_user->phone;
            $update_user->status = $request->status ? $request->status : $update_user->status;
            $update_user->save();

            $user_profiles = User::with('profiles')->findOrFail($update_user->id);

            $update_profile = Profile::findOrFail($user_profiles->profiles[0]->id);
            // $update_profile->username = $request->name ? trim(preg_replace('/\s+/', '_', $request->name)) : $user_profiles->profiles[0]->username;

            if ($check_avatar[2] === "avatar.png") {
                $old_photo = public_path() . '/' . $update_user->profiles[0]->photo;
                unlink($old_photo);

                $initial = $this->initials($update_user->name);
                $path = public_path() . '/thumbnail_images/users/';
                $fontPath = public_path('fonts/Oliciy.ttf');
                $char = $initial;
                $newAvatarName = rand(12, 34353) . time() . '_avatar.png';
                $dest = $path . $newAvatarName;

                $createAvatar = makeAvatar($fontPath, $dest, $char);
                $photo = $createAvatar == true ? $newAvatarName : '';

                // store into database field photo
                $save_path = 'thumbnail_images/users/';
                $update_profile->photo = $save_path . $photo;
            }

            $update_profile->about = $request->about ? $request->about : $user_profiles->profiles[0]->about;
            $update_profile->address = $request->address ? $request->address : $user_profiles->profiles[0]->address;
            $update_profile->post_code = $request->post_code ? $request->post_code : $user_profiles->profiles[0]->post_code;
            $update_profile->city = $request->city ? $request->city : $user_profiles->profiles[0]->city;
            $update_profile->district = $request->district ? $request->district : $user_profiles->profiles[0]->district;
            $update_profile->province = $request->province ? $request->province : $user_profiles->profiles[0]->province;
            $update_profile->country = $request->country ? $request->country : $user_profiles->profiles[0]->country;
            $update_profile->save();

            $new_user_updated = User::whereId($update_user->id)->with('profiles')->get();

            $data_event = [
                'type' => 'update-profile',
                'notif' => "{$update_user->name}, has been updated!",
            ];

            event(new UpdateProfileEvent($data_event));


            return response()->json([
                'success' => true,
                'message' => "Update user {$update_user->name}, berhasil",
                'data' => $new_user_updated
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ]);
        }
    }

    public function change_password(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'current_password'      => 'required',
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

            $user = $request->user();

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'error' => true,
                    'message' => 'The current password is incorrect!!'
                ]);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            $data_event = [
                'type' => 'change-password',
                'notif' => "Your password, has been changes!",
            ];

            event(new UpdateProfileEvent($data_event));

            $user_has_update = User::with('profiles')
            ->with('roles')
            ->findOrFail($user->id);

            return response()->json([
                'success' => true,
                'message' => "Your password successfully updates!",
                'data' => $user_has_update
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ]);
        }
    }

    /**
     * Display a listing of the resource.
     * @author Puji Ermanto <pujiermanto@gmail.com>
     * @return \Illuminate\Http\Response
     */


    public function get_unique_code()
    {
        try {

            $uniquecode = $this->webfitur->get_unicode();

            return response()->json([
                'success' => true,
                'message' => 'Uniqcode Data',
                'data' => $uniquecode
            ]);

        } catch (\Throwable $th) {
            return response()->json([
                'error' => true,
                'message' => $th->getMessage()
            ]);
        }
    }

    public function satuanBeli(Request $request) {
        try {
            $keywords = $request->query('keywords');

            if($keywords) {
                $barangs = SatuanBeli::whereNull('deleted_at')
                ->select('id', 'nama')
                ->where('nama', 'like', '%'.$keywords.'%')
                ->orderByDesc('id', 'DESC')
                ->paginate(10);
            } else {
                $barangs =  SatuanBeli::whereNull('deleted_at')
                ->select('id', 'nama')
                ->orderByDesc('id', 'DESC')
                ->paginate(10);
            }

            return new ResponseDataCollect($barangs);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
