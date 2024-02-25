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
use App\Models\{Pembelian,ItemPembelian,Supplier,Barang,Kas,Hutang,ItemHutang,PembayaranAngsuran};
use Auth;

class DataItemPembelianController extends Controller
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
        try {
            $itemId = $request->item_id;
            $dataPembelian = Pembelian::findOrFail($id);


            if($dataPembelian->po === "True") {
                $updateItemPembelian = ItemPembelian::findOrFail($itemId);

                if($request->qty) {
                    $updateItemPembelian->qty = intval($request->qty);
                    $updateItemPembelian->last_qty = $request->last_qty;
                    $updateItemPembelian->subtotal = intval($request->qty) * intval($updateItemPembelian->harga_beli);

                    // $dataBarang = Barang::whereKode($updateItemPembelian->kode_barang)
                    // ->first();
                    // $updateStok = Barang::findOrFail($dataBarang->id);
                    // $updateStok->toko = $dataBarang->toko + intval($request->qty);;
                    // $updateStok->save();
                }

                if($request->harga_beli) {
                    $updateItemPembelian->harga_beli = intval($request->harga_beli);
                    $updateItemPembelian->subtotal = intval($updateItemPembelian->qty) * intval($request->harga_beli);
                }

                $updateItemPembelian->save();

                $dataItemPembelian = ItemPembelian::whereKode($updateItemPembelian->kode)->get();

                $totalSubtotal = $dataItemPembelian->sum('subtotal');

                $dataPembelian->jumlah = $dataPembelian->jumlah;
                $dataPembelian->bayar = $dataPembelian->jumlah;
                $dataPembelian->diterima = $totalSubtotal;
                $dataPembelian->jt = $request->jt ? $request->jt : $dataPembelian->jt;
               
                $dataPembelian->save();

                $data_event = [
                    'type' => 'updated',
                    'routes' => 'purchase-order-edit',
                    'notif' => "Update itempembelian, successfully update!"
                ];
            } else {                
                $updateItemPembelian = ItemPembelian::findOrFail($itemId);
                $qty = $updateItemPembelian->qty;
                $lastQty = $updateItemPembelian->last_qty;
                $newQty = $request->qty;

                // echo "Qty = " . $qty;
                // echo "<br>";
                // echo "last Qty = " . $lastQty;
                // echo "<br>";
                // echo "new qty = " . $newQty;
                // die;

                if($request->qty) {
                    $updateItemPembelian->qty = $newQty;
                    $updateItemPembelian->last_qty = $qty;
                    $updateItemPembelian->subtotal = intval($request->qty) * intval($updateItemPembelian->harga_beli);
                }

                if($request->harga_beli) {
                    $updateItemPembelian->harga_beli = intval($request->harga_beli);
                    $updateItemPembelian->subtotal = intval($updateItemPembelian->qty) * intval($request->harga_beli);
                }

                $updateItemPembelian->save();

                $dataItemPembelian = ItemPembelian::whereKode($updateItemPembelian->kode)->get();

                $totalSubtotal = $dataItemPembelian->sum('subtotal');

                $dataPembelian->jumlah = $totalSubtotal;
                $dataPembelian->bayar = $dataPembelian->bayar;
                $dataPembelian->diterima = $dataPembelian->diterima;
                $dataPembelian->jt = $request->jt ? $request->jt : $dataPembelian->jt;
                $dataPembelian->save();

                $data_event = [
                    'type' => 'updated',
                    'routes' => 'pembelian-langsung-edit',
                    'notif' => "Update itempembelian, successfully update!"
                ];

            }

            event(new EventNotification($data_event));

            $newUpdateItem = ItemPembelian::findOrFail($itemId);
            $newDataPembelian = Pembelian::findOrFail($dataPembelian->id);

            return response()->json([
                'success' => true,
                'message' => "Item pembelian update!",
                'data' => $newDataPembelian,
                'items' => $newUpdateItem
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function updateItemPo(Request $request, $id)
    {
        try {
            $itemId = $request->item_id;

            $dataPembelian = Pembelian::findOrFail($id);

            $updateItemPembelian = ItemPembelian::findOrFail($itemId);

            $dataBarang = Barang::whereKode($updateItemPembelian->kode_barang)->first();

            $stokBarangUpdate = Barang::findOrFail($dataBarang->id);

            if($request->qty) {
                $updateItemPembelian->qty = intval($request->qty);
                $updateItemPembelian->subtotal = intval($request->qty) * intval($updateItemPembelian->harga_beli);

                $stokBarangUpdate->toko = $dataBarang->toko + $request->qty;
            }

            if($request->harga_beli) {
                $updateItemPembelian->harga_beli = intval($request->harga_beli);
                $updateItemPembelian->subtotal = intval($updateItemPembelian->qty) * intval($request->harga_beli);

                $stokBarangUpdate->hpp = $request->harga_beli;
            }

            $updateItemPembelian->save();
            
            $dataItemPembelian = ItemPembelian::whereKode($updateItemPembelian->kode)->get();

            $totalSubtotal = $dataItemPembelian->sum('subtotal');

            $stokBarangUpdate->save();

            $dataPembelian->jumlah = $totalSubtotal;
            $dataPembelian->bayar = $totalSubtotal;
            $dataPembelian->diterima = $totalSubtotal;
            $dataPembelian->hutang = $dataPembelian->hutang - $totalSubtotal;
            $dataPembelian->save();

            $data_event = [
                'type' => 'updated',
                'routes' => 'pembelian-langsung-edit',
                'notif' => "Update itempembelian, successfully update!"
            ];

            event(new EventNotification($data_event));

            $newUpdateItem = ItemPembelian::findOrFail($itemId);
            $newDataPembelian = Pembelian::findOrFail($dataPembelian->id);

            return response()->json([
                'success' => true,
                'message' => 'Item pembelian update!',
                'data' => $newDataPembelian,
                'items' => $newUpdateItem
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
        //
    }
}
