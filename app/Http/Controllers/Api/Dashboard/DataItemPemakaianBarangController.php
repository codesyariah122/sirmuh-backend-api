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
use App\Models\{ItemPemakaian,  Barang, Supplier, PemakaianBarang};
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

            foreach($data['barangs'] as $item) {
                $newItem = new ItemPemakaian;
                $dataSupplier = Supplier::findOrFail($item['supplier_id']);
                $newItem->kode_pemakaian = $data['kode'];
                $newItem->barang_asal = $item['kode_barang'];
                $newItem->harga = $item['harga'];
                $newItem->total = $item['qty'] > 0 ? intval($item['harga']) * $item['qty'] : intval($item['harga']);
                $newItem->supplier = $dataSupplier->kode;
                $newItem->save();

                return response()->json([
                    'success' => true,
                    'message' => "New item pemakaian {$newItem->barang_asal}, successfully added✨",
                    'draft' => true,
                    'item_pemakaian_id' => $newItem->id,
                    'data' => $newItem
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
        $detailPemakaian = PemakaianBarang::whereKode($id)->first();
        $dataPemakaian = PemakaianBarang::findOrFail($detailPemakaian->id);

        $items = ItemPemakaian::query()
        ->select('itempemakaian.id','itempemakaian.kode_pemakaian', 'itempemakaian.barang_asal', 'itempemakaian.qty_asal', 'itempemakaian.barang_tujuan', 'itempemakaian.qty_tujuan', 'itempemakaian.harga', 'itempemakaian.biaya', 'itempemakaian.total', 'itempemakaian.supplier', 'barang.id as id_barang', 'barang.kode as kode', 'barang.nama as nama', 'barang.toko as stok_barang', 'barang.satuan', 'barang.hpp', 'supplier.kode as kode_supplier', 'supplier.nama as nama_supplier')
        ->leftJoin('barang', 'itempemakaian.barang_asal', '=', 'barang.kode')
        ->leftJoin('supplier', 'itempemakaian.supplier', '=', 'supplier.kode')
        ->where('itempemakaian.kode_pemakaian', $id)
        ->get();

        $lastItem = $items->last();

        $lastItemId = $lastItem ? $lastItem->id : null;

        return response()->json([
            'success' => true,
            'message' => "Show item pemakaian barang {$id}",
            'data' => $items,
            'detail' => $dataPemakaian,
            'last_item_pemakaian_id' => $lastItemId
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
        try {
            $data = $request->all();
            $dataItemPemakaian = ItemPemakaian::findOrFail($id);
            $dataItemPemakaian->qty_asal = $data['qty'];
            $dataItemPemakaian->harga = $data['harga'];
            $dataItemPemakaian->total = intval($data['qty']) * intval($data['harga']);
            $dataItemPemakaian->save();

            return response()->json([
                'success' => true,
                'message' => "successfully update new qty item pemakaian {$dataItemPemakaian->kode}",
                'data' => $dataItemPemakaian
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
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
            $itemPemakaian = ItemPemakaian::findOrFail($id);
            $itemPemakaian->delete();

            return response()->json([
                'success' => true,
                'message' => "Item pemakaian {$itemPemakaian->kode}, successfully deleted✨",
                'data' => $itemPemakaian
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
