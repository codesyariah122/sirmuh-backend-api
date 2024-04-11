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
use App\Models\{Penjualan};
use App\Events\{EventNotification};
use App\Helpers\{UserHelpers, WebFeatureHelpers};
use App\Http\Resources\ResponseDataCollect;
use Image;
use Auth;
use PDF;

class DataLaporanPenjualanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $keywords = $request->keywords;
            $startDate = $request->start_date;
            $endDate = $request->end_date;

            // var_dump($startDate); var_dump($endDate); die;
            $downloadData = $request->download_data;

            $query = Penjualan::query()
            ->select(
                'penjualan.id',
                'penjualan.tanggal', 'penjualan.kode', 'penjualan.pelanggan', 'penjualan.operator','penjualan.jumlah','penjualan.bayar','penjualan.diskon','penjualan.tax','penjualan.lunas','penjualan.visa',
                // DB::raw('GROUP_CONCAT(itempenjualan.qty) as qty'),
                // DB::raw('GROUP_CONCAT(itempenjualan.subtotal) as subtotal'),
                // DB::raw('GROUP_CONCAT(itempenjualan.harga) as harga'),
                'pelanggan.nama as nama_pelanggan',
                'pelanggan.alamat as alamat_pelanggan'
            )
            ->leftJoin('pelanggan', 'penjualan.pelanggan', '=', 'pelanggan.kode')
            ->groupBy(
                'penjualan.id',
                'penjualan.tanggal',
                'penjualan.kode',
                'penjualan.pelanggan',
                'penjualan.operator',
                'penjualan.jumlah',
                'penjualan.bayar',
                'penjualan.diskon',
                'penjualan.tax',
                'penjualan.lunas',
                'penjualan.visa',
                'pelanggan.nama',
                'pelanggan.alamat'
            )
            ->orderByDesc('penjualan.tanggal')
            ->limit(10);

            if ($startDate || $endDate) {
                $query->whereBetween('penjualan.tanggal', [$startDate, $endDate]);
            }

            $penjualans = $query
            ->orderByDesc('penjualan.id')
            ->paginate(10);

            return new ResponseDataCollect($penjualans);
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
        //
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
