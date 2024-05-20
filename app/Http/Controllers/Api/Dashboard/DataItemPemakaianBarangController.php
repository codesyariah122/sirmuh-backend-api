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
use App\Models\{ItemPemakaian,  Barang, Supplier};
use Auth;

class DataItemPemakaianBarangController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

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
            $data = $request->all();

            $dataSupplier = Supplier::findOrFail($data['barang']['supplier_id']);

            $newItem = new ItemPemakaian;

            $newItem->kode_pemakaian = $data['kode'];
            $newItem->draft = $data['draft'];
            $newItem->barang_asal = $data['barang']['kode_barang'];
            $newItem->harga = $data['barang']['harga_beli'];
            $newItem->total = $data['barang']['qty'] > 0 ? intval($data['barang']['harga_beli']) * $data['barang']['qty'] : intval($data['barang']['harga_beli']);
            $newItem->supplier = $dataSupplier->kode;
            $newItem->save();

            return response()->json([
                'success' => true,
                'message' => "New item pemakaian {$newItem->barang_asal}, successfully added✨",
                'data' => $newItem
            ]);
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
        $dataItem = ItemPemakaian::whereKodePemakaian($id)->first();
        $item = ItemPemakaian::query()
        ->select('itempemakaian.id','itempemakaian.kode_pemakaian', 'itempemakaian.draft', 'itempemakaian.barang_asal', 'itempemakaian.qty_asal', 'itempemakaian.barang_tujuan', 'itempemakaian.qty_tujuan', 'itempemakaian.harga', 'itempemakaian.biaya', 'itempemakaian.total', 'itempemakaian.supplier', 'barang.id as id_barang', 'barang.kode as kode_barang', 'barang.nama as nama_barang', 'barang.toko as stok_barang', 'barang.satuan', 'barang.hpp as harga_beli', 'supplier.kode as kode_supplier', 'supplier.nama as nama_supplier')
        ->leftJoin('barang', 'itempemakaian.barang_asal', '=', 'barang.kode')
        ->leftJoin('supplier', 'itempemakaian.supplier', '=', 'supplier.kode')
        ->findOrFail($dataItem->id);

        return response()->json([
            'success' => true,
            'message' => "Show item pemakaian barang {$item->kode}",
            'data' => $item
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
        //
    }
}
