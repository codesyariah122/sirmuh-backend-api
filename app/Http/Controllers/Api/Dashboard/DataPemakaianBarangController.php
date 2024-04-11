<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Events\{EventNotification};
use App\Helpers\{WebFeatureHelpers};
use App\Http\Resources\{ResponseDataCollect, RequestDataCollect};
use App\Models\{Penjualan,ItemPenjualan,Pelanggan,Barang,Kas};
use Auth;

class DataPemakaianBarangController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $keywords = $request->query('keywords');
            $today = now()->toDateString();

            $query = Penjualan::query()
            ->select(
                'penjualan.id','penjualan.kode','penjualan.tanggal','penjualan.operator','penjualan.alamat_pelanggan','penjualan.keterangan',
                'itempenjualan.kode', 'itempenjualan.kode_barang', 'itempenjualan.nama_barang', 'itempenjualan.satuan', 'itempenjualan.qty',
                'pelanggan.nama as pelanggan_nama', 'pelanggan.alamat as pelanggan_alamat'
            )
            ->leftJoin('itempenjualan', 'penjualan.kode', '=', 'itempenjualan.kode')
            ->leftJoin('pelanggan', 'penjualan.pelanggan', '=', 'pelanggan.kode')
            ->orderByDesc('penjualan.id')
            ->limit(10);

            if ($keywords) {
                $query->where('penjualan.kode', 'like', '%' . $keywords . '%');
            }

            $query->whereDate('penjualan.tanggal', '=', $today);

            $penjualans = $query
            ->where('penjualan.pelanggan', '=', '--')
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
