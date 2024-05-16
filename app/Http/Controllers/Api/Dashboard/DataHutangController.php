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
use App\Jobs\ProcessLargeRequest;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Events\{EventNotification};
use App\Models\{Toko, Hutang, Pembelian, ItemPembelian,PembayaranAngsuran,Kas,ItemHutang, Supplier};
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

    // public function index(Request $request)
    // {
    //     try {
    //         $keywords = $request->query('keywords');

    //         $hutangsPaginated = collect();

    //         Hutang::select('hutang.id', 'hutang.kode', 'hutang.tanggal', 'hutang.supplier', 'hutang.jumlah', 'hutang.bayar', 'hutang.operator', 'pembelian.id as id_pembelian', 'pembelian.kode as kode_pembelian', 'pembelian.tanggal as tanggal_pembelian', 'pembelian.jt as jatuh_tempo', 'pembelian.lunas', 'pembelian.visa', 'itemhutang.jumlah_hutang as jumlah_hutang', 'supplier.nama as nama_supplier')
    //         ->leftJoin('itemhutang', 'hutang.kode', '=', 'itemhutang.kode_hutang')
    //         ->leftJoin('supplier', 'hutang.supplier', '=', 'supplier.kode')
    //         ->leftJoin('pembelian', 'hutang.kode', 'pembelian.kode')
    //         // ->where('pembelian.jt', '>', 0)
    //         ->when($keywords, function ($query, $keywords) {
    //             return $query->where('hutang.kode', 'like', '%' . $keywords . '%');
    //         })
    //         ->orderByDesc('hutang.id')
    //         ->chunk(1000, function ($hutangsChunk) use (&$hutangsPaginated) {
    //             $hutangsPaginated = $hutangsPaginated->concat($hutangsChunk);
    //         });

    //         $page = $request->query('page', 1);

    //         $perPage = 10;
    //         $total = $hutangsPaginated->count();
    //         $items = $hutangsPaginated->forPage($page, $perPage)->values();
    //         $paginator = new LengthAwarePaginator($items, $total, $perPage, $page);

    //         return new ResponseDataCollect($paginator);

    //     } catch (\Throwable $th) {
    //         throw $th;
    //     }
    // }

    public function index(Request $request)
    {
        try {
            $keywords = $request->query('keywords');
            $page = $request->query('page');
            $supplier = $request->query('supplier');
            $viewAll = $request->query('view_all');
            $now = now();
            $startOfMonth = $now->startOfMonth()->toDateString();
            $endOfMonth = $now->endOfMonth()->toDateString();
            $dateTransaction = $request->query('date_transaction');
            $user = Auth::user();
            
            $query = Hutang::select('hutang.id', 'hutang.kode', 'hutang.kd_beli', 'hutang.tanggal', 'hutang.supplier', 'hutang.jumlah', 'hutang.bayar', 'hutang.operator', 'pembelian.id as id_pembelian', 'pembelian.kode as kode_pembelian', 'pembelian.tanggal as tanggal_pembelian', 'pembelian.jt as jatuh_tempo', 'pembelian.kode as kode_pembelian', 'pembelian.lunas', 'pembelian.visa', 'itemhutang.kode as kode_item_hutang', 'itemhutang.kode_hutang','itemhutang.jumlah_hutang as jumlah_hutang', 'supplier.nama as nama_supplier')
            ->leftJoin('itemhutang', 'hutang.kode', '=', 'itemhutang.kode_hutang')
            ->leftJoin('supplier', 'hutang.supplier', '=', 'supplier.kode')
            ->leftJoin('pembelian', 'hutang.kd_beli', 'pembelian.kode');
            // ->where('pembelian.jt', '>', 0);

            if ($viewAll === true || $viewAll === "true") {
                $query->whereBetween('hutang.tanggal', [$startOfMonth, $endOfMonth]);
            }  else {
                $query->limit(10);
            }

            if ($keywords) {
                $query->where('hutang.supplier', 'like', '%' . $keywords . '%');
            }

            if ($supplier) {
                $query->where('hutang.supplier', 'like', '%' . $supplier . '%');
            }

            if ($dateTransaction) {
                $query->whereDate('hutang.tanggal', '=', $dateTransaction);
            }

            $query->orderByDesc('hutang.id')
            ->where(function ($query) use ($user) {
                if ($user->role !== 1) {
                    $query->whereRaw('LOWER(hutang.operator) like ?', [strtolower('%' . $user->name . '%')]);
                } 
            });

            $hutangs = $query->paginate(10);

            return new ResponseDataCollect($hutangs);

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
            ->select('hutang.*', 'itemhutang.jumlah_hutang', 'pembelian.kode as kode_pembelian', 'pembelian.jt as jatuh_tempo','pembelian.jumlah as jumlah_pembelian', 'pembelian.diterima','pembelian.bayar', 'pembelian.visa','pembelian.lunas','pembelian.po', 'pembelian.hutang as sisa_hutang','supplier.id as id_supplier', 'supplier.kode as kode_supplier', 'supplier.nama as nama_supplier', 'supplier.alamat as alamat_supplier', 'itempembelian.nama_barang', 'itempembelian.kode_barang', 'itempembelian.qty as qty_pembelian', 'itempembelian.satuan as satuan_pembelian_barang', 'itempembelian.harga_beli as harga_beli','itempembelian.subtotal','barang.kategori', 'barang.kode as kode_barang', 'barang.kode_barcode as kode_barcode',  'kas.id as kas_id', 'kas.kode as kas_kode', 'kas.nama as kas_nama')
            ->leftJoin('pembelian', 'hutang.kd_beli', '=', 'pembelian.kode')
            ->leftJoin('supplier', 'hutang.supplier', '=', 'supplier.kode')
            ->leftJoin('itempembelian', 'itempembelian.kode', '=', 'pembelian.kode')
            ->leftJoin('barang', 'barang.kode', '=', 'itempembelian.kode_barang')
            ->leftJoin('kas', 'pembelian.kode_kas', '=', 'kas.kode')
            ->leftJoin('itemhutang','hutang.kode','=','itemhutang.kode');

            $hutang = $query->where('hutang.id', $id)->first();

            $angsurans = PembayaranAngsuran::whereKode($hutang->kode)
            ->orderByDesc('id')
            ->get();

            return response()->json([
                'success' => true,
                'message' => 'Detail hutang',
                'data' => $hutang,
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

            $user = Auth::user();

            $query =  Hutang::query()
            ->select('hutang.*', 'pembelian.jt as jatuh_tempo','pembelian.kode_kas','pembelian.jumlah as jumlah_pembelian', 'pembelian.diterima','pembelian.bayar as bayar_pembelian', 'pembelian.visa','pembelian.lunas', 'supplier.id as id_supplier', 'supplier.kode as kode_supplier', 'supplier.nama as nama_supplier', 'itempembelian.nama_barang', 'itempembelian.kode_barang', 'itempembelian.qty as qty_pembelian', 'itempembelian.satuan as satuan_pembelian_barang', 'itempembelian.harga_beli as harga_beli', 'barang.kategori', 'barang.kode as kode_barang', 'barang.kode_barcode as kode_barcode',  'kas.id as kas_id', 'kas.kode as kas_kode', 'kas.nama as kas_nama', 'pembayaran_angsuran.tanggal as tanggal_angsuran', 'pembayaran_angsuran.angsuran_ke', 'pembayaran_angsuran.bayar_angsuran', 'pembayaran_angsuran.jumlah as jumlah_angsuran')
            ->leftJoin('pembelian', 'hutang.kd_beli', '=', 'pembelian.kode')
            ->leftJoin('supplier', 'hutang.supplier', '=', 'supplier.kode')
            ->leftJoin('itempembelian', 'itempembelian.kode', '=', 'pembelian.kode')
            ->leftJoin('barang', 'barang.kode', '=', 'itempembelian.kode_barang')
            ->leftJoin('kas', 'pembelian.kode_kas', '=', 'kas.kode')
            ->leftJoin('pembayaran_angsuran', 'hutang.kode', '=', 'pembayaran_angsuran.kode');

            $hutang = $query->where('hutang.id', $id)->first();

            $bayar = intval($request->bayar);
            $jmlHutang = intval($hutang->jumlah);
            $kasId = $request->kode_kas;

            if(gettype($kasId) === "string") {
                $kodeKas = Kas::whereKode($kasId)->first();
                $dataKas = Kas::findOrFail($kodeKas->id);
            } else {
                $dataKas = Kas::findOrFail($kasId);
            }

            $checkAngsuran = PembayaranAngsuran::where('kode', $hutang->kode)
            ->get();

            if(count($checkAngsuran) > 0) {
                $dataPembelian = Pembelian::whereKode($hutang->kd_beli)->first();
                $supplier = Supplier::whereKode($hutang->supplier)->first();
                $updatePembelian = Pembelian::findOrFail($dataPembelian->id);
                $updateSupplier = Supplier::findOrFail($supplier->id);
                $updatePembelian->bayar = intval($dataPembelian->bayar) + $bayar;
                // $updatePembelian->diterima = intval($dataPembelian->diterima) + $bayar;

                if($bayar >= $dataPembelian->hutang) {
                    $updatePembelian->lunas = "True";
                    $updatePembelian->visa = "LUNAS";
                    $updatePembelian->hutang = 0;
                    $updateSupplier->saldo_hutang = 0;
                    if($dataPembelian->po === "True") {
                        // $updatePembelian->angsuran = $updatePembelian->bayar;
                        $updatePembelian->receive = "True";
                        $updatePembelian->kekurangan_sdh_dibayar = "True";
                        $dataItemPembelian = ItemPembelian::whereKode($updatePembelian->kode)->first();
                        $updateItemPembelian = ItemPembelian::findOrFail($dataItemPembelian->id);
                        $updateItemPembelian->stop_qty = "True";
                        $updateItemPembelian->save();
                    }
                } else {
                    $updatePembelian->lunas = "False";
                    $updatePembelian->visa = "HUTANG";
                    $updatePembelian->hutang = intval($dataPembelian->hutang) - $bayar;
                    $updateSupplier->saldo_hutang = intval($supplier->saldo_hutang) - $bayar;
                }

                $updatePembelian->save();
                $updateSupplier->save();

                $updateHutang = Hutang::findOrFail($hutang->id);
                if($bayar >= $jmlHutang) {
                    $updateHutang->jumlah = 0;
                    $updateHutang->bayar = $bayar;
                    $updateHutang->kembali = $request->kembali ? $request->kembali : $bayar - $jmlHutang;
                } else {
                    $updateHutang->jumlah = $jmlHutang - $bayar;
                    $updateHutang->bayar = intval($hutang->bayar) + $bayar;
                    $updateHutang->kembali = 0;
                }
                // $updateHutang->kode_kas = "{$dataKas->nama} ($dataKas->kode)";
                $updateHutang->ket = $request->ket ?? "";
                $updateHutang->save();

                $dataItemHutang = ItemHutang::whereKode($updateHutang->kode)->first();
                $updateItemHutang = ItemHutang::findOrFail($dataItemHutang->id);
                if($bayar >= $jmlHutang) {
                    $updateItemHutang->return = $bayar - $jmlHutang;
                } else {
                    $updateItemHutang->return = 0;
                }
                $updateItemHutang->jumlah = $updateHutang->jumlah;
                $updateItemHutang->save();

                $angsuranTerakhir = PembayaranAngsuran::where('kode', $hutang->kode)
                ->orderBy('angsuran_ke', 'desc')
                ->first();

                $angsuranKeBaru = ($angsuranTerakhir) ? $angsuranTerakhir->angsuran_ke + 1 : 1;

                $angsuran = new PembayaranAngsuran;
                $angsuran->kode = $hutang->kode;
                $angsuran->tanggal = $hutang->tanggal;
                $angsuran->operator = $user->name;
                $angsuran->angsuran_ke = $angsuranKeBaru;
                $angsuran->kas = "{$dataKas->nama} ($dataKas->kode)";
                $angsuran->kode_faktur = $updatePembelian->kode;
                $angsuran->kode_pelanggan = NULL;
                $angsuran->kode_faktur = NULL;
                $angsuran->bayar_angsuran = $bayar;
                if($bayar >= intval($angsuranTerakhir->jumlah)) {
                    $angsuran->jumlah = 0;
                } else { 
                    $angsuran->jumlah = intval($angsuranTerakhir->jumlah) - $bayar;
                }
                $angsuran->keterangan = $request->keterangan;
                $angsuran->save();

                $notifEvent =  "Hutang dengan kode {$hutang->kode}, dibayar {$bayar} 💸";

                $updateKas = Kas::findOrFail($dataKas->id);
                $updateKas->saldo = intval($dataKas->saldo) - intval($bayar);
                $updateKas->save();

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
                return response()->json([
                    'success' => true,
                    'message' => "Hutang dengan kode {$hutang->kode}, dibayar {$bayar} 💸",
                    'data' => $hutang
                ], 200);
            } else {
                $dataPembelian = Pembelian::whereKode($hutang->kd_beli)->first();
                $updatePembelian = Pembelian::findOrFail($dataPembelian->id);
                $updatePembelian->bayar = intval($dataPembelian->bayar) + $bayar;
                // $updatePembelian->diterima = intval($dataPembelian->diterima) + $bayar;

                if($bayar >= $dataPembelian->hutang) {
                    $updatePembelian->lunas = "True";
                    $updatePembelian->visa = "LUNAS";
                    $updatePembelian->hutang = 0;
                    if($dataPembelian->po === "True") {
                        // $updatePembelian->angsuran = $updatePembelian->bayar;
                        $updatePembelian->receive = "True";
                        $updatePembelian->kekurangan_sdh_dibayar = "True";
                    }
                } else {
                    $updatePembelian->lunas = "False";
                    $updatePembelian->visa = "HUTANG";
                    $updatePembelian->hutang = intval($dataPembelian->hutang) - $bayar;
                }
                $updatePembelian->save();

                $updateHutang = Hutang::findOrFail($hutang->id);
                if($bayar >= $jmlHutang) {
                    $updateHutang->jumlah = $bayar - $jmlHutang;
                    $updateHutang->bayar = $bayar;
                } else {
                    $updateHutang->jumlah = $jmlHutang - $bayar;
                    $updateHutang->bayar = intval($hutang->bayar) + $bayar;
                }
                $updateHutang->ket = $request->ket ?? "";
                $updateHutang->save();

                $dataItemHutang = ItemHutang::whereKode($updateHutang->kode)->first();
                $updateItemHutang = ItemHutang::findOrFail($dataItemHutang->id);
                if($bayar >= $jmlHutang) {
                    $updateItemHutang->return = $bayar - $jmlHutang;
                } else {
                    $updateItemHutang->return = 0;
                }
                $updateItemHutang->jumlah = $updateHutang->jumlah;
                $updateItemHutang->save();
                
                $notifEvent =  "Hutang dengan kode {$hutang->kode}, dibayar {$bayar} 💸";

                $updateKas = Kas::findOrFail($dataKas->id);
                $updateKas->saldo = intval($dataKas->saldo) - intval($bayar);
                $updateKas->save();

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
                return response()->json([
                    'success' => true,
                    'message' => "Hutang dengan kode {$hutang->kode}, dibayar {$bayar} 💸",
                    'data' => $hutang
                ], 200);
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
            ->leftJoin('pembelian', 'hutang.kd_beli', '=', 'pembelian.kode')
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
            'hutang.kode', 'hutang.tanggal','hutang.supplier','hutang.jumlah as jml_hutang','hutang.bayar as byr_hutang','hutang.operator',
            'itemhutang.jumlah as hutang_jumlah',
            'itemhutang.jumlah_hutang as jumlah_hutang',
            'pembelian.kode as kode_transaksi',
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
            'pembelian.biayabongkar',
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
        ->leftJoin('itemhutang', 'hutang.kd_beli', '=', 'itemhutang.kode')
        ->leftJoin('pembelian', 'pembelian.kode', '=', 'hutang.kd_beli')
        ->leftJoin('itempembelian', 'itempembelian.kode', '=', 'pembelian.kode')
        ->leftJoin('supplier', 'itempembelian.supplier', '=', 'supplier.kode')
        ->leftJoin('kas', 'pembelian.kode_kas', '=', 'kas.kode')
        ->leftJoin('pembayaran_angsuran', 'hutang.kode', '=', 'pembayaran_angsuran.kode')
        ->where('hutang.kode', $kode);

        $hutang = $query->first();
        $angsurans = PembayaranAngsuran::whereKode($hutang->kode)->get();
        $angsuran_awal = PembayaranAngsuran::whereKode($hutang->kode)->first();

        $setting = "";
        // echo "<pre>";
        // var_dump($hutang);
        // var_dump($hutang->hutang);
        // var_dump($hutang->jml_hutang);
        // var_dump($hutang->angsuran_ke);
        // var_dump($hutang->bayar_angsuran); 
        // echo "</pre>";
        // die;

        switch ($type) {
            case "nota-kecil":
            return view('bayar-hutang.nota_kecil', compact('hutang', 'angsurans', 'kode', 'toko', 'nota_type', 'helpers'));
            break;
            case "nota-besar":
            $pdf = PDF::loadView('bayar-hutang.nota_besar', compact('hutang', 'angsurans', 'kode', 'toko', 'nota_type', 'helpers'));
            $pdf->setPaper(0, 0, 609, 440, 'portrait');
            return $pdf->stream('Bayar-Hutang-' . $hutang->kode . '.pdf');
            break;
        }
    }
}
