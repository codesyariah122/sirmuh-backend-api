<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Events\{EventNotification};
use App\Helpers\{WebFeatureHelpers};
use App\Http\Resources\{ResponseDataCollect, RequestDataCollect};
use App\Models\{Pemasukan, SetupPerusahaan, Kas, User, Roles};
use Auth;


class DataPemasukanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->helpers = new WebFeatureHelpers;
    }

    public function pemasukanWeekly()
    {
        try {
            $startOfMonth = now()->startOfMonth();
            $endOfMonth = now()->endOfMonth();

            $query = Pemasukan::query()
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

    public function index(Request $request)
    {
        try {
            $keywords = $request->query('keywords');
            $jenis = $request->query('jenis');
            $viewAll = $request->query('view_all');
            $today = now()->toDateString();
            $now = now();
            $startOfMonth = $now->startOfMonth()->toDateString();
            $endOfMonth = $now->endOfMonth()->toDateString();
            $dateTransaction = $request->query('date_transaction');

            $query = Pemasukan::query()
            ->select('pemasukan.*', 'jenis_pemasukan.kode as kode_jenis_pemasukan', 'jenis_pemasukan.nama as jenis_pemasukan_nama', 'kas.kode as kas_kode', 'kas.nama as nama_kas')
            ->leftJoin('jenis_pemasukan', 'pemasukan.kd_biaya', '=', 'jenis_pemasukan.kode')
            ->leftJoin('kas', 'pemasukan.kode_kas', '=', 'kas.kode')
            ->whereNull('pemasukan.deleted_at')
            ->limit(10);

            if ($dateTransaction) {
                $query->whereDate('pemasukan.tanggal', '=', $dateTransaction);
            }

            if ($keywords) {
                $query->where('pemasukan.kode', 'like', '%' . $keywords . '%');
            }

            if ($jenis) {
                $query->where('jenis_pemasukan.kode', 'like', '%' . $jenis . '%');
            }

            if($viewAll === false || $viewAll === "false") {
                // $query->whereDate('pembelian.tanggal', '=', $today);
                $query->whereBetween('pemasukan.tanggal', [$startOfMonth, $endOfMonth]);
            }
            

            $pemasukans = $query->orderBy("pemasukan.id", "DESC")
            ->paginate(10);

            return new ResponseDataCollect($pemasukans);
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
            'kode' => 'required',
            'jenis_pemasukan' => 'required',
            'kode_kas' => 'required',
            'jumlah' => 'required'
        ]);

           if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $newPemasukan = new Pemasukan;
        $newPemasukan->kode = $request->kode;
        $newPemasukan->tanggal = $request->tanggal;
        $newPemasukan->kd_biaya = $request->jenis_pemasukan;
        $newPemasukan->keterangan = $request->keterangan;
        $newPemasukan->kode_kas = $request->kode_kas;
        $newPemasukan->jumlah = $request->jumlah;
        $newPemasukan->operator = $request->operator;
        $newPemasukan->kode_pelanggan = $request->kode_pelanggan;
        $newPemasukan->nama_pelanggan = $request->nama_pelanggan;
        $newPemasukan->save();

        if($newPemasukan) {
            $dataKas = Kas::whereKode($request->kode_kas)->first();
            $updateKas = Kas::findOrFail($dataKas->id);
            $updateKas->saldo = intval($dataKas->saldo) + intval($request->jumlah);
            $updateKas->save();

            $userOnNotif = Auth::user();
            $data_event = [
                'routes' => 'kas',
                'alert' => 'success',
                'type' => 'add-data',
                'notif' => "Pemasukan {$newPemasukan->kode}, baru saja ditambahkan 🤙!",
                'data' => $newPemasukan->kode,
                'user' => $userOnNotif
            ];
            event(new EventNotification($data_event));


            $newDataPemasukan = Pemasukan::query()
            ->select('pemasukan.id', 'pemasukan.kode', 'pemasukan.tanggal', 'pemasukan.kd_biaya', 'pemasukan.keterangan', 'pemasukan.kode_kas', 'pemasukan.jumlah', 'pemasukan.operator', 'pemasukan.kode_pelanggan', 'pemasukan.nama_pelanggan', 'jenis_pemasukan.kode as kode_jenis_pemasukan', 'jenis_pemasukan.nama as nama_jenis_pemasukan')
            ->leftJoin('jenis_pemasukan', 'pemasukan.kd_biaya', '=', 'jenis_pemasukan.kode')
            ->findOrFail($newPemasukan->id);

            $historyKeterangan = "{$userOnNotif->name}, menambahkan data pemasukan {$newDataPemasukan->nama_jenis_pemasukan} dengan kode [{$newDataPemasukan->kode}], sebesar {$this->helpers->format_uang($newDataPemasukan->jumlah)}";
            $dataHistory = [
                'user' => $userOnNotif->name,
                'keterangan' => $historyKeterangan,
                'routes' => '/dashboard/backoffice/pemasukan',
                'route_name' => 'Data Pemasukan'
            ];
            $createHistory = $this->helpers->createHistory($dataHistory);

            return response()->json([
                'success' => true,
                'message' => "Pemasukan dengan kode {$newPemasukan->kode}, successfully added✨!",
                'data' => $newDataPemasukan
            ]);
        }

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
            $pemasukan = Pemasukan::query()
            ->select('pemasukan.*', 'jenis_pemasukan.kode as kode_biaya', 'jenis_pemasukan.nama as biaya_nama', 'kas.kode as kas_kode', 'kas.nama as nama_kas')
            ->leftJoin('jenis_pemasukan', 'pemasukan.kd_biaya', '=', 'jenis_pemasukan.kode')
            ->leftJoin('kas', 'pemasukan.kode_kas', '=', 'kas.kode')
            ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => "Detail Pemasukan",
                'data' => $pemasukan
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
        try {
            $user = Auth::user();

            $userRole = Roles::findOrFail($user->role);

            if($userRole->name === "MASTER" || $userRole->name === "ADMIN") {
                $pemasukan = Pemasukan::whereNull('deleted_at')
                ->findOrFail($id);
                $pemasukan->delete();

                $dataKas = Kas::whereKode($pemasukan->kode_kas)->first();
                $updateKas = Kas::findOrFail($dataKas->id);
                $updateKas->saldo = intval($dataKas->saldo) - intval($pemasukan->jumlah);
                $updateKas->save();

                $data_event = [
                    'alert' => 'error',
                    'routes' => 'pemasukan',
                    'type' => 'removed',
                    'notif' => "Pemasukan with kode {$pemasukan->kode}, has move to trash, please check trash!",
                    'user' => Auth::user()
                ];

                event(new EventNotification($data_event));

                return response()->json([
                    'success' => true,
                    'message' => "Pemasukan with kode {$pemasukan->kode} has move to trash, please check trash"
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

}
