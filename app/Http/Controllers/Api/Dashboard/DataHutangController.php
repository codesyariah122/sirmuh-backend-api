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
use App\Models\{Hutang};
use App\Http\Resources\{ResponseDataCollect, RequestDataCollect};
use App\Helpers\{UserHelpers, WebFeatureHelpers};
use Auth;


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
            $query =  DB::table('hutang')
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
            $query =  DB::table('hutang')
            ->select('hutang.*', 'pembelian.jt as jatuh_tempo','pembelian.kode_kas','pembelian.jumlah as jumlah_pembelian', 'pembelian.diterima','pembelian.bayar', 'pembelian.visa','pembelian.lunas', 'supplier.id as id_supplier', 'supplier.kode as kode_supplier', 'supplier.nama as nama_supplier', 'itempembelian.nama_barang', 'itempembelian.kode_barang', 'itempembelian.qty as qty_pembelian', 'itempembelian.satuan as satuan_pembelian_barang', 'itempembelian.harga_beli as harga_beli', 'barang.kategori', 'barang.kode as kode_barang', 'barang.kode_barcode as kode_barcode',  'kas.id as kas_id', 'kas.kode as kas_kode', 'kas.nama as kas_nama')
            ->leftJoin('pembelian', 'hutang.kode', '=', 'pembelian.kode')
            ->leftJoin('supplier', 'hutang.supplier', '=', 'supplier.kode')
            ->leftJoin('itempembelian', 'itempembelian.kode', '=', 'pembelian.kode')
            ->leftJoin('barang', 'barang.kode', '=', 'itempembelian.kode_barang')
            ->leftJoin('kas', 'pembelian.kode_kas', '=', 'kas.kode');

            $hutang = $query->where('hutang.id', $id)->first();

            $bayar = intval($request->bayar);
            $kasId = $request->kas_id;

            var_dump($hutang->jumlah); die;

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
}
