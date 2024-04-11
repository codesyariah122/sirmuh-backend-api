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
use App\Models\{Penjualan,ItemPenjualan,Pelanggan,Barang,Kas,Toko,LabaRugi,Piutang,ItemPiutang,FakturTerakhir,PembayaranAngsuran,Roles, Pemasukan, SetupPerusahaan};
use Auth;
use PDF;

class DataPenjualanTokoController extends Controller
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
         $pelanggan = $request->query('pelanggan');
         $dateTransaction = $request->query('date_transaction');
         $viewAll = $request->query('view_all');
         $user = Auth::user();

         $query = Penjualan::query()
         ->select(
            'penjualan.id','penjualan.tanggal', 'penjualan.kode', 'penjualan.pelanggan','penjualan.keterangan', 'penjualan.kode_kas', 'penjualan.jumlah','penjualan.bayar', 'penjualan.dikirim','penjualan.lunas','penjualan.operator', 'penjualan.receive', 'penjualan.biayakirim','penjualan.status', 'kas.nama as nama_kas', 'pelanggan.nama as nama_pelanggan'
        )
         ->leftJoin('kas', 'penjualan.kode_kas', '=', 'kas.kode')
         ->leftJoin('pelanggan', 'penjualan.pelanggan', '=', 'pelanggan.kode')
         ->orderByDesc('penjualan.id')
         ->where('jenis', 'PENJUALAN TOKO')
         ->limit(10);

        if ($dateTransaction) {
            $query->whereDate('penjualan.tanggal', '=', $dateTransaction);
        }

        if ($pelanggan) {
            $query->where('penjualan.pelanggan', 'like', '%' . $pelanggan . '%');
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
        ->where('penjualan.po', '=', 'False')
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
                // 'ongkir' => ['required', 'not_in:0']
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $data = $request->all();
            $barangs = $data['barangs'];
            
            $dataBarangs = json_decode($barangs, true);

            $currentDate = now()->format('ymd');
            $randomNumber = sprintf('%05d', mt_rand(0, 99999));

            $lastIncrement = Penjualan::max('id') ?? 0;
            $increment = $lastIncrement + 1;

            $formattedIncrement = sprintf('%03d', $increment);

            $generatedCode = 'R43-' . $currentDate . $formattedIncrement;

            $pelanggan = Pelanggan::findOrFail($data['pelanggan']);

            $barangIds = array_column($dataBarangs, 'id');
            $barangs = Barang::whereIn('id', $barangIds)->get();
            // $updateStokBarang = Barang::findOrFail($data['barang']);
            // $updateStokBarang->toko = $updateStokBarang->toko + $request->qty;
            // $updateStokBarang->save();
            $kas = Kas::findOrFail($data['kode_kas']);
            // if($kas->saldo < $data['diterima']) {
            //     return response()->json([
            //         'error' => true,
            //         'message' => "Saldo tidak mencukupi!!"
            //     ]);
            // }

            $newPenjualanToko = new Penjualan;
            $newPenjualanToko->tanggal = $data['tanggal'] ? $data['tanggal'] : $currentDate;
            $newPenjualanToko->pelanggan = $pelanggan->kode;
            $newPenjualanToko->nama_pelanggan = $pelanggan->nama;
            $newPenjualanToko->alamat_pelanggan = $pelanggan->alamat;
            $newPenjualanToko->kode = $data['ref_code'] ? $data['ref_code'] : $generatedCode;
            $newPenjualanToko->draft = $data['draft'] ? 1 : 0;
            $newPenjualanToko->kode_kas = $kas->kode;
            
            if(isset($data['jumlah']) && is_numeric($data['jumlah'])) {
                $newPenjualanToko->jumlah = $data['jumlah'];
            } else {
                $newPenjualanToko->jumlah = 0;
            }
            
            $newPenjualanToko->bayar = $data['bayar'];

            if($data['piutang'] !== 'undefined') {
                $newPenjualanToko->angsuran = $data['bayar'];
                $newPenjualanToko->lunas = "False";
                $newPenjualanToko->visa = 'PIUTANG';
                $newPenjualanToko->piutang = $data['piutang'];
                $newPenjualanToko->po = 'False';
                $newPenjualanToko->receive = "False";
                $newPenjualanToko->jt = $data['jt'] ?? 7;
                $newPenjualanToko->status = "HOLD";
                $newPenjualanToko->keterangan = $data['keterangan'];

                // Masuk ke hutang
                $dataPerusahaan = SetupPerusahaan::with('tokos')->findOrFail(1);
                $masuk_hutang = new Piutang;
                $masuk_hutang->kode = $dataPerusahaan->kd_bayar_piutang.'-'. $currentDate . $randomNumber;
                $masuk_hutang->kd_jual = $data['ref_code'];
                $masuk_hutang->tanggal = $currentDate;
                $masuk_hutang->pelanggan = $pelanggan->kode;
                $masuk_hutang->alamat = $pelanggan->alamat;
                $masuk_hutang->jumlah = $data['piutang'];
                $masuk_hutang->kode_kas = $newPenjualanToko->kode_kas;
                $masuk_hutang->operator = $data['operator'];
                $masuk_hutang->save();

                $item_piutang = new ItemPiutang;
                $item_piutang->kode = $masuk_hutang->kode;
                $item_piutang->kd_jual = $data['ref_code'];
                $item_piutang->kode_piutang = $masuk_hutang->kode;
                $item_piutang->tgl_piutang = $currentDate;
                $item_piutang->jumlah_piutang = $masuk_hutang->jumlah;
                $item_piutang->jumlah = $masuk_hutang->jumlah;
                $item_piutang->save();

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
                $angsuran->bayar_angsuran = $data['diterima'];
                $angsuran->jumlah = $item_piutang->jumlah;
                $angsuran->save();
            } else {
                if(intval($data['bayar']) >= intval($data['jumlah'])) {
                    $newPenjualanToko->kembali = intval($data['bayar']) - intval($data['jumlah']);
                } else {
                    $newPenjualanToko->kembali = intval($data['jumlah']) - intval($data['bayar']);
                }

                if(intval($data['bayar']) > intval($data['jumlah'])) {
                    $newPenjualanToko->lunas = "True";
                    $newPenjualanToko->visa = "LUNAS";
                } else if(intval($data['bayar']) == intval($data['jumlah'])) {
                    $newPenjualanToko->lunas = "True";
                    $newPenjualanToko->visa = "UANG PAS";
                } else {
                    $newPenjualanToko->lunas = "True";
                }

                // $newPenjualanToko->lunas = $data['pembayaran'] === 'cash' ? "True" : "False";
                // $newPenjualanToko->visa = $data['pembayaran'] === 'cash' ? 'UANG PAS' : 'HUTANG';
                // $newPenjualanToko->piutang = $data['piutang'];
                $newPenjualanToko->dikirim = $data['status_kirim'] !== 'PROSES' ? $data['jumlah'] : 0;
                $newPenjualanToko->po = 'False';
                $newPenjualanToko->receive = $data['status_kirim'] !== "PROSES" ? "True" : "False";
                $newPenjualanToko->jt = $data['jt'] ?? 0;
                $newPenjualanToko->status = $data['status_kirim'] ? $data['status_kirim'] : 'DIKIRIM';
            }
            $newPenjualanToko->return = "False";
            $newPenjualanToko->jenis = "PENJUALAN TOKO";
            $newPenjualanToko->keterangan = $data['keterangan'];
            $newPenjualanToko->operator = $data['operator'];
            $newPenjualanToko->biayakirim = $data['ongkir'];
            
            $newPenjualanToko->save();
            
            $updateDrafts = ItemPenjualan::whereKode($newPenjualanToko->kode)->get();
            foreach($updateDrafts as $idx => $draft) {
                $updateDrafts[$idx]->draft = 0;
                $updateDrafts[$idx]->save();
            }

            $dikirim = intval($newPenjualanToko->bayar);
            $updateKas = Kas::findOrFail($data['kode_kas']);
            $updatesaldo = intval($kas->saldo) + intval($dikirim);
            $updateKas->saldo = $updatesaldo;
            $updateKas->save();

            $updatePenjualanDraft = Penjualan::findOrFail($newPenjualanToko->id);
            $updatePenjualanDraft->draft = 0;
            $updatePenjualanDraft->save();

            $userOnNotif = Auth::user();

            $itemPenjualanBarang = ItemPenjualan::whereKode($newPenjualanToko->kode)->first();
            $newPenjualanData = Penjualan::findOrFail($newPenjualanToko->id);
            $hpp = $itemPenjualanBarang->hpp * $data['qty'];
            $diskon = $newPenjualanToko->diskon;
            $labarugi = ($newPenjualanToko->bayar - $hpp) - $diskon;

            $newLabaRugi = new LabaRugi;
            $newLabaRugi->tanggal = now()->toDateString();
            $newLabaRugi->kode = $newPenjualanData->kode;
            $newLabaRugi->kode_barang = $itemPenjualanBarang->kode_barang;
            $newLabaRugi->nama_barang = $itemPenjualanBarang->nama_barang;
            $newLabaRugi->penjualan = $newPenjualanData->bayar;
            $newLabaRugi->hpp = $itemPenjualanBarang->harga;
            $newLabaRugi->diskon =  $newPenjualanData->diskon;
            $newLabaRugi->labarugi = $labarugi;
            $newLabaRugi->operator = $data['operator'];
            $newLabaRugi->keterangan = "PENJUALAN TOKO";
            $newLabaRugi->pelanggan = $pelanggan->kode;
            $newLabaRugi->nama_pelanggan = $pelanggan->nama;

            $newLabaRugi->save();

            $simpanFaktur = new FakturTerakhir;
            $simpanFaktur->faktur = $newPenjualanData->kode;
            $simpanFaktur->tanggal = $newPenjualanData->tanggal;
            $simpanFaktur->save();

                // $perusahaan = SetupPerusahaan::with('tokos')->findOrFail(1);
                // $pemasukan = new Pemasukan;
                // $pemasukan->kode = $newPenjualanToko->kode;
                // $pemasukan->tanggal = $newPenjualanToko->tanggal;
                // $pemasukan->kd_biaya = $perusahaan->kd_penjualan_toko;
                // $pemasukan->keterangan = "PENJUALAN TOKO";
                // $pemasukan->kode_kas = $newPenjualanToko->kode_kas;
                // $pemasukan->jumlah = $newPenjualanToko->jumlah;
                // $pemasukan->operator = $newPenjualanToko->operator;
                // $pemasukan->kode_pelanggan = $pelanggan->kode;
                // $pemasukan->nama_pelanggan = $pelanggan->nama;
                // $pemasukan->save();


            if($data['status_kirim'] === "DIKIRIM") {     
                $dataItemPenjualan = ItemPenjualan::whereKode($newPenjualanToko->kode)->first();
                $updateItem = ItemPenjualan::findOrFail($dataItemPenjualan->id);
                $updateItem->qty_terima = $dataItemPenjualan->qty;
                $updateItem->save();              
                $updateKas = Kas::findOrFail($kas->id);
                $updateKas->saldo = $kas->saldo - intval($data['ongkir']);
                $updateKas->save();
            }

            $newPenjualanTokoSaved =  Penjualan::query()
            ->select(
                'penjualan.*',
                'itempenjualan.*',
                'pelanggan.nama as nama_pelanggan',
                'pelanggan.alamat as alamat_pelanggan'
            )
            ->leftJoin('itempenjualan', 'penjualan.kode', '=', 'itempenjualan.kode')
            ->leftJoin('pelanggan', 'penjualan.pelanggan', '=', 'pelanggan.kode')
            ->where('penjualan.id', $newPenjualanToko->id)
            ->get();

            $data_event = [
                'routes' => 'penjualan-toko',
                'alert' => 'success',
                'type' => 'add-data',
                'notif' => "Penjualan dengan kode {$newPenjualanToko->kode}, baru saja ditambahkan 🤙!",
                'data' => $newPenjualanToko->kode,
                'user' => $userOnNotif
            ];

            event(new EventNotification($data_event));

            return new RequestDataCollect($newPenjualanTokoSaved);
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

    $query = Penjualan::select(
        'penjualan.*',
        'itempenjualan.*',
        'pelanggan.nama as pelanggan_nama',
        'pelanggan.alamat as pelanggan_alamat',
        'barang.kode as kode_barang',
        'barang.nama as barang_nama',
        'barang.satuan as barang_satuan',
        'barang.harga_toko as harga_toko',
        'kas.kode', 'kas.nama as nama_kas',
        DB::raw('COALESCE(itempenjualan.kode, penjualan.kode) as kode')
    )
    ->leftJoin('kas', 'penjualan.kode_kas', '=', 'kas.kode')
    ->leftJoin('itempenjualan', 'penjualan.kode', '=', 'itempenjualan.kode')
    ->leftJoin('pelanggan', 'penjualan.pelanggan', '=', 'pelanggan.kode')
    ->leftJoin('barang', 'itempenjualan.kode_barang', '=', 'barang.kode')
    ->where('penjualan.jenis', 'PENJUALAN TOKO')
    ->where('penjualan.kode', $kode);

    $barangs = $query->get();
    $penjualan = $query->get()[0];
    
    $setting = "";

    switch($type) {
        case "nota-kecil":
        return view('penjualan.nota_kecil', compact('penjualan', 'barangs', 'kode', 'toko', 'nota_type', 'helpers'));
        break;
        case "nota-besar":
        $pdf = PDF::loadView('penjualan.nota_besar', compact('penjualan', 'barangs', 'kode', 'toko', 'nota_type', 'helpers'));
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
                'penjualan.id','penjualan.kode', 'penjualan.tanggal', 'penjualan.pelanggan', 'penjualan.kode_kas', 'penjualan.keterangan', 'penjualan.diskon','penjualan.tax', 'penjualan.jumlah', 'penjualan.bayar','penjualan.dikirim', 'penjualan.kembali','penjualan.operator', 'penjualan.jt as tempo' ,'penjualan.lunas', 'penjualan.visa', 'penjualan.piutang', 'penjualan.receive',  'penjualan.status','penjualan.biayakirim', 'penjualan.po', 'kas.id as kas_id', 'kas.kode as kas_kode', 'kas.nama as kas_nama','kas.saldo as kas_saldo','pelanggan.id as id_pelanggan','pelanggan.kode as kode_pelanggan','pelanggan.nama as nama_pelanggan', 'pelanggan.alamat', 'return_penjualan.tanggal as tanggal_return','return_penjualan.qty','return_penjualan.satuan','return_penjualan.nama_barang','return_penjualan.harga','return_penjualan.jumlah as jumlah_return', 'return_penjualan.alasan'
            )
            ->leftJoin('pelanggan', 'penjualan.pelanggan', '=',  'pelanggan.kode')
            ->leftJoin('return_penjualan', 'penjualan.kode', '=', 'return_penjualan.no_faktur')
            ->leftJoin('kas', 'penjualan.kode_kas', '=', 'kas.kode')
            ->where('penjualan.id', $id)
            ->where('penjualan.po', 'False')
            ->first();


            $items = ItemPenjualan::query()
            ->select('itempenjualan.*','barang.id as id_barang','barang.kode as kode_barang', 'barang.nama as nama_barang', 'barang.photo', 'barang.hpp as harga_beli_barang', 'barang.expired as expired_barang', 'barang.ada_expired_date','pelanggan.id as id_pelanggan','pelanggan.nama as nama_pelanggan','pelanggan.alamat as alamat_pelanggan', 'supplier.kode as kode_supplier', 'supplier.nama as nama_supplier')
            ->leftJoin('pelanggan', 'itempenjualan.pelanggan', '=', 'pelanggan.kode')
            ->leftJoin('barang', 'itempenjualan.kode_barang', '=', 'barang.kode')
            ->leftJoin('supplier', 'itempenjualan.supplier', '=', 'supplier.kode')
            ->where('itempenjualan.kode', $penjualan->kode)
            ->orderByDesc('itempenjualan.id')
            ->get();

            return response()->json([
                'success' => true,
                'message' => "Detail penjualan {$penjualan->kode}",
                'data' => $penjualan,
                'items' => $items
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

            if(gettype($data['bayar']) === 'string') {
                $bayar = intval(preg_replace("/[^0-9]/", "", $data['bayar']));
            } else {
                $bayar = intval($data['bayar']);
            }

            if(gettype($data['diterima']) === 'string') {
                $dikirim = preg_replace("/[^0-9]/", "", $data['diterima']);
            } else {
                $dikirim = intval($data['diterima']);
            }

            $updatePembelian = Penjualan::findOrFail($id);
            $pelanggan = Pelanggan::findOrFail($data['pelanggan']);
            $kas = Kas::whereKode($data['kode_kas'])->first();
            $dataFaktur = FakturTerakhir::whereFaktur($updatePembelian->kode)->first();

            $updatePembelian->draft = 0;
            $updatePembelian->kode_kas = $kas->kode;
            $currentDate = now()->format('ymd');

            
            if($data['piutang']) {
                $updatePembelian->angsuran = $data['bayar'] ? $data['bayar'] : $data['bayarDp'];
                $updatePembelian->lunas = "False";
                $updatePembelian->visa = 'PIUTANG';
                $updatePembelian->piutang = $data['piutang'];
                $updatePembelian->po = $data['pembayaran'] !== 'cash' ? 'True' : 'False';
                $updatePembelian->receive = "False";
                $updatePembelian->jt = $data['jt'];

                // Masuk ke piutang
                $masuk_piutang = new Piutang;
                $masuk_piutang->kode = $updatePembelian->kode;
                $masuk_piutang->tanggal = $currentDate;
                $masuk_piutang->pelanggan = $pelanggan->kode;
                $masuk_piutang->jumlah = $data['piutang'];
                $masuk_piutang->kode_kas = $updatePembelian->kode_kas;
                $masuk_piutang->operator = $updatePembelian->operator;
                $masuk_piutang->save();

                $item_piutang = new ItemPiutang;
                $item_piutang->kode = $updatePembelian->kode;
                $item_piutang->kode_piutang = $masuk_piutang->kode;
                $item_piutang->tgl_piutang = $currentDate;
                $item_piutang->jumlah_piutang = $masuk_piutang->jumlah;
                $item_piutang->jumlah = $masuk_piutang->jumlah;
                $item_piutang->save();

                $angsuranTerakhir = PembayaranAngsuran::where('kode', $masuk_piutang->kode)
                ->orderBy('angsuran_ke', 'desc')
                ->first();

                $angsuranKeBaru = ($angsuranTerakhir) ? $angsuranTerakhir->angsuran_ke + 1 : 1;

                $angsuran = new PembayaranAngsuran;
                $angsuran->kode = $masuk_piutang->kode;
                $angsuran->tanggal = $masuk_piutang->tanggal;
                $angsuran->angsuran_ke = $angsuranKeBaru;
                $angsuran->kode_pelanggan = NULL;
                $angsuran->kode_faktur = NULL;
                $angsuran->bayar_angsuran = $data['bayarDp'];
                $angsuran->jumlah = $item_piutang->jumlah_hutang;
                $angsuran->save();
            } else {
                $updatePembelian->jumlah = $data['jumlah'] ? $data['jumlah'] : $updatePembelian->jumlah;
                $updatePembelian->bayar = $data['bayar'] ? $bayar : $updatePembelian->bayar;

                if($dikirim  > $updatePembelian->jumlah) {
                    $updatePembelian->kembali = $data['bayar'] - $updatePembelian->jumlah;
                    $updatePembelian->lunas = "True";
                    $updatePembelian->visa = "LUNAS";
                } else if($dikirim == $updatePembelian->jumlah) {
                    $updatePembelian->kembali = $updatePembelian->jumlah - $data['bayar'];
                    $updatePembelian->lunas = "True";
                    $updatePembelian->visa = "UANG PAS";
                } else {
                    $updatePembelian->kembali = $updatePembelian->jumlah - $data['bayar'];
                    $updatePembelian->lunas = "True";
                }
                
            }

            $updatePembelian->save();

            $updateFakturTerakhir = FakturTerakhir::findOrFail($dataFaktur->id);
            $updateFakturTerakhir = $currentDate;
            $updateFakturTerakhir->save();
            
            $updateKas = Kas::findOrFail($kas->id);
            $updateKas->saldo = intval($kas->saldo) - intval($data['bayar']);
            $updateKas->save();

            if($updatePembelian) {
                $userOnNotif = Auth::user();

                $updatePembelianSaved =  Penjualan::query()
                ->select(
                    'penjualan.*',
                    'itempenjualan.*',
                    'supplier.nama as nama_supplier',
                    'supplier.alamat as alamat_supplier'
                )
                ->leftJoin('itempenjualan', 'penjualan.kode', '=', 'itempenjualan.kode')
                ->leftJoin('supplier', 'penjualan.supplier', '=', 'supplier.kode')
                ->where('penjualan.id', $updatePembelian->id)
                ->first();

                $data_event = [
                    'routes' => 'penjualan-toko',
                    'alert' => 'success',
                    'type' => 'add-data',
                    'notif' => "Pembelian dengan kode {$updatePembelian->kode}, berhasil diupdate 🤙!",
                    'data' => $updatePembelian->kode,
                    'user' => $userOnNotif
                ];

                event(new EventNotification($data_event));

                return response()->json([
                    'success' => true,
                    'message' => "Data penjualan , berhasil diupdate 👏🏿"
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
                $deletePenjualan = Penjualan::findOrFail($id);

                $dataPiutang = Piutang::where('kode', $deletePenjualan->kode)->first();

                if($dataPiutang) {
                    $deletePiutang = Piutang::findOrFail($dataPiutang->id);
                    $deletePiutang->delete();

                    $hutangItems = ItemPiutang::where('kode', $deletePenjualan->kode)->get();
                    foreach($piutangItems as $itemPiutang) {                    
                        $deleteItemPiutang = ItemPiutang::findOrFail($itemPiutang->id);
                        $deleteItemPiutang->delete();
                    }

                    $angsuranItems = PembayaranAngsuran::where('kode', $deletePenjualan->kode)->get();
                    foreach($angsuranItems as $itemAngsuran) {                    
                        $deleteAngsuran = PembayaranAngsuran::findOrFail($itemAngsuran->id);
                        $deleteAngsuran->delete();
                    }
                }

                $deletePenjualan->delete();

                $penjualanItems = ItemPenjualan::where('kode', $deletePenjualan->kode)->get();
                foreach($penjualanItems as $itemPenjualan) {                
                    $deleteItem = ItemPenjualan::findOrFail($itemPenjualan->id);
                    $deleteItem->delete();

                    $dataBarang = Barang::where('kode', $itemPenjualan->kode_barang)->first();
                    $updateStokBarang = Barang::findOrFail($dataBarang->id);
                    $updateStokBarang->toko = $dataBarang->toko - $dataItemPembelian->qty;
                    $updateStokBarang->last_qty = $dataBarang->toko;
                    $updateStokBarang->save();
                }

                $dataKas = Kas::where('kode', $deletePenjualan->kode_kas)->first();
                $updateKas = Kas::findOrFail($dataKas->id);
                $updateKas->saldo = $dataKas->saldo - $deletePenjualan->jumlah;
                $updateKas->save();

                $data_event = [
                    'alert' => 'error',
                    'routes' => 'penjualan-toko',
                    'type' => 'removed',
                    'notif' => "Penjualan dengan kode, {$deletePenjualan->kode}, successfully deleted!",
                    'user' => Auth::user()
                ];

                event(new EventNotification($data_event));

                return response()->json([
                    'success' => true,
                    'message' => "Penjualan dengan kode, {$deletePenjualan->kode} berhasil dihapus 👏"
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
