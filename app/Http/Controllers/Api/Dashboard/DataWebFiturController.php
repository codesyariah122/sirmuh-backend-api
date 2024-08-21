<?php

namespace App\Http\Controllers\Api\Dashboard;

use Illuminate\Support\Facades\Http;
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
    Biaya,
    PurchaseOrder,
    JenisPemasukan,
    Pemasukan,
    Pengeluaran,
    PemasukanPenjualan
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

    public function pemasukanWeekly()
    {
        try {
            $startOfMonth = now()->startOfMonth();
            $endOfMonth = now()->endOfMonth();

            $query = PemasukanPenjualan::query()
            ->select(
                DB::raw('YEARWEEK(tanggal) as minggu'),
                DB::raw('SUM(jumlah) as total_pemasukan')
            );

            $pemasukanPerMinggu = $query->whereBetween('tanggal', [$startOfMonth, $endOfMonth])
            ->groupBy('minggu')
            ->orderBy('minggu', 'asc')
            ->get();

            $chartData = $pemasukanPerMinggu->map(function ($pemasukan) {
                $year = substr($pemasukan->minggu, 0, 4);
                $week = substr($pemasukan->minggu, 4, 2);

                $startOfWeek = date('Y-m-d', strtotime($year . 'W' . $week));
                $endOfWeek = date('Y-m-d', strtotime($year . 'W' . $week . '7'));

                return [
                    'week_start' => $startOfWeek,
                    'week_end' => $endOfWeek,
                    'total_pemasukan' => $pemasukan->total_pemasukan,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Total Pemasukan Mingguan',
                'label' => 'Total Pemasukan',
                'data' => $chartData
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function penjualanWeekly()
    {
        try {
            $query = Penjualan::query()
            ->select(
                DB::raw('YEARWEEK(tanggal) as minggu'),
                DB::raw('SUM(jumlah) as total_jumlah')
            )
            ->leftJoin('kas', 'penjualan.kode_kas', '=', 'kas.kode')
            ->leftJoin('pelanggan', 'penjualan.pelanggan', '=', 'pelanggan.kode')
            ->where('jenis', 'PENJUALAN TOKO');

            $query->where('penjualan.po', '=', 'False')
            ->groupBy('minggu')
            ->orderBy('minggu', 'asc');

            $penjualanPerMinggu = $query->get();

            $chartData = $penjualanPerMinggu->map(function ($penjualan) {
                $year = substr($penjualan->minggu, 0, 4);
                $week = substr($penjualan->minggu, 4, 2);

                $startOfWeek = now()->isoWeek($week)->year($year)->startOfWeek()->format('Y-m-d');
                $endOfWeek = now()->isoWeek($week)->year($year)->endOfWeek()->format('Y-m-d');

                return [
                    'week_start' => $startOfWeek,
                    'week_end' => $endOfWeek,
                    'total_jumlah' => $penjualan->total_jumlah,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Grafik Penjualan Weekly',
                'label' => 'Total Jumlah Penjualan',
                'data' => $chartData
            ]);

        } catch (\Throwable $th) {
            throw $th;
        }
    }


    public function penjualanDaily()
    {
        try {
            $query = Penjualan::query()
            ->select(
                'penjualan.tanggal',
                DB::raw('SUM(penjualan.jumlah) as total_jumlah')
            )
            ->leftJoin('kas', 'penjualan.kode_kas', '=', 'kas.kode')
            ->leftJoin('pelanggan', 'penjualan.pelanggan', '=', 'pelanggan.kode')
            ->where('jenis', 'PENJUALAN TOKO');


            $query->where('penjualan.po', '=', 'False')
            ->groupBy('penjualan.tanggal')
            ->orderBy('penjualan.tanggal', 'asc');

            $penjualans = $query->get();

            $chartData = $penjualans->map(function($penjualan) {
                return [
                    'label' => $penjualan->tanggal,
                    'value' => $penjualan->total_jumlah,
                ];
            });

            return response()->json(['data' => $chartData]);

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
                ->select('id', 'kode', 'nama', 'photo', 'kategori', 'satuanbeli', 'satuan', 'isi', 'toko', 'last_qty', 'gudang', 'hpp', 'harga_toko', 'harga_partai','tgl_terakhir', 'ada_expired_date', 'expired')
                ->paginate(10);
                break;

                case 'DATA_PELANGGAN':
                $deleted = Pelanggan::onlyTrashed()
                ->select('id', 'kode', 'nama', 'alamat', 'telp', 'pekerjaan', 'tgl_lahir', 'saldo_piutang', 'point', 'sales', 'area', 'max_piutang', 'kota', 'rayon', 'saldo_tabungan')
                ->paginate(10);
                break;

                case 'DATA_SUPPLIER':
                $deleted = Supplier::onlyTrashed()
                ->select('id', 'nama', 'kode', 'alamat', 'kota', 'telp', 'fax', 'email', 'saldo_hutang')
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
                ->select('id', 'kode', 'tanggal', 'kode_kas', 'jumlah','bayar','diterima','lunas','operator', 'supplier')
                ->where('po', 'False')
                ->paginate(10);
                break;

                case 'PENJUALAN_TOKO':
                $deleted = Penjualan::onlyTrashed()
                ->select('id', 'kode', 'tanggal', 'kode_kas', 'jumlah','bayar','lunas','operator')
                ->paginate(10);
                break;

                case 'PURCHASE_ORDER':
                $deleted = Pembelian::onlyTrashed()
                ->select('id', 'kode', 'tanggal', 'kode_kas', 'supplier', 'jumlah','bayar')
                ->where('po', 'True')
                ->paginate(10);
                break;

                case 'DATA_PEMASUKAN':
                $deleted = Pemasukan::onlyTrashed()
                ->select('id', 'kode', 'tanggal', 'kd_biaya', 'keterangan', 'kode_kas','jumlah', 'operator', 'deleted_at')
                ->paginate(10);
                break;

                case 'DATA_PENGELUARAN':
                $deleted = Pengeluaran::onlyTrashed()
                ->select('id', 'kode', 'tanggal', 'kd_biaya', 'keterangan', 'kode_kas','jumlah', 'operator', 'deleted_at')
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

                case 'PENJUALAN_TOKO':
                $restored_biaya = Penjualan::onlyTrashed()
                ->findOrFail($id);
                $restored_biaya->restore();
                $restored = Penjualan::findOrFail($id);
                $name = $restored->kode;
                $data_event = [
                    'alert' => 'info',
                    'type' => 'restored',
                    'routes' => 'penjualan-toko',
                    'notif' => "Penjualan, {$name} has been restored!",
                    'data' => $restored->deleted_at,
                    'user' => Auth::user()
                ];
                break;

                case 'PURCHASE_ORDER':
                $restored_biaya = Pembelian::onlyTrashed()
                ->findOrFail($id);
                $restored_biaya->restore();
                $restored = Pembelian::findOrFail($id);
                $name = $restored->kode;
                $data_event = [
                    'alert' => 'info',
                    'type' => 'restored',
                    'routes' => 'purchase-order',
                    'notif' => "Pembelian, {$name} has been restored!",
                    'data' => $restored->deleted_at,
                    'user' => Auth::user()
                ];
                break;

                case 'DATA_PEMASUKAN':
                $restored_pemasukan = Pemasukan::onlyTrashed()
                ->findOrFail($id);
                $restored_pemasukan->restore();
                $restored = Pemasukan::findOrFail($id);
                $kode = $restored->kode;
                $data_event = [
                    'alert' => 'info',
                    'type' => 'restored',
                    'routes' => 'pemasukan',
                    'notif' => "Pemasukan, {$kode} has been restored!",
                    'data' => $restored->deleted_at,
                    'user' => Auth::user()
                ];
                $dataKas = Kas::whereKode($restored->kode_kas)->first();
                $updateKas = Kas::findOrFail($dataKas->id);
                $updateKas->saldo = intval($dataKas->saldo) + intval($restored->jumlah);
                $updateKas->save();
                break;

                case 'DATA_PENGELUARAN':
                $restored_pengeluaran = Pengeluaran::onlyTrashed()
                ->findOrFail($id);
                $restored_pengeluaran->restore();
                $restored = Pengeluaran::findOrFail($id);
                $kode = $restored->kode;
                $data_event = [
                    'alert' => 'info',
                    'type' => 'restored',
                    'routes' => 'pengeluaran',
                    'notif' => "Pengeluaran, {$kode} has been restored!",
                    'data' => $restored->deleted_at,
                    'user' => Auth::user()
                ];
                $dataKas = Kas::whereKode($restored->kode_kas)->first();
                $updateKas = Kas::findOrFail($dataKas->id);
                $updateKas->saldo = intval($dataKas->saldo) - intval($restored->jumlah);
                $updateKas->save();
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
                $user = Auth::user();
                $historyKeterangan = "{$user->name}, telah menghapus data barang : [{$deleted->nama}], Dari supplier : {$deleted->supplier}";
                $dataHistory = [
                    'user' => $user->name,
                    'keterangan' => $historyKeterangan,
                    'routes' => '/dashboard/master/barang/barang-by-suppliers',
                    'route_name' => 'Data Barang'
                ];
                $createHistory = $this->helpers->createHistory($dataHistory);
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
                $kas = Kas::whereKode($deleted->kode_kas)->first();
                $updateKas = Kas::findOrFail($kas->id);
                $updateKas->saldo = intval($kas->saldo) + intval($deleted->jumlah);
                $updateKas->save();

                $items = ItemPembelian::whereKode($deleted->kode)->get();
                foreach($items as $item) {
                    $barangs = Barang::whereKode($item->kode_barang)->get();
                    foreach($barangs as $barang) {
                        $reverse = $barang->toko - $item->qty;
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

                case 'PENJUALAN_TOKO':
                $deleted = Penjualan::onlyTrashed()
                ->findOrFail($id);
                $items = ItemPenjualan::whereKode($deleted->kode)->get();
                foreach($items as $item) {
                    $barangs = Barang::whereKode($item->kode_barang)->get();
                    foreach($barangs as $barang) {
                        $reverse = $barang->toko + $item->qty;
                        $barang->toko  = $reverse;
                        $barang->last_qty = NULL;
                        $barang->save();
                    }
                }

                ItemPenjualan::whereKode($deleted->kode)->forceDelete();
                $deleted->forceDelete();

                $message = "Data penjualan, {$deleted->kode} has permanently deleted !";
                $data_event = [
                    'alert' => 'error',
                    'type' => 'destroyed',
                    'routes' => 'penjualan-toko',
                    'notif' => "Penjualan, {$deleted->kode} has permanently deleted!",
                    'data' => $deleted->deleted_at,
                    'user' => Auth::user()
                ];
                break;

                case 'PURCHASE_ORDER':
                $deleted = Pembelian::onlyTrashed()
                ->findOrFail($id);
                $kas = Kas::whereKode($deleted->kode_kas)->first();
                $updateKas = Kas::findOrFail($kas->id);
                $updateKas->saldo = intval($kas->saldo) + intval($deleted->jumlah);
                $updateKas->save();

                $items = ItemPembelian::whereKode($deleted->kode)->get();
                foreach($items as $item) {
                    $barangs = Barang::whereKode($item->kode_barang)->get();
                    foreach($barangs as $barang) {
                        $reverse = $barang->toko - $item->qty;
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
                    'routes' => 'purchase-order',
                    'notif' => "Pembelian, {$deleted->kode} has permanently deleted!",
                    'data' => $deleted->deleted_at,
                    'user' => Auth::user()
                ];
                break;

                case 'DATA_PEMASUKAN':
                $deleted = Pemasukan::onlyTrashed()
                ->findOrFail($id);

                $deleted->forceDelete();

                $message = "Data pemasukan, {$deleted->kode} has permanently deleted !";
                $data_event = [
                    'alert' => 'error',
                    'type' => 'destroyed',
                    'routes' => 'pemasukan',
                    'notif' => "Pemasukan, {$deleted->kode} has permanently deleted!",
                    'data' => $deleted->deleted_at,
                    'user' => Auth::user()
                ];
                break;

                case 'DATA_PENGELUARAN':
                $deleted = Pengeluaran::onlyTrashed()
                ->findOrFail($id);

                $deleted->forceDelete();

                $message = "Data pengeluaran, {$deleted->kode} has permanently deleted !";
                $data_event = [
                    'alert' => 'error',
                    'type' => 'destroyed',
                    'routes' => 'pengeluaran',
                    'notif' => "Pengeluaran, {$deleted->kode} has permanently deleted!",
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
                ->where('po', 'False')
                ->get();
                break;

                case 'PENJUALAN_TOKO':
                $countTrash = Penjualan::onlyTrashed()
                ->get();
                break;

                case 'PURCHASE_ORDER':
                $countTrash = Pembelian::onlyTrashed()
                ->where('po', 'True')
                ->get();
                break;

                case 'DATA_PEMASUKAN':
                $countTrash = Pemasukan::onlyTrashed()
                ->get();
                break;

                case 'DATA_PENGELUARAN':
                $countTrash = Pengeluaran::onlyTrashed()
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
                        // 'admin' => $admin,
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
                ->where('toko', '<=', '0')
                ->orderBy('toko')
                ->limit(5)
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

    public function barangTerlarisWeekly()
    {
        try {
            $result = ItemPenjualan::barangTerlarisWeekly();

            return response()->json([
                'success' => true,
                'label' => 'Total Qty',
                'message' => "10 Barang terlaris mingguan",
                'data' => $result,
            ]);
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
                $result = Pelanggan::select('pelanggan.kode', 'pelanggan.nama', DB::raw('COALESCE(SUM(penjualan.jumlah), 0) as total_penjualan'))
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

                $name = $request->name;
                $firstName = explode(' ', trim($name))[0];

                $username = strtolower($firstName);
                $originalUsername = $username;
                $counter = 1;

                while (User::where('username', $username)->exists()) {
                    $username = $originalUsername . $counter;
                    $counter++;
                }

                $update_user_karyawan = Karyawan::whereKode($prepare_user->name)->first();
                $user_karyawan_update = Karyawan::findOrFail($update_user_karyawan->id);
                $user_karyawan_update->kode = $request->name ? $request->name : $user_karyawan_update->kode;
                $user_karyawan_update->nama = $request->name ? $request->name : $update_user->name;
                $user_karyawan_update->alamat = $request->alamat ? $request->alamat :$update_user_karyawan->alamat;
                $user_karyawan_update->save();

                $user_id = $prepare_user->id;
                $update_user = User::findOrFail($user_id);
                $update_user->name = $request->name ? $request->name : $update_user->name;
                $update_user->username = $username;
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
                    // ->mixedCase()
                    // ->letters()
                    // ->numbers()
                    // ->symbols()
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
        $currentDate = now()->format('dmy');
        $randomNumber = sprintf('%05d', mt_rand(0, 99999));

        switch($type) {
            case "pembelian-langsung":
            $generatedCode = $perusahaan->kd_pembelian .'-'. $currentDate . $randomNumber;
            break;
            case "purchase-order":
            $generatedCode = $perusahaan->kd_pembelian .'-'. "PO".$currentDate . $randomNumber;
            break;
            case "penjualan-toko":
            $generatedCode = $perusahaan->kd_penjualan_toko .'-'. $currentDate . $randomNumber;
            break;
            case "penjualan-po":
            $generatedCode = $perusahaan->kd_penjualan_toko. '-'. "PO".$currentDate . $randomNumber;
            break;
            case "penjualan-partai":
            $generatedCode = $perusahaan->kd_penjualan_toko .'-PRT'. $currentDate . $randomNumber;
            break;
            case "bayar-hutang":
            $generatedCode = $perusahaan->kd_bayar_hutang . $currentDate . $randomNumber;
            break;
            case "bayar-piutang":
            $generatedCode = $perusahaan->kd_bayar_piutang . $currentDate . $randomNumber;
            break;
            case "mutasi-kas":
            $generatedCode = $perusahaan->kd_mutasi_kas . '-' . $currentDate . $randomNumber;
            break;
            case "pemasukan":
            $generatedCode = "TPK-" . $currentDate . $randomNumber;
            break;
            case "pengeluaran":
            $generatedCode = $perusahaan->kd_pengeluaran . '-' . $currentDate . $randomNumber;
            break;
            case "koreksi-stok":
            $generatedCode = "KS" . '-' . $currentDate . $randomNumber;
            break;
            case "pemakaian-barang":
            $generatedCode = "PEM" . '-' . $currentDate . $randomNumber;
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
        $diterima   = intval($total) - ($diskon / 100 * $total);
        $kembali = ($bayar != 0) ? intval($bayar) - $diterima : 0;
        $data    = [
            'total' => $total,
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

    public function edited_update_stok_barang(Reqeust $request)
    {
        try {
            $barangs = $request->barangs;
            $type = $request->type;

            switch($type) {
               case "pembelian":
               foreach ($barangs as $barang) {
                $updateBarang = Barang::findOrFail($barang['id']);
                if($barang['qty'] > $updateBarang->last_qty){
                    $bindStok = $barang['qty'] + $updateBarang->last_qty;
                    $newStok = $updateBarang->toko + $barang['qty'];
                } else if($barang['qty'] < $updateBarang->last_qty){
                    $bindStok = $updateBarang->last_qty - $barang['qty'];
                    $newStok = $updateBarang->toko - $bindStok;
                } else {
                    $newStok = $updateBarang->toko;
                }
                $newStok = $updateBarang->toko + $barang['qty'];
                $updateBarang->toko = $newStok;
                $updateBarang->last_qty = $barang['qty'];
                $updateBarang->save();
            }
            break;
            case "penjualan":
            foreach($barangs as $barang) {
                $stok = Barang::findOrFail($barang['id']);
                $updateBarang = Barang::findOrFail($barang['id']);
                $qtyBarang = $barang['qty'];
                $lastQty = $stok->last_qty;
                $stokBarang = intval($stok->toko);
                $updateBarang->toko = $stokBarang - $qtyBarang;
                $updateBarang->last_qty = $barang['qty'];
                $updateBarang->save();
            }
            break;
        }

        $data_event = [
            'type' => 'updated',
            'routes' => 'data-barang',
            'notif' => "Stok barang, successfully update!"
        ];

        event(new EventNotification($data_event));

        return response()->json([
            'success' => true,
            'message' => 'Stok barang update!',
            'data' => $barangs
        ]);
    } catch (\Throwable $th) {
        throw $th;
    }
}


public function update_stok_barang_po(Request $request)
{
    try {
        $barangs = $request->barangs;
        $type = $request->type;

        switch($type) {
            case "pembelian":
            foreach ($barangs as $barang) {
                $checkItems = Barang::whereKode($barang['kode_barang'])->get();
                foreach($checkItems as $item) {
                    $updateBarang = Barang::findOrFail($item['id']);
                    $lastQty = $item->toko;
                    $updateBarang->toko = $updateBarang->toko + $barang['qty'];
                    $updateBarang->last_qty = $lastQty;
                    $updateBarang->save();
                }
            }
            break;
            case "penjualan":
            break;
        }

        $data_event = [
            'type' => 'updated',
            'routes' => 'data-barang',
            'notif' => "Stok barang, successfully update!"
        ];

        event(new EventNotification($data_event));

        return response()->json([
            'success' => true,
            'message' => 'Stok barang update!',
            'data' => $barangs
        ]);

    } catch (\Throwable $th) {
        throw $th;
    }
}

public function edit_stok_data_barang(Request $request)
{
    try {
        $barangs = $request->barangs;
        $type = $request->type;

        switch($type) {
           case "pembelian":
           foreach ($barangs as $barang) {
            $updateBarang = Barang::findOrFail($barang['id']);
                // if($barang['qty'] > $updateBarang->last_qty){
                //     $newStok = $updateBarang->toko + $barang['qty'];
                // } else {
                //     $newStok = $updateBarang->toko;
                // }
            if($barang['last_qty'] !== NULL && $barang['last_qty'] >= 0) {
                $lastQty = $barang['last_qty'];
            } else {
                $lastQty = $updateBarang->toko;
            }

                // $newStok = $updateBarang->toko + $barang['qty'];
            $newStok = max(0, $updateBarang->toko) - $barang['qty'];
            $updateBarang->toko = $newStok;
            $updateBarang->last_qty = $lastQty;
            $updateBarang->save();
        }
        break;
        case "penjualan":
        foreach($barangs as $barang) {
            $stok = Barang::findOrFail($barang['id']);
            $updateBarang = Barang::findOrFail($barang['id']);
            $qtyBarang = $barang['qty'];
            if($barang['last_qty'] !== NULL && $barang['last_qty'] >= 0) {
                $lastQty = $barang['last_qty'];
            } else {
                $lastQty = $updateBarang->toko;
            }
            $stokBarang = max(0, $stok->toko);
            $updateBarang->toko = $stokBarang + $qtyBarang;
            $updateBarang->last_qty = $lastQty;
            $updateBarang->save();
        }
        break;
    }

    $data_event = [
        'type' => 'updated',
        'routes' => 'data-barang',
        'notif' => "Stok barang, successfully update!"
    ];

    event(new EventNotification($data_event));

    return response()->json([
        'success' => true,
        'message' => 'Stok barang update!',
        'data' => $barangs
    ]);

} catch (\Throwable $th) {
    throw $th;
}
}

public function update_stok_barang_all(Request $request)
{
    try {
        $barangs = $request->barangs;
        $type = $request->type;

        switch($type) {
           case "pembelian":
           foreach ($barangs as $barang) {
            $updateBarang = Barang::findOrFail($barang['id']);
                // if($barang['qty'] > $updateBarang->last_qty){
                //     $newStok = $updateBarang->toko + $barang['qty'];
                // } else {
                //     $newStok = $updateBarang->toko;
                // }
            if($barang['last_qty'] !== NULL && $barang['last_qty'] >= 0) {
                $lastQty = $barang['last_qty'];
            } else {
                $lastQty = $updateBarang->toko;
            }

                // $newStok = $updateBarang->toko + $barang['qty'];
            $newStok = $barang['qty'] + max(0, $updateBarang->toko);
            $updateBarang->toko = $newStok;
            $updateBarang->last_qty = $lastQty;
            $updateBarang->save();
        }
        break;
        case "penjualan":
        foreach($barangs as $barang) {
            $stok = Barang::findOrFail($barang['id']);
            $updateBarang = Barang::findOrFail($barang['id']);
            $qtyBarang = $barang['qty'];
            if($barang['last_qty'] !== NULL && $barang['last_qty'] >= 0) {
                $lastQty = $barang['last_qty'];
            } else {
                $lastQty = $updateBarang->toko;
            }
            $stokBarang = intval($stok->toko);
            $updateBarang->toko = $stokBarang - $qtyBarang;
            $updateBarang->last_qty = $lastQty;
            $updateBarang->save();
        }
        break;
    }

    $data_event = [
        'type' => 'updated',
        'routes' => 'data-barang',
        'notif' => "Stok barang, successfully update!"
    ];

    event(new EventNotification($data_event));

    return response()->json([
        'success' => true,
        'message' => 'Stok barang update!',
        'data' => $barangs
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
        $kode_kas = $request->kode_kas;
        $kd_barang = $request->kd_barang;
        $supplierId = null;
        $barangs = $request->barangs;

        foreach($barangs as $barang) {
            $supplierId = $barang['supplier_id'] !== NULL ? $barang['supplier_id'] : $request->supplierId;
        }

        $lastItemPembelianId = NULL;

        $supplier = Supplier::findOrFail($supplierId);
        $checkingKas = Kas::findOrFail($kode_kas);

        if($draft) {
            foreach($barangs as $barang) {
                $dataBarang = Barang::whereKode($barang['kode_barang'])->first();
                        // Update Barang
                $existingItem = ItemPembelian::where('kode_barang', $dataBarang->kode)
                ->where('kode', $kode)
                ->where('draft', 1)
                ->first();

                $subTotal = $barang['harga_beli'] * $barang['qty'];
                if($checkingKas->saldo < $subTotal) {
                    return response()->json([
                        'error' => true,
                        'message' => "Saldo kas {$checkingKas->kode} tidak mencukupi 🫣"
                    ]);
                }

                if ($existingItem) {
                    $existingItem->qty = $barang['qty'];
                    $existingItem->last_qty = $barang['last_qty'];
                    $existingItem->harga_beli = intval($barang['harga_beli']);
                    $existingItem->subtotal = $subTotal;
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
                    $draftItemPembelian->last_qty = NULL;
                    $draftItemPembelian->isi = $dataBarang->isi;
                    $draftItemPembelian->nourut = $barang['nourut'];
                    $draftItemPembelian->harga_beli = $barang['harga_beli'] ?? $dataBarang->hpp;
                    $draftItemPembelian->harga_toko = $dataBarang->harga_toko;
                    $draftItemPembelian->harga_cabang = $dataBarang->harga_cabang;
                    $draftItemPembelian->harga_partai = $dataBarang->harga_partai;
                    $draftItemPembelian->subtotal = $barang['qty'] > 0 ? $dataBarang->hpp * $barang['qty'] : $dataBarang->hpp;
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
                ->where('kode', $kode)
                ->where('draft', 1)
                ->first();

                $subTotal = $barang['harga_beli'] * $barang['qty'];
                if($checkingKas->saldo < $subTotal) {
                    return response()->json([
                        'error' => true,
                        'message' => "Saldo kas {$checkingKas->kode} tidak mencukupi 🫣"
                    ]);
                }

                if ($existingItem) {
                    $updateExistingItem = ItemPembelian::findOrFail($existingItem->id);

                            // Jika sudah ada, update informasi yang diperlukan
                    $updateExistingItem->qty = $barang['qty'];
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
                    $draftItemPembelian->subtotal = $barang['qty'] > 0 ? $dataBarang->hpp * $barang['qty'] : $dataBarang->hpp;
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
                'supplier.id as id_supplier', 'supplier.nama as supplier_nama'
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


public function delete_item_pembelian_po($id)
{
    try {
        $itemPembelian = ItemPembelian::findOrFail($id);
        $itemPembelian->qty = 0;
        $itemPembelian->last_qty = NULL;
        $itemPembelian->stop_qty = "False";
        $itemPembelian->subtotal = $itemPembelian->harga_beli;
        $itemPembelian->qty_terima = 0;
        $itemPembelian->save();

        $dataPembelian = Pembelian::whereKode($itemPembelian->kode)->first();
        $udpateDataPembelian = Pembelian::findOrFail($dataPembelian->id);
        $udpateDataPembelian->diterima = 0;
        $udpateDataPembelian->jt = 0;
        $udpateDataPembelian->sisa_dp = 0;
        $udpateDataPembelian->jumlah = intval($dataPembelian->jumlah) + intval($dataPembelian->biayabongkar);
        $udpateDataPembelian->biayabongkar = 0; 
        $udpateDataPembelian->save();

        $purchaseOrders = PurchaseOrder::where('kode_barang', $itemPembelian->kode_barang)
        ->where('po_ke', '!=', 0)
        ->get();

        foreach($purchaseOrders as $poitem) {
            $deletePo = PurchaseOrder::findOrFail($poitem->id);
            $deletePo->forceDelete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Item pembelian successfully deleted!',
            'data' => $udpateDataPembelian
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
        $kode_kas = $request->kode_kas;
        $kd_barang = $request->kd_barang;
        $pelanggan = null;
        $supplierId = null;
        $barangs = $request->barangs;

        foreach($barangs as $barang) {
            $pelanggan = $barang['pelanggan'];
            $supplierId = $barang['supplier_id'];
        }

        $lastItemPembelianId = NULL;

        $pelanggan = Pelanggan::findOrFail($pelanggan);
        $supplier = Supplier::findOrFail($supplierId);
        $checkingKas = Kas::findOrFail($kode_kas);

        if($draft) {
            foreach($barangs as $key => $barang) {
                $dataBarang = Barang::whereKode($barang['kode_barang'])->first();

                $existingItem = ItemPenjualan::where('kode_barang', $dataBarang->kode)
                ->where('draft', 1)
                ->where('kode', $kode)
                ->first();

                if($barang['harga_toko'] !== NULL) {
                    $harga = $barang['harga_toko'];
                } else {
                    $harga = $barang['harga_partai'];
                }

                if ($existingItem) {
                    $updateExistingItem = ItemPenjualan::findOrFail($existingItem->id);
                    $updateExistingItem->qty = $barang['qty'];
                    $updateExistingItem->harga = intval($harga);
                    // if($barang['ppn'] > 0) {
                    //     $updateExistingItem->subtotal = (($barang['ppn'] / 100) * ($harga * $barang['qty']));
                    // } else if($barang['diskon'] > 0) {
                    //     $updateExistingItem->subtotal = (($barang['diskon'] / 100) * ($harga * $barang['qty']));
                    // } else {
                    //     $updateExistingItem->subtotal = intval($harga) * $barang['qty'];
                    // }
                    $updateExistingItem->subtotal = intval($harga) * $barang['qty'];
                    $updateExistingItem->save();
                    $lastItemPembelianId = $updateExistingItem->id;
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
                    $draftItemPembelian->harga = $harga;

                    // if($barang['ppn'] > 0) {
                    //     $draftItemPembelian->subtotal = (($barang['ppn'] / 100) * ($harga * $barang['qty']));
                    // } else if($barang['diskon'] > 0) {
                    //     $draftItemPembelian->subtotal = (($barang['diskon'] / 100) * ($harga * $barang['qty']));
                    // } else {
                    //     $draftItemPembelian->subtotal = $harga * $barang['qty'];
                    // }
                    $draftItemPembelian->subtotal = $harga * $barang['qty'];
                    $draftItemPembelian->isi = $dataBarang->isi;
                    $draftItemPembelian->ppn = $barang['ppn'] > 0 ? "True" : "False";

                    if($barang['diskon'] > 0) {
                        $total = $harga * $barang['qty'];
                        $diskonAmount = ($barang['diskon'] / 100) * $total;
                        $totalSetelahDiskon = $total - $diskonAmount;
                        $draftItemPembelian->diskon_rupiah = $totalSetelahDiskon;
                    }

                    if($barang['ppn'] > 0) {
                        $total = $harga * $barang['qty'];
                        $ppnAmount = ($barang['ppn'] / 100) * $total;
                        $totalSetelahDiskon = $total - $ppnAmount;
                        $draftItemPembelian->jumlah_ppn = $totalSetelahDiskon;
                    }
                    $draftItemPembelian->save();
                    $lastItemPembelianId = $draftItemPembelian->id;
                }
            }
            return response()->json([
                'draft' => true,
                'message' => 'Draft item penjualan successfully updated!',
                'data' => $kode,
                'itempembelian_id' => $lastItemPembelianId
            ], 200);
        } else {
            foreach($barangs as $key => $barang) {
                $dataBarang = Barang::whereKode($barang['kode_barang'])->first();

                $existingItem = ItemPenjualan::where('kode_barang', $dataBarang->kode)
                ->where('draft', 1)
                ->where('kode', $kode)
                ->first();

                // $beforeExisting = ItemPenjualan::where('kode_barang', $dataBarang->kode)
                // ->where('draft', 1)
                // ->first();
                // var_dump($beforeExisting); die;
                // $deletedBeforeExisting = ItemPenjualan::where('kode_barang', $beforeExisting->kode_barang);
                // $deletedBeforeExisting->delete();

                if($barang['harga_toko'] !== NULL) {
                    $harga = $barang['harga_toko'];
                } else {
                    $harga = $barang['harga_partai'];
                }

                if ($existingItem) {
                    $updateExistingItem = ItemPenjualan::findOrFail($existingItem->id);
                    $updateExistingItem->qty = $barang['qty'];
                    $updateExistingItem->harga = $harga;
                    // if($barang['ppn'] > 0) {
                    //     $updateExistingItem->subtotal = (($barang['ppn'] / 100) * ($harga * $barang['qty']));
                    // } else if($barang['diskon'] > 0) {
                    //     $updateExistingItem->subtotal = (($barang['diskon'] / 100) * ($harga * $barang['qty']));
                    // } else {
                    //     $updateExistingItem->subtotal = intval($harga) * $barang['qty'];
                    // }
                    $updateExistingItem->subtotal = intval($harga) * $barang['qty'];
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
                    $draftItemPembelian->harga = $harga;
                    // if($barang['ppn'] > 0) {
                    //     $draftItemPembelian->subtotal = (($barang['ppn'] / 100) * ($harga * $barang['qty']));
                    // } else if($barang['diskon'] > 0) {
                    //     $draftItemPembelian->subtotal = (($barang['diskon'] / 100) * ($harga * $barang['qty']));
                    // } else {
                    //     $draftItemPembelian->subtotal = $harga * $barang['qty'];
                    // }
                    $draftItemPembelian->subtotal = $harga * $barang['qty'];
                    $draftItemPembelian->isi = $dataBarang->isi;
                    $draftItemPembelian->ppn = $barang['ppn'] > 0 ? "True" : "False";

                    if($barang['diskon'] > 0) {
                        $total = $harga * $barang['qty'];
                        $diskonAmount = ($barang['diskon'] / 100) * $total;
                        $totalSetelahDiskon = $total - $diskonAmount;
                        $draftItemPembelian->diskon_rupiah = $totalSetelahDiskon;
                    }

                    if($barang['ppn'] > 0) {
                        $total = $harga * $barang['qty'];
                        $ppnAmount = ($barang['ppn'] / 100) * $total;
                        $totalSetelahPpn = $total - $ppnAmount;
                        $draftItemPembelian->jumlah_ppn = $totalSetelahPpn;
                    }

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

public function check_stok_barang(Request $request, $id)
{
    try {
        $barang = Barang::findOrFail($id);

        if($barang->toko > 0) {
            return response()->json([
                'success' => true,
                'message' => 'Stok tersedia',
                'data' => [
                    'id_barang' => $barang->id,
                    'stok' => $barang->toko
                ]
            ]);
        } else {
            return response()->json([
                'error' => true,
                'message' => "Out of stok !!"
            ]);
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
                'barang.id as id_barang', 'barang.kode as barang_kode', 'barang.nama as barang_nama', 'barang.hpp as harga_beli_barang', 'barang.harga_toko', 'barang.harga_partai', 'barang.toko', 'barang.supplier', 'supplier.id as id_supplier','supplier.nama as nama_supplier', 'supplier.kode as kode_supplier'
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
        $itemPenjualan = ItemPenjualan::findOrFail($id);
        $itemPenjualan->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Item penjualan successfully deleted!',
            'data' => $itemPenjualan
        ], 200);
    } catch (\Throwable $th) {
        throw $th;
    }
}

public function delete_item_penjualan_po($id)
{
    try {
        $itemPenjualan = ItemPenjualan::findOrFail($id);
        $itemPenjualan->qty = 0;
        $itemPenjualan->last_qty = NULL;
        $itemPenjualan->stop_qty = "False";
        $itemPenjualan->subtotal = $itemPenjualan->harga;
        $itemPenjualan->qty_terima = 0;
        $itemPenjualan->save();

        $dataPenjualan = Penjualan::whereKode($itemPenjualan->kode)->first();
        $updatePenjualan = Penjualan::findOrFail($dataPenjualan->id);
        $updatePenjualan->dikirim = 0;
        $updatePenjualan->kembali = 0;
        $updatePenjualan->save();

        $purchaseOrders = PurchaseOrder::where('kode_barang', $itemPenjualan->kode_barang)
        ->where('po_ke', '!=', 0)
        ->get();

        foreach($purchaseOrders as $poitem) {
            $deletePo = PurchaseOrder::findOrFail($poitem->id);
            $deletePo->forceDelete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Item pembelian successfully deleted!',
            'data' => $updatePenjualan
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

        if ($userRole->name !== "MASTER" && $userRole->name !== "ADMIN" && $userRole->name !== "KASIR" && $userRole->name !== "GUDANG" && $userRole->name !== "KASIR_GUDANG") {
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

public function checkInternetConnection()
{
    $urlToCheck = 'https://sockjs-ap1.pusher.com';
    try {
        $startTime = microtime(true);

        $fileSize = 1024 * 1024;
        $response = Http::get($urlToCheck);
        $fileDownloadTime = microtime(true) - $startTime;

        $speedInKbps = ($fileSize * 8) / ($fileDownloadTime * 1024);
        $speedInMbps = $speedInKbps / 1000;

        return response()->json([
            'success' => true,
            'time_taken' => round($fileDownloadTime, 2),
            'speed' => intval($speedInMbps),
        ]);
    } catch (\Exception $e) {
        return false;
    }
}

public function update_status_kirim(Request $request, $id)
{
    try {
        $user = Auth::user();
        $userRole = Roles::findOrFail($user->role);

        $dataPenjualan = Penjualan::findOrFail($id);
        $dataPenjualan->dikirim = $request->status_kirim === "DIKIRIM" ? $dataPenjualan->jumlah : 0;
        $dataPenjualan->receive = $request->status_kirim === "DIKIRIM" ? "True" : "False";
        $dataPenjualan->status = $request->status_kirim;
        $dataPenjualan->save();

        $dataItemPenjualan = ItemPenjualan::whereKode($dataPenjualan->kode)->first();
        $updateItem = ItemPenjualan::findOrFail($dataItemPenjualan->id);
        $updateItem->qty_terima = $dataItemPenjualan->qty;
        $updateItem->save();

        $kas = Kas::where('kode', $dataPenjualan['kode_kas'])->first();

        $updateKas = Kas::findOrFail($kas->id);
        $updateKas->saldo = $kas->saldo - intval($dataPenjualan->biayakirim);
        $updateKas->save();


        $data_event = [
            'alert' => 'success',
            'routes' => 'penjualan',
            'type' => 'updated',
            'notif' => "Status kirim Penjualan dengan kode, {$dataPenjualan->kode}, has been updated!",
            'user' => Auth::user()
        ];

        event(new EventNotification($data_event));

        return response()->json([
            'success' => true,
            'message' => "Status kirim penjualan dengan kode, {$dataPenjualan->kode} has been updated 😵‍💫"
        ]);
    } catch (\Throwable $th) {
        throw $th;
    }
}

}
