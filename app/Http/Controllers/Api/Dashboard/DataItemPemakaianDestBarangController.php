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
use App\Models\{ItemPemakaianOrigin, ItemPemakaianDest,  Barang, Supplier, PemakaianBarang};
use Auth;

class DataItemPemakaianDestBarangController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
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
                $newItem = new ItemPemakaianDest;
                $dataSupplier = Supplier::findOrFail($item['supplier_id']);
                $newItem->kode_pemakaian = $data['kode'];
                $newItem->qty = $item['qty'];
                $newItem->barang = $item['kode_barang'];
                $newItem->harga = $item['harga_beli'];
                $newItem->total = $item['qty'] > 0 ? intval($item['harga_beli']) * $item['qty'] : intval($item['harga_beli']);
                $newItem->supplier = $dataSupplier->kode;
                $newItem->save();

                return response()->json([
                    'success' => true,
                    'message' => "New item pemakaian {$newItem->barang}, successfully added✨",
                    'draft' => true,
                    'item_pemakaian_id' => $newItem->id,
                    'data' => $newItem->kode
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
    public function item_pemakaian_result($id)
    {
        try {
            $items = ItemPemakaianOrigin::query()
            ->select('itempemakaianorigin.id','itempemakaianorigin.kode_pemakaian', 'itempemakaianorigin.barang', 'itempemakaianorigin.qty', 'itempemakaianorigin.harga', 'itempemakaianorigin.total', 'itempemakaianorigin.supplier', 'barang.id as id_barang', 'barang.kode as kode', 'barang.nama as nama', 'barang.toko as stok_barang', 'barang.satuan', 'barang.hpp as harga_beli', 'supplier.id as supplier_id','supplier.kode as kode_supplier', 'supplier.nama as nama_supplier')
            ->leftJoin('barang', 'itempemakaianorigin.barang', '=', 'barang.kode')
            ->leftJoin('supplier', 'itempemakaianorigin.supplier', '=', 'supplier.kode')
            ->where('itempemakaianorigin.kode_pemakaian', $id)
            ->get();

            $detailPemakaian = PemakaianBarang::whereKode($id)->first();
            $detail = PemakaianBarang::findOrFail($detailPemakaian->id);

            $lastItem = $items->last();

            $lastItemId = $lastItem ? $lastItem->id : null;

            return response()->json([
                'success' => true,
                'message' => "Show item pemakaian barang {$id}",
                'data' => $items,
                'detail' => $detail,
                'last_item_pemakaian_id' => $lastItemId
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    
    public function show($id)
    {
       try {
        $items = ItemPemakaianDest::query()
        ->select('itempemakaiandest.id','itempemakaiandest.kode_pemakaian', 'itempemakaiandest.barang', 'itempemakaiandest.qty', 'itempemakaiandest.harga', 'itempemakaiandest.total', 'itempemakaiandest.supplier', 'barang.id as id_barang', 'barang.kode as kode', 'barang.nama as nama', 'barang.toko as stok_barang', 'barang.satuan', 'barang.hpp as harga_beli', 'supplier.id as supplier_id','supplier.kode as kode_supplier', 'supplier.nama as nama_supplier')
        ->leftJoin('barang', 'itempemakaiandest.barang', '=', 'barang.kode')
        ->leftJoin('supplier', 'itempemakaiandest.supplier', '=', 'supplier.kode')
        ->where('itempemakaiandest.kode_pemakaian', $id)
        ->get();

        $detailPemakaian = PemakaianBarang::whereKode($id)->first();
        $detail = PemakaianBarang::findOrFail($detailPemakaian->id);

        $lastItem = $items->last();

        $lastItemId = $lastItem ? $lastItem->id : null;

        return response()->json([
            'success' => true,
            'message' => "Show item pemakaian barang {$id}",
            'data' => $items,
            'detail' => $lastItem,
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
