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
use App\Models\{Penjualan,ItemPenjualan,Supplier,Pelanggan,Barang,Kas,Piutang,ItemPiutang, PembayaranAngsuran};
use Auth;

class DataItemPenjualanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        try {
            $itemPenjualan = ItemPenjualan::paginate(10);

            return new ResponseDataCollect($itemPenjualan);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function penjualanTerbaik()
    {
        $penjualanTerbaik = ItemPenjualan::penjualanTerbaikSatuBulanKedepan();

        return response()->json([
          'success' => true,
          'message' => 'Prediksi penjualan terbaik satu bulan kedepan 🛒🛍️',
          'data' => $penjualanTerbaik
      ], 200);
    }

    public function barangTerlaris()
    {
        $barangTerlaris = ItemPenjualan::barangTerlaris();

        return response()->json([
          'success' => true,
          'message' => 'Barang terlaris 🛒🛍️',
          'data' => $barangTerlaris
      ], 200);
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
        try {
            $itemId = $request->item_id;

            $dataPembelian = Penjualan::findOrFail($id);

            if($dataPembelian->po === "True") {
                $updateItemPembelian = ItemPenjualan::findOrFail($itemId);

                $dataBarang = Barang::whereKode($updateItemPembelian->kode_barang)->first();

                $stokBarangUpdate = Barang::findOrFail($dataBarang->id);

                if($request->qty) {
                    $updateItemPembelian->qty = intval($request->qty);
                    $updateItemPembelian->subtotal = intval($request->qty) * intval($updateItemPembelian->harga_beli);

                    if(intval($request->qty) < $dataBarang->last_qty) {
                        $newStok = $stokBarangUpdate->toko - intval($request->qty);
                    } else if(intval($request->qty) > $dataBarang->last_qty) {
                        $newStok = $stokBarangUpdate->toko - intval($request->qty);
                    } else {
                        $newStok = $dataBarang->toko;
                    }
                    

                    $stokBarangUpdate->toko = $newStok;
                    $stokBarangUpdate->last_qty = $request->qty;
                }

                if($request->harga) {
                    $updateItemPembelian->harga = intval($request->harga);
                    $updateItemPembelian->subtotal = intval($updateItemPembelian->qty) * intval($request->harga);

                    $stokBarangUpdate->hpp = $request->harga;
                }

                $updateItemPembelian->save();

                $dataItemPembelian = ItemPembelian::whereKode($updateItemPembelian->kode)->get();

                $totalSubtotal = $dataItemPembelian->sum('subtotal');

                $stokBarangUpdate->save();

                $dataPembelian->jumlah = $totalSubtotal;
                $dataPembelian->bayar = $dataPembelian->bayar;
                $dataPembelian->hutang = intval($updateItemPembelian->subtotal) - intval($dataPembelian->bayar);
                $dataPembelian->jt = $request->jt ? $request->jt : $dataPembelian->jt;
                $dataPembelian->save();

                $data_event = [
                    'type' => 'updated',
                    'routes' => 'purchase-order-edit',
                    'notif' => "Update itempembelian, successfully update!"
                ];
            } else {
                $updateItemPembelian = ItemPenjualan::findOrFail($itemId);
                $dataBarang = Barang::whereKode($updateItemPembelian->kode_barang)->first();

                $stokBarangUpdate = Barang::findOrFail($dataBarang->id);

                if($request->qty) {
                    $updateItemPembelian->qty = intval($request->qty);
                    $updateItemPembelian->subtotal = intval($request->qty) * intval($updateItemPembelian->harga_beli);

                    if(intval($request->qty) < intval($dataBarang->last_qty)) {
                        $bindStok = intval($stokBarangUpdate->last_qty) - intval($request->qty);
                        $newStok = $stokBarangUpdate->toko - $bindStok;;
                    } else if(intval($request->qty) > intval($dataBarang->last_qty)) {
                        $newStok = $stokBarangUpdate->toko - intval($request->qty);
                    } else {
                        $newStok = $dataBarang->toko;
                    }
                    
                    $stokBarangUpdate->toko = $newStok;
                    $stokBarangUpdate->last_qty = $request->qty;
                }

                if($request->harga) {
                    $updateItemPembelian->harga = intval($request->harga);
                    $updateItemPembelian->subtotal = intval($updateItemPembelian->qty) * intval($request->harga);

                    $stokBarangUpdate->harga_toko = $request->harga;
                }

                $updateItemPembelian->save();

                $dataItemPembelian = ItemPenjualan::whereKode($updateItemPembelian->kode)->get();

                $totalSubtotal = $dataItemPembelian->sum('subtotal');

                $stokBarangUpdate->save();

                $dataPembelian->jumlah = $totalSubtotal;
                $dataPembelian->bayar = $totalSubtotal;
                $dataPembelian->save();
                $data_event = [
                    'type' => 'updated',
                    'routes' => 'penjualan-toko-edit',
                    'notif' => "Update itempenjualan toko, successfully update!"
                ];

            }

            event(new EventNotification($data_event));

            $newUpdateItem = ItemPenjualan::findOrFail($itemId);
            $newDataPembelian = Penjualan::findOrFail($dataPembelian->id);

            return response()->json([
                'success' => true,
                'message' => "Item penjualan update!",
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
