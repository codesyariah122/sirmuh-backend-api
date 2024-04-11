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
use App\Models\{Roles,Pembelian,ItemPembelian,Supplier,Barang,Kas,Toko,Hutang,ItemHutang,PembayaranAngsuran,PurchaseOrder,SetupPerusahaan, ReturnPembelian, Pemasukan};
use Auth;
use PDF;

class DataReturnPembelianController extends Controller
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

    public function index(Request $request)
    {
        try {
            $keywords = $request->query('keywords');
            $supplier = $request->query('supplier');
            $viewAll = $request->query('view_all');
            $today = now()->toDateString();
            $now = now();
            $startOfMonth = $now->startOfMonth()->toDateString();
            $endOfMonth = $now->endOfMonth()->toDateString();
            $dateTransaction = $request->query('date_transaction');

            $user = Auth::user();

            $query = ReturnPembelian::query()
            ->select(
                'return_pembelian.*','pembelian.id as id_pembelian','pembelian.tanggal as tanggal_pembelian','pembelian.kode as kode_pembelian','pembelian.jumlah as jumlah_pembelian','pembelian.operator','pembelian.jt','pembelian.lunas', 'pembelian.visa', 'pembelian.hutang','pembelian.keterangan','pembelian.diskon','pembelian.tax','pembelian.supplier', 'supplier.nama as nama_supplier', 'pembelian.return', 'kas.id as kas_id', 'kas.nama as nama_kas'
            )
            ->leftJoin('pembelian', 'return_pembelian.no_faktur', '=', 'pembelian.kode')
            ->leftJoin('supplier', 'pembelian.supplier', '=', 'supplier.kode')
            ->leftJoin('kas', 'return_pembelian.kode_kas', '=', 'kas.kode')
            ->limit(10);

            if ($dateTransaction) {
                $query->whereDate('return_pembelian.tanggal', '=', $dateTransaction);
            }

            if ($keywords) {
                $query->where('return_pembelian.no_faktur', 'like', '%' . $keywords . '%');
            }

            if ($supplier) {
                $query->where('pembelian.supplier', 'like', '%' . $supplier . '%');
            }

            if($viewAll === false || $viewAll === "false") {
                // $query->whereDate('pembelian.tanggal', '=', $today);
                $query->whereBetween('return_pembelian.tanggal', [$startOfMonth, $endOfMonth]);
            }

            $return_pembelians = $query
            ->where(function ($query) use ($user) {
                if ($user->role !== 1) {
                    $query->whereRaw('LOWER(return_pembelian.operator) like ?', [strtolower('%' . $user->name . '%')]);
                } 
            })
            ->orderByDesc('return_pembelian.id')
            ->paginate(10);

            return new ResponseDataCollect($return_pembelians);

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
            $pembelian = Pembelian::findOrFail($data['pembelian_id']);
            $itemPembelian = ItemPembelian::findOrFail($data['item_id']);
            $dataBarang = Barang::whereKode($itemPembelian->kode_barang)->first();
            $updateStokBarang = Barang::findOrFail($dataBarang->id);

            if(intval($data['item_qty']) > intval($itemPembelian->qty)) {
                return response()->json([
                    'error' => true,
                    'message' => $data['item_qty'] . " melebihi batas maximal 🫣"
                ]);
            }

            if(intval($data['item_qty']) === 0) {
                return response()->json([
                    'error' => true,
                    'message' => "Return quantity tidak boleh menghasilkan nilai 0 🫣"
                ]);
            }

            $dataPerusahaan = SetupPerusahaan::with('tokos')->findOrFail(1);
            $currentDate = now()->format('ymd');
            $randomNumber = sprintf('%05d', mt_rand(0, 99999));
            $kode = $dataPerusahaan->kd_return_pembelian;
            $returnPembelian = new ReturnPembelian;
            $returnPembelian->kode = $kode. '-'. $currentDate . $randomNumber;
            $returnPembelian->tanggal = $currentDate;
            $returnPembelian->kode_barang = $itemPembelian->kode_barang;
            $returnPembelian->nama_barang = $itemPembelian->nama_barang;
            $returnPembelian->satuan = $itemPembelian->satuan;
            $returnPembelian->no_faktur = $pembelian->kode;
            $returnPembelian->tgl_beli = $pembelian->tanggal;
            $returnPembelian->supplier = $itemPembelian->supplier;
            $returnPembelian->qty = $data['item_qty_return'];
            $returnPembelian->harga = $itemPembelian->harga_beli;
            $returnPembelian->jumlah = intval($itemPembelian->harga_beli) * intval($data['item_qty_return']);
            $returnPembelian->alasan = $data['alasan'];
            $returnPembelian->kode_kas = $pembelian->kode_kas;
            $returnPembelian->operator = $pembelian->operator;
            $returnPembelian->kembali = "False";
            $returnPembelian->lokasistok = $pembelian->lokasistok !== NULL ? $pembelian->lokasistok : NULL;
            $returnPembelian->save();
            
            $itemPembelian->return = "True";
            $itemPembelian->save();

            if($returnPembelian->po === "True") {
                $newPemasukan = new Pemasukan;
                $newPemasukan->kode = "TPK"."-".$currentDate.$randomNumber;
                $newPemasukan->tanggal = $returnPembelian->tanggal;
                $newPemasukan->kd_biaya = "0003";
                $newPemasukan->keterangan = $returnPembelian->keterangan;
                $newPemasukan->kode_kas = $returnPembelian->kode_kas;
                $newPemasukan->jumlah = $returnPembelian->jumlah;
                $newPemasukan->operator = $returnPembelian->operator;
                $newPemasukan->save();
            }

            $data_event = [
                'routes' => 'return-pembelian',
                'alert' => 'success',
                'type' => 'return-data',
                'notif' => "Item pembelian dengan kode {$pembelian->kode}, berhasil di return 🤙!",
                'data' => $pembelian->kode,
                'user' => $userOnNotif
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => "Item pembelian dengan kode : {$itemPembelian->kode}, berhasil di return",
                'data' => $returnPembelian->kode
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
            $pembelian = Pembelian::findOrFail($id);
            $dataReturnPembelian = ReturnPembelian::where('no_faktur', $pembelian->kode)
            ->where('kembali', 'False')
            ->first();
            $returnPembelian = ReturnPembelian::select('return_pembelian.*', 'pembelian.id as id_pembelian', 'pembelian.return', 'kas.id as kas_id', 'kas.kode as kode_kas', 'kas.nama as nama_kas')
            ->leftJoin('pembelian', 'return_pembelian.no_faktur', '=', 'pembelian.kode')
            ->leftJoin('kas', 'return_pembelian.kode_kas', '=', 'kas.kode')
            ->findOrFail($dataReturnPembelian->id);

            return response()->json([
                'success' => true,
                'message' => "detail data return pembelian",
                'data' => $returnPembelian
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
            $pembelian = Pembelian::findOrFail($id);
            $dataItem = ItemPembelian::where('kode', $pembelian->kode)
            ->where('kode_barang', $data['kode_barang'])->first();
            $itemPembelian = ItemPembelian::findOrFail($dataItem->id);
            $dataBarang = Barang::whereKode($itemPembelian->kode_barang)->first();
            $updateStokBarang = Barang::findOrFail($dataBarang->id);

            $bindQty = intval($dataItem->qty) - intval($data['item_qty']);
            $lastQty = $dataItem->qty;
            $itemPembelian->qty = $bindQty;
            $itemPembelian->last_qty = $lastQty;
            $itemPembelian->harga_beli = $data['item_hargabeli'];
            $itemPembelian->subtotal = intval($data['item_hargabeli']) * $bindQty;
            $itemPembelian->return = "True";
            $itemPembelian->save();
            $itemSubtotal = ItemPembelian::whereKode($itemPembelian->kode)->get()->sum('subtotal');

            $pembelian->jumlah = $itemSubtotal;
            $pembelian->diterima = $itemSubtotal;
            $pembelian->return = "True";
            $pembelian->save();

            $kas->saldo = intval($kas->saldo) + $data['item_subtotal'];
            $kas->save();

            $bindingStok = $data['item_qty'] !== NULL ? intval($itemPembelian->last_qty) - intval($itemPembelian->qty) : 0;
            $updateStokBarang->toko = intval($dataBarang->toko) - intval($data['item_qty']);
            $updateStokBarang->last_qty = $dataBarang->toko;
            $updateStokBarang->save();

            $dataReturn = ReturnPembelian::where('no_faktur', $pembelian->kode)->first();
            $returnPembelian = ReturnPembelian::findOrFail($dataReturn->id);
            $returnPembelian->kembali = "True";
            $returnPembelian->save();

            $data_event = [
                'routes' => 'return-pembelian',
                'alert' => 'success',
                'type' => 'return-data',
                'notif' => "Item pembelian dengan kode {$pembelian->kode}, berhasil di return 🤙!",
                'data' => $pembelian->kode,
                'user' => $userOnNotif
            ];

            event(new EventNotification($data_event));

            return response()->json([
                'success' => true,
                'message' => "Item pembelian dengan kode : {$itemPembelian->kode}, berhasil di return",
                'data' => $returnPembelian->kode
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

        $query = ReturnPembelian::query()
        ->select(
            'return_pembelian.*',
            'pembelian.tanggal as tanggal_pembelian', 'itempembelian.kode as kode_item', 'itempembelian.qty as qty_item', 'itempembelian.last_qty', 'itempembelian.harga_beli as harga_beli', 'itempembelian.subtotal',
            'supplier.kode as kode_supplier',
            'supplier.nama as nama_supplier',
            'supplier.alamat as alamat_supplier',
            'barang.nama as nama_barang',
            'barang.satuan as satuan_barang'
        )
        ->leftJoin('pembelian', 'return_pembelian.no_faktur', '=', 'pembelian.kode')
        ->leftJoin('itempembelian', 'return_pembelian.no_faktur', '=', 'itempembelian.kode')
        ->leftJoin('supplier', 'return_pembelian.supplier', '=', 'supplier.kode')
        ->leftJoin('barang', 'return_pembelian.kode_barang', '=', 'barang.kode')
        ->orderByDesc('return_pembelian.id')
            // ->whereDate('pembelian.tanggal', '=', $today)
        ->where('return_pembelian.kode', $kode);

        $pembelian = $query->first();

        // echo "<pre>";
        // var_dump($orders);
        // echo "</pre>";
        // die;

        switch($type) {
            case "nota-kecil":
            return view('return-pembelian.nota_kecil', compact('pembelian', 'kode', 'toko', 'nota_type', 'helpers'));
            break;
            case "nota-besar":
            $pdf = PDF::loadView('return-pembelian.nota_besar', compact('pembelian','kode', 'toko', 'nota_type', 'helpers'));
            $pdf->setPaper(0,0,609,440, 'potrait');
            return $pdf->stream('Transaksi-'. $pembelian->kode .'.pdf');
            break;
        }
    }
}
