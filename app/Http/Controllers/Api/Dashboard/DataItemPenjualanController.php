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

    public function update_po_item(Request $request, $id)
    {
        try {
            $itemId = $request->item_id;
            $dataPembelian = Pembelian::findOrFail($id);
            $updateItemPembelian = ItemPembelian::findOrFail($itemId);

            $updatePurchaseOrderItem = PurchaseOrder::where('kode_barang', $updateItemPembelian->kode_barang)
            ->orderBy('qty', 'ASC')
            ->first();

            $purchaseOrderTerakhir = PurchaseOrder::where('kode_po', $updatePurchaseOrderItem->kode_po)
            ->orderBy('po_ke', 'desc')
            ->first();

            $poKeBaru = ($purchaseOrderTerakhir) ? $purchaseOrderTerakhir->po_ke + 1 : 1;
            $supplier = Supplier::whereKode($updateItemPembelian->supplier)->first();

            $updatePurchaseOrder = PurchaseOrder::findOrFail($updatePurchaseOrderItem->id);
            $updatePurchaseOrder->kode_po = $dataPembelian->kode;
            $updatePurchaseOrder->dp_awal = $dataPembelian->bayar;
            $updatePurchaseOrder->po_ke = $poKeBaru;
            $updatePurchaseOrder->nama_barang = $updateItemPembelian->nama_barang;
            $updatePurchaseOrder->kode_barang = $updateItemPembelian->kode_barang;
            $updatePurchaseOrder->qty = $updateItemPembelian->qty;
            $updatePurchaseOrder->supplier = "{$supplier->nama}({$updateItemPembelian->supplier})";
            $updatePurchaseOrder->harga_satuan = $updateItemPembelian->harga_beli;
            $updatePurchaseOrder->subtotal = $totalSubtotal;
            $updatePurchaseOrder->type = "pembelian";
            $updatePurchaseOrder->sisa_dp = $dataPembelian->bayar - $totalSubtotal;
            $updatePurchaseOrder->save();


            $data_event = [
                'type' => 'updated',
                'routes' => 'purchase-order-edit',
                'notif' => "Update itempembelian, successfully update!"
            ];

            return response()->json([
                'success' => true,
                'message' => "Item peurchase order update!",
                'purchase_orders' => $updatePurchaseOrder
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function update_item_harga_po(Request $request, $id)
    {
        try {
            $order_id = $request->order_id;

            $dataItemPembelian = ItemPembelian::findOrFail($id);
            $dataItemPembelian->harga_beli = $request->harga_beli;
            $dataItemPembelian->subtotal = $request->qty * $request->harga_beli;
            $dataItemPembelian->save();


            $previousPo = PurchaseOrder::where('id', '<', $order_id)
            ->orderBy('id', 'desc')
            ->first();
            $dataPoUpdate = PurchaseOrder::findOrFail($order_id);
            $dataPoUpdate->harga_satuan = $request->harga_beli;
            $dataPoUpdate->subtotal = $dataPoUpdate->qty * $request->harga_beli;

            $previousSubTotal = $dataItemPembelian->qty * $dataItemPembelian->harga_beli;
            
            if($previousSubTotal > $dataPoUpdate->subtotal) {
                $sisaDp = $previousSubTotal - $dataPoUpdate->subtotal;
            } else {
                $sisaDp = $dataPoUpdate->subtotal - $previousSubTotal;
            }

            $dataPoUpdate->sisa_dp = $sisaDp;
            $dataPoUpdate->save();

            $itemPurchaseOrders = PurchaseOrder::where('kode_po', $dataPoUpdate->kode_po)->get();
            $totalSubTotalOrder = $itemPurchaseOrders->sum('subtotal');
            $dataPembelian = Pembelian::whereKode($dataItemPembelian->kode)->first();
            $updatePembelian = Pembelian::findOrFail($dataPembelian->id);
            $updatePembelian->diterima = $totalSubTotalOrder;
            $updatePembelian->save();

            $newDataPembelian = Pembelian::select('pembelian.kode', 'pembelian.draft', 'pembelian.tanggal', 'pembelian.supplier', 'pembelian.kode_kas', 'pembelian.jumlah', 'pembelian.bayar', 'pembelian.diterima', 'pembelian.jt', 'pembelian.lunas','pembelian.visa','pembelian.hutang','pembelian.po', 'itempembelian.kode as kode_item_pembelian', 'itempembelian.draft', 'itempembelian.kode_barang', 'itempembelian.nama_barang', 'itempembelian.satuan', 'itempembelian.qty', 'itempembelian.last_qty', 'itempembelian.harga_beli', 'itempembelian.subtotal')
            ->leftJoin('itempembelian', 'pembelian.kode', '=', 'itempembelian.kode')
            ->where('pembelian.kode', $updatePembelian->kode)
            ->first();

            return response()->json([
                'success' => true,
                'message' => "Item pembelian update!",
                'data' => $newDataPembelian,
                'orders' => $order_id,
                'sisa_dp' => $dataPoUpdate->sisa_dp
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function update_item_pembelian_po_qty(Request $request, $id)
    {
        try {
            $order_id = $request->order_id;

            $dataItemPembelian = ItemPembelian::findOrFail($id);
            $dataItemPembelian->qty = $request->qty;
            $dataItemPembelian->last_qty = $request->last_qty;
            $dataItemPembelian->subtotal = $request->qty * $dataItemPembelian->harga_beli;

            if($request->qty < $dataItemPembelian->last_qty) {
                $totalTerima = $dataItemPembelian->last_qty - $dataItemPembelian->qty;
            } else {
                $totalTerima = $request->qty;
            }
            $dataItemPembelian->qty_terima = $totalTerima;

            $dataItemPembelian->save();

            $previousPo = PurchaseOrder::where('id', '<', $order_id)
            ->orderBy('id', 'desc')
            ->first();
            $dataPoUpdate = PurchaseOrder::findOrFail($order_id);
            $dataPoUpdate->qty = $request->qty;
            $dataPoUpdate->subtotal = $request->qty * $dataItemPembelian->harga_beli;

            $previousSubTotal = $dataItemPembelian->qty * $dataItemPembelian->harga_beli;
            
            if($previousSubTotal > $dataPoUpdate->subtotal) {
                $sisaDp = $previousSubTotal - $dataPoUpdate->subtotal;
            } else {
                $sisaDp = $dataPoUpdate->subtotal - $previousSubTotal;
            }

            $dataPoUpdate->sisa_dp = $sisaDp;
            $dataPoUpdate->save();

            $itemPurchaseOrders = PurchaseOrder::where('kode_po', $dataPoUpdate->kode_po)->get();
            $totalSubTotalOrder = $itemPurchaseOrders->sum('subtotal');
            $dataPembelian = Pembelian::whereKode($dataItemPembelian->kode)->first();
            $updatePembelian = Pembelian::findOrFail($dataPembelian->id);
            $updatePembelian->diterima = $totalSubTotalOrder;
            $updatePembelian->save();

            $newDataPembelian = Pembelian::select('pembelian.kode', 'pembelian.draft', 'pembelian.tanggal', 'pembelian.supplier', 'pembelian.kode_kas', 'pembelian.jumlah', 'pembelian.bayar', 'pembelian.diterima', 'pembelian.jt', 'pembelian.lunas','pembelian.visa','pembelian.hutang','pembelian.po', 'itempembelian.kode as kode_item_pembelian', 'itempembelian.draft', 'itempembelian.kode_barang', 'itempembelian.nama_barang', 'itempembelian.satuan', 'itempembelian.qty', 'itempembelian.last_qty', 'itempembelian.harga_beli', 'itempembelian.subtotal')
            ->leftJoin('itempembelian', 'pembelian.kode', '=', 'itempembelian.kode')
            ->where('pembelian.kode', $updatePembelian->kode)
            ->first();

            return response()->json([
                'success' => true,
                'message' => "Item pembelian update!",
                'data' => $newDataPembelian,
                'orders' => $order_id,
                'sisa_dp' => $dataPoUpdate->sisa_dp
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
    
    public function update(Request $request, $id)
    {
        try {
            $itemId = $request->item_id;
            $dataPembelian = Penjualan::findOrFail($id);


            if($dataPembelian->po === "True") {
                $updateItemPembelian = ItemPenjualan::findOrFail($itemId);

                if($request->qty) {
                    $updateItemPembelian->qty = intval($request->qty);
                    $updateItemPembelian->last_qty = $updateItemPembelian->qty;
                    $updateItemPembelian->subtotal = intval($request->qty) * intval($updateItemPembelian->harga);
                }

                if($request->harga) {
                    $updateItemPembelian->harga = intval($request->harga);
                    $updateItemPembelian->subtotal = intval($updateItemPembelian->qty) * intval($request->harga);
                }

                $updateItemPembelian->save();

                $dataItemPembelian = ItemPenjualan::whereKode($updateItemPembelian->kode)->get();

                $totalSubtotal = $dataItemPembelian->sum('subtotal');

                $dataPembelian->jumlah = $totalSubtotal;
                $dataPembelian->bayar = $request->bayar;
                $dataPembelian->kembali = $totalSubtotal - $request->bayar;
                $dataPembelian->jt = $request->jt ? $request->jt : $dataPembelian->jt;
               
                $dataPembelian->save();

                $data_event = [
                    'type' => 'updated',
                    'routes' => 'penjualan-toko',
                    'notif' => "Update itempenjualan, successfully update!"
                ];
            } else {                
                $updateItemPembelian = ItemPenjualan::findOrFail($itemId);
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
                    $updateItemPembelian->subtotal = intval($request->qty) * intval($updateItemPembelian->harga);
                }

                if($request->harga) {
                    $updateItemPembelian->harga = intval($request->harga);
                    $updateItemPembelian->subtotal = intval($updateItemPembelian->qty) * intval($request->harga);
                }

                $updateItemPembelian->save();

                $dataItemPembelian = ItemPenjualan::whereKode($updateItemPembelian->kode)->get();

                $totalSubtotal = $dataItemPembelian->sum('subtotal');

                $dataPembelian->jumlah = $totalSubtotal;
                $dataPembelian->bayar = $dataPembelian->bayar;
                $dataPembelian->kembali = $dataPembelian->bayar - $totalSubtotal;
                $dataPembelian->jt = $request->jt ? $request->jt : $dataPembelian->jt;
                $dataPembelian->save();

                $data_event = [
                    'type' => 'updated',
                    'routes' => 'pembelian-langsung-edit',
                    'notif' => "Update itempembelian, successfully update!"
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
