<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash, Validator, Http};
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Events\{EventNotification};
use App\Helpers\{WebFeatureHelpers};
use App\Http\Resources\{ResponseDataCollect, RequestDataCollect};
use App\Models\{Penjualan,ItemPenjualan,Pelanggan,Barang,Kas,Toko,LabaRugi,Piutang,ItemPiutang,FakturTerakhir,PembayaranAngsuran,PurchaseOrder, Supplier, Pemasukan, SetupPerusahaan};
use Auth;
use PDF;

class DataPenjualanPoController extends Controller
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
         $today = now()->toDateString();
         $now = now();
         $startOfMonth = $now->startOfMonth()->toDateString();
         $endOfMonth = $now->endOfMonth()->toDateString();
         $dateTransaction = $request->query('date_transaction');
         $viewAll = $request->query('view_all');
         $user = Auth::user();

         $query = Penjualan::query()
         ->select(
            'penjualan.id','penjualan.tanggal', 'penjualan.kode', 'penjualan.pelanggan','penjualan.keterangan', 'penjualan.kode_kas', 'penjualan.jumlah','penjualan.bayar','penjualan.dikirim','penjualan.lunas','penjualan.operator', 'penjualan.piutang','penjualan.receive','penjualan.biayakirim','kas.nama as nama_kas', 'pelanggan.nama as nama_pelanggan')
         ->leftJoin('kas', 'penjualan.kode_kas', '=', 'kas.kode')
         ->leftJoin('pelanggan', 'penjualan.pelanggan', '=', 'pelanggan.kode')
         ->addSelect(DB::raw('(SELECT stop_qty FROM itempenjualan WHERE itempenjualan.kode = penjualan.kode ORDER BY id DESC LIMIT 1) as stop_qty'))
         ->orderByDesc('penjualan.id')
         ->limit(10);

         if ($dateTransaction) {
            $query->whereDate('penjualan.tanggal', '=', $dateTransaction);
        }

        if ($keywords) {
            $query->where('penjualan.kode', 'like', '%' . $keywords . '%');
        }

        if($viewAll === false || $viewAll === "false") {
            // $query->whereDate('penjualan.tanggal', '=', $today);
            $query->whereBetween('penjualan.tanggal', [$startOfMonth, $endOfMonth]);
        }

        $penjualans = $query
        ->where(function ($query) use ($user) {
            if ($user->role !== 1) {
                $query->whereRaw('LOWER(penjualan.operator) like ?', [strtolower('%' . $user->name . '%')]);
            } 
        })
        ->where('penjualan.po', '=', 'True')
        ->orderByDesc('penjualan.id')
        ->paginate(10);

        return new ResponseDataCollect($penjualans);

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

            $lastIncrement = Penjualan::max('id') ?? 0;
            $increment = $lastIncrement + 1;

            $formattedIncrement = sprintf('%03d', $increment);

            $pelanggan = Pelanggan::findOrFail($data['pelanggan']);

            $barangIds = array_column($dataBarangs, 'id');
            $barangs = Barang::whereIn('id', $barangIds)->get();

            $kas = Kas::findOrFail($data['kode_kas']);

            if($kas->saldo < $data['diterima']) {
                return response()->json([
                    'error' => true,
                    'message' => "Saldo tidak mencukupi!!"
                ]);
            }

            $dataItemPenjualan = ItemPenjualan::whereKode($data['ref_code'])->first();
            $subtotal = intval($dataItemPenjualan->subtotal);
            $dataSupplier = Supplier::where('kode', $dataItemPenjualan->supplier)->first();

            $newPenjualan = new Penjualan;
            $newPenjualan->tanggal = $data['tanggal'] ? $data['tanggal'] : $currentDate;
            $newPenjualan->kode = $data['ref_code'];
            $newPenjualan->draft = 0;
            $newPenjualan->pelanggan = $pelanggan->kode;
            $newPenjualan->nama_pelanggan = $pelanggan->nama;
            $newPenjualan->alamat_pelanggan = $pelanggan->alamat;
            $newPenjualan->kode_kas = $kas->kode;
            $newPenjualan->jumlah = $data['jumlah'];
            $newPenjualan->bayar = $data['bayar'];
            $newPenjualan->kembali = $data['jumlah'] - $data['bayar'];
            $newPenjualan->lunas = "False";
            $newPenjualan->visa = "DP AWAL";
            $newPenjualan->jenis = "PENJUALAN PO";
            $newPenjualan->piutang = $data['piutang'];
            $newPenjualan->po = 'True';
            $newPenjualan->receive = "False";
            $newPenjualan->jt = $data['jt'];
            $newPenjualan->keterangan = $data['keterangan'] ? $data['keterangan'] : NULL;
            $newPenjualan->operator = $data['operator'];

            $newPenjualan->save();
            
            $updateDrafts = ItemPenjualan::whereKode($newPenjualan->kode)->get();
            foreach($updateDrafts as $idx => $draft) {
                $updateDrafts[$idx]->draft = 0;
                $updateDrafts[$idx]->save();
            }

            $updateKas = Kas::findOrFail($data['kode_kas']);
            $updateKas->saldo = intval($updateKas->saldo) + $newPenjualan->jumlah;
            $updateKas->save();

            $userOnNotif = Auth::user();

            $items = ItemPenjualan::whereKode($newPenjualan->kode)->get();

            $poTerakhir = PurchaseOrder::where('kode_po', $newPenjualan->kode)
            ->orderBy('po_ke', 'desc')
            ->first();

            $poKeBaru = ($poTerakhir) ? $poTerakhir->po_ke + 1 : 0;
            
            $pelanggan = Pelanggan::whereKode($newPenjualan->pelanggan)->first();

            if(count($items) > 0) {
                foreach($items as $item) {
                    $newPurchaseOrder = new PurchaseOrder;
                    $newPurchaseOrder->kode_po = $newPenjualan->kode;
                    $newPurchaseOrder->dp_awal = $newPenjualan->jumlah;
                    $newPurchaseOrder->po_ke = $poKeBaru;
                    $newPurchaseOrder->qty = $item->qty;
                    $newPurchaseOrder->nama_barang = $item->nama_barang;
                    $newPurchaseOrder->kode_barang = $item->kode_barang;
                        // $newPurchaseOrder->pelanggan = "{$pelanggan->nama}({$item->supplier})";
                    $newPurchaseOrder->supplier = "{$dataSupplier->kode}({$dataItemPenjualan->supplier})";
                    $newPurchaseOrder->harga_satuan = $item->harga_beli;
                    $newPurchaseOrder->subtotal = $item->qty * $item->harga_beli;
                    $newPurchaseOrder->sisa_dp = $newPenjualan->jumlah - ($item->qty * $item->harga_beli);
                    $newPurchaseOrder->type = "penjualan";
                    $newPurchaseOrder->save();
                }
            } else {
                $newPurchaseOrder = new PurchaseOrder;
                $newPurchaseOrder->kode_po = $newPenjualan->kode;
                $newPurchaseOrder->dp_awal = $newPenjualan->jumlah;
                $newPurchaseOrder->po_ke = $poKeBaru;
                $newPurchaseOrder->qty = $dataItemPenjualan->qty;
                $newPurchaseOrder->nama_barang = $dataItemPenjualan->nama_barang;
                $newPurchaseOrder->kode_barang = $dataItemPenjualan->kode_barang;
                    // $newPurchaseOrder->pelanggan = "{$pelanggan->kode}({$newPenjualan->pelanggan})";
                $newPurchaseOrder->supplier = "{$dataSupplier->kode}({$dataItemPenjualan->supplier})";
                $newPurchaseOrder->harga_satuan = $dataItemPenjualan->harga_beli;
                $newPurchaseOrder->subtotal = $dataItemPenjualan->qty * $dataItemPenjualan->harga_beli;
                $newPurchaseOrder->sisa_dp = $newPenjualan->jumlah - ($dataItemPenjualan->qty * $dataItemPenjualan->harga_beli);
                $newPurchaseOrder->type = "penjualan";
                $newPurchaseOrder->save();
            }

            $newPenjualanSaved =  Penjualan::query()
            ->select(
                'penjualan.*',
                'itempenjualan.*',
                'pelanggan.nama as nama_pelanggan',
                'pelanggan.alamat as alamat_pelanggan'
            )
            ->leftJoin('itempenjualan', 'penjualan.kode', '=', 'itempenjualan.kode')
            ->leftJoin('pelanggan', 'penjualan.pelanggan', '=', 'pelanggan.kode')
            ->where('penjualan.id', $newPenjualan->id)
            ->get();

            $data_event = [
                'routes' => 'penjualan-po',
                'alert' => 'success',
                'type' => 'add-data',
                'notif' => "Penjualan dengan kode {$newPenjualan->kode}, baru saja ditambahkan 🤙!",
                'data' => $newPenjualan->kode,
                'user' => $userOnNotif
            ];

            event(new EventNotification($data_event));

            return new RequestDataCollect($newPenjualanSaved);
        } catch (\Throwable $th) {
            throw $th;
        }
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

            // echo "<pre>";
            // var_dump($toko['name']); die;
            // echo "</pre>";

        $query = Penjualan::query()
        ->select(
            'penjualan.*',
            'itempenjualan.*',
            'pelanggan.nama as pelanggan_nama',
            'pelanggan.alamat as pelanggan_alamat',
            'barang.kode as kode_barang',
            'barang.nama as barang_nama',
            'barang.satuan as barang_satuan',
            'barang.harga_toko as harga_toko',
            'kas.kode', 'kas.nama as nama_kas',
            'kas.no_rek',
            DB::raw('COALESCE(itempenjualan.kode, penjualan.kode) as kode')
        )
        ->leftJoin('kas', 'penjualan.kode_kas', '=', 'kas.kode')
        ->leftJoin('itempenjualan', 'penjualan.kode', '=', 'itempenjualan.kode')
        ->leftJoin('pelanggan', 'penjualan.pelanggan', '=', 'pelanggan.kode')
        ->leftJoin('barang', 'itempenjualan.kode_barang', '=', 'barang.kode')
                // ->whereDate('pembelian.tanggal', '=', $today)
        ->where('penjualan.jenis', 'PENJUALAN PO')
        ->where('penjualan.kode', $kode);

        $barangs = $query->get();
        // echo "<pre>";
        // var_dump($barangs);
        // echo "</pre>";
        // die;
        $penjualan = $query->get()[0];

        foreach($barangs as $barang) {            
            $orders = PurchaseOrder::where('kode_po', $kode)
            ->where('kode_barang', $barang->kode_barang)
            ->get()->sum('qty');
        }

        $setting = "";

        switch($type) {
            case "nota-kecil":
            return view('penjualan.po.nota_kecil', compact('penjualan', 'barangs', 'orders', 'kode', 'toko', 'nota_type', 'helpers'));
            break;
            case "nota-besar":
            $pdf = PDF::loadView('penjualan.po.nota_besar', compact('penjualan', 'barangs', 'orders', 'kode', 'toko', 'nota_type', 'helpers'));
            $pdf->setPaper(0,0,350,440, 'potrait');
            return $pdf->stream('Transaksi-'. $penjualan->kode .'.pdf');
            break;
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
            $penjualan = Penjualan::query()
            ->select(
                'penjualan.id','penjualan.kode', 'penjualan.tanggal', 'penjualan.pelanggan', 'penjualan.kode_kas', 'penjualan.keterangan', 'penjualan.diskon','penjualan.tax', 'penjualan.jumlah', 'penjualan.bayar', 'penjualan.dikirim', 'penjualan.kembali','penjualan.operator', 'penjualan.jt as tempo' ,'penjualan.lunas', 'penjualan.visa', 'penjualan.piutang', 'penjualan.po','penjualan.receive','penjualan.biayakirim','penjualan.status', 'penjualan.tahan', 'kas.id as kas_id', 'kas.kode as kas_kode', 'kas.nama as kas_nama','kas.saldo as kas_saldo','pelanggan.id as id_pelanggan','pelanggan.kode as kode_pelanggan','pelanggan.nama as nama_pelanggan', 'pelanggan.alamat'
            )
            ->leftJoin('pelanggan', 'penjualan.pelanggan', '=',  'pelanggan.kode')
            ->leftJoin('kas', 'penjualan.kode_kas', '=', 'kas.kode')
            ->where('penjualan.id', $id)
            ->where('penjualan.po', '=', 'True')
            ->first();


            $items = ItemPenjualan::query()
            ->select('itempenjualan.*','barang.id as id_barang','barang.kode as kode_barang', 'barang.nama as nama_barang', 'barang.photo', 'barang.hpp as harga_beli_barang', 'barang.harga_toko', 'barang.toko as available_stok', 'barang.expired as expired_barang', 'barang.ada_expired_date','pelanggan.id as id_pelanggan','pelanggan.nama as nama_pelanggan','pelanggan.alamat as alamat_pelanggan', 'supplier.kode as kode_supplier', 'supplier.nama as nama_supplier')
            ->leftJoin('pelanggan', 'itempenjualan.pelanggan', '=', 'pelanggan.kode')
            ->leftJoin('barang', 'itempenjualan.kode_barang', '=', 'barang.kode')
            ->leftJoin('supplier', 'barang.kategori', '=', 'supplier.nama')
            ->where('itempenjualan.kode', $penjualan->kode)
            ->orderByDesc('itempenjualan.id')
            ->get();

            $purchaseOrders = PurchaseOrder::where('kode_po', '=', $penjualan->kode)
            ->orderBy('po_ke', 'DESC')
            ->get();

            return response()->json([
                'success' => true,
                'message' => "Detail penjualan {$penjualan->kode}",
                'data' => $penjualan,
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
            // $validator = Validator::make($request->all(), [
            //     'ongkir' => 'required',
            // ]);

            // if ($validator->fails()) {
            //     return response()->json($validator->errors(), 400);
            // }

            $data = $request->all();
            $currentDate = now()->format('ymd');
            $randomNumber = sprintf('%05d', mt_rand(0, 99999));
            $bayar = intval(preg_replace("/[^0-9]/", "", $data['bayar']));
            $dikirim = intval(preg_replace("/[^0-9]/", "", $data['dikirim']));

            $updatePenjualan = Penjualan::where('po', 'True')
            ->findOrFail($id);
            $dataPelanggan = Pelanggan::whereKode($data['pelanggan'])->first();
            $pelanggan = Pelanggan::findOrFail($dataPelanggan->id);
            $dataItemPo = PurchaseOrder::where('kode_po', $updatePenjualan->kode)->get();
            $totalSubtotal = $dataItemPo->sum('subtotal');

            $kas = Kas::whereKode($data['kode_kas'])->first();

            if(intval($kas->saldo) < $dikirim) {
                return response()->json([
                    'error' => true,
                    'message' => "Saldo tidak mencukupi!!"
                ]);
            }

            $updatePenjualan->draft = 0;
            $updatePenjualan->kode_kas = $kas->kode;

            if($dikirim > $bayar) {
                $updatePenjualan->lunas = "False";
                $updatePenjualan->visa = "PIUTANG";
                $updatePenjualan->piutang = $data['piutang'];
                $updatePenjualan->receive = "False";
                $updatePenjualan->tahan = "True";
                $updatePenjualan->status = "PENDING";
                // Masuk ke piutang
                $dataPerusahaan = SetupPerusahaan::with('tokos')->findOrFail(1);
                $masuk_piutang = new Piutang;
                $masuk_piutang->kode = $dataPerusahaan->kd_bayar_piutang.'-'. $currentDate . $randomNumber;
                $masuk_piutang->kd_jual = $updatePenjualan->kode;
                $masuk_piutang->tanggal = $currentDate;
                $masuk_piutang->pelanggan = $pelanggan->kode;
                $masuk_piutang->alamat = $pelanggan->alamat;
                $masuk_piutang->jumlah = $data['piutang'];
                // $masuk_piutang->bayar = $totalSubtotal;
                $masuk_piutang->bayar = $bayar - $data['jumlah_saldo'];
                $masuk_piutang->kode_kas = $updatePenjualan->kode_kas;
                $masuk_piutang->operator = $data['operator'];
                $masuk_piutang->save();

                $item_piutang = new ItemPiutang;
                $item_piutang->kode = $masuk_piutang->kode;
                $item_piutang->kd_jual = $updatePenjualan->kode;
                $item_piutang->kode_piutang = $masuk_piutang->kode;
                $item_piutang->tgl_piutang = $currentDate;
                $item_piutang->jumlah_piutang = $masuk_piutang->jumlah;
                $sisa_piutang = $masuk_piutang->jumlah - $masuk_piutang->bayar;
                $item_piutang->jumlah = $sisa_piutang < 0 ? 0 : $sisa_piutang;
                $item_piutang->save();

                $angsuranTerakhir = PembayaranAngsuran::where('kode', $masuk_piutang->kode)
                ->orderBy('angsuran_ke', 'desc')
                ->first();

                $angsuranKeBaru = ($angsuranTerakhir) ? $angsuranTerakhir->angsuran_ke + 1 : 1;

                $angsuran = new PembayaranAngsuran;
                $angsuran->kode = $masuk_piutang->kode;
                $angsuran->tanggal = $masuk_piutang->tanggal;
                $angsuran->operator = $data['operator'];
                $angsuran->angsuran_ke = $angsuranKeBaru;
                $angsuran->kode_pelanggan = NULL;
                $angsuran->kode_faktur = $updatePenjualan->kode;
                $angsuran->bayar_angsuran = $data['bayar'] ? $bayar - $data['jumlah_saldo'] : 0;
                $angsuran->jumlah = $item_piutang->jumlah_piutang;
                $angsuran->keterangan = "Pembayaran angsuran melalui kas : {$updatePenjualan->kode_kas}";
                $angsuran->save();

                // $updateKas = Kas::findOrFail($kas->id);
                // $bindCalc = $updatePenjualan->diterima - $updatePenjualan->jumlah;
                // $updateKas->saldo = $kas->saldo - $bindCalc;
                // $updateKas->save();
            } else if($data['sisa_dp'] > 0) {
                $updatePenjualan->kembali = $data['sisa_dp'];
                $updatePenjualan->dikirim = $totalSubtotal;
                $updatePenjualan->lunas = "True";
                $updatePenjualan->visa = "LUNAS";
                $updatePenjualan->receive = "True";
                $updatePenjualan->tahan = "False";
                $updatePenjualan->piutang = 0;
                $updatePenjualan->status = "DIKIRIM";
            } else {
                if($bayar > $data['jumlah_saldo']) {
                    $updateKas = Kas::findOrFail($kas->id);
                    $bindCalc = $bayar - $data['jumlah_saldo'];
                    $updateKas->saldo = $kas->saldo - $bindCalc;
                    $updateKas->save();
                }
                $updatePenjualan->dikirim = $totalSubtotal;
                $updatePenjualan->lunas = "True";
                $updatePenjualan->visa = "LUNAS";
                $updatePenjualan->jt = 0;
                $updatePenjualan->piutang = 0;
                $updatePenjualan->receive = "True";
                $updatePenjualan->tahan = "False";
                $updatePenjualan->status = "DIKIRIM";
                $updatePenjualan->biayakirim = intval($data['ongkir']);

                $dataPelanggan = Pelanggan::whereKode($updatePenjualan->pelanggan)->first();
                $pelanggan = Pelanggan::findOrFail($dataPelanggan->id);
                $itemPenjualanBarang = ItemPenjualan::whereKode($updatePenjualan->kode)->first();
                $newPenjualanData = Penjualan::findOrFail($updatePenjualan->id);
                $hpp = $itemPenjualanBarang->hpp * $itemPenjualanBarang->qty;
                $diskon = $updatePenjualan->diskon;
                $labarugi = ($updatePenjualan->bayar - $hpp) - $diskon;
                
                $newLabaRugi = new LabaRugi;
                $newLabaRugi->tanggal = now()->toDateString();
                $newLabaRugi->kode = $newPenjualanData->kode;
                $newLabaRugi->kode_barang = $itemPenjualanBarang->kode_barang;
                $newLabaRugi->nama_barang = $itemPenjualanBarang->nama_barang;
                $newLabaRugi->penjualan = $newPenjualanData->bayar;
                $newLabaRugi->hpp = $itemPenjualanBarang->hpp;
                $newLabaRugi->diskon =  $newPenjualanData->diskon;
                $newLabaRugi->labarugi = $labarugi;
                $newLabaRugi->operator = $data['operator'];
                $newLabaRugi->keterangan = "PENJUALAN P.O";
                $newLabaRugi->pelanggan = $pelanggan->kode;
                $newLabaRugi->nama_pelanggan = $pelanggan->nama;

                $newLabaRugi->save();

                $simpanFaktur = new FakturTerakhir;
                $simpanFaktur->faktur = $newPenjualanData->kode;
                $simpanFaktur->tanggal = $newPenjualanData->tanggal;
                $simpanFaktur->save();

                // $perusahaan = SetupPerusahaan::with('tokos')->findOrFail(1);
                // $pemasukan = new Pemasukan;
                // $pemasukan->kode = $updatePenjualan->kode;
                // $pemasukan->tanggal = $updatePenjualan->tanggal;
                // $pemasukan->kd_biaya = $perusahaan->kd_penjualan_toko."-PO";
                // $pemasukan->keterangan = "PENJUALAN P.O";
                // $pemasukan->kode_kas = $updatePenjualan->kode_kas;
                // $pemasukan->jumlah = $updatePenjualan->jumlah;
                // $pemasukan->operator = $updatePenjualan->operator;
                // $pemasukan->kode_pelanggan = $pelanggan->kode;
                // $pemasukan->nama_pelanggan = $pelanggan->nama;
                // $pemasukan->save();
            }

            $updatePenjualan->multiple_input = $data["multiple_input"];
            $updatePenjualan->jumlah = $data['jumlah_saldo'] ? $data['jumlah_saldo'] : $updatePenjualan->jumlah;
            $updatePenjualan->bayar = $bayar;
            $updatePenjualan->kembali = $data['masuk_hutang'] ? 0 : $data['piutang'];
            // $updatePenjualan->biayakirim = $updatePenjualan->biayakirim + $data['ongkir'];

            if($updatePenjualan->save()) {
                $userOnNotif = Auth::user();

                $dataItems = ItemPenjualan::whereKode($updatePenjualan->kode)->get();
                foreach($dataItems as $item) {
                    $updateItemPenjualan = ItemPenjualan::findOrFail($item->id);
                    $updateItemPenjualan->stop_qty = "False";
                    $updateItemPenjualan->save();
                }

                if($data['ongkir']) {                    
                    $updateKas = Kas::findOrFail($kas->id);
                    $updateKas->saldo = $kas->saldo - intval($data['ongkir']);
                    $updateKas->save();
                }


                $updatePenjualanSaved =  Penjualan::query()
                ->select(
                    'penjualan.*',
                    'itempenjualan.*',
                    'pelanggan.nama as nama_pelanggan',
                    'pelanggan.alamat as alamat_pelanggan'
                )
                ->leftJoin('itempenjualan', 'penjualan.kode', '=', 'itempenjualan.kode')
                ->leftJoin('pelanggan', 'penjualan.pelanggan', '=', 'pelanggan.kode')
                ->where('penjualan.id', $updatePenjualan->id)
                ->first();

                $data_event = [
                    'routes' => 'penjualan-po',
                    'alert' => 'success',
                    'type' => 'add-data',
                    'notif' => "Penjualan dengan kode {$updatePenjualan->kode}, berhasil diupdate 🤙!",
                    'data' => $updatePenjualan->kode,
                    'user' => $userOnNotif
                ];

                event(new EventNotification($data_event));

                return response()->json([
                    'success' => true,
                    'message' => "Data penjualan , berhasil diupdate 👏🏿",
                    'data' => $updatePenjualan
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
        //
    }
}
