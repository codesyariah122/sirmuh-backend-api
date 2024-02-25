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
            ->select('piutang.*', 'penjualan.jt as jatuh_tempo','penjualan.kode_kas','penjualan.jumlah as jumlah_penjualan','penjualan.bayar as bayar_penjualan', 'penjualan.visa','penjualan.lunas', 'penjualan.piutang as piutang_penjualan', 'pelanggan.id as id_pelanggan', 'pelanggan.kode as kode_pelanggan', 'pelanggan.nama as nama_pelanggan', 'itempenjualan.nama_barang', 'itempenjualan.kode_barang', 'itempenjualan.qty as qty_penjualan', 'itempenjualan.satuan as satuan_penjualan_barang', 'itempenjualan.harga as harga_beli', 'barang.kategori', 'barang.kode as kode_barang', 'barang.kode_barcode as kode_barcode',  'kas.id as kas_id', 'kas.kode as kas_kode', 'kas.nama as kas_nama', 'pembayaran_angsuran.tanggal as tanggal_angsuran', 'pembayaran_angsuran.angsuran_ke', 'pembayaran_angsuran.bayar_angsuran', 'pembayaran_angsuran.jumlah as jumlah_angsuran')
            ->leftJoin('penjualan', 'piutang.kode', '=', 'penjualan.kode')
            ->leftJoin('pelanggan', 'piutang.pelanggan', '=', 'pelanggan.kode')
            ->leftJoin('itempenjualan', 'itempenjualan.kode', '=', 'penjualan.kode')
            ->leftJoin('barang', 'barang.kode', '=', 'itempenjualan.kode_barang')
            ->leftJoin('kas', 'penjualan.kode_kas', '=', 'kas.kode')
            ->leftJoin('pembayaran_angsuran', 'piutang.kode', '=', 'pembayaran_angsuran.kode');

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

                if($bayar >= $dataPenjualan->piutang) {
                    $updatePenjualan->lunas = 1;
                    $updatePenjualan->visa = "LUNAS";
                    $updatePenjualan->piutang = $bayar - intval($dataPenjualan->hutang);
                } else {
                    $updatePenjualan->lunas = 0;
                    $updatePenjualan->visa = "HUTANG";
                    $updatePenjualan->piutang = intval($dataPenjualan->piutang) - $bayar;
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

                $notifEvent =  "Piutang dengan kode {$piutang->kode}, dibayarkan {$bayar} 💸";

                $updateKas = Kas::findOrFail($dataKas->id);
                $updateKas->saldo = intval($dataKas->saldo) + $bayar;
                $updateKas->save();

                $userOnNotif = Auth::user();

                $data_event = [
                    'routes' => 'piutang-pelanggan',
                    'alert' => 'success',
                    'type' => 'update-data',
                    'notif' => $notifEvent,
                    'data' => $piutang->kode,
                    'user' => $userOnNotif
                ];

                event(new EventNotification($data_event));
                return response()->json([
                    'success' => true,
                    'message' => "Piutang dengan kode {$piutang->kode}, dibayarkan {$bayar} 💸",
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

        $query = Piutang::query()
        ->select(
            'piutang.kode', 'piutang.tanggal','piutang.pelanggan','piutang.jumlah as jml_piutang','piutang.bayar as byr_piutang','piutang.operator',
            'itempiutang.jumlah as piutang_jumlah',
            'itempiutang.jumlah_piutang as jumlah_piutang',
            'penjualan.tanggal as tanggal_penjualan',
            'penjualan.kode_kas',
            'penjualan.jumlah as jumlah_penjualan',
            'penjualan.bayar as bayar_penjualan',
            'penjualan.visa',
            'penjualan.po',
            'penjualan.jenis as jenis_penjualan',
            'penjualan.jt',
            'penjualan.lunas as status_lunas',
            'penjualan.piutang as piutang_penjualan',
            'itempenjualan.kode_barang',
            'itempenjualan.nama_barang',
            'itempenjualan.qty',
            'itempenjualan.satuan',
            'itempenjualan.harga as harga_toko',
            'itempenjualan.pelanggan',
            'pelanggan.nama as nama_pelanggan',
            'pelanggan.kode as kode_pelanggan',
            'kas.kode as kode_kas',
            'kas.nama',
            'kas.saldo',
            'pembayaran_angsuran.*'
        )
        ->leftJoin('itempiutang', 'piutang.kode', '=', 'itempiutang.kode')
        ->leftJoin('penjualan', 'penjualan.kode', '=', 'piutang.kode')
        ->leftJoin('itempenjualan', 'itempenjualan.kode', '=', 'penjualan.kode')
        ->leftJoin('pelanggan', 'itempenjualan.pelanggan', '=', 'pelanggan.kode')
        ->leftJoin('kas', 'penjualan.kode_kas', '=', 'kas.kode')
        ->leftJoin('pembayaran_angsuran', 'piutang.kode', '=', 'pembayaran_angsuran.kode')
        ->where('piutang.kode', $kode);

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
            return view('terima-piutang.nota_kecil', compact('piutang', 'angsurans', 'kode', 'toko', 'nota_type', 'helpers'));
            break;
            case "nota-besar":
            $pdf = PDF::loadView('terima-piutang.nota_besar', compact('piutang', 'angsurans', 'kode', 'toko', 'nota_type', 'helpers'));
            $pdf->setPaper(0, 0, 609, 440, 'portrait');
            return $pdf->stream('Terima-Piutang-' . $piutang->kode . '.pdf');
            break;
        }
    }
}
