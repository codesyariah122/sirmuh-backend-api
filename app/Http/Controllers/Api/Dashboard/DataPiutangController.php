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
use App\Models\{Toko, Piutang, Penjualan, ItemPenjualan,PembayaranAngsuran,Kas,ItemPiutang};
use App\Http\Resources\{ResponseDataCollect, RequestDataCollect};
use App\Helpers\{UserHelpers, WebFeatureHelpers};
use Auth;
use PDF;

class DataPiutangController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    private $helpers,$user_helpers;

    public function __construct()
    {
        $this->helpers = new WebFeatureHelpers;
        $this->user_helpers = new UserHelpers;
    }

    public function index(Request $request)
    {
        try {
            $keywords = $request->query('keywords');
            $sortName = $request->query('sort_name');
            $sortType = $request->query('sort_type');
            $startDate = $request->query("start_date");
            $endDate = $request->query("end_date");

            $query = Piutang::select('piutang.id','piutang.kode', 'piutang.tanggal', 'piutang.jumlah', 'piutang.operator', 'penjualan.id as id_penjualan', 'penjualan.kode as kode_penjualan','penjualan.tanggal as tanggal_penjualan', 'penjualan.jt as jatuh_tempo', 'penjualan.lunas', 'pelanggan.kode as kode_pelanggan', 'pelanggan.nama as nama_pelanggan')
            ->leftJoin('pelanggan', 'piutang.pelanggan', '=', 'pelanggan.kode')
            ->leftJoin('penjualan', 'piutang.kode', 'penjualan.kode')
            ->where('penjualan.jt', '>', 0);

            if ($keywords) {
                $query->where('piutang.supplier', 'like', '%' . $keywords . '%');
            }

            if ($sortName && $sortType) {
                $query->orderBy($sortName, $sortType);
            } else {
                if($startDate && $endDate) {
                    $query->whereBetween('piutang.tanggal', [$startDate, $endDate]);
                }
            }

            $query->orderByDesc('piutang.id');

            $piutangs = $query->paginate(10);

            return new ResponseDataCollect($piutangs);

        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function data_hutang()
    {
        try {

        }catch (\Throwable $th) {
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
        try {
            $query =  Piutang::query()
            ->select('piutang.*', 'itempiutang.jumlah_piutang', 'pembelian.jt as jatuh_tempo','pembelian.kode_kas','pembelian.jumlah as jumlah_pembelian', 'pembelian.diterima','pembelian.bayar', 'pembelian.visa','pembelian.lunas','pelanggan.id as id_pelanggan', 'pelanggan.kode as kode_pelanggan', 'pelanggan.nama as nama_pelanggan','pelanggan.alamat as alamat_pelanggan', 'itempembelian.nama_barang', 'itempembelian.kode_barang', 'itempembelian.qty as qty_pembelian', 'itempembelian.satuan as satuan_pembelian_barang', 'itempembelian.harga_beli as harga_beli','itempembelian.subtotal','barang.kategori', 'barang.kode as kode_barang', 'barang.kode_barcode as kode_barcode',  'kas.id as kas_id', 'kas.kode as kas_kode', 'kas.nama as kas_nama')
            ->leftJoin('pembelian', 'piutang.kode', '=', 'pembelian.kode')
            ->leftJoin('pelanggan', 'piutang.pelanggan', '=', 'pelanggan.kode')
            ->leftJoin('itempembelian', 'itempembelian.kode', '=', 'pembelian.kode')
            ->leftJoin('barang', 'barang.kode', '=', 'itempembelian.kode_barang')
            ->leftJoin('kas', 'pembelian.kode_kas', '=', 'kas.kode')
            ->leftJoin('itempiutang','piutang.kode','=','itempiutang.kode');

            $piutang = $query->where('piutang.id', $id)->first();

            $angsurans = PembayaranAngsuran::whereKode($piutang->kode)
            ->orderByDesc('id')
            ->get();

            return response()->json([
                'success' => true,
                'message' => 'Detail piutang',
                'data' => $piutang,
                'angsurans' => $angsurans
            ], 200);
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

            $validator = Validator::make($request->all(), [
                'bayar' => 'required',
            ]);


            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $query =  Piutang::query()
            ->select('piutang.*', 'penjualan.jt as jatuh_tempo','penjualan.kode_kas','penjualan.jumlah as jumlah_penjualan','penjualan.bayar as bayar_penjualan', 'penjualan.visa','penjualan.lunas', 'pelanggan.id as id_pelanggan', 'pelanggan.kode as kode_pelanggan', 'pelanggan.nama as nama_pelanggan', 'itempenjualan.nama_barang', 'itempenjualan.kode_barang', 'itempenjualan.qty as qty_penjualan', 'itempenjualan.satuan as satuan_penjualan_barang', 'itempenjualan.harga as harga_beli', 'barang.kategori', 'barang.kode as kode_barang', 'barang.kode_barcode as kode_barcode',  'kas.id as kas_id', 'kas.kode as kas_kode', 'kas.nama as kas_nama', 'pembayaran_angsuran.tanggal as tanggal_angsuran', 'pembayaran_angsuran.angsuran_ke', 'pembayaran_angsuran.bayar_angsuran', 'pembayaran_angsuran.jumlah as jumlah_angsuran')
            ->leftJoin('penjualan', 'piutang.kode', '=', 'penjualan.kode')
            ->leftJoin('pelanggan', 'piutang.pelanggan', '=', 'pelanggan.kode')
            ->leftJoin('itempenjualan', 'itempenjualan.kode', '=', 'penjualan.kode')
            ->leftJoin('barang', 'barang.kode', '=', 'itempenjualan.kode_barang')
            ->leftJoin('kas', 'penjualan.kode_kas', '=', 'kas.kode')
            ->leftJoin('pembayaran_angsuran', 'piutang.kode', '=', 'pembayaran_angsuran.kode');

            $piutang = $query->where('piutang.id', $id)->first();

            $bayar = intval($request->bayar);
            $jmlHutang = intval($piutang->jumlah);
            $kasId = $request->kas_id;

            $dataKas = Kas::findOrFail($piutang->kas_id);

            $checkAngsuran = PembayaranAngsuran::where('kode', $piutang->kode)
                   ->get();
                   
            if(count($checkAngsuran) > 0) {
                $dataPenjualan = Penjualan::whereKode($piutang->kode)->first();
                $updatePenjualan = Penjualan::findOrFail($dataPenjualan->id);
                $updatePenjualan->bayar = intval($dataPenjualan->bayar_pembelian) + $bayar;
                $updatePenjualan->diterima = intval($dataPenjualan->diterima) + $bayar;

                if($bayar >= $dataPenjualan->hutang) {
                    $updatePenjualan->lunas = 1;
                    $updatePenjualan->visa = "LUNAS";
                    $updatePenjualan->hutang = $bayar - intval($dataPenjualan->hutang);
                } else {
                    $updatePenjualan->lunas = 0;
                    $updatePenjualan->visa = "HUTANG";
                    $updatePenjualan->hutang = intval($dataPenjualan->hutang) - $bayar;
                }
                $updatePenjualan->save();

                $updatePiutang = Piutang::findOrFail($piutang->id);
                if($bayar >= $jmlHutang) {
                    $updatePiutang->jumlah = $bayar - $jmlHutang;
                    $updatePiutang->bayar = $bayar;
                } else {
                    $updatePiutang->jumlah = $jmlHutang - $bayar;
                    $updatePiutang->bayar = intval($piutang->bayar) + $bayar;
                }
                $updatePiutang->ket = $request->ket ?? "";
                $updatePiutang->save();

                $dataItemHutang = ItemPiutang::whereKode($updatePiutang->kode)->first();
                $updateItemHutang = ItemPiutang::findOrFail($dataItemHutang->id);
                if($bayar >= $jmlHutang) {
                    $updateItemHutang->return = $bayar - $jmlHutang;
                } else {
                    $updateItemHutang->return = 0;
                }
                $updateItemHutang->jumlah = $updatePiutang->jumlah;
                $updateItemHutang->save();

                $angsuranTerakhir = PembayaranAngsuran::where('kode', $piutang->kode)
                ->orderBy('angsuran_ke', 'desc')
                ->first();

                $angsuranKeBaru = ($angsuranTerakhir) ? $angsuranTerakhir->angsuran_ke + 1 : 1;

                $angsuran = new PembayaranAngsuran;
                $angsuran->kode = $piutang->kode;
                $angsuran->tanggal = $piutang->tanggal;
                $angsuran->angsuran_ke = $angsuranKeBaru;
                $angsuran->kode_pelanggan = NULL;
                $angsuran->kode_faktur = NULL;
                $angsuran->bayar_angsuran = $bayar;
                $angsuran->jumlah = intval($angsuranTerakhir->jumlah) - $bayar;
                $angsuran->save();

                $notifEvent =  "Hutang dengan kode {$piutang->kode}, dibayar {$bayar} 💸";

                $updateKas = Kas::findOrFail($dataKas->id);
                $updateKas->saldo = intval($dataKas->saldo) + $bayar;
                $updateKas->save();

                $userOnNotif = Auth::user();

                $data_event = [
                    'routes' => 'bayar-hutang',
                    'alert' => 'success',
                    'type' => 'update-data',
                    'notif' => $notifEvent,
                    'data' => $piutang->kode,
                    'user' => $userOnNotif
                ];

                event(new EventNotification($data_event));
                return response()->json([
                    'success' => true,
                    'message' => "Hutang dengan kode {$piutang->kode}, dibayar {$bayar} 💸",
                    'data' => $piutang
                ], 200);
            } else {
                return response()->json([
                    'failed' => true,
                    'message' => "Piutang not found"
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

    public function check_bayar_hutang(Request $request, $id)
    {
        try {
            $query =  DB::table('hutang')
            ->select('hutang.*', 'pembelian.jt as jatuh_tempo','pembelian.kode_kas','pembelian.jumlah as jumlah_pembelian', 'pembelian.diterima','pembelian.bayar', 'pembelian.visa','pembelian.lunas', 'supplier.id as id_supplier', 'supplier.kode as kode_supplier', 'supplier.nama as nama_supplier', 'itempembelian.nama_barang', 'itempembelian.kode_barang', 'itempembelian.qty as qty_pembelian', 'itempembelian.satuan as satuan_pembelian_barang', 'itempembelian.harga_beli as harga_beli', 'barang.kategori', 'barang.kode as kode_barang', 'barang.kode_barcode as kode_barcode',  'kas.id as kas_id', 'kas.kode as kas_kode', 'kas.nama as kas_nama')
            ->leftJoin('pembelian', 'hutang.kode', '=', 'pembelian.kode')
            ->leftJoin('supplier', 'hutang.supplier', '=', 'supplier.kode')
            ->leftJoin('itempembelian', 'itempembelian.kode', '=', 'pembelian.kode')
            ->leftJoin('barang', 'barang.kode', '=', 'itempembelian.kode_barang')
            ->leftJoin('kas', 'pembelian.kode_kas', '=', 'kas.kode');

            $piutang = $query->where('hutang.id', $id)->first();
            $jmlHutang = intval($piutang->jumlah);
            $bayar = intval($request->query('bayar'));
            if($bayar >= $jmlHutang) {
                // masuk lunas
                $kembali = $bayar - $jmlHutang;
                $formatKembali = $this->helpers->format_uang($kembali);
                $kembaliTerbilang = ''.ucwords($this->helpers->terbilang($jmlHutang). ' Rupiah');
                $data = [
                    'lunas' => true,
                    'jmlHutang' => $this->helpers->format_uang($jmlHutang),
                    'message' => 'Pembayaran hutang telah terbayar lunas 🏦💵💵',
                    'bayar' => $bayar,
                    'bayarRupiah' => $this->helpers->format_uang($bayar),
                    'kembali' => $kembali,
                    'formatRupiah' => $formatKembali,
                    'terbilang' => $kembaliTerbilang,
                    'kasId' => $piutang->kas_id
                ];
            } else {
                $sisaHutang = $jmlHutang - $bayar;
                $formatSisaHutang = $this->helpers->format_uang($sisaHutang);
                $sisaHutangTerbilang = ''.ucwords($this->helpers->terbilang($jmlHutang). ' Rupiah');
                $data = [
                    'lunas' => false,
                    'message' => 'Pembayaran hutang masuk dalam angsuran 💱',
                    'jmlHutang' => $this->helpers->format_uang($jmlHutang),
                    'bayar' => $bayar,
                    'bayarRupiah' => $this->helpers->format_uang($bayar),
                    'sisaHutang' => $sisaHutang,
                    'formatRupiah' => $formatSisaHutang,
                    'terbilang' => $sisaHutangTerbilang,
                    'kasId' => $piutang->kas_id

                ];
            }

            return response()->json($data);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function cetak_nota($type, $kode, $id_perusahaan)
    {
        $ref_code = $kode;
        $nota_type = $type === 'nota-kecil' ? "Nota Kecil" : "Nota Besar";
        $helpers = $this->helpers;
        $today = now()->toDateString();
        $toko = Toko::whereId($id_perusahaan)
        ->select("name", "logo", "address", "kota", "provinsi")
        ->first();

        $query = Hutang::query()
        ->select(
            'hutang.kode', 'hutang.tanggal','hutang.supplier','hutang.jumlah as jml_hutang','hutang.bayar as byr_hutang','hutang.operator',
            'itemhutang.jumlah as hutang_jumlah',
            'itemhutang.jumlah_hutang as jumlah_hutang',
            'pembelian.tanggal as tanggal_pembelian',
            'pembelian.kode_kas',
            'pembelian.jumlah as jumlah_pembelian',
            'pembelian.bayar as bayar_pembelian',
            'pembelian.diterima',
            'pembelian.visa',
            'pembelian.po',
            'pembelian.jt',
            'pembelian.lunas',
            'pembelian.hutang',
            'itempembelian.kode_barang',
            'itempembelian.nama_barang',
            'itempembelian.qty',
            'itempembelian.satuan',
            'itempembelian.harga_beli',
            'itempembelian.supplier',
            'supplier.nama as nama_supplier',
            'supplier.kode as kode_supplier',
            'kas.kode as kode_kas',
            'kas.nama',
            'kas.saldo',
            'pembayaran_angsuran.*'
        )
        ->leftJoin('itemhutang', 'hutang.kode', '=', 'itemhutang.kode')
        ->leftJoin('pembelian', 'pembelian.kode', '=', 'hutang.kode')
        ->leftJoin('itempembelian', 'itempembelian.kode', '=', 'pembelian.kode')
        ->leftJoin('supplier', 'itempembelian.supplier', '=', 'supplier.kode')
        ->leftJoin('kas', 'pembelian.kode_kas', '=', 'kas.kode')
        ->leftJoin('pembayaran_angsuran', 'hutang.kode', '=', 'pembayaran_angsuran.kode')
        ->where('hutang.kode', $kode);

        $piutang = $query->first();
        $angsurans = PembayaranAngsuran::whereKode($piutang->kode)->get();

        $setting = "";

        // echo "<pre>";
        // var_dump($piutang);
        // var_dump($piutang->hutang);
        // var_dump($piutang->jml_hutang);
        // var_dump($piutang->angsuran_ke);
        // var_dump($piutang->bayar_angsuran); 
        // echo "</pre>";
        // die;

        switch ($type) {
            case "nota-kecil":
            return view('bayar-hutang.nota_kecil', compact('hutang', 'angsurans', 'kode', 'toko', 'nota_type', 'helpers'));
            break;
            case "nota-besar":
            $pdf = PDF::loadView('bayar-hutang.nota_besar', compact('hutang', 'angsurans', 'kode', 'toko', 'nota_type', 'helpers'));
            $pdf->setPaper(0, 0, 609, 440, 'portrait');
            return $pdf->stream('Bayar-Hutang-' . $piutang->kode . '.pdf');
            break;
        }
    }
}
