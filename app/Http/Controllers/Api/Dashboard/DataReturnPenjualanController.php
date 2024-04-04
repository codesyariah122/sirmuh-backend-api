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
use App\Models\{Roles,Penjualan,ItemPenjualan,Pelanggan,Barang,Kas,Toko,Piutang,ItemPiutang,PembayaranAngsuran,PurchaseOrder,SetupPerusahaan, ReturnPenjualan};
use Auth;
use PDF;

class DataReturnPenjualanController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->helpers = new WebFeatureHelpers;
    }

    public function index(Request $request)
    {
        try {
            $keywords = $request->query('keywords');
            $viewAll = $request->query('view_all');
            $today = now()->toDateString();
            $now = now();
            $startOfMonth = $now->startOfMonth()->toDateString();
            $endOfMonth = $now->endOfMonth()->toDateString();
            $dateTransaction = $request->query('date_transaction');

            $user = Auth::user();

            $query = ReturnPenjualan::query()
            ->select(
                'return_penjualan.*','penjualan.id as id_penjualan','penjualan.tanggal as tanggal_penjualan','penjualan.kode as kode_penjualan','penjualan.jumlah as jumlah_penjualan','penjualan.operator','penjualan.jt','penjualan.lunas', 'penjualan.visa', 'penjualan.piutang','penjualan.keterangan','penjualan.diskon','penjualan.tax','penjualan.return', 'kas.id as kas_id', 'kas.nama as nama_kas'
            )
            ->leftJoin('penjualan', 'return_penjualan.no_faktur', '=', 'penjualan.kode')
            ->leftJoin('kas', 'return_penjualan.kode_kas', '=', 'kas.kode')
            ->limit(10);

            if ($dateTransaction) {
                $query->whereDate('return_penjualan.tgl_jual', '=', $dateTransaction);
            }

            if ($keywords) {
                $query->where('return_penjualan.no_faktur', 'like', '%' . $keywords . '%');
            }

            if($viewAll === false || $viewAll === "false") {
                // $query->whereDate('pembelian.tanggal', '=', $today);
                $query->whereBetween('return_penjualan.tanggal', [$startOfMonth, $endOfMonth]);
            }

            $return_penjualans = $query
            ->where(function ($query) use ($user) {
                if ($user->role !== 1) {
                    $query->whereRaw('LOWER(return_penjualan.operator) like ?', [strtolower('%' . $user->name . '%')]);
                } 
            })
            ->orderByDesc('return_penjualan.id')
            ->paginate(10);

            return new ResponseDataCollect($return_penjualans);

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
                'alasan' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $userOnNotif = Auth::user();
            $data = $request->all();
            $kas = Kas::findOrFail($data['kas_id']);
            $penjualan = Penjualan::findOrFail($data['penjualan_id']);
            $itemPenjualan = ItemPenjualan::findOrFail($data['item_id']);
            $dataBarang = Barang::whereKode($itemPenjualan->kode_barang)->first();
            $updateStokBarang = Barang::findOrFail($dataBarang->id);

            if(intval($data['item_qty']) > intval($itemPenjualan->qty)) {
                return response()->json([
                    'error' => true,
                    'message' => $data['item_qty'] . " melebihi batas maximal !!"
                ]);
            }

            if(intval($data['item_qty']) == 0) {
                return response()->json([
                    'error' => true,
                    'message' => "Quantity tidak boleh 0 !!"
                ]);
            }

            $dataPerusahaan = SetupPerusahaan::with('tokos')->findOrFail(1);
            $currentDate = now()->format('ymd');
            $randomNumber = sprintf('%05d', mt_rand(0, 99999));
            $kode = $dataPerusahaan->kd_return_penjualan;
            $returnPenjualan = new ReturnPenjualan;
            $returnPenjualan->kode = $kode. '-'. $currentDate . $randomNumber;
            $returnPenjualan->tanggal = $currentDate;
            $returnPenjualan->jenis_penjualan = $penjualan->jenis;
            $returnPenjualan->tgl_jual = $penjualan->tanggal;
            $returnPenjualan->kode_barang = $itemPenjualan->kode_barang;
            $returnPenjualan->nama_barang = $itemPenjualan->nama_barang;
            $returnPenjualan->satuan = $itemPenjualan->satuan;
            $returnPenjualan->no_faktur = $penjualan->kode;
            $returnPenjualan->pelanggan = $itemPenjualan->pelanggan;
            $returnPenjualan->qty = $data['item_qty_return'];
            $returnPenjualan->harga = $itemPenjualan->harga;
            $returnPenjualan->jumlah = intval($itemPenjualan->harga) * intval($data['item_qty_return']);
            $returnPenjualan->alasan = $data['alasan'];
            $returnPenjualan->kode_kas = $penjualan->kode_kas;
            $returnPenjualan->operator = $penjualan->operator;
            $returnPenjualan->kembali = "False";
            $returnPenjualan->hpp = intval($itemPenjualan->harga);
            $returnPenjualan->lokasistok = $itemPenjualan->lokasistok !== NULL ? $itemPenjualan->lokasistok : NULL;
            
            if($returnPenjualan->save()) {
                $itemPenjualan->return = "True";
                $itemPenjualan->save();

                $data_event = [
                    'routes' => 'return-penjualan',
                    'alert' => 'success',
                    'type' => 'return-data',
                    'notif' => "Item penjualan dengan kode {$penjualan->kode}, berhasil di return 🤙!",
                    'data' => $penjualan->kode,
                    'user' => $userOnNotif
                ];

                event(new EventNotification($data_event));

                return response()->json([
                    'success' => true,
                    'message' => "Item penjualan dengan kode : {$itemPenjualan->kode}, berhasil di return",
                    'data' => $returnPenjualan->kode
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
            $penjualan = Penjualan::findOrFail($id);
            $dataReturnPenjualan = ReturnPenjualan::where('no_faktur', $penjualan->kode)
            ->where('kembali', 'False')
            ->first();
            $returnPenjualan = ReturnPenjualan::select('return_penjualan.*', 'penjualan.id as id_penjualan', 'penjualan.return', 'kas.id as kas_id', 'kas.kode as kode_kas', 'kas.nama as nama_kas')
            ->leftJoin('penjualan', 'return_penjualan.no_faktur', '=', 'penjualan.kode')
            ->leftJoin('kas', 'return_penjualan.kode_kas', '=', 'kas.kode')
            ->findOrFail($dataReturnPenjualan->id);

            return response()->json([
                'success' => true,
                'message' => "detail data return penjualan",
                'data' => $returnPenjualan
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
            $userOnNotif = Auth::user();
            $data = $request->all();
            $kas = Kas::findOrFail($data['kas_id']);
            $penjualan = Penjualan::findOrFail($id);
            $dataItem = ItemPenjualan::where('kode', $penjualan->kode)
            ->where('kode_barang', $data['kode_barang'])->first();
            $itemPenjualan = ItemPenjualan::findOrFail($dataItem->id);
            $dataBarang = Barang::whereKode($itemPenjualan->kode_barang)->first();
            $updateStokBarang = Barang::findOrFail($dataBarang->id);

            $bindQty = intval($dataItem->qty) - intval($data['item_qty']);
            $lastQty = $dataItem->qty;
            $itemPenjualan->qty = $bindQty;
            $itemPenjualan->last_qty = $lastQty;
            $itemPenjualan->harga = $data['item_harga'];
            $itemPenjualan->subtotal = intval($data['item_harga']) * $bindQty;
            $itemPenjualan->return = "True";
            $itemPenjualan->save();
            $itemSubtotal = ItemPenjualan::whereKode($itemPenjualan->kode)->get()->sum('subtotal');

            $penjualan->jumlah = $itemSubtotal;
            $penjualan->kembali = $penjualan->bayar - $itemSubtotal;
            $penjualan->dikirim = $itemSubtotal;
            $penjualan->return = "True";
            $penjualan->save();

            $kas->saldo = intval($kas->saldo) - $data['item_subtotal'];
            $kas->save();

            $bindingStok = $data['item_qty'] !== NULL ? intval($itemPenjualan->last_qty) - intval($itemPenjualan->qty) : 0;
            $updateStokBarang->toko = intval($dataBarang->toko) + intval($data['item_qty']);
            $updateStokBarang->last_qty = $dataBarang->toko;
            $updateStokBarang->save();

            $dataReturn = ReturnPenjualan::where('no_faktur', $penjualan->kode)->first();
            $returnPenjualan = ReturnPenjualan::findOrFail($dataReturn->id);
            $returnPenjualan->kembali = "True";
            $returnPenjualan->save();

            $data_event = [
                'routes' => 'return-penjualan',
                'alert' => 'success',
                'type' => 'return-data',
                'notif' => "Item penjualan dengan kode {$penjualan->kode}, berhasil di return 🤙!",
                'data' => $penjualan->kode,
                'user' => $userOnNotif
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => "Item penjualan dengan kode : {$itemPenjualan->kode}, berhasil di return",
                'data' => $returnPenjualan->kode
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

    public function cetak_nota($type, $kode, $id_perusahaan)
    {
        $ref_code = $kode;
        $nota_type = $type === 'nota-kecil' ? "Nota Kecil": "Nota Besar";
        $helpers = $this->helpers;
        $today = now()->toDateString();
        $toko = Toko::whereId($id_perusahaan)
        ->select("name","logo","address","kota","provinsi")
        ->first();

        $query = ReturnPenjualan::query()
        ->select(
            'return_penjualan.*',
            'penjualan.tanggal as tanggal_penjualan', 'itempenjualan.kode as kode_item', 'itempenjualan.qty as qty_item', 'itempenjualan.last_qty', 'itempenjualan.harga as item_harga', 'itempenjualan.subtotal', 'itempenjualan.supplier',
            'pelanggan.kode as kode_pelanggan',
            'pelanggan.nama as nama_pelanggan',
            'pelanggan.alamat as alamat_pelanggan',
            'barang.nama as nama_barang',
            'barang.satuan as satuan_barang',
            'supplier.nama as nama_supplier'
        )
        ->leftJoin('penjualan', 'return_penjualan.no_faktur', '=', 'penjualan.kode')
        ->leftJoin('itempenjualan', 'return_penjualan.no_faktur', '=', 'itempenjualan.kode')
        ->leftJoin('pelanggan', 'return_penjualan.pelanggan', '=', 'pelanggan.kode')
        ->leftJoin('supplier', 'itempenjualan.supplier', '=', 'supplier.kode')
        ->leftJoin('barang', 'return_penjualan.kode_barang', '=', 'barang.kode')
        ->orderByDesc('return_penjualan.id')
            // ->whereDate('penjualan.tanggal', '=', $today)
        ->where('return_penjualan.kode', $kode);

        $penjualan = $query->first();

        // echo "<pre>";
        // var_dump($orders);
        // echo "</pre>";
        // die;

        switch($type) {
            case "nota-kecil":
            return view('return-penjualan.nota_kecil', compact('penjualan', 'kode', 'toko', 'nota_type', 'helpers'));
            break;
            case "nota-besar":
            $pdf = PDF::loadView('return-penjualan.nota_besar', compact('penjualan','kode', 'toko', 'nota_type', 'helpers'));
            $pdf->setPaper(0,0,609,440, 'potrait');
            return $pdf->stream('Transaksi-'. $penjualan->kode .'.pdf');
            break;
        }
    }

}
