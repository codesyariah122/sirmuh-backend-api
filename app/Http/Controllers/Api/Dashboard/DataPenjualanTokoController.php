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
use App\Models\{Penjualan,ItemPenjualan,Pelanggan,Barang,Kas,Toko,LabaRugi};
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

         $user = Auth::user()->name;

         $query = Penjualan::query()
         ->select(
            'penjualan.id','penjualan.tanggal', 'penjualan.kode', 'penjualan.pelanggan','penjualan.keterangan', 'penjualan.kode_kas', 'penjualan.jumlah','penjualan.bayar','penjualan.kembali','penjualan.tax','penjualan.diskon','penjualan.lunas','penjualan.operator','penjualan.jt','penjualan.piutang',
            'itempenjualan.kode', 'itempenjualan.qty','itempenjualan.subtotal','itempenjualan.ppn','itempenjualan.diskon','itempenjualan.diskon_rupiah',
            'pelanggan.nama as pelanggan_nama',
            'pelanggan.alamat as pelanggan_alamat',
            'barang.nama as barang_nama',
            'barang.satuan as barang_satuan',
            DB::raw('COALESCE(itempenjualan.kode, penjualan.kode) as kode')
        )
         ->leftJoin('itempenjualan', 'penjualan.kode', '=', 'itempenjualan.kode')
         ->leftJoin('pelanggan', 'penjualan.pelanggan', '=', 'pelanggan.kode')
         ->leftJoin('barang', 'itempenjualan.kode_barang', '=', 'barang.kode')
         ->orderByDesc('penjualan.id')
         ->limit(10);

         if ($keywords) {
            $query->where('penjualan.kode', 'like', '%' . $keywords . '%');
        }

        $query->whereDate('penjualan.tanggal', '=', $today);
        $penjualans = $query
        ->where(function ($query) use ($user) {
            if ($user !== "Vicky Andriani") {
                $query->whereRaw('LOWER(penjualan.operator) like ?', [strtolower('%' . $user . '%')]);
            }
        })
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
                'pelanggan' => 'required',
                'kode_kas' => 'required',
                'barangs' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            } else {
                $data = $request->all();
                $barangs = $data['barangs'];
                // if(is_array($barangs) || is_object($barangs)) {
                //     $dataBarangs = $data['barangs'];
                // } else {}
                $dataBarangs = json_decode($barangs, true);

                $currentDate = now()->format('ymd');

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

                foreach($barangs as $barang) {
                    $barangId = $barang->id;
                    $qtyToUpdate = 0;

                    foreach ($dataBarangs as $dataBarang) {
                        if ($dataBarang['id'] == $barangId) {
                            $qtyToUpdate = $dataBarang['qty'];
                            break;
                        }
                    }

                    $barang->toko = $barang->toko + $qtyToUpdate;
                    $barang->save();
                }

                $kas = Kas::findOrFail($data['kode_kas']);

                // if($kas->saldo < $data['diterima']) {
                //     return response()->json([
                //         'error' => true,
                //         'message' => "Saldo tidak mencukupi!!"
                //     ]);
                // }

                $newPenjualan = new Penjualan;
                $newPenjualan->tanggal = $currentDate;
                $newPenjualan->kode = $data['ref_code'] ? $data['ref_code'] : $generatedCode;
                $newPenjualan->pelanggan = $pelanggan->kode;
                $newPenjualan->kode_kas = $kas->kode;
                $newPenjualan->subtotal = $barang->hpp * $data['qty'];
                $newPenjualan->diskon = $data['diskon'];
                // $newPenjualan->kembali = $data['kembali'] ? $data['kembali'] : $data['bayar'] - ($barang->hpp * $data['qty']);
                $newPenjualan->kembali = $data['kembali'];
                $newPenjualan->jumlah = $data['jumlah'];
                $newPenjualan->bayar = $data['bayar'];
                $newPenjualan->lunas = $data['pembayaran'] === 'cash' ? "True" : "False";
                $newPenjualan->visa = $data['pembayaran'] === 'cash' ? 'UANG PAS' : 'HUTANG';
                $newPenjualan->piutang = $data['pembayaran'] !== 'cash' ? $data['bayar'] : 0.00;
                $newPenjualan->po = $data['pembayaran'] !== 'cash' ? 'True' : 'False';
                $newPenjualan->receive = "True";
                $newPenjualan->jt = $data['jt'] ?? 0.00;
                $newPenjualan->keterangan = $data['keterangan'];
                $newPenjualan->operator = $data['operator'];

                $newPenjualan->save();

                $updateDrafts = ItemPenjualan::whereKode($newPenjualan->kode)->get();
                foreach($updateDrafts as $idx => $draft) {
                    $updateDrafts[$idx]->draft = 0;
                    $updateDrafts[$idx]->save();
                }

                // $updateQty = ItemPenjualan::findOrFail($newItemPenjualan->id);
                // $updateQty->qty = $newItemPenjualan->qty + $data['qty'];
                $newPenjualanData = Penjualan::findOrFail($newPenjualan->id);
                $diterima = intval($newPenjualanData->jumlah);
                $updateKas = Kas::findOrFail($data['kode_kas']);
                $updateKas->saldo = intval($updateKas->saldo) + intval($diterima);
                $updateKas->save();

                $userOnNotif = Auth::user();

                if($newPenjualan) {
                   $hpp = $barang->hpp * $data['qty'];
                   $diskon = $newPenjualan->diskon;
                   $labarugi = $newPenjualan->bayar - $hpp - $diskon;

                   $newLabaRugi = new LabaRugi;
                   $newLabaRugi->tanggal = now()->toDateString();
                   $newLabaRugi->kode = $newPenjualanData->kode;
                   $newLabaRugi->kode_barang = $barang->kode;
                   $newLabaRugi->nama_barang = $barang->nama;
                   $newLabaRugi->penjualan = $newPenjualanData->bayar;
                   $newLabaRugi->hpp = $barang->hpp;
                   $newLabaRugi->diskon =  $newPenjualanData->diskon;
                   $newLabaRugi->labarugi = $labarugi;
                   $newLabaRugi->operator = $data['operator'];
                   $newLabaRugi->keterangan = "PENJUALAN BARANG";
                   $newLabaRugi->pelanggan = $pelanggan->kode;
                   $newLabaRugi->nama_pelanggan = $pelanggan->nama;

                   $newLabaRugi->save();

                 $newPenjualanSaved =  Penjualan::query()
                 ->select(
                    'penjualan.*',
                    'itempenjualan.*',
                    'pelanggan.nama as nama_pelanggan',
                    'pelanggan.alamat as alamat_pelanggan'
                )
                 ->leftJoin('itempenjualan', 'penjualan.kode', '=', 'itempenjualan.kode')
                 ->leftJoin('pelanggan', 'penjualan.pelanggan', '=', 'pelanggan.kode')
                 ->where('penjualan.id',$newPenjualan->id)
                 ->get();

                 $data_event = [
                    'routes' => 'penjualan-toko',
                    'alert' => 'success',
                    'type' => 'add-data',
                    'notif' => "Penjualan dengan kode {$newPenjualan->kode}, berhasil 🤙!",
                    'data' =>$newPenjualan->kode,
                    'user' => $userOnNotif
                ];

                event(new EventNotification($data_event));

                return new RequestDataCollect($newPenjualanSaved);
            }
        }

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
        'barang.nama as barang_nama',
        'barang.satuan as barang_satuan',
        DB::raw('COALESCE(itempenjualan.kode, penjualan.kode) as kode')
    )
    ->leftJoin('itempenjualan', 'penjualan.kode', '=', 'itempenjualan.kode')
    ->leftJoin('pelanggan', 'penjualan.pelanggan', '=', 'pelanggan.kode')
    ->leftJoin('barang', 'itempenjualan.kode_barang', '=', 'barang.kode')
                // ->whereDate('pembelian.tanggal', '=', $today)
    ->where('penjualan.kode', $kode);

    $barangs = $query->get();
    $penjualan = $query->get()[0];
        // echo "<pre>";
        // var_dump($penjualan);
        // echo "</pre>";
        // die;

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
        //
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
