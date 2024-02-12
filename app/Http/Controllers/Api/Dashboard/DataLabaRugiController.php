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

            if ($keywords) {
                $query->where(function ($query) use ($keywords) {
                    $query->where('kode', 'like', '%' . $keywords . '%')
                    ->orWhere('nama_barang', 'like', '%' . $keywords . '%')
                    ->orWhere('pelanggan', 'like', '%' . $keywords . '%')
                    ->orWhere('operator', 'like', '%' . $keywords . '%');
                });
            }

            if ($startDate && $endDate) {
                $query->whereBetween('tanggal', [$startDate, $endDate]);
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
