<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Exports\CampaignDataExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\ContextData;
use App\Models\{
    User, 
    Roles, 
    Bank, 
    Barang, 
    ItemPenjualan, 
    SatuanBeli, 
    SatuanJual, 
    Pembelian, 
    ItemPembelian, 
    Supplier, 
    Penjualan, 
    Pelanggan, 
    Perusahaan, 
    SetupPerusahaan,
    Kas,
    FakturTerakhir
};
use App\Events\{EventNotification};
use App\Helpers\{UserHelpers, WebFeatureHelpers};
use App\Http\Resources\ResponseDataCollect;
use Image;
use Auth;


class DataWebFiturController extends Controller
{

    private $helpers;

    public function __construct()
    {
        $this->helpers = new WebFeatureHelpers;
    }

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

                case 'DATA_PELANGGAN':
                $deleted = Pelanggan::onlyTrashed()
                ->select('id', 'kode', 'nama', 'alamat', 'telp', 'pekerjaan', 'tgl_lahir', 'saldo_piutang', 'point', 'sales', 'area', 'max_piutang', 'kota', 'rayon', 'saldo_tabungan')
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
                    'routes' => 'user-data',
                    'notif' => "{$name}, has been restored!",
                    'data' => $restored->deleted_at,
                    'user' => Auth::user()
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
                    'routes' => 'user-role',
                    'notif' => "{$name} has been restored!",
                    'data' => $restored->deleted_at,
                    'user' => Auth::user()
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
                    'routes' => 'bank-data',
                    'notif' => "Bank, {$name} has been restored!",
                    'data' => $restored->deleted_at,
                    'user' => Auth::user()
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
                    'routes' => 'data-barang',
                    'notif' => "Barang, {$name} has been restored!",
                    'data' => $restored->deleted_at,
                    'user' => Auth::user()
                ];
                break;

                case 'DATA_PELANGGAN':
                $restored_barang = Pelanggan::onlyTrashed()
                ->findOrFail($id);
                $restored_barang->restore();
                $restored = Pelanggan::findOrFail($id);
                $name = $restored->nama;
                $data_event = [
                    'alert' => 'info',
                    'type' => 'restored',
                    'routes' => 'data-pelanggan',
                    'notif' => "Pelanggan, {$name} has been restored!",
                    'data' => $restored->deleted_at,
                    'user' => Auth::user()
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
                    'routes' => 'barang',
                    'notif' => "User {$deleted->name} has permanently deleted!",
                    'data' => $deleted->deleted_at,
                    'user' => Auth::user()
                ];

                break;

                case 'DATA_BARANG':
                $deleted = Barang::onlyTrashed()
                ->findOrFail($id);

                $file_path = $deleted->photo;
                
                if($file_path !== NULL) {                    
                    if (Storage::disk('public')->exists($file_path)) {
                        Storage::disk('public')->delete($file_path);
                    }
                }

                $deleted->suppliers()->forceDelete();
                $deleted->forceDelete();

                $message = "Data barang, {$deleted->nama} has permanently deleted !";
                $data_event = [
                    'alert' => 'error',
                    'type' => 'destroyed',
                    'notif' => "Barang, {$deleted->nama} has permanently deleted!",
                    'data' => $deleted->deleted_at,
                    'user' => Auth::user()
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

                case 'DATA_PELANGGAN':
                $countTrash = Pelanggan::onlyTrashed()
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
                $user_per_role = $this->helpers;
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

    public function toTheBest($type)
    {
        try {
            switch($type) {
                case "barang":
                $title = "barang terlaris";
                $icon = " 🛒🛍️";
                $label = "Total Quantity";
                $result = ItemPenjualan::barangTerlaris();
                break;

                case "supplier":
                $title = "supplier terbaik";
                $icon = "🤝🏼";
                $label = "Jumlah Quantity";
                $result = Supplier::select('supplier.kode', 'supplier.nama', DB::raw('COALESCE(SUM(pembelian.jumlah), 0) as total_pembelian'))
                ->leftJoin('pembelian', 'supplier.kode', '=', 'pembelian.supplier')
                ->whereNull('supplier.deleted_at')
                ->groupBy('supplier.kode', 'supplier.nama')
                ->orderByDesc('total_pembelian')
                ->take(10)
                ->get();
                break;

                case "pelanggan":
                $title = "pelanggan terbaik";
                $icon = "🎖️";
                $label = "Total Pembelian";
                $result = Pelanggan::select('pelanggan.kode', 'pelanggan.nama', DB::raw('COALESCE(SUM(penjualan.subtotal), 0) as total_penjualan'))
                ->leftJoin('penjualan', 'pelanggan.kode', '=', 'penjualan.pelanggan')
                ->whereNull('pelanggan.deleted_at')
                ->groupBy('pelanggan.kode', 'pelanggan.nama')
                ->orderBy('total_penjualan', 'DESC')
                ->take(10)
                ->get();
                break;
            }

            return response()->json([
                'success' => true,
                'label' => $label,
                'message' => "10 {$title} {$icon}",
                'data' => $result,
            ]);
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

    public function satuanJual(Request $request) {
        try {
            $keywords = $request->query('keywords');

            if($keywords) {
                $barangs = SatuanJual::whereNull('deleted_at')
                ->select('id', 'nama')
                ->where('nama', 'like', '%'.$keywords.'%')
                ->orderByDesc('id', 'DESC')
                ->paginate(10);
            } else {
                $barangs =  SatuanJual::whereNull('deleted_at')
                ->select('id', 'nama')
                ->orderByDesc('id', 'DESC')
                ->paginate(10);
            }

            return new ResponseDataCollect($barangs);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function calculateBarang()
    {
        $penjualanHarian = Barang::select(DB::raw('DATE(created_at) as tanggal'), DB::raw('SUM(jumlah_terjual) as total_penjualan'))
        ->groupBy('tanggal')
        ->get();

        var_dump($penjualanHarian); die;
        
    }

    public function loadForm($diskon, $ppn, $total)
    {
        $helpers = $this->helpers;
        $diskonAmount = intval($diskon) / 100 * intval($total);
        $ppnAmount = intval($ppn) / 100 * intval($total);

        $totalAfterDiscount = intval($total) - $diskonAmount;
        $totalWithPPN = $totalAfterDiscount + $ppnAmount;

        $data  = [
            'totalrp' => $this->helpers->format_uang($totalWithPPN),
            'diskonrp' => $this->helpers->format_uang($diskonAmount),
            'ppnrp' => $this->helpers->format_uang($ppnAmount),
            'total_after_diskon' => $this->helpers->format_uang($totalAfterDiscount),
            'total_with_ppn' => $this->helpers->format_uang($totalWithPPN),
            'bayar' => $totalWithPPN,
            'bayarrp' => $this->helpers->format_uang((intval($diskon) && intval($ppn)) ? $totalWithPPN : $total),
            'terbilang' => ucwords($this->helpers->terbilang($totalWithPPN). ' Rupiah')
        ];

        return new ResponseDataCollect($data);
    }

    public function generateReference($type)
    {
        $perusahaan = SetupPerusahaan::with('tokos')->findOrFail(1);
        $currentDate = now()->format('ymd');
        $randomNumber = sprintf('%05d', mt_rand(0, 99999));

        switch($type) {
            case "pembelian-langsung": 
            case "purchase-order":
            $generatedCode = $perusahaan->kd_pembelian .'-'. $currentDate . $randomNumber;
            break;
            case "penjualan-toko":
            $generatedCode = $perusahaan->kd_penjualan_toko .'-'. $currentDate . $randomNumber;
            break;
        }

        $data = [
            'ref_code' => $generatedCode
        ];

        return new ResponseDataCollect($data);
    }

    public function loadFormPenjualan($diskon = 0, $total = 0, $bayar = 0)
    {
        $diterima   = $total - ($diskon / 100 * $total);
        $kembali = ($bayar != 0) ? $bayar - $diterima : 0;
        $data    = [
            'totalrp' => $this->helpers->format_uang($total),
            'bayar' => $bayar,
            'bayarrp' => $this->helpers->format_uang($bayar),
            'terbilang' => ucwords($this->helpers->terbilang($bayar). ' Rupiah'),
            'kembalirp' => $this->helpers->format_uang($kembali),
            'kembali_terbilang' => ucwords($this->helpers->terbilang($kembali). ' Rupiah'),
        ];

        return response()->json($data);
    }

    public function update_stok_barang(Request $request)
    {
        try {
            $barangs = $request->barangs;
            $type = $request->type;

            switch($type) {
                case "pembelian-langsung":
                foreach($barangs as $barang) {
                    $updateBarang = Barang::findOrFail($barang['id']);
                    $qtyBarang = intval($barang['qty']);
                    $stokBarang = intval($updateBarang->toko);
                    $updateBarang->toko = $stokBarang + $qtyBarang;
                    $updateBarang->save();
                }
                break;
                case "penjualan-toko":
                foreach($barangs as $barang) {
                    $updateBarang = Barang::findOrFail($barang['id']);
                    $qtyBarang = intval($barang['qty']);
                    $stokBarang = intval($updateBarang->toko);
                    $updateBarang->toko = $stokBarang - $qtyBarang;
                    $updateBarang->save();
                }
                break;
            }

            return response()->json([
                'success' => true,
                'message' => 'Stok barang update!'
            ]);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function update_item_pembelian(Request $request)
    {
        try {
            $draft = $request->draft;
            $kode = $request->kode;
            $barangs = $request->barangs;
            $lastItemPembelianId = NULL;
            if($draft) {
                foreach($barangs as $barang) {
                    $dataBarang = Barang::whereKode($barang['kode'])->firstOrFail();
                    // Update Barang
                    $existingItem = ItemPembelian::where('kode', $kode)
                    ->where('kode_barang', $dataBarang->kode)
                    ->where('draft', 1)
                    ->first();
                    if ($existingItem) {
                            // Jika sudah ada, update informasi yang diperlukan
                        $existingItem->qty = intval($barang['qty']);
                        $existingItem->harga_beli = intval($barang['harga_beli']);
                        $existingItem->subtotal = $barang['harga_beli'] * $barang['qty'];

                            // Update atribut lainnya sesuai kebutuhan
                        $existingItem->save();
                        $lastItemPembelianId = $existingItem->id;
                    } else {
                        $draftItemPembelian = new ItemPembelian;
                        $draftItemPembelian->kode = $kode;
                        $draftItemPembelian->draft = $draft;
                        $draftItemPembelian->kode_barang = $dataBarang->kode;
                        $draftItemPembelian->nama_barang = $dataBarang->nama;
                        $draftItemPembelian->satuan = $dataBarang->satuan;
                        $draftItemPembelian->qty = $barang['qty'];
                        $draftItemPembelian->isi = $dataBarang->isi;
                        $draftItemPembelian->nourut = $barang['nourut'];
                        $draftItemPembelian->harga_beli = $barang['harga_beli'] ?? $dataBarang->hpp;
                        $draftItemPembelian->harga_toko = $dataBarang->harga_toko;
                        $draftItemPembelian->harga_cabang = $dataBarang->harga_cabang;
                        $draftItemPembelian->harga_partai = $dataBarang->harga_partai;
                        $draftItemPembelian->subtotal = $dataBarang->hpp * $barang['qty'];
                        $draftItemPembelian->isi = $dataBarang->isi;

                        if($barang['diskon']) {
                            $total = $dataBarang->hpp * $barang['qty'];
                            $diskonAmount = $barang['diskon'] / 100 * $total;
                            $totalSetelahDiskon = $total - $diskonAmount;
                            $draftItemPembelian->harga_setelah_diskon = $totalSetelahDiskon;
                        }

                            // if($barang['ppn']) {
                            //     $total = $dataBarang->hpp * $barang['qty'];
                            //     $ppnAmount = $barang['ppn'] / 100 * $total;
                            //     $totalSetelahPpn = $total - $diskonAmount;
                            //     $draftItemPembelian->harga_setelah_diskon = $totalSetelahDiskon;
                            // }

                        $draftItemPembelian->save();
                        $lastItemPembelianId = $draftItemPembelian->id;
                    }
                }
                    return response()->json([
                        'draft' => true,
                        'message' => 'Draft item pembelian successfully updated!',
                        'data' => $kode,
                        'itempembelian_id' => $lastItemPembelianId
                    ], 200);
                } else {
                 return response()->json([
                    'failed' => true,
                    'message' => 'Draft item pembelian has no success updated!',
                    'data' => $kode
                ], 203);
             }

         } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function list_draft_itempembelian($kode)
    {
        try {
            if($kode) {                
                $listDrafts = ItemPembelian::whereDraft(1)
                ->select("id", "kode", "nourut", "kode_barang", "nama_barang", "satuan", "qty", "harga_beli", "harga_toko", "diskon", "subtotal", "expired")
                ->whereKode($kode)
                ->get();
                return new ResponseDataCollect($listDrafts);
            } else {
                return response()->json([
                    'failed' => true,
                    'message' => 'Draft item pembelian has no success updated!'
                ], 203);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function delete_item_pembelian($id)
    {
        try {
            $itemPembelian = ItemPembelian::findOrFail($id);
            $itemPembelian->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Item pembelian successfully deleted!'
            ], 200);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function update_item_penjualan(Request $request)
    {
        try {
            $draft = $request->draft;
            $kode = $request->kode;
            $barangs = $request->barangs;

            if($draft) {
                foreach($barangs as $barang) {
                    $dataBarang = Barang::whereKode($barang['kode'])->firstOrFail();
                    $existingItem = ItemPenjualan::where('kode', $kode)
                    ->where('kode_barang', $dataBarang->kode)
                    ->where('draft', 1)
                    ->first();
                    if ($existingItem) {
                        $existingItem->qty = intval($barang['qty']);
                        $existingItem->subtotal = $dataBarang->hpp * $barang['qty'];
                        $existingItem->diskon = $barang['diskon'];
                        $existingItem->diskon_rupiah = $barang['diskon_rupiah'];

                        $existingItem->save();
                    } else {
                        $draftItemPembelian = new ItemPenjualan;
                        $draftItemPembelian->kode = $kode;
                        $draftItemPembelian->draft = $draft;
                        $draftItemPembelian->kode_barang = $dataBarang->kode;
                        $draftItemPembelian->nama_barang = $dataBarang->nama;
                        $draftItemPembelian->satuan = $dataBarang->satuan;
                        $draftItemPembelian->qty = $barang['qty'];
                        $draftItemPembelian->isi = $dataBarang->isi;
                        $draftItemPembelian->nourut = $barang['nourut'];
                        $draftItemPembelian->harga = $dataBarang->hpp;
                        $draftItemPembelian->diskon = $barang['diskon'];
                        $draftItemPembelian->hpp = $dataBarang->hpp;
                        $draftItemPembelian->diskon_rupiah = $barang['diskon_rupiah'];
                        $draftItemPembelian->subtotal = $dataBarang->hpp * $barang['qty'];
                        $draftItemPembelian->isi = $dataBarang->isi;

                        if($barang['diskon']) {
                            $total = $dataBarang->hpp * $barang['qty'];
                            $diskonAmount = $barang['diskon'] / 100 * $total;
                            $totalSetelahDiskon = $total - $diskonAmount;
                            $draftItemPembelian->harga_setelah_diskon = $totalSetelahDiskon;
                        }

                                // if($barang['ppn']) {
                                //     $total = $dataBarang->hpp * $barang['qty'];
                                //     $ppnAmount = $barang['ppn'] / 100 * $total;
                                //     $totalSetelahPpn = $total - $diskonAmount;
                                //     $draftItemPembelian->harga_setelah_diskon = $totalSetelahDiskon;
                                // }

                        $draftItemPembelian->save();
                    }
                }
                return response()->json([
                    'draft' => true,
                    'message' => 'Draft item pembelian successfully updated!',
                    'data' => $kode
                ], 200);
            } else {
             return response()->json([
                'failed' => true,
                'message' => 'Draft item pembelian has no success updated!',
                'data' => $kode
            ], 203);
         }

     } catch (\Throwable $th) {
        throw $th;
    }
    }

    public function list_draft_itempenjualan($kode)
    {
        try {
            if($kode) {                
                $listDrafts = ItemPembelian::whereDraft(1)
                ->select("id", "kode", "nourut", "nama_barang", "satuan", "qty", "harga_beli", "harga_toko", "diskon", "subtotal", "expired")
                ->whereKode($kode)
                ->get();
                return new ResponseDataCollect($listDrafts);
            } else {
                return response()->json([
                    'failed' => true,
                    'message' => 'Draft item pembelian has no success updated!'
                ], 204);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function delete_item_penjualan($id)
    {
        try {
            $itemPembelian = ItemPenjualan::findOrFail($id);
            $itemPembelian->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Item penjualan successfully deleted!'
            ], 200);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function check_saldo(Request $request, $id) 
    {
        try {
            $entitas = intval($request->entitas);
            $check_saldo = Kas::findOrFail($id);
            $saldo = intval($check_saldo->saldo);
            if($saldo < $entitas) {
                return response()->json([
                    'error' => true,
                    'message' => 'Saldo tidak mencukupi!'
                ], 202);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function update_faktur_terakhir(Request $request)
    {
        try {
            $existingFaktur = FakturTerakhir::whereFaktur($request->faktur)
            ->first();
            $today = now()->toDateString();
            if($existingFaktur === NULL) {
                $updateFakturTerakhir = new FakturTerakhir;
                $updateFakturTerakhir->faktur = $request->faktur;
                $updateFakturTerakhir->save();

            } else {
               $updateFakturTerakhir = FakturTerakhir::whereFaktur($request->faktur)
               ->first();
               $updateFakturTerakhir->faktur = $request->faktur;
               $updateFakturTerakhir->tanggal = $today;
               $updateFakturTerakhir->save();

           }
           return response()->json([
            'success' => true,
            'message' => 'Faktur terakhir terupdate!'
        ], 200);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
