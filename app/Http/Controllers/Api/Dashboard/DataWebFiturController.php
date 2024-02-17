<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Log;
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
    FakturTerakhir,
    Karyawan,
    Biaya
};
use App\Events\{EventNotification};
use App\Helpers\{UserHelpers, WebFeatureHelpers};
use App\Http\Resources\ResponseDataCollect;
use Intervention\Image\Facades\Image;
use Auth;


class DataWebFiturController extends Controller
{

    private $helpers,$user_helpers;

    public function __construct()
    {
        $this->helpers = new WebFeatureHelpers;
        $this->user_helpers = new UserHelpers;
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

                case 'DATA_SUPPLIER':
                $deleted = Supplier::onlyTrashed()
                ->select('id', 'nama', 'kode', 'alamat', 'kota', 'telp', 'fax', 'email', 'saldo_piutang')
                ->paginate(10);
                break;

                case 'DATA_KARYAWAN':
                $deleted = Karyawan::onlyTrashed()
                ->select('id', 'nama', 'kode', 'level')
                ->with('users')
                ->paginate(10);
                break;

                case 'DATA_KAS':
                $deleted = Kas::onlyTrashed()
                ->select('id', 'kode', 'nama', 'saldo')
                ->paginate(10);
                break;

                case 'DATA_BIAYA':
                $deleted = Biaya::onlyTrashed()
                ->select('id', 'kode', 'nama', 'saldo')
                ->paginate(10);
                break;

                case 'PEMBELIAN_LANGSUNG':
                $deleted = Pembelian::onlyTrashed()
                ->select('id', 'kode', 'tanggal', 'kode_kas', 'jumlah','bayar','diterima','lunas','operator')
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

                case 'DATA_SUPPLIER':
                $restored_barang = Supplier::onlyTrashed()
                ->findOrFail($id);
                $restored_barang->restore();
                $restored = Supplier::findOrFail($id);
                $name = $restored->nama;
                $data_event = [
                    'alert' => 'info',
                    'type' => 'restored',
                    'routes' => 'supplier',
                    'notif' => "Supplier, {$name} has been restored!",
                    'data' => $restored->deleted_at,
                    'user' => Auth::user()
                ];
                break;

                case 'DATA_KARYAWAN':
                $restored_karyawan = Karyawan::onlyTrashed()
                ->findOrFail($id);
                $restored_karyawan->restore();
                $restored = Karyawan::findOrFail($id);
                $name = $restored->nama;
                $data_event = [
                    'alert' => 'info',
                    'type' => 'restored',
                    'routes' => 'karyawan',
                    'notif' => "Karyawan, {$name} has been restored!",
                    'data' => $restored->deleted_at,
                    'user' => Auth::user()
                ];
                break;

                case 'DATA_KAS':
                $restored_kas = Kas::onlyTrashed()
                ->findOrFail($id);
                $restored_kas->restore();
                $restored = Kas::findOrFail($id);
                $name = $restored->nama;
                $data_event = [
                    'alert' => 'info',
                    'type' => 'restored',
                    'routes' => 'kas',
                    'notif' => "Kas, {$name} has been restored!",
                    'data' => $restored->deleted_at,
                    'user' => Auth::user()
                ];
                break;

                case 'DATA_BIAYA':
                $restored_biaya = Biaya::onlyTrashed()
                ->findOrFail($id);
                $restored_biaya->restore();
                $restored = Biaya::findOrFail($id);
                $name = $restored->nama;
                $data_event = [
                    'alert' => 'info',
                    'type' => 'restored',
                    'routes' => 'biaya',
                    'notif' => "Biaya, {$name} has been restored!",
                    'data' => $restored->deleted_at,
                    'user' => Auth::user()
                ];
                break;

                case 'PEMBELIAN_LANGSUNG':
                $restored_biaya = Pembelian::onlyTrashed()
                ->findOrFail($id);
                $restored_biaya->restore();
                $restored = Pembelian::findOrFail($id);
                $name = $restored->kode;
                $data_event = [
                    'alert' => 'info',
                    'type' => 'restored',
                    'routes' => 'pembelian-langsung',
                    'notif' => "Pembelian, {$name} has been restored!",
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
                    'routes' => 'data-barang',
                    'notif' => "Barang, {$deleted->nama} has permanently deleted!",
                    'data' => $deleted->deleted_at,
                    'user' => Auth::user()
                ];
                break;

                case 'DATA_PELANGGAN':
                $deleted = Pelanggan::onlyTrashed()
                ->findOrFail($id);

                $deleted->forceDelete();

                $message = "Data pelanggan, {$deleted->nama} has permanently deleted !";
                $data_event = [
                    'alert' => 'error',
                    'type' => 'destroyed',
                    'routes' => 'data-pelanggan',
                    'notif' => "Pelanggan, {$deleted->nama} has permanently deleted!",
                    'data' => $deleted->deleted_at,
                    'user' => Auth::user()
                ];
                break;

                case 'DATA_SUPPLIER':
                $deleted = Supplier::onlyTrashed()
                ->findOrFail($id);

                $deleted->forceDelete();

                $message = "Data supplier, {$deleted->nama} has permanently deleted !";
                $data_event = [
                    'alert' => 'error',
                    'type' => 'destroyed',
                    'routes' => 'supplier',
                    'notif' => "Supplier, {$deleted->nama} has permanently deleted!",
                    'data' => $deleted->deleted_at,
                    'user' => Auth::user()
                ];
                break;

                case 'DATA_KARYAWAN':
                $deleted = Karyawan::onlyTrashed()
                ->findOrFail($id);

                $deleted->forceDelete();

                $message = "Data karyawan, {$deleted->nama} has permanently deleted !";
                $data_event = [
                    'alert' => 'error',
                    'type' => 'destroyed',
                    'routes' => 'karyawan',
                    'notif' => "Karyawan, {$deleted->nama} has permanently deleted!",
                    'data' => $deleted->deleted_at,
                    'user' => Auth::user()
                ];
                break;

                case 'DATA_KAS':
                $deleted = Kas::onlyTrashed()
                ->findOrFail($id);

                $deleted->forceDelete();

                $message = "Data kas, {$deleted->nama} has permanently deleted !";
                $data_event = [
                    'alert' => 'error',
                    'type' => 'destroyed',
                    'routes' => 'kas',
                    'notif' => "Kas, {$deleted->nama} has permanently deleted!",
                    'data' => $deleted->deleted_at,
                    'user' => Auth::user()
                ];
                break;

                case 'DATA_BIAYA':
                $deleted = Biaya::onlyTrashed()
                ->findOrFail($id);

                $deleted->forceDelete();

                $message = "Data biaya, {$deleted->nama} has permanently deleted !";
                $data_event = [
                    'alert' => 'error',
                    'type' => 'destroyed',
                    'routes' => 'biaya',
                    'notif' => "Biaya, {$deleted->nama} has permanently deleted!",
                    'data' => $deleted->deleted_at,
                    'user' => Auth::user()
                ];
                break;

                case 'PEMBELIAN_LANGSUNG':
                $deleted = Pembelian::onlyTrashed()
                ->findOrFail($id);
                $items = ItemPembelian::whereKode($deleted->kode)->get();
                foreach($items as $item) {
                    $barangs = Barang::whereKode($item->kode_barang)->get();
                    foreach($barangs as $barang) {
                        $reverse = $barang->toko - $barang->last_qty;
                        $barang->toko  = $reverse;
                        $barang->last_qty = NULL;
                        $barang->save();
                    }
                }

                ItemPembelian::whereKode($deleted->kode)->forceDelete();
                $deleted->forceDelete();

                $message = "Data pembelian, {$deleted->kode} has permanently deleted !";
                $data_event = [
                    'alert' => 'error',
                    'type' => 'destroyed',
                    'routes' => 'pembelian-langsung',
                    'notif' => "Pembelian, {$deleted->kode} has permanently deleted!",
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

                case 'DATA_SUPPLIER':
                $countTrash = Supplier::onlyTrashed()
                ->get();
                break;

                case 'DATA_KARYAWAN':
                $countTrash = Karyawan::onlyTrashed()
                ->get();
                break;

                case 'DATA_KAS':
                $countTrash = Kas::onlyTrashed()
                ->get();
                break;

                case 'DATA_BIAYA':
                $countTrash = Biaya::onlyTrashed()
                ->get();
                break;

                case 'PEMBELIAN_LANGSUNG':
                $countTrash = Pembelian::onlyTrashed()
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
                $barangLimits = Barang::whereNull('barang.deleted_at')
                ->select('barang.kode', 'barang.nama', 'barang.toko', 'supplier.kode as kode_supplier', 'supplier.nama as nama_supplier')
                ->leftJoin('supplier', 'barang.supplier', '=', 'supplier.kode')
                ->where('toko', 'LIKE', '-%')
                ->orderBy('toko')
                ->limit(10)
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

            $update_user = User::with('roles')
            ->findOrFail($user_id);

            $user_photo = $update_user->photo;
            
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
                $new_profile = User::findOrFail($update_user->id);
                
                $new_profile->photo = "thumbnail_images/users/" . $filenametostore;
                $new_profile->save();

                $profile_has_update = User::with('roles')->findOrFail($update_user->id);

                $data_event = [
                    'type' => 'update-photo',
                    'routes' => 'profile',
                    'notif' => "{$update_user->name} photo, has been updated!"
                ];

                event(new EventNotification($data_event));

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
            throw $th;
        }
    }

    public function update_user_profile(Request $request, $id)
    {
        try {
            $isLogin = Auth::user();
            $userToken = $isLogin->logins[0]->user_token_login;

            if($userToken) {

                $prepare_user = User::findOrFail($id);


                $check_avatar = explode('_', $prepare_user->photo);

                $update_user_karyawan = Karyawan::whereNama($prepare_user->name)->first();

                $user_karyawan_update = Karyawan::findOrFail($update_user_karyawan->id);
                $user_karyawan_update->nama = $request->name ? $request->name : $update_user->name;
                $user_karyawan_update->alamat = $request->alamat ? $request->alamat : null;
                $user_karyawan_update->save();

                $user_id = $prepare_user->id;
                $update_user = User::findOrFail($user_id);

                $update_user->name = $request->name ? $request->name : $update_user->name;
                $update_user->email = $request->email ? $request->email : $update_user->email;
                $update_user->phone = $request->phone ? $this->user_helpers->formatPhoneNumber($request->phone) : $update_user->phone;

                if ($check_avatar[2] === "avatar.png") {
                    $old_photo = public_path($update_user->photo);
                    if (file_exists($old_photo)) {
                        unlink($old_photo);
                    }

                    $initial = $this->initials($update_user->name);
                    $path = public_path() . '/thumbnail_images/users/';
                    $fontPath = public_path('fonts/Oliciy.ttf');
                    $char = $initial;
                    $newAvatarName = rand(12, 34353) . time() . '_avatar.png';
                    $dest = $path . $newAvatarName;

                    $createAvatar = WebFeatureHelpers::makeAvatar($fontPath, $dest, $char);
                    $photo = $createAvatar == true ? $newAvatarName : '';

                    $save_path = 'thumbnail_images/users/';
                    $update_user->photo = $save_path . $photo;
                }

                $update_user->save();

                $new_user_updated = User::whereId($update_user->id)->with('karyawans')->get();

                $data_event = [
                    'type' => 'update-profile',
                    'routes' => 'profile',
                    'notif' => "{$update_user->name}, has been updated!",
                ];

                event(new EventNotification($data_event));


                return response()->json([
                    'success' => true,
                    'message' => "Update user {$update_user->name}, berhasil",
                    'data' => $new_user_updated
                ]);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function update_user_profile_karyawan(Request $request, $id)
    {
        try {
            $prepare_user = User::findOrFail($id);

            $check_avatar = explode('_', $prepare_user->photo);

            $user_id = $prepare_user->id;
            $update_user = User::findOrFail($user_id);

            $update_user->name = $request->name ? $request->name : $update_user->name;
            $update_user->email = $request->email ? $request->email : $update_user->email;

            if ($check_avatar[2] === "avatar.png") {
                $old_photo = public_path($update_user->photo);
                if (file_exists($old_photo)) {
                    unlink($old_photo);
                }

                $initial = $this->initials($update_user->name);
                $path = public_path() . '/thumbnail_images/users/';
                $fontPath = public_path('fonts/Oliciy.ttf');
                $char = $initial;
                $newAvatarName = rand(12, 34353) . time() . '_avatar.png';
                $dest = $path . $newAvatarName;

                $createAvatar = WebFeatureHelpers::makeAvatar($fontPath, $dest, $char);
                $photo = $createAvatar == true ? $newAvatarName : '';

                $save_path = 'thumbnail_images/users/';
                $update_user->photo = $save_path . $photo;
            }

            $update_user->save();

            $new_user_updated = User::whereId($update_user->id)->with('karyawans')->get();

            $data_event = [
                'type' => 'update-profile',
                'routes' => 'profile',
                'notif' => "{$update_user->name}, has been updated!",
            ];

            event(new EventNotification($data_event));


            return response()->json([
                'success' => true,
                'message' => "Update user {$update_user->name}, berhasil",
                'data' => $new_user_updated
            ]);
        } catch (\Throwable $th) {
            throw $th;
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
            'terbilang' => ''.ucwords($this->helpers->terbilang($totalWithPPN). ' Rupiah')
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

    public function generate_terbilang(Request $request)
    {
        try {
            $jml = $request->query('jml');
            $terbilang = ucwords($this->helpers->terbilang($jml). " Rupiah");
            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil nilai terbilang rupiah',
                'data' => $terbilang
            ]);
        } catch(\Throwable $th) {
            throw $th;
        }
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
            'kembali_terbilang' => '' . ucwords($this->helpers->terbilang($kembali). ' Rupiah'),
        ];

        return response()->json($data);
    }

    public function stok_barang_update_inside_transaction(Request $request, $id)
    {
        try {
            $type = $request->type;
            $data = $request->data;
            switch($type) {
                case 'pembelian':
                $dataBarang = Barang::findOrFail($id);
                $newStok = intval($dataBarang->toko) + $data['qty'];
                $dataBarang->toko = $newStok;
                $dataBarang->save();
                break;

                case 'penjualan':
                $dataBarang = Barang::findOrFail($id);
                $newStok = intval($dataBarang->toko) - $data['qty'];
                $dataBarang->toko = $newStok;
                $dataBarang->save();
                break;
            }

            $dataBarangUpdated = Barang::findOrFail($id);

            $data_event = [
                'type' => 'updated',
                'routes' => 'data-barang',
                'notif' => "Stok barang, successfully update!"
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => 'Stok Barang updated',
                'data' => $dataBarangUpdated
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function update_stok_barang(Request $request, $id)
    {
        try {
            $qty = $request->qty;
            $barang = Barang::findOrFail($id);
            $newStok = $barang->toko + $qty;
            // var_dump($newStok);
            // echo "<br> anjing";
            // var_dump($qty); 
            // die;
            $barang->toko = $newStok;
            $barang->save();

            $dataBarangNewStok = Barang::select('id', 'kode','nama','toko')
            ->findOrFail($barang->id);

            $data_event = [
                'type' => 'updated',
                'routes' => 'data-barang',
                'notif' => "Stok barang, successfully update!"
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => 'Stok barang update!',
                'data' => $dataBarangNewStok
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
            $kd_barang = $request->kd_barang;
            $supplierId = null;
            $barangs = $request->barangs;

            foreach($barangs as $barang) {
                $supplierId = $barang['supplier_id'];
            }

            $lastItemPembelianId = NULL;

            $supplier = Supplier::findOrFail($supplierId);

            if($draft) {
                foreach($barangs as $barang) {
                    $dataBarang = Barang::whereKode($barang['kode_barang'])->first();
                        // Update Barang
                    $existingItem = ItemPembelian::where('kode_barang', $dataBarang->kode)
                    ->where('draft', 1)
                    ->first();
                    if ($existingItem) {
                            // Jika sudah ada, update informasi yang diperlukan
                        $existingItem->qty = intval($barang['qty']);
                        $existingItem->last_qty = intval($barang['qty']);
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
                        $draftItemPembelian->supplier = $supplier->kode;
                        $draftItemPembelian->satuan = $dataBarang->satuan;
                        $draftItemPembelian->qty = $barang['qty'];
                        $draftItemPembelian->last_qty = intval($barang['qty']);
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

                foreach($barangs as $barang) {
                    $dataBarang = Barang::whereKode($barang['kode_barang'])->first();
                        // Update Barang

                    $existingItem = ItemPembelian::where('kode_barang', $dataBarang->kode)
                    ->where('draft', 1)
                    ->first();

                        // echo "<pre>";
                        // var_dump($existingItem); 
                        // echo "</pre>";
                        // die;

                    if ($existingItem) {
                        $updateExistingItem = ItemPembelian::findOrFail($existingItem->id);
                            // Jika sudah ada, update informasi yang diperlukan
                        $updateExistingItem->qty = intval($barang['qty']);
                        $updateExistingItem->harga_beli = intval($barang['harga_beli']);
                        $updateExistingItem->subtotal = $barang['harga_beli'] * $barang['qty'];
                            // Update atribut lainnya sesuai kebutuhan
                        $updateExistingItem->save();
                        $lastItemPembelianId = $updateExistingItem->id;
                    } else {
                        $draftItemPembelian = new ItemPembelian;
                        $draftItemPembelian->kode = $kode;
                        $draftItemPembelian->draft = 1;
                        $draftItemPembelian->kode_barang = $dataBarang->kode;
                        $draftItemPembelian->nama_barang = $dataBarang->nama;
                        $draftItemPembelian->supplier = $supplier->kode;
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
                    'failed' => true,
                    'message' => 'Draft item pembelian successfully updated!',
                    'data' => $kode,
                    'itempembelian_id' => $lastItemPembelianId
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
                $listDrafts = ItemPembelian::select(
                    'itempembelian.id',
                    'itempembelian.kode',
                    'itempembelian.nourut',
                    'itempembelian.kode_barang',
                    'itempembelian.nama_barang',
                    'itempembelian.supplier',
                    'itempembelian.satuan',
                    'itempembelian.qty',
                    'itempembelian.harga_beli',
                    'itempembelian.harga_toko',
                    'itempembelian.diskon',
                    'itempembelian.subtotal',
                    'barang.id as id_barang', 'barang.kode as barang_kode', 'barang.nama as barang_nama', 'barang.hpp', 'barang.toko','barang.ada_expired_date', 'barang.expired',
                    'supplier.id as id_supplier'
                )
                ->leftJoin('supplier', 'itempembelian.supplier', '=', 'supplier.kode')
                ->leftJoin('barang', 'itempembelian.kode_barang', '=', 'barang.kode')
                ->where('itempembelian.draft', 1)
                ->where('itempembelian.kode', $kode)
                ->orderByDesc('itempembelian.id')
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
            $kd_barang = $request->kd_barang;
            $pelanggan = null;
            $supplierId = null;
            $barangs = $request->barangs;

            foreach($barangs as $barang) {
                $supplierId = $barang['supplier_id'];
                $pelanggan = $barang['pelanggan'];
            }

            $lastItemPembelianId = NULL;

            $supplier = Supplier::findOrFail($supplierId);
            $pelanggan = Pelanggan::findOrFail($pelanggan);

            if($draft) {
                foreach($barangs as $barang) {
                    $dataBarang = Barang::whereKode($barang['kode_barang'])->first();
                        // Update Barang
                    $existingItem = ItemPenjualan::where('kode_barang', $dataBarang->kode)
                    ->where('draft', 1)
                    ->first();
                    if ($existingItem) {
                            // Jika sudah ada, update informasi yang diperlukan
                        $existingItem->qty = intval($barang['qty']);
                        $existingItem->harga = intval($barang['harga_toko']);
                        $existingItem->subtotal = $barang['harga_toko'] * $barang['qty'];
                            // Update atribut lainnya sesuai kebutuhan
                        $existingItem->save();
                        $lastItemPembelianId = $existingItem->id;
                    } else {
                        $draftItemPembelian = new ItemPenjualan;
                        $draftItemPembelian->kode = $kode;
                        $draftItemPembelian->draft = $draft;
                        $draftItemPembelian->kode_barang = $dataBarang->kode;
                        $draftItemPembelian->nama_barang = $dataBarang->nama;
                        $draftItemPembelian->supplier = $supplier->kode;
                        $draftItemPembelian->pelanggan = $pelanggan->kode;
                        $draftItemPembelian->satuan = $dataBarang->satuan;
                        $draftItemPembelian->qty = $barang['qty'];
                        $draftItemPembelian->isi = $dataBarang->isi;
                        $draftItemPembelian->nourut = $barang['nourut'];
                        $draftItemPembelian->harga = $barang['harga_toko'] ?? $dataBarang->harga_toko;
                        $draftItemPembelian->subtotal = $dataBarang->harga_toko * $barang['qty'];
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
                foreach($barangs as $barang) {
                    $dataBarang = Barang::whereKode($barang['kode_barang'])->first();
                        // Update Barang

                    $existingItem = ItemPenjualan::where('kode_barang', $dataBarang->kode)
                    ->where('draft', 1)
                    ->first();

                        // echo "<pre>";
                        // var_dump($existingItem); 
                        // echo "</pre>";
                        // die;

                    if ($existingItem) {
                        $updateExistingItem = ItemPenjualan::findOrFail($existingItem->id);
                            // Jika sudah ada, update informasi yang diperlukan
                        $updateExistingItem->qty = intval($barang['qty']);
                        $updateExistingItem->harga_beli = intval($barang['harga_beli']);
                        $updateExistingItem->subtotal = $barang['harga_beli'] * $barang['qty'];
                            // Update atribut lainnya sesuai kebutuhan
                        $updateExistingItem->save();
                        $lastItemPembelianId = $updateExistingItem->id;
                    } else {
                        $draftItemPembelian = new ItemPenjualan;
                        $draftItemPembelian->kode = $kode;
                        $draftItemPembelian->draft = 1;
                        $draftItemPembelian->kode_barang = $dataBarang->kode;
                        $draftItemPembelian->nama_barang = $dataBarang->nama;
                        $draftItemPembelian->supplier = $supplier->kode;
                        $draftItemPembelian->pelanggan = $pelanggan->kode;
                        $draftItemPembelian->satuan = $dataBarang->satuan;
                        $draftItemPembelian->qty = $barang['qty'];
                        $draftItemPembelian->isi = $dataBarang->isi;
                        $draftItemPembelian->nourut = $barang['nourut'];
                        $draftItemPembelian->harga = $dataBarang->harga_toko;
                        $draftItemPembelian->subtotal = $dataBarang->hpp * $barang['qty'];
                        $draftItemPembelian->isi = $dataBarang->isi;

                        if($barang['diskon']) {
                            $total = $dataBarang->harga_toko * $barang['qty'];
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
                    'failed' => true,
                    'message' => 'Draft item penjualan successfully updated!',
                    'data' => $kode,
                    'itempembelian_id' => $lastItemPembelianId
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
                $listDrafts = ItemPenjualan::select(
                    'itempenjualan.id',
                    'itempenjualan.kode',
                    'itempenjualan.nourut',
                    'itempenjualan.kode_barang',
                    'itempenjualan.nama_barang',
                    'itempenjualan.satuan',
                    'itempenjualan.qty',
                    'itempenjualan.harga',
                    'itempenjualan.hpp',
                    'itempenjualan.diskon',
                    'itempenjualan.subtotal',
                    'itempenjualan.expired',
                    'pelanggan.id as id_pelanggan','pelanggan.nama as nama_pelanggan','pelanggan.kode as kode_pelanggan','pelanggan.alamat as alamat_pelanggan',
                    'barang.id as id_barang', 'barang.kode as barang_kode', 'barang.nama as barang_nama', 'barang.hpp as harga_beli_barang', 'barang.harga_toko', 'barang.toko', 'barang.supplier', 'supplier.id as id_supplier','supplier.nama as nama_supplier', 'supplier.kode as kode_supplier'
                )
                ->leftJoin('barang', 'itempenjualan.kode_barang', '=', 'barang.kode')
                ->leftJoin('supplier', 'barang.supplier', '=', 'supplier.kode')
                ->leftJoin('pelanggan', 'itempenjualan.pelanggan', '=', 'pelanggan.kode')
                ->where('itempenjualan.draft', 1)
                ->where('itempenjualan.kode', $kode)
                ->orderByDesc('itempenjualan.id')
                ->get();

                return new ResponseDataCollect($listDrafts);
            } else {
                return response()->json([
                    'failed' => true,
                    'message' => 'Draft item penjualan has no success updated!'
                ], 203);
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

public function check_roles_access()
{
    try {
        $user = Auth::user();

        $userRole = Roles::findOrFail($user->role);

        if ($userRole->name !== "MASTER" && $userRole->name !== "ADMIN" && $userRole->name !== "GUDANG") { 
            return response()->json([
                'error' => true,
                'message' => 'Hak akses tidak di ijinkan 🚫'
            ]);
        } else {
            return response()->json([
                'success' => true,
            ], 200);
        }

    } catch (\Throwable $th) {
        throw $th;
    }
}

public function check_password_access()
{
    try {
        $user = Auth::user();

        $userRole = Roles::findOrFail($user->role);

        if ($userRole->name !== "MASTER") { 
            return response()->json([
                'error' => true,
                'message' => 'Hak akses tidak di ijinkan 🚫'
            ]);
        } else {
            return response()->json([
                'success' => true,
            ], 200);
        }

    } catch (\Throwable $th) {
        throw $th;
    }
}
}
