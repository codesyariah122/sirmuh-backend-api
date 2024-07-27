<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\Collection;
use App\Events\{EventNotification};
use App\Helpers\{UserHelpers, WebFeatureHelpers};
use App\Http\Resources\{ResponseDataCollect, RequestDataCollect};
use App\Models\{PurchaseOrder,Pembelian,ItemPembelian,Supplier,Barang,Kas,Toko,Hutang,ItemHutang,PembayaranAngsuran,Roles,SetupPerusahaan, Pengeluaran};
use Auth;
use PDF;

class DataPurchaseOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    private $helpers;

    public function __construct()
    {
        $this->helpers = new WebFeatureHelpers;
    }

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
            $supplier = $request->query('supplier');
            $dateTransaction = $request->query('date_transaction');
            $today = now()->toDateString();
            $now = now();
            $startOfMonth = $now->startOfMonth()->toDateString();
            $endOfMonth = $now->endOfMonth()->toDateString();

            $user = Auth::user();

            $query = Pembelian::query()
            ->select(
                'pembelian.id','pembelian.tanggal','pembelian.kode','pembelian.kode_kas','pembelian.supplier','pembelian.jumlah', 'pembelian.bayar','pembelian.kembali', 'pembelian.sisa_dp', 'pembelian.diterima','pembelian.operator','pembelian.jt','pembelian.lunas', 'pembelian.visa', 'pembelian.hutang','pembelian.keterangan','pembelian.diskon','pembelian.tax', 'pembelian.return', 'pembelian.biayabongkar', 'supplier.kode as kode_supplier','supplier.nama as nama_supplier', 'kas.kode as kas_kode', 'kas.nama as kas_nama'
            )
            ->leftJoin('supplier', 'pembelian.supplier', '=', 'supplier.kode')
            ->leftJoin('kas', 'pembelian.kode_kas', '=', 'kas.kode')
            ->limit(10);

            if ($dateTransaction) {
                $query->whereDate('pembelian.tanggal', '=', $dateTransaction);
            }

            if ($supplier) {
                $query->where('pembelian.supplier', 'like', '%' . $supplier . '%');
            }

            if ($keywords) {
                $query->where('pembelian.kode', 'like', '%' . $keywords . '%');
            }

            if($viewAll === false || $viewAll === "false") {
                // $query->whereDate('pembelian.tanggal', '=', $today);
                $query->whereBetween('pembelian.tanggal', [$startOfMonth, $endOfMonth]);
            }

            $pembelians = $query
            ->where(function ($query) use ($user) {
                if ($user->role !== 1) {
                    $query->whereRaw('LOWER(pembelian.operator) like ?', [strtolower('%' . $user->name . '%')]);
                }
            })
            ->where('pembelian.po', '=', 'True')
            ->addSelect(DB::raw('(SELECT stop_qty FROM itempembelian WHERE itempembelian.kode = pembelian.kode ORDER BY id DESC LIMIT 1) as stop_qty'))
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
                'bayar' => 'required'
            ]);


            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }
            $userOnNotif = Auth::user();

            $data = $request->all();

            $barangs = $data['barangs'];

            $dataBarangs = json_decode($barangs, true);

            $currentDate = now()->format('ymd');
            $randomNumber = sprintf('%05d', mt_rand(0, 99999));

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
            $newPembelian->return = "False";
            $newPembelian->biayabongkar = $data['biayabongkar'] ?? NULL;
            $newPembelian->jt = $data['jt'];
            $newPembelian->keterangan = $data['keterangan'];
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

            if($newPembelian) {

                $perusahaan = SetupPerusahaan::with('tokos')->findOrFail(1);
                $newPengeluaran = new Pengeluaran;
                $newPengeluaran->kode = $perusahaan->kd_pengeluaran."-".$currentDate.$randomNumber;
                $newPengeluaran->tanggal = $newPembelian->tanggal;
                $newPengeluaran->kd_biaya = "DP";
                $newPengeluaran->keterangan = $newPembelian->keterangan;
                $newPengeluaran->kode_kas = $newPembelian->kode_kas;
                $newPengeluaran->jumlah = $newPembelian->jumlah;
                $newPengeluaran->operator = $newPembelian->operator;
                $newPengeluaran->save();

                $items = ItemPembelian::whereKode($newPembelian->kode)->get();

                $poTerakhir = PurchaseOrder::where('kode_po', $newPembelian->kode)
                ->orderBy('po_ke', 'desc')
                ->first();

                $poKeBaru = ($poTerakhir) ? $poTerakhir->po_ke + 1 : 0;

                $supplier = Supplier::whereKode($newPembelian->supplier)->first();

                if(count($items) > 0) {
                    foreach($items as $item) {
                        $newPurchaseOrder = new PurchaseOrder;
                        $newPurchaseOrder->kode_po = $newPembelian->kode;
                        $newPurchaseOrder->dp_awal = $newPembelian->jumlah;
                        $newPurchaseOrder->po_ke = $poKeBaru;
                        $newPurchaseOrder->qty = $item->qty;
                        $newPurchaseOrder->nama_barang = $item->nama_barang;
                        $newPurchaseOrder->kode_barang = $item->kode_barang;
                        $newPurchaseOrder->supplier = "{$supplier->nama}({$item->supplier})";
                        $newPurchaseOrder->harga_satuan = $item->harga_beli;
                        $newPurchaseOrder->subtotal = $item->qty * $item->harga_beli;
                        $newPurchaseOrder->sisa_dp = $newPembelian->jumlah - ($item->qty * $item->harga_beli);
                        $newPurchaseOrder->type = "pembelian";
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
                    $newPurchaseOrder->type = "pembelian";
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

                $historyKeterangan = "{$userOnNotif->name}, berhasil melakukan transaksi purchase orders [{$newPembelian->kode}], sebesar {$this->helpers->format_uang($newPembelian->jumlah)}";
                $dataHistory = [
                    'user' => $userOnNotif->name,
                    'keterangan' => $historyKeterangan,
                    'routes' => '/dashboard/transaksi/beli/purchase-order',
                    'route_name' => 'Purchase Order'
                ];
                $createHistory = $this->helpers->createHistory($dataHistory);

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
                'pembelian.id','pembelian.kode', 'pembelian.tanggal', 'pembelian.supplier', 'pembelian.kode_kas', 'pembelian.kas_biaya', 'pembelian.keterangan', 'pembelian.diskon','pembelian.tax', 'pembelian.jumlah', 'pembelian.bayar', 'pembelian.diterima','pembelian.kembali', 'pembelian.sisa_dp','pembelian.operator', 'pembelian.jt as tempo' ,'pembelian.lunas', 'pembelian.visa', 'pembelian.hutang', 'pembelian.po', 'pembelian.return', 'pembelian.biayabongkar', 'pembelian.kekurangan_deposit', 'pembelian.kekurangan_sdh_dibayar', 'pembelian.created_at', 'kas.id as kas_id', 'kas.kode as kas_kode', 'kas.nama as kas_nama','kas.saldo as kas_saldo','return_pembelian.kode as kode_return', 'return_pembelian.tanggal as tanggal_return','return_pembelian.qty','return_pembelian.satuan','return_pembelian.nama_barang','return_pembelian.harga','return_pembelian.jumlah as jumlah_return', 'return_pembelian.alasan', 'supplier.kode as supplier_kode', 'supplier.nama as supplier_nama','supplier.saldo_hutang as saldo_hutang','supplier.alamat'
            )
            ->leftJoin('kas', 'pembelian.kode_kas', '=', 'kas.kode')
            ->leftJoin('return_pembelian', 'pembelian.kode', '=', 'return_pembelian.no_faktur')
            ->leftJoin('supplier', 'pembelian.supplier', '=', 'supplier.kode')
            ->where('pembelian.id', $id)
            ->where('pembelian.po', 'True')
            ->first();

            $items = ItemPembelian::query()
            ->select('itempembelian.*','barang.id as id_barang','barang.kode as kode_barang', 'barang.nama as nama_barang', 'barang.hpp as harga_beli_barang', 'barang.toko as stok_barang','barang.expired as expired_barang', 'barang.satuan as satuan_barang', 'barang.ada_expired_date','supplier.id as id_supplier','supplier.kode as kode_supplier','supplier.nama as nama_supplier', 'supplier.saldo_hutang', 'supplier.alamat as alamat_supplier')
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

    public function updateMultipleInput(Request $request, $id)
    {
        try {
            $multiple_input = $request->multiple_input;
            $updatePembelian = Pembelian::findOrFail($id);
            $updatePembelian->multiple_input = $multiple_input ? "True" : "False";
            $updatePembelian->save();
            return response()->json([
                'success' => true,
                'message' => "Detail pembelian {$updatePembelian->kode}, multipe input successfully updated",
                'data' => $updatePembelian
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $data = $request->all();
            $currentDate = $currentDate = now()->format('ymd');
            $randomNumber = sprintf('%05d', mt_rand(0, 99999));
            $bayar = intval(preg_replace("/[^0-9]/", "", $data['bayar']));
            $diterima = intval(preg_replace("/[^0-9]/", "", $data['diterima']));
            $sisa_dp = $data['sisa_dp'] < 0 ? 0 : $data['sisa_dp'];

            if(isset($data['kembali'])) {
                $kembali = $data['kembali'] ? intval(preg_replace("/[^0-9]/", "", $data['kembali'])) : 0;
            }
            $updatePembelian = Pembelian::where('po', 'True')
            ->findOrFail($id);
            $supplier = Supplier::whereKode($updatePembelian->supplier)->first();
            $updateSupplier = Supplier::findOrFail($supplier->id);
            $dataItemPo = PurchaseOrder::where('kode_po', $updatePembelian->kode)->get();
            $totalSubtotal = $dataItemPo->sum('subtotal');

            $kas = Kas::whereKode($data['kode_kas'])->first();
            if($data['kas_biaya']) {
                $kasBiaya = Kas::findOrFail($data['kas_biaya']);
            }

            // var_dump(intval($kas->saldo));
            // var_dump($data['hutang']);
            // var_dump(intval($kas->saldo) < $diterima);
            // die;

            // var_dump($data['jt']); die;

            $updatePembelian->draft = 0;
            $updatePembelian->kode_kas = $kas->kode;
            $updatePembelian->keterangan = $data['keterangan'] !== NULL ? $data['keterangan'] : $updatePembelian->keterangan;

            // var_dump($diterima);
            // var_dump($bayar);
            // var_dump($data['sisa_dp']);
            // die;

            if($diterima > $bayar) {
                $updatePembelian->lunas = "False";
                $updatePembelian->visa = "HUTANG";
                $updatePembelian->hutang = intval($data['hutang']);
                $updatePembelian->jt = $data['jt'];

                if(intval($kas->saldo) < $data['hutang']) {
                    return response()->json([
                        'error' => true,
                        'message' => "Saldo tidak mencukupi!!"
                    ]);
                }

                // Masuk ke hutang
                $dataPerusahaan = SetupPerusahaan::with('tokos')->findOrFail(1);
                $masuk_hutang = new Hutang;
                $masuk_hutang->kode = $dataPerusahaan->kd_bayar_hutang.'-'. str_replace('-', '', $updatePembelian->tanggal) . $randomNumber;
                $masuk_hutang->kd_beli = $updatePembelian->kode;
                $masuk_hutang->tanggal = $data['tanggal'] ? $data['tanggal'] : $currentDate;
                $masuk_hutang->supplier = $updatePembelian->supplier;
                $masuk_hutang->jumlah = intval($data['hutang']);
                // $masuk_hutang->bayar = $totalSubtotal;
                $masuk_hutang->bayar = $bayar - $data['jumlah_saldo'];
                $masuk_hutang->kode_kas = $updatePembelian->kode_kas;
                $masuk_hutang->operator = $data['operator'];
                $masuk_hutang->save();

                $item_hutang = new ItemHutang;
                $item_hutang->kode = $masuk_hutang->kode;
                $item_hutang->kd_beli = $updatePembelian->kode;
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
                $angsuran->operator = $data['operator'];
                $angsuran->angsuran_ke = $angsuranKeBaru;
                $angsuran->kode_pelanggan = NULL;
                $angsuran->kode_faktur = $updatePembelian->kode;
                $angsuran->bayar_angsuran = $data['bayar'] ? $bayar - $data['jumlah_saldo'] : 0;
                $angsuran->jumlah = $item_hutang->jumlah_hutang;
                $angsuran->keterangan = "Pembayaran angsuran awal melalui kas : {$updatePembelian->kode_kas}";
                $angsuran->save();

                // $updateKas = Kas::findOrFail($kas->id);
                // $bindCalc = $updatePembelian->diterima - $updatePembelian->jumlah;
                // $updateKas->saldo = $kas->saldo - $bindCalc;
                // $updateKas->save();

                $updateSupplier->saldo_hutang = intval($data['biayabongkar']) > 0 ? intval($data['hutang']) - intval($data['biayabongkar']) : $data['hutang'];
                $updateSupplier->save();
            } else if($data['sisa_dp'] !== 0) {
                $updatePembelian->sisa_dp = $sisa_dp;
                $updatePembelian->lunas = "False";
                $updatePembelian->visa = "DP AWAL";
                $updatePembelian->hutang = 0;
                // if($bayar > $data['jumlah_saldo']) {
                //     $updateKas = Kas::findOrFail($kas->id);
                //     $bindCalc = intval($bayar) - intval($data['jumlah_saldo']);
                //     $updateKas->saldo = intval($kas->saldo) - intval($bindCalc);
                //     $updateKas->save();
                // }
            } else {
                if($bayar > $data['jumlah_saldo']) {
                    $updateKas = Kas::findOrFail($kas->id);
                    $bindCalc = intval($bayar) - intval($data['jumlah_saldo']);
                    $updateKas->saldo = intval($kas->saldo) - intval($bindCalc);
                    $updateKas->save();
                }
                $updatePembelian->lunas = "True";
                $updatePembelian->visa = "LUNAS";
                $updatePembelian->sisa_dp = $sisa_dp;
                $updatePembelian->jt = 0;
                $updatePembelian->hutang = 0;
            }

            $updatePembelian->kas_biaya = intval($data['biayabongkar']) > 0 ? $kasBiaya->kode : NULL;
            $updatePembelian->multiple_input = $data["multiple_input"];

            $dataJumlah = $data['jumlah_saldo'] ? $data['jumlah_saldo'] : $updatePembelian->jumlah;

            if(intval($data['biayabongkar']) > 0) {
                $dataJumlahWithBiaya = $dataJumlah - intval($data['biayabongkar']);
            }

            $updatePembelian->jumlah = intval($data['biayabongkar']) > 0 ? $dataJumlahWithBiaya : $dataJumlah;
            $updatePembelian->bayar = $bayar;
            $updatePembelian->diterima = $totalSubtotal;
            if(isset($data['kembali'])) {
                $updatePembelian->kembali = $kembali;
            }
            $updatePembelian->return = "False";
            $updatePembelian->biayabongkar = $data['biayabongkar'];
            if($sisa_dp === 0) {
                $calculateKekurangan = ($bayar - intval($updatePembelian->jumlah)) - (intval($data['biayabongkar']) - intval($data['sisa_dp']));
                $updatePembelian->kekurangan_deposit = $calculateKekurangan < 0 ? 0 : $calculateKekurangan;
            }
            $updatePembelian->kekurangan_sdh_dibayar = "True";

            if($updatePembelian->save()) {
                $userOnNotif = Auth::user();

                if($updatePembelian->lunas === "True") {
                    $dataItems = ItemPembelian::whereKode($updatePembelian->kode)->get();
                    foreach($dataItems as $item) {
                        $updateItemPembelian = ItemPembelian::findOrFail($item->id);
                        $updateItemPembelian->stop_qty = "True";
                        $updateItemPembelian->save();
                    }
                }

                if(intval($data['biayabongkar']) > 0) {
                    $updateKasBiaya = Kas::findOrFail($data['kas_biaya']);
                    $updateKasBiaya->saldo = intval($kasBiaya->saldo) - $data['biayabongkar'];
                    $updateKasBiaya->save();
                }

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
                    'routes' => 'purchase-order',
                    'alert' => 'success',
                    'type' => 'add-data',
                    'notif' => "Pembelian dengan kode {$updatePembelian->kode}, berhasil diupdate 🤙!",
                    'data' => $updatePembelian->kode,
                    'user' => $userOnNotif
                ];

                event(new EventNotification($data_event));

                $historyKeterangan = "{$userOnNotif->name}, berhasil terima purchase orders [{$updatePembelian->kode}], sebesar {$this->helpers->format_uang($updatePembelian->diterima)}";
                $dataHistory = [
                    'user' => $userOnNotif->name,
                    'keterangan' => $historyKeterangan,
                    'routes' => '/dashboard/transaksi/beli/purchase-order',
                    'route_name' => 'Purchase Order'
                ];
                $createHistory = $this->helpers->createHistory($dataHistory);

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

    public function tambah_sisa_dp(Request $request, $kode)
    {
        try {
            $validator = Validator::make($request->all(), [
                'tambah' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $dataPembelian = Pembelian::whereKode($kode)->first();
            $pembelian = Pembelian::findOrFail($dataPembelian->id);
            
            if($request->kode_kas !== NULL) {
                $dataKas = Kas::findOrFail($request->kode_kas);
                $dataKas->saldo = intval($dataKas->saldo) - intval($request->tambah);
            } else {
                $kas = Kas::whereKodeKas($dataPembelian->kode_kas)->first();
                $dataKas = Kas::findOrFail($kas->id);
                $dataKas->saldo = intval($kas->saldo) - intval($request->tambah);
            }

            $pembelian->sisa_dp = intval($dataPembelian->sisa_dp) + $request->tambah;
            $pembelian->count_tambah_sisadp = intval($dataPembelian->count_tambah_sisadp) + 1;
            $pembelian->save();
            $dataKas->save();

            $userOnNotif = Auth::user();
            $historyKeterangan = "{$userOnNotif->name}, menambhakan Sisa DP sebesar {$this->helpers->format_uang($request->tambah)}, dari kas {$dataKas->nama}, di pembelian dengan kode : {$pembelian->kode}";
            $dataHistory = [
                'user' => $userOnNotif->name,
                'keterangan' => $historyKeterangan,
                'routes' => '/dashboard/transaksi/beli/purchase-order',
                'route_name' => 'Purchase Order'
            ];
            $createHistory = $this->helpers->createHistory($dataHistory);

            return response()->json([
                'success' => true,
                'message' => "Berhasil tambah Sisa DP Sebesar : 💸 {$request->tambah}",
                'sisa_dp_update' => $pembelian->sisa_dp 
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function tambah_dp_awal(Request $request, $kode)
    {
        try {
            $validator = Validator::make($request->all(), [
                'tambah' => 'required'
            ]);
            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }


            $dataPembelian = Pembelian::whereKode($kode)->first();
            $pembelian = Pembelian::findOrFail($dataPembelian->id);
            if($request->kode_kas !== NULL) {
                $dataKas = Kas::findOrFail($request->kode_kas);
                $dataKas->saldo = intval($dataKas->saldo) - intval($request->tambah);
            } else {
                $kas = Kas::whereKodeKas($dataPembelian->kode_kas)->first();
                $dataKas = Kas::findOrFail($kas->id);
                $dataKas->saldo = intval($kas->saldo) - intval($request->tambah);
            }
            $pembelian->jumlah = intval($dataPembelian->jumlah) + $request->tambah;
            $pembelian->count_tambah_dp = intval($dataPembelian->count_tambah_dp) + 1;
            $pembelian->save();
            $dataKas->save();

            $userOnNotif = Auth::user();
            $historyKeterangan = "{$userOnNotif->name}, menambhakan DP Awal sebesar {$this->helpers->format_uang($request->tambah)}, dari kas {$dataKas->nama}, di pembelian dengan kode : {$pembelian->kode}";
            $dataHistory = [
                'user' => $userOnNotif->name,
                'keterangan' => $historyKeterangan,
                'routes' => '/dashboard/transaksi/beli/purchase-order',
                'route_name' => 'Purchase Order'
            ];
            $createHistory = $this->helpers->createHistory($dataHistory);

            return response()->json([
                'success' => true,
                'message' => "Berhasil menambahkan DP 💸 sejumlah {$request->tambah}",
                'total_dp' => $pembelian->jumlah
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
           $user = Auth::user();

           $userRole = Roles::findOrFail($user->role);

           if($userRole->name === "MASTER" || $userRole->name === "ADMIN") {
            $delete_pembelian = Pembelian::findOrFail($id);

            $dataHutang = Hutang::where('kode', $delete_pembelian->kode)->first();

            if($dataHutang) {
                $delete_hutang = Hutang::findOrFail($dataHutang->id);
                $delete_hutang->delete();

                $hutangItems = ItemHutang::where('kode', $delete_pembelian->kode)->get();
                foreach($hutangItems as $itemHutang) {
                    $deleteItemHutang = ItemHutang::findOrFail($itemHutang->id);
                    $deleteItemHutang->delete();
                }

                $angsuranItems = PembayaranAngsuran::where('kode', $delete_pembelian->kode)->get();
                foreach($angsuranItems as $itemAngsuran) {
                    $deleteAngsuran = PembayaranAngsuran::findOrFail($itemAngsuran->id);
                    $deleteAngsuran->delete();
                }
            }

            $delete_pembelian->delete();

            $pembelianItems = ItemPembelian::where('kode', $delete_pembelian->kode)->get();
            foreach($pembelianItems as $itemPembelian) {
                $deleteItem = ItemPembelian::findOrFail($itemPembelian->id);
                $deleteItem->delete();
            }

            $dataKas = Kas::where('kode', $delete_pembelian->kode_kas)->first();
            $updateKas = Kas::findOrFail($dataKas->id);
            $updateKas->saldo = $dataKas->saldo + $delete_pembelian->jumlah;
            $updateKas->save();

            $dataKasBiaya = Kas::where('kode', $delete_pembelian->kas_biaya)->first();
            $updateKasBiaya = Kas::findOrFail($dataKasBiaya->id);
            $updateKasBiaya->saldo = $dataKasBiaya->saldo + $delete_pembelian->biayabongkar;
            $updateKasBiaya->save();

            $orderItems = PurchaseOrder::where('kode_po', $delete_pembelian->kode)->get();
            foreach($orderItems as $item) {
                $barangItems = Barang::where('kode', $item->kode_barang)->get();
                foreach($barangItems as $barang) {
                    $updateStokBarang = Barang::findOrFail($barang->id);
                    $lastQty = $updateStokBarang->toko;
                    $updateStokBarang->toko = $updateStokBarang->toko - $item->qty;
                    $updateStokBarang->last_qty = $lastQty;
                    $updateStokBarang->save();
                }

                $deleted_order = PurchaseOrder::findOrFail($item->id);
                $deleted_order->delete();
            }

            $data_event = [
                'alert' => 'error',
                'routes' => 'purchase-order',
                'type' => 'removed',
                'notif' => "Pembelian dengan kode, {$delete_pembelian->kode}, successfully deleted!",
                'user' => Auth::user()
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => "Pembelian dengan kode, {$delete_pembelian->kode} berhasil dihapus 👏"
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
