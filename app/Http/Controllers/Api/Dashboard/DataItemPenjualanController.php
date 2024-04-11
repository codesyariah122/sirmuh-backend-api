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
use App\Models\{Penjualan,ItemPenjualan,Supplier,Pelanggan,Barang,Kas,Piutang,ItemPiutang, PembayaranAngsuran, PurchaseOrder};
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
            $dataPenjualan = Penjualan::findOrFail($id);
            $updateItemPenjualan = ItemPenjualan::findOrFail($itemId);

            $updatePurchaseOrderItem = PurchaseOrder::where('kode_barang', $updateItemPenjualan->kode_barang)
            ->orderBy('qty', 'ASC')
            ->first();

            $purchaseOrderTerakhir = PurchaseOrder::where('kode_po', $updatePurchaseOrderItem->kode_po)
            ->orderBy('po_ke', 'desc')
            ->first();

            $poKeBaru = ($purchaseOrderTerakhir) ? $purchaseOrderTerakhir->po_ke + 1 : 1;
            $supplier = Supplier::whereKode($updateItemPenjualan->supplier)->first();

            $updatePurchaseOrder = PurchaseOrder::findOrFail($updatePurchaseOrderItem->id);
            $updatePurchaseOrder->kode_po = $dataPenjualan->kode;
            $updatePurchaseOrder->dp_awal = $dataPenjualan->bayar;
            $updatePurchaseOrder->po_ke = $poKeBaru;
            $updatePurchaseOrder->nama_barang = $updateItemPenjualan->nama_barang;
            $updatePurchaseOrder->kode_barang = $updateItemPenjualan->kode_barang;
            $updatePurchaseOrder->qty = $updateItemPenjualan->qty;
            $updatePurchaseOrder->supplier = "{$supplier->nama}({$updateItemPenjualan->supplier})";
            $updatePurchaseOrder->harga_satuan = $updateItemPenjualan->harga;
            $updatePurchaseOrder->subtotal = $totalSubtotal;
            $updatePurchaseOrder->type = "Penjualan";
            $updatePurchaseOrder->sisa_dp = $dataPenjualan->bayar - $totalSubtotal;
            $updatePurchaseOrder->save();


            $data_event = [
                'type' => 'updated',
                'routes' => 'penjualan-po',
                'notif' => "Update ItemPenjualan, successfully update!"
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

            $dataItemPenjualan = ItemPenjualan::findOrFail($id);
            $dataItemPenjualan->harga = $request->harga;
            $dataItemPenjualan->subtotal = $request->qty * $request->harga;
            $dataItemPenjualan->save();


            $previousPo = PurchaseOrder::where('id', '<', $order_id)
            ->orderBy('id', 'desc')
            ->first();
            $dataPoUpdate = PurchaseOrder::findOrFail($order_id);
            $dataPoUpdate->harga_satuan = $request->harga;
            $dataPoUpdate->subtotal = $dataPoUpdate->qty * $request->harga;

            $previousSubTotal = $dataItemPenjualan->qty * $dataItemPenjualan->harga;
            
            if($previousSubTotal > $dataPoUpdate->subtotal) {
                $sisaDp = $previousSubTotal - $dataPoUpdate->subtotal;
            } else {
                $sisaDp = $dataPoUpdate->subtotal - $previousSubTotal;
            }

            $dataPoUpdate->sisa_dp = $sisaDp;
            $dataPoUpdate->save();

            $itemPurchaseOrders = PurchaseOrder::where('kode_po', $dataPoUpdate->kode_po)->get();
            $totalSubTotalOrder = $itemPurchaseOrders->sum('subtotal');
            $dataPenjualan = Penjualan::whereKode($dataItemPenjualan->kode)->first();
            $updatePenjualan = Penjualan::findOrFail($dataPenjualan->id);
            $updatePenjualan->dikirim = $totalSubTotalOrder;
            $updatePenjualan->save();

            $newDataPenjualan = Penjualan::select('penjualan.kode', 'penjualan.draft', 'penjualan.tanggal', 'penjualan.pelanggan', 'penjualan.kode_kas', 'penjualan.jumlah', 'penjualan.bayar', 'penjualan.dikirim', 'penjualan.jt', 'penjualan.lunas','penjualan.visa','penjualan.piutang','penjualan.po', 'itempenjualan.kode as kode_item_Penjualan', 'itempenjualan.draft', 'itempenjualan.kode_barang', 'itempenjualan.nama_barang', 'itempenjualan.satuan', 'itempenjualan.qty', 'itempenjualan.last_qty', 'itempenjualan.harga', 'itempenjualan.subtotal')
            ->leftJoin('itempenjualan', 'penjualan.kode', '=', 'itempenjualan.kode')
            ->where('penjualan.kode', $updatePenjualan->kode)
            ->first();

            return response()->json([
                'success' => true,
                'message' => "Item penjualan update!",
                'data' => $newDataPenjualan,
                'orders' => $order_id,
                'sisa_dp' => $dataPoUpdate->sisa_dp
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function update_item_penjualan_po_qty(Request $request, $id)
    {
        try {
            $order_id = $request->order_id;

            $dataItemPenjualan = ItemPenjualan::findOrFail($id);
            $dataItemPenjualan->qty = $request->qty;
            $dataItemPenjualan->last_qty = $request->last_qty;
            $dataItemPenjualan->subtotal = $request->qty * $dataItemPenjualan->harga;

            if($request->qty < $dataItemPenjualan->last_qty) {
                $totalTerima = $dataItemPenjualan->last_qty - $dataItemPenjualan->qty;
            } else {
                $totalTerima = $request->qty;
            }
            $dataItemPenjualan->qty_terima = $totalTerima;

            $dataItemPenjualan->save();

            $previousPo = PurchaseOrder::where('id', '<', $order_id)
            ->orderBy('id', 'desc')
            ->first();
            $dataPoUpdate = PurchaseOrder::findOrFail($order_id);
            $dataPoUpdate->qty = $request->qty;
            $dataPoUpdate->subtotal = $request->qty * $dataItemPenjualan->harga;

            $previousSubTotal = $dataItemPenjualan->qty * $dataItemPenjualan->harga;
            
            if($previousSubTotal > $dataPoUpdate->subtotal) {
                $sisaDp = $previousSubTotal - $dataPoUpdate->subtotal;
            } else {
                $sisaDp = $dataPoUpdate->subtotal - $previousSubTotal;
            }

            $dataPoUpdate->sisa_dp = $sisaDp;
            $dataPoUpdate->save();

            $itemPurchaseOrders = PurchaseOrder::where('kode_po', $dataPoUpdate->kode_po)->get();
            $totalSubTotalOrder = $itemPurchaseOrders->sum('subtotal');
            $dataPenjualan = Penjualan::whereKode($dataItemPenjualan->kode)->first();
            $updatePenjualan = Penjualan::findOrFail($dataPenjualan->id);
            $updatePenjualan->dikirim = $totalSubTotalOrder;
            $updatePenjualan->save();

            $newDataPenjualan = Penjualan::select('penjualan.kode', 'penjualan.draft', 'penjualan.tanggal', 'penjualan.pelanggan', 'penjualan.kode_kas', 'penjualan.jumlah', 'penjualan.bayar', 'penjualan.dikirim', 'penjualan.jt', 'penjualan.lunas','penjualan.visa','penjualan.piutang','penjualan.po', 'itempenjualan.kode as kode_item_penjualan', 'itempenjualan.draft', 'itempenjualan.kode_barang', 'itempenjualan.nama_barang', 'itempenjualan.supplier', 'itempenjualan.satuan', 'itempenjualan.qty', 'itempenjualan.last_qty', 'itempenjualan.harga', 'itempenjualan.subtotal')
            ->leftJoin('itempenjualan', 'penjualan.kode', '=', 'itempenjualan.kode')
            ->where('penjualan.kode', $updatePenjualan->kode)
            ->first();

            return response()->json([
                'success' => true,
                'message' => "Item Penjualan update!",
                'data' => $newDataPenjualan,
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
            $dataPenjualan = Penjualan::findOrFail($id);

            if($dataPenjualan->po === "True") {
                $updateItemPenjualan = ItemPenjualan::findOrFail($itemId);
                $dataKas = Kas::whereKode($dataPenjualan->kode_kas)->first();
                $dataItemPenjualan = ItemPenjualan::whereKode($updateItemPenjualan->kode)->get();

                $totalSubtotal = $dataItemPenjualan->sum('subtotal');

                if($dataKas->saldo < $totalSubtotal) {
                    return response()->json([
                        'error' => true,
                        'message' => "Oops saldo tidak mencukupi 🤦"
                    ]);
                }

                $dataBarang = Barang::whereKode($updateItemPenjualan->kode_barang)->first();
                
                if($request->qty) {
                    if($dataBarang->toko < $request->qty) {
                        return response()->json([
                            'error_stok' => true,
                            'message' => 'Out of stok 🙅🏿‍♂️'
                        ]);
                    }
                    $updateItemPenjualan->qty = $request->qty;
                    $updateItemPenjualan->last_qty = $request->last_qty;
                    $updateItemPenjualan->stop_qty = $request->stop_qty;
                    $totalQty = $request->qty + $request->last_qty;
                    $updateItemPenjualan->subtotal = intval($totalQty) * intval($updateItemPenjualan->harga);
                    $updateItemPenjualan->qty_terima = $updateItemPenjualan->qty_terima + $request->qty;
                }

                if($request->harga) {
                    $updateItemPenjualan->harga = intval($request->harga);
                    $totalQty = $updateItemPenjualan->qty + $updateItemPenjualan->last_qty;
                    $updateItemPenjualan->subtotal = intval($totalQty) * intval($request->harga);
                }

                $updateItemPenjualan->save();
                
                $supplier = Supplier::whereKode($updateItemPenjualan->supplier)->first();

                $updatePurchaseOrderItems = PurchaseOrder::where('kode_po', $updateItemPenjualan->kode)->get();
                $itemPurchaseOrders = PurchaseOrder::where('kode_po', $updateItemPenjualan->kode)
                ->get();
                $totalQty = $itemPurchaseOrders->sum('qty');
                $purchaseOrderTerakhir = PurchaseOrder::where('kode_barang', $updateItemPenjualan->kode_barang)
                ->where('kode_po', $dataPenjualan->kode)
                ->latest('po_ke')
                ->first();

                $subQtyTotal = ($totalQty + $request->qty) * $updateItemPenjualan->harga;
                $subTotalPo = $dataPenjualan->jumlah - $subQtyTotal;
                $poKeBaru = $purchaseOrderTerakhir ? $purchaseOrderTerakhir->po_ke + 1 : 1;

                $updatePurchaseOrder = new PurchaseOrder;
                $updatePurchaseOrder->kode_po = $dataPenjualan->kode;
                $updatePurchaseOrder->dp_awal = $dataPenjualan->bayar;
                $updatePurchaseOrder->po_ke = $poKeBaru;
                $updatePurchaseOrder->nama_barang = $updateItemPenjualan->nama_barang;
                $updatePurchaseOrder->kode_barang = $updateItemPenjualan->kode_barang;
                $updatePurchaseOrder->qty = $updateItemPenjualan->qty;
                $updatePurchaseOrder->supplier = "{$supplier->nama}({$updateItemPenjualan->supplier})";
                $updatePurchaseOrder->harga_satuan = $updateItemPenjualan->harga;
                $updatePurchaseOrder->subtotal = $request->qty * $updateItemPenjualan->harga;
                $updatePurchaseOrder->sisa_dp = $subTotalPo < 0 ? 0 : $subTotalPo;
                $updatePurchaseOrder->type = "Penjualan";
                $updatePurchaseOrder->save();

                $dataItemPenjualan = ItemPenjualan::whereKode($updateItemPenjualan->kode)->get();

                $totalSubtotal = $dataItemPenjualan->sum('subtotal');

                $itemPurchaseOrders = PurchaseOrder::where('kode_po', $updateItemPenjualan->kode)->get();
                $totalSubtotalPo = $itemPurchaseOrders->sum('subtotal');

                $dataPenjualan->jumlah = $dataPenjualan->jumlah;
                $dataPenjualan->bayar = $dataPenjualan->jumlah;
                $dataPenjualan->dikirim = $totalSubtotalPo;
                $dataPenjualan->jt = $request->jt ? $request->jt : $dataPenjualan->jt;
               
                $dataPenjualan->save();

                $updateDataPenjualan = Penjualan::findOrFail($dataPenjualan->id);
                if($updateDataPenjualan->dikirim > $updateDataPenjualan->bayar) {
                    $updateDataPenjualan->piutang = $updateDataPenjualan->dikirim - $updateDataPenjualan->bayar;
                    $updateDataPenjualan->tahan = "True";
                    $updateDataPenjualan->status = "HOLD";
                    $updateDataPenjualan->save();
                }

                // $purchaseOrderTerakhir = PurchaseOrder::where('kode_po', $dataPenjualan->kode)
                // ->orderBy('po_ke', 'desc')
                // ->first();

                // $poKeBaru = ($purchaseOrderTerakhir) ? $purchaseOrderTerakhir->po_ke + 1 : 1;
                // $supplier = Supplier::whereKode($updateItemPenjualan->supplier)->first();

                // $updatePurchaseOrderItem = new PurchaseOrder;
                // $updatePurchaseOrderItem->kode_po = $dataPenjualan->kode;
                // $updatePurchaseOrderItem->dp_awal = $dataPenjualan->bayar;
                // $updatePurchaseOrderItem->po_ke = $poKeBaru;
                // $updatePurchaseOrderItem->nama_barang = $updateItemPenjualan->nama_barang;
                // $updatePurchaseOrderItem->kode_barang = $updateItemPenjualan->kode_barang;
                // $updatePurchaseOrderItem->qty = $updateItemPenjualan->qty;
                // $updatePurchaseOrderItem->supplier = "{$supplier->nama}({$updateItemPenjualan->supplier})";
                // $updatePurchaseOrderItem->harga_satuan = $updateItemPenjualan->harga;
                // $updatePurchaseOrderItem->subtotal = $totalSubtotal;
                // $updatePurchaseOrderItem->sisa_dp = $dataPenjualan->bayar - $totalSubtotal;
                // $updatePurchaseOrderItem->save();

                $data_event = [
                    'type' => 'updated',
                    'routes' => 'purchase-order-edit',
                    'notif' => "Update ItemPenjualan, successfully update!"
                ];
            } else {              
                $updateItemPenjualan = ItemPenjualan::findOrFail($itemId);
                $qty = $updateItemPenjualan->qty;
                $lastQty = $updateItemPenjualan->last_qty;
                $newQty = $request->qty;

                $dataKas = Kas::whereKode($dataPenjualan->kode_kas)->first();
                $dataItemPenjualan = ItemPenjualan::whereKode($updateItemPenjualan->kode)->get();

                $totalSubtotal = $dataItemPenjualan->sum('subtotal');

                if($dataKas->saldo < $totalSubtotal) {
                    return response()->json([
                        'error' => true,
                        'message' => "Oops saldo tidak mencukupi 🤦"
                    ]);
                }

                // echo "Qty = " . $qty;
                // echo "<br>";
                // echo "last Qty = " . $lastQty;
                // echo "<br>";
                // echo "new qty = " . $newQty;
                // die;

                if($request->qty) {
                    $updateItemPenjualan->qty = $newQty;
                    $updateItemPenjualan->last_qty = $qty;
                    $updateItemPenjualan->subtotal = intval($request->qty) * intval($updateItemPenjualan->harga);
                }

                if($request->harga) {
                    $updateItemPenjualan->harga = intval($request->harga);
                    $updateItemPenjualan->subtotal = intval($updateItemPenjualan->qty) * intval($request->harga);
                }

                $updateItemPenjualan->save();

                $dataItemPenjualan = ItemPenjualan::whereKode($updateItemPenjualan->kode)->get();

                $totalSubtotal = $dataItemPenjualan->sum('subtotal');

                $dataPenjualan->jumlah = $totalSubtotal;
                $dataPenjualan->bayar = $dataPenjualan->bayar;
                $dataPenjualan->dikirim = $dataPenjualan->dikirim;
                $dataPenjualan->jt = $request->jt ? $request->jt : $dataPenjualan->jt;
                $dataPenjualan->save();

                $data_event = [
                    'type' => 'updated',
                    'routes' => 'Penjualan-langsung-edit',
                    'notif' => "Update ItemPenjualan, successfully update!"
                ];

            }

            event(new EventNotification($data_event));

            $newUpdateItem = ItemPenjualan::findOrFail($itemId);
            $newDataPenjualan = Penjualan::findOrFail($dataPenjualan->id);

            return response()->json([
                'success' => true,
                'message' => "Item Penjualan update!",
                'data' => $newDataPenjualan,
                'items' => $newUpdateItem,
                'orders' => $updatePurchaseOrder ?? []
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
