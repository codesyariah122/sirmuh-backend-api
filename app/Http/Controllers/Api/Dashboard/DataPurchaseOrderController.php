<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Collection;
use App\Events\{EventNotification};
use App\Helpers\{UserHelpers, WebFeatureHelpers};
use App\Http\Resources\{ResponseDataCollect, RequestDataCollect};
use App\Models\{PurchaseOrder,Pembelian,ItemPembelian,Supplier,Barang,Kas,Toko,Hutang,ItemHutang,PembayaranAngsuran,Roles};
use Auth;
use PDF;

class DataPurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function list_item_po(Request $request)
    {
        try {
            $kode_po = $request->query('kode_po');

            $purchaseOrdes = PurchaseOrder::select('purchase_orders.*', 'itempembelian.qty as qty_pembelian', 'itempembelian.harga_beli as harga_beli', 'itempembelian.subtotal', 'supplier.kode', 'supplier.nama')
            ->leftJoin('itempembelian', 'purchase-order.kode', '=', 'itempembelian.kode')
            ->leftJoin('supplier', 'purchase_orders.supplier', '=', 'supplier.kode')
            ->where('kode_po', $kode_po)
            ->get();

            return new ResponseDataCollect($pembelians);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function index(Request $request)
    {
        try {
            $keywords = $request->query('keywords');
            $viewAll = $request->query('view_all');
            $dateTransaction = $request->query('date_transaction');
            $today = now()->toDateString();

            $user = Auth::user();

            $query = Pembelian::query()
            ->select(
                'pembelian.id','pembelian.tanggal','pembelian.kode','pembelian.kode_kas','pembelian.supplier','pembelian.jumlah', 'pembelian.bayar', 'pembelian.diterima','pembelian.operator','pembelian.jt','pembelian.lunas', 'pembelian.visa', 'pembelian.hutang','pembelian.keterangan','pembelian.diskon','pembelian.tax', 'supplier.kode as kode_supplier','supplier.nama as nama_supplier', 'kas.kode as kas_kode', 'kas.nama as kas_nama'
            )
            ->leftJoin('supplier', 'pembelian.supplier', '=', 'supplier.kode')
            ->leftJoin('kas', 'pembelian.kode_kas', '=', 'kas.kode')
            ->limit(10);

            if ($dateTransaction) {
                $query->whereDate('pembelian.tanggal', '=', $dateTransaction);
            }

            if ($keywords) {
                $query->where('pembelian.kode', 'like', '%' . $keywords . '%');
            }

            if(!$viewAll) {
                $query->whereDate('pembelian.tanggal', '=', $today);
            }

            $pembelians = $query
            ->where(function ($query) use ($user) {
                if ($user->role !== 1) {
                    $query->whereRaw('LOWER(pembelian.operator) like ?', [strtolower('%' . $user->name . '%')]);
                } 
            })
            ->where('pembelian.po', '=', 'True')
            ->orderByDesc('pembelian.id')
            ->paginate(10);


            return new ResponseDataCollect($pembelians);

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
        try {
            $validator = Validator::make($request->all(), [
                'kode_kas' => 'required',
                'barangs' => 'required',
            ]);


            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $data = $request->all();
            // echo "<pre>";
            // var_dump($data); die;
            // echo "</pre>";

            $barangs = $data['barangs'];
            
            $dataBarangs = json_decode($barangs, true);

            $currentDate = now()->format('ymd');

            $lastIncrement = Pembelian::max('id') ?? 0;
            $increment = $lastIncrement + 1;

            $formattedIncrement = sprintf('%03d', $increment);

            $supplier = Supplier::findOrFail($data['supplier']);

            $barangIds = array_column($dataBarangs, 'id');
            $barangs = Barang::whereIn('id', $barangIds)->get();

            $kas = Kas::findOrFail($data['kode_kas']);

            if($kas->saldo < $data['diterima']) {
                return response()->json([
                    'error' => true,
                    'message' => "Saldo tidak mencukupi!!"
                ]);
            }

            $dataItemPembelian = ItemPembelian::whereKode($data['ref_code'])->first();
            $subtotal = intval($dataItemPembelian->subtotal);

            $newPembelian = new Pembelian;
            $newPembelian->tanggal = $data['tanggal'] ? $data['tanggal'] : $currentDate;
            $newPembelian->kode = $data['ref_code'];
            $newPembelian->draft = 0;
            $newPembelian->supplier = $supplier->kode;
            $newPembelian->kode_kas = $kas->kode;
            $newPembelian->jumlah = $data['jumlah'];
            $newPembelian->bayar = $data['bayar'];
            $newPembelian->diterima = $data['diterima'];
            $newPembelian->lunas = "False";
            $newPembelian->visa = "DP AWAL";
            $newPembelian->hutang = $data['hutang'];
            $newPembelian->po = 'True';
            $newPembelian->receive = "False";
            $newPembelian->jt = $data['jt'];
            $newPembelian->keterangan = $data['keterangan'] ? $data['keterangan'] : NULL;
            $newPembelian->operator = $data['operator'];

            $newPembelian->save();
            
            $updateDrafts = ItemPembelian::whereKode($newPembelian->kode)->get();
            foreach($updateDrafts as $idx => $draft) {
                $updateDrafts[$idx]->draft = 0;
                $updateDrafts[$idx]->save();
            }

            $updateKas = Kas::findOrFail($data['kode_kas']);
            $updateKas->saldo = intval($updateKas->saldo) - $newPembelian->jumlah;
            $updateKas->save();

            $userOnNotif = Auth::user();

            if($newPembelian) {

                $items = ItemPembelian::whereKode($newPembelian->kode)->get();

                $poTerakhir = PurchaseOrder::where('kode_po', $newPembelian->kode)
                ->orderBy('po_ke', 'desc')
                ->first();

                $poKeBaru = ($poTerakhir) ? $poTerakhir->po_ke + 1 : 1;

                $supplier = Supplier::whereKode($newPembelian->supplier)->first();


                if(count($items) > 0) {
                    foreach($items as $item) {
                        $newPurchaseOrder = new PurchaseOrder;
                        $newPurchaseOrder->kode_po = $newPembelian->kode;
                        $newPurchaseOrder->dp_awal = $newPembelian->jumlah;
                        $newPurchaseOrder->po_ke = $item->nourut;
                        $newPurchaseOrder->qty = $item->qty;
                        $newPurchaseOrder->nama_barang = $item->nama_barang;
                        $newPurchaseOrder->kode_barang = $item->kode_barang;
                        $newPurchaseOrder->supplier = "{$supplier->nama}({$item->supplier})";
                        $newPurchaseOrder->harga_satuan = $item->harga_beli;
                        $newPurchaseOrder->subtotal = $item->qty * $item->harga_beli;
                        $newPurchaseOrder->sisa_dp = $newPembelian->jumlah - ($item->qty * $item->harga_beli);
                        $newPurchaseOrder->save();
                    }
                } else {
                    $newPurchaseOrder = new PurchaseOrder;
                    $newPurchaseOrder->kode_po = $newPembelian->kode;
                    $newPurchaseOrder->dp_awal = $newPembelian->jumlah;
                    $newPurchaseOrder->po_ke = $poKeBaru;
                    $newPurchaseOrder->qty = $dataItemPembelian->qty;
                    $newPurchaseOrder->nama_barang = $dataItemPembelian->nama_barang;
                    $newPurchaseOrder->kode_barang = $dataItemPembelian->kode_barang;
                    $newPurchaseOrder->supplier = "{$supplier->kode}({$newPembelian->supplier})";
                    $newPurchaseOrder->harga_satuan = $dataItemPembelian->harga_beli;
                    $newPurchaseOrder->subtotal = $dataItemPembelian->qty * $dataItemPembelian->harga_beli;
                    $newPurchaseOrder->sisa_dp = $newPembelian->jumlah - ($dataItemPembelian->qty * $dataItemPembelian->harga_beli);
                    $newPurchaseOrder->save();
                }

                $newPembelianSaved =  Pembelian::query()
                ->select(
                    'pembelian.*',
                    'itempembelian.*',
                    'supplier.nama as nama_supplier',
                    'supplier.alamat as alamat_supplier'
                )
                ->leftJoin('itempembelian', 'pembelian.kode', '=', 'itempembelian.kode')
                ->leftJoin('supplier', 'pembelian.supplier', '=', 'supplier.kode')
                ->where('pembelian.id', $newPembelian->id)
                ->get();

                $data_event = [
                    'routes' => 'purchase-order',
                    'alert' => 'success',
                    'type' => 'add-data',
                    'notif' => "Pembelian dengan kode {$newPembelian->kode}, baru saja ditambahkan 🤙!",
                    'data' => $newPembelian->kode,
                    'user' => $userOnNotif
                ];

                event(new EventNotification($data_event));

                return new RequestDataCollect($newPembelianSaved);
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
            $pembelian = Pembelian::query()
            ->select(
                'pembelian.id','pembelian.kode', 'pembelian.tanggal', 'pembelian.supplier', 'pembelian.kode_kas', 'pembelian.keterangan', 'pembelian.diskon','pembelian.tax', 'pembelian.jumlah', 'pembelian.bayar', 'pembelian.diterima','pembelian.operator', 'pembelian.jt as tempo' ,'pembelian.lunas', 'pembelian.visa', 'pembelian.hutang', 'pembelian.po', 'kas.id as kas_id', 'kas.kode as kas_kode', 'kas.nama as kas_nama','kas.saldo as kas_saldo'
            )
            ->leftJoin('kas', 'pembelian.kode_kas', '=', 'kas.kode')
            ->where('pembelian.id', $id)
            ->where('pembelian.po', 'True')
            ->first();

            $items = ItemPembelian::query()
            ->select('itempembelian.*','barang.id as id_barang','barang.kode as kode_barang', 'barang.nama as nama_barang', 'barang.hpp as harga_beli_barang','barang.toko as stok_barang','barang.expired as expired_barang', 'barang.ada_expired_date','supplier.id as id_supplier','supplier.nama as nama_supplier','supplier.alamat as alamat_supplier')
            ->leftJoin('supplier', 'itempembelian.supplier', '=', 'supplier.kode')
            ->leftJoin('barang', 'itempembelian.kode_barang', '=', 'barang.kode')
            ->where('itempembelian.kode', $pembelian->kode)
            ->orderByDesc('itempembelian.id')
            ->get();

            $purchaseOrders = PurchaseOrder::where('kode_po', '=', $pembelian->kode)
            ->orderBy('po_ke', 'DESC')
            ->get();

            return response()->json([
                'success' => true,
                'message' => "Detail pembelian {$pembelian->kode}",
                'data' => $pembelian,
                'items' => $items,
                'purchase_orders' => $purchaseOrders
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
            $currentDate = now()->format('ymd');
            $bayar = preg_replace("/[^0-9]/", "", $data['bayar']);
            $diterima = preg_replace("/[^0-9]/", "", $data['diterima']);
            $updatePembelian = Pembelian::where('po', 'True')
            ->findOrFail($id);
            $dataItemPo = PurchaseOrder::where('kode_po', $updatePembelian->kode)->get();
            $totalSubtotal = $dataItemPo->sum('subtotal');

            $kas = Kas::whereKode($data['kode_kas'])->first();

            if(intval($kas->saldo) < $diterima) {
                return response()->json([
                    'error' => true,
                    'message' => "Saldo tidak mencukupi!!"
                ]);
            }

            $updatePembelian->draft = 0;
            $updatePembelian->kode_kas = $kas->kode;
 
            if($diterima > $bayar) {
                $updatePembelian->lunas = "False";
                $updatePembelian->visa = "HUTANG";
                $updatePembelian->hutang = $data['hutang'];

                // Masuk ke hutang
                $masuk_hutang = new Hutang;
                $masuk_hutang->kode = $updatePembelian->kode;
                $masuk_hutang->tanggal = $currentDate;
                $masuk_hutang->supplier = $updatePembelian->supplier;
                $masuk_hutang->jumlah = $data['hutang'];
                // $masuk_hutang->bayar = $totalSubtotal;
                $masuk_hutang->bayar = $bayar - $data['jumlah_saldo'];
                $masuk_hutang->kode_kas = $updatePembelian->kode_kas;
                $masuk_hutang->operator = $data['operator'];
                $masuk_hutang->save();

                $item_hutang = new ItemHutang;
                $item_hutang->kode = $updatePembelian->kode;
                $item_hutang->kode_hutang = $masuk_hutang->kode;
                $item_hutang->tgl_hutang = $currentDate;
                $item_hutang->jumlah_hutang = $masuk_hutang->jumlah;
                $sisa_hutang = $masuk_hutang->jumlah - $masuk_hutang->bayar;
                $item_hutang->jumlah = $sisa_hutang < 0 ? 0 : $sisa_hutang;
                $item_hutang->save();

                $angsuranTerakhir = PembayaranAngsuran::where('kode', $masuk_hutang->kode)
                ->orderBy('angsuran_ke', 'desc')
                ->first();

                $angsuranKeBaru = ($angsuranTerakhir) ? $angsuranTerakhir->angsuran_ke + 1 : 1;

                $angsuran = new PembayaranAngsuran;
                $angsuran->kode = $masuk_hutang->kode;
                $angsuran->tanggal = $masuk_hutang->tanggal;
                $angsuran->angsuran_ke = $angsuranKeBaru;
                $angsuran->kode_pelanggan = NULL;
                $angsuran->kode_faktur = NULL;
                $angsuran->bayar_angsuran = $data['bayar'] ? $bayar - $data['jumlah_saldo'] : 0;
                $angsuran->jumlah = $item_hutang->jumlah_hutang;
                $angsuran->save();

                // $updateKas = Kas::findOrFail($kas->id);
                // $bindCalc = $updatePembelian->diterima - $updatePembelian->jumlah;
                // $updateKas->saldo = $kas->saldo - $bindCalc;
                // $updateKas->save();
            } else if($data['sisa_dp']) {
                $updatePembelian->lunas = "False";
                $updatePembelian->visa = "DP AWAL";
                $updatePembelian->hutang = 0;
            } else {
                $updatePembelian->lunas = "True";
                $updatePembelian->visa = "LUNAS";
                $updatePembelian->hutang = 0;
            }

            $updatePembelian->jumlah = $data['jumlah_saldo'] ? $data['jumlah_saldo'] : $updatePembelian->jumlah;
            $updatePembelian->bayar = $bayar;
            $updatePembelian->diterima = $totalSubtotal;

            if($updatePembelian->save()) {
                $userOnNotif = Auth::user();

                // $updateKas = Kas::findOrFail($kas->id);
                // $updateKas->saldo = $kas->saldo - $updatePembelian->diterima;
                // $updateKas->save();

                $updatePembelianSaved =  Pembelian::query()
                ->select(
                    'pembelian.*',
                    'itempembelian.*',
                    'supplier.nama as nama_supplier',
                    'supplier.alamat as alamat_supplier'
                )
                ->leftJoin('itempembelian', 'pembelian.kode', '=', 'itempembelian.kode')
                ->leftJoin('supplier', 'pembelian.supplier', '=', 'supplier.kode')
                ->where('pembelian.id', $updatePembelian->id)
                ->first();

                $data_event = [
                    'routes' => $updatePembelian->po === "False" ? 'pembelian-langsung' : 'purchase-order',
                    'alert' => 'success',
                    'type' => 'add-data',
                    'notif' => "Pembelian dengan kode {$updatePembelian->kode}, berhasil diupdate 🤙!",
                    'data' => $updatePembelian->kode,
                    'user' => $userOnNotif
                ];

                event(new EventNotification($data_event));

                return response()->json([
                    'success' => true,
                    'message' => "Data pembelian , berhasil diupdate 👏🏿",
                    'data' => $updatePembelian
                ]);
            }
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
         $user = Auth::user();

         $userRole = Roles::findOrFail($user->role);

         if($userRole->name === "MASTER" || $userRole->name === "ADMIN") {                
            $delete_pembelian = Pembelian::whereNull('deleted_at')
            ->where('po', 'True')
            ->findOrFail($id);
            $delete_pembelian->delete();

                // $kas = Kas::whereKode($delete_pembelian->kode_kas)->first();
                // $updateKas = Kas::findOrFail($kas->id);
                // $updateKas->saldo = intval($kas->saldo) + intval($delete_pembelian->jumlah);
                // $updateKas->save();

            $data_event = [
                'alert' => 'error',
                'routes' => 'purchase-order',
                'type' => 'removed',
                'notif' => "Pembelian dengan kode, {$delete_pembelian->kode}, has move to trash, please check trash!",
                'user' => Auth::user()
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => "Pembelian dengan kode, {$delete_pembelian->kode} has move to trash, please check trash"
            ]);
        } else {
            return response()->json([
                'error' => true,
                'message' => "Hak akses tidak di ijinkan 📛"
            ]);
        }
    } catch (\Throwable $th) {
        throw $th;
    }
}
}
