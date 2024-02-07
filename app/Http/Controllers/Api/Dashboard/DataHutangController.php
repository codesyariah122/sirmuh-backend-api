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
use App\Models\{Toko, Hutang, Pembelian, ItemPembelian,PembayaranAngsuran};
use App\Http\Resources\{ResponseDataCollect, RequestDataCollect};
use App\Helpers\{UserHelpers, WebFeatureHelpers};
use Auth;
use PDF;

class DataHutangController extends Controller
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

            $query = DB::table('hutang')
            ->select('hutang.*', 'pembelian.jt as jatuh_tempo', 'pembelian.jumlah as jumlah_pembelian', 'pembelian.bayar', 'pembelian.diterima', 'itempembelian.id as itempembelian_id', 'itempembelian.kode as itempembelian_kode', 'itempembelian.qty', 'itempembelian.subtotal','itempembelian.satuan','supplier.nama as nama_supplier', 'barang.kode as kode_barang', 'barang.nama as barang_nama', 'barang.hpp as barang_harga_beli')
            ->leftJoin('pembelian', 'hutang.kode', '=', 'pembelian.kode')
            ->leftJoin('itempembelian', 'pembelian.kode', '=', 'itempembelian.kode')
            ->leftJoin('barang', 'itempembelian.kode_barang', '=', 'barang.kode')
            ->leftJoin('supplier', 'pembelian.supplier', '=', 'supplier.kode')
            ->where('pembelian.jt', '>', 0);

            if ($keywords) {
                $query->where('hutang.supplier', 'like', '%' . $keywords . '%');
            }

            if ($sortName && $sortType) {
                $query->orderBy($sortName, $sortType);
            } else {
                if($startDate && $endDate) {
                    $query->whereBetween('hutang.tanggal', [$startDate, $endDate]);
                }
            }
            $query->orderByDesc('hutang.id');

            $hutangs = $query->paginate(10);

            return response()->json([
                'success' => true,
                'message' => 'List data hutang',
                'data' => $hutangs
            ], 200);

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
            $query =  Hutang::query()
            ->select('hutang.*', 'pembelian.jt as jatuh_tempo','pembelian.kode_kas','pembelian.jumlah as jumlah_pembelian', 'pembelian.diterima','pembelian.bayar', 'pembelian.visa','pembelian.lunas', 'supplier.id as id_supplier', 'supplier.kode as kode_supplier', 'supplier.nama as nama_supplier', 'itempembelian.nama_barang', 'itempembelian.kode_barang', 'itempembelian.qty as qty_pembelian', 'itempembelian.satuan as satuan_pembelian_barang', 'itempembelian.harga_beli as harga_beli', 'barang.kategori', 'barang.kode as kode_barang', 'barang.kode_barcode as kode_barcode',  'kas.id as kas_id', 'kas.kode as kas_kode', 'kas.nama as kas_nama')
            ->leftJoin('pembelian', 'hutang.kode', '=', 'pembelian.kode')
            ->leftJoin('supplier', 'hutang.supplier', '=', 'supplier.kode')
            ->leftJoin('itempembelian', 'itempembelian.kode', '=', 'pembelian.kode')
            ->leftJoin('barang', 'barang.kode', '=', 'itempembelian.kode_barang')
            ->leftJoin('kas', 'pembelian.kode_kas', '=', 'kas.kode');

            $hutang = $query->where('hutang.id', $id)->get();

            return response()->json([
                'success' => true,
                'message' => 'Detail hutang',
                'data' => $hutang
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
            $query =  Hutang::query()
            ->select('hutang.*', 'pembelian.jt as jatuh_tempo','pembelian.kode_kas','pembelian.jumlah as jumlah_pembelian', 'pembelian.diterima','pembelian.bayar', 'pembelian.visa','pembelian.lunas', 'supplier.id as id_supplier', 'supplier.kode as kode_supplier', 'supplier.nama as nama_supplier', 'itempembelian.nama_barang', 'itempembelian.kode_barang', 'itempembelian.qty as qty_pembelian', 'itempembelian.satuan as satuan_pembelian_barang', 'itempembelian.harga_beli as harga_beli', 'barang.kategori', 'barang.kode as kode_barang', 'barang.kode_barcode as kode_barcode',  'kas.id as kas_id', 'kas.kode as kas_kode', 'kas.nama as kas_nama')
            ->leftJoin('pembelian', 'hutang.kode', '=', 'pembelian.kode')
            ->leftJoin('supplier', 'hutang.supplier', '=', 'supplier.kode')
            ->leftJoin('itempembelian', 'itempembelian.kode', '=', 'pembelian.kode')
            ->leftJoin('barang', 'barang.kode', '=', 'itempembelian.kode_barang')
            ->leftJoin('kas', 'pembelian.kode_kas', '=', 'kas.kode');

            $hutang = $query->where('hutang.id', $id)->first();

            $bayar = intval($request->bayar);
            $jmlHutang = intval($hutang->jumlah);
            $kasId = $request->kas_id;

            if($bayar >= $jmlHutang) {
                // delete hutang, update pembelian, itempembelian
                $dataPembelian = Pembelian::whereKode($hutang->kode)->first();
                $updatePembelian = Pembelian::findOrFail($dataPembelian->id);
                $updatePembelian->bayar = intval($dataPembelian->bayar) + $bayar;
                $updatePembelian->diterima = intval($dataPembelian->diterima) + $bayar;
                $updatePembelian->jt = 0;
                $updatePembelian->lunas = 1;
                $updatePembelian->visa = "LUNAS";
                $updatePembelian->save();
                $updateHutang = Hutang::findOrFail($hutang->id);
                $updateHutang->jumlah = $bayar - $jmlHutang;
                $updateHutang->save();
                
                $notifEvent = "Hutang dengan kode {$hutang->kode}, Lunas 🛒💸💰";

                return response()->json([
                    'success' => true,
                    'message' => "Hutang dengan kode {$hutang->kode}, Lunas 🛒💸💰",
                    'data' => $hutang
                ], 200);
            } else {
                $dataPembelian = Pembelian::whereKode($hutang->kode)->first();
                $updatePembelian = Pembelian::findOrFail($dataPembelian->id);
                $updatePembelian->bayar = intval($dataPembelian->bayar) + $bayar;
                $updatePembelian->diterima = intval($dataPembelian->diterima) + $bayar;
                $updatePembelian->lunas = 0;
                $updatePembelian->visa = "HUTANG";
                $updatePembelian->save();
                $updateHutang = Hutang::findOrFail($hutang->id);
                $updateHutang->jumlah = $jmlHutang - $bayar;
                if($bayar < $jmlHutang) {
                   $angsuranTerakhir = PembayaranAngsuran::where('kode', $hutang->kode)
                   ->orderBy('angsuran_ke', 'desc')
                   ->first();

                   $angsuranKeBaru = ($angsuranTerakhir) ? $angsuranTerakhir->angsuran_ke + 1 : 1;

                    $angsuran = new PembayaranAngsuran;
                    $angsuran->kode = $hutang->kode;
                    $angsuran->tanggal = $hutang->tanggal;
                    $angsuran->angsuran_ke = $angsuranKeBaru;
                    $angsuran->kode_pelanggan = NULL;
                    $angsuran->kode_faktur = NULL;
                    $angsuran->jumlah = $jmlHutang;
                    $angsuran->save();
                }
                $updateHutang->bayar = $bayar;
                $updateHutang->ket = $request->ket ?? "";
                $updateHutang->save();

                $notifEvent =  "Hutang dengan kode {$hutang->kode}, dibayar {$bayar} 💸";
                return response()->json([
                    'success' => true,
                    'message' => "Hutang dengan kode {$hutang->kode}, dibayar {$bayar} 💸",
                    'data' => $hutang
                ], 200);
            }

            $userOnNotif = Auth::user();

            $data_event = [
                    'routes' => 'bayar-hutang',
                    'alert' => 'success',
                    'type' => 'update-data',
                    'notif' => $notifEvent,
                    'data' => $hutang->kode,
                    'user' => $userOnNotif
                ];

                event(new EventNotification($data_event));

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

            $hutang = $query->where('hutang.id', $id)->first();
            $jmlHutang = intval($hutang->jumlah);
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
                    'kasId' => $hutang->kas_id
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
                    'kasId' => $hutang->kas_id

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
            'hutang.*',
            'itemhutang.jumlah as hutang_jumlah',
            'itemhutang.jumlah_hutang as jumlah_hutang',
            'pembelian.tanggal as tanggal_pembelian',
            'pembelian.supplier',
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
            'supplier.nama as nama_supplier',
            'supplier.kode as kode_supplier',
            'kas.kode as kode_kas',
            'kas.nama',
            'kas.saldo'
        )
        ->leftJoin('itemhutang', 'hutang.kode', '=', 'itemhutang.kode')
        ->leftJoin('pembelian', 'pembelian.kode', '=', 'hutang.kode')
        ->leftJoin('itempembelian', 'itempembelian.kode', '=', 'pembelian.kode')
        ->leftJoin('supplier', 'pembelian.supplier', '=', 'supplier.kode')
        ->leftJoin('kas', 'pembelian.kode_kas', '=', 'kas.kode')
        ->where('hutang.kode', $kode);

        $hutang = $query->first();

        $setting = "";

        switch ($type) {
            case "nota-kecil":
            return view('bayar-hutang.nota_kecil', compact('hutang', 'kode', 'toko', 'nota_type', 'helpers'));
            break;
            case "nota-besar":
            $pdf = PDF::loadView('bayar-hutang.nota_besar', compact('hutang', 'kode', 'toko', 'nota_type', 'helpers'));
            $pdf->setPaper(0, 0, 609, 440, 'portrait');
            return $pdf->stream('Bayar-Hutang-' . $hutang->kode . '.pdf');
            break;
        }
    }
}
