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
use App\Models\{Barang, Kategori, SatuanBeli, SatuanJual, Supplier, LabaRugi};
use Auth;
use PDF;

class DataLabaRugiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try{
            $keywords = $request->query('keywords');
            $today = now()->toDateString();
            $pelanggan = $request->query('pelanggan');
            $dateTransaction = $request->query('date_transaction');
            $viewAll = $request->query('view_all');
            $user = Auth::user();
            $currentMonth = now()->format('m');
            $currentYear = now()->format('Y');

            $query = LabaRugi::select(
                "labarugi.id",
                "labarugi.tanggal",
                "labarugi.kode",
                "labarugi.kode_barang",
                "labarugi.nama_barang",
                "labarugi.penjualan",
                "labarugi.hpp",
                "labarugi.diskon",
                "labarugi.labarugi",
                "labarugi.operator",
                "labarugi.pelanggan",
                "labarugi.keterangan",
                "labarugi.nama_pelanggan", 
                'barang.nama as barang_nama',
                'barang.satuan as satuan_barang',
                'barang.hpp as hpp_barang',
                'barang.harga_toko as harga_toko'
            )
            ->leftJoin('barang', 'labarugi.kode_barang', '=', 'barang.kode')
            ->whereYear('labarugi.tanggal', $currentYear)
            ->whereMonth('labarugi.tanggal', $currentMonth)
            ->orderByDesc('labarugi.id')
            ->limit(10);

            $keywords = $request->query('keywords');
            $startDate = $request->start_date;
            $endDate = $request->end_date;

            if($viewAll === false || $viewAll === "false") {
                $query->whereDate('labarugi.tanggal', '=', $today);
            }

            if ($keywords) {
                $query->where('labarugi.kode', 'like', '%' . $keywords . '%');
            }

            if ($pelanggan) {
                $query->where('labarugi.pelanggan', $pelanggan);
            }

            if ($dateTransaction) {
                $query->whereDate('labarugi.tanggal', '=', $dateTransaction);
            }

            $labarugi = $query->paginate(10);

            return new ResponseDataCollect($labarugi);
        }catch(\Throwable $th) {
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

    public function labaRugiWeekly()
    {
        try {
            $query = LabaRugi::query()
            ->select(
                DB::raw('YEARWEEK(tanggal) as minggu'),
                DB::raw('SUM(labarugi) as total_laba')
            )
            ->groupBy('minggu')
            ->orderBy('minggu', 'asc');

            $labaRugiPerMinggu = $query->get();

            $chartData = $labaRugiPerMinggu->map(function ($labaRugi) {
                $year = substr($labaRugi->minggu, 0, 4);
                $week = substr($labaRugi->minggu, 4, 2);

            // Mengonversi minggu dan tahun menjadi tanggal awal dan akhir dari minggu tersebut
                $startOfWeek = date('Y-m-d', strtotime($year . 'W' . $week));
                $endOfWeek = date('Y-m-d', strtotime($year . 'W' . $week . '7'));

                return [
                    'week_start' => $startOfWeek,
                    'week_end' => $endOfWeek,
                    'total_laba' => $labaRugi->total_laba,
                ];
            });

            return response()->json([
                'success' => true, 
                'message' => 'Grafik Laba Rugi Weekly',
                'label' => 'Total Laba Rugi',
                'data' => $chartData
            ]);

        } catch (\Throwable $th) {
            throw $th;
        }
    }



    public function labaRugiDaily(int $day) 
    {
        try {
            $label = "Total Penjualan Harian";
            $startDate = now()->subDays($day - 1)->startOfDay();
            $endDate = now()->endOfDay();

            $totalLabaPerDay = LabaRugi::whereBetween('tanggal', [$startDate, $endDate])
            ->groupBy(\DB::raw('DATE(tanggal)'))
            ->select(\DB::raw('DATE(tanggal) as date, SUM(labarugi) as total_laba'))
            ->orderBy('date', 'ASC')
            ->get();

            return response()->json([
                'success' => true,
                'label' => $label,
                'message' => 'Laba Harian untuk ' . $day . ' Hari Terakhir 📝',
                'data' => $totalLabaPerDay,
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    
    public function labaRugiLastMonth(int $jmlMonth)
    {
        try {
            $label = "Total Penjualan";
            $startDate = now()->subMonthsNoOverflow($jmlMonth - 1)->startOfMonth();
            // $startDate = now()->subMonthsNoOverflow($jmlMonth)->startOfMonth();

            // endDate menggunakan now() agar termasuk bulan saat ini
            $endDate = now()->endOfMonth();

            // Query the labarugi table for the specified period and group by month
            $totalLabaPerMonth = LabaRugi::whereBetween('tanggal', [$startDate, $endDate])
            ->groupBy(\DB::raw('YEAR(tanggal), MONTH(tanggal)'))
            ->select(\DB::raw('YEAR(tanggal) as year, MONTH(tanggal) as month, SUM(labarugi) as total_laba'))
            ->orderBy('labarugi', 'DESC')
            ->get();

            return response()->json([
                'success' => true,
                'label' => $label,
                'message' => 'Laba 3 BLN Terakhir 📝',
                'data' => $totalLabaPerMonth,
            ]);
        } catch(\Throwable $th) {
            throw $th;
        }
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

        } catch(\Throwable $th) {
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
        //
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
