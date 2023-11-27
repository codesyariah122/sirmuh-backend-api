<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\{WebFeatureHelpers};
use App\Http\Resources\BarangCollection;
use App\Models\{Barang};

class DataBarangController extends Controller 
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    private $feature_helpers;

    public function __construct()
    {
        $this->feature_helpers = new WebFeatureHelpers;
    }
    public function index(Request $request)
    {
        try {
            $keywords = $request->query('keywords');
            if($keywords) {
                $barangs = Barang::whereNull('deleted_at')
                ->select('kode', 'nama', 'kategori', 'satuanbeli', 'satuan', 'isi', 'toko', 'gudang', 'hpp', 'harga_toko', 'diskon', 'jenis', 'supplier', 'kode_barcode', 'tgl_terakhir', 'harga_terakhir')
                ->where('nama', 'like', '%'.$keywords.'%')
                ->orderByDesc('harga_toko')
                ->paginate(10);
            } else {
                $barangs = Barang::whereNull('deleted_at')
                ->select('kode', 'nama', 'kategori', 'satuanbeli', 'satuan', 'isi', 'toko', 'gudang', 'hpp', 'harga_toko', 'diskon', 'jenis', 'supplier', 'kode_barcode', 'tgl_terakhir', 'harga_terakhir')
                ->orderByDesc('harga_toko')
                ->paginate(10);
            }

            foreach ($barangs as $item) {
                $kodeBarcode = $item->kode_barcode;
                $this->feature_helpers->generateQrCode($kodeBarcode);
            }

            return new BarangCollection($barangs);

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
