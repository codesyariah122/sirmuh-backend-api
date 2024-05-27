<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\{WebFeatureHelpers};
use App\Http\Resources\ResponseDataCollect;
use App\Models\{Barang, User, Toko, Pembelian};

class PublicFeatureController extends Controller
{

    private $feature_helpers;

    public function __construct()
    {
        $this->feature_helpers = new WebFeatureHelpers;
    }

    public function detail_data_view(Request $request)
    {
        try {
            $type = $request->query('type');
            $query = $request->query('query');
            $helpers = $this->feature_helpers;

            switch($type) {
                case "barang":
                $detailData = Barang::query()
                ->whereNull('barang.deleted_at')
                ->select('barang.id', 'barang.kode as kode_barang', 'barang.nama as nama_barang', 'barang.photo', 'barang.kategori_barang', 'barang.satuanbeli', 'barang.satuan', 'barang.isi', 'barang.toko',  'barang.hpp', 'barang.harga_toko', 'barang.diskon', 'barang.jenis', 'barang.supplier', 'barang.kode_barcode', 'barang.tgl_terakhir', 'barang.harga_terakhir', 'barang.ket', 'supplier.kode as kode_supplier', 'supplier.nama as nama_supplier')
                ->leftJoin('supplier', 'barang.supplier', 'supplier.kode')
                ->where('barang.kode_barcode', $query)
                ->with("kategoris")
                // ->with('suppliers')
                ->first();
                break;

                default:
                $detailData = [];
            }

            $barcodeFileName = $helpers->generateBarcode($detailData->kode_barcode);
            $detailData->kode_barcode = Storage::url("barcodes/{$detailData->kode_barcode}_barcode.png");

            return view('detail', compact('helpers'), ['detail' => $detailData, 'type' => $type, 'nama' => "Detail Barang {$detailData->nama}"]);

        } catch (\Throwable $th) {
            return response()->view('errors.error-page', ['message' => "Error parameters !!"], 400);
            // throw $th;
        }
    }

    public function detail_data(Request $request)
    {
        try {
        	$type = $request->query('type');
        	$query = $request->query('query');

            switch($type) {
            	case "barang":
            	$detailData = Barang::whereNull('deleted_at')
                ->select('id', 'kode', 'nama', 'photo', 'kategori', 'kategori_barang', 'satuanbeli', 'satuan', 'isi', 'toko',  'hpp', 'harga_toko', 'diskon', 'jenis', 'supplier',  'kode_barcode', 'tgl_terakhir', 'harga_terakhir', 'ket')
                ->whereKodeBarcode($query)
                ->with("kategoris")
                ->with('suppliers')
                ->get();
                break;

                default:
                $detailData = [];
            }

            foreach ($detailData  as $item) {
                $kodeBarcode = $item->kode_barcode;
                $this->feature_helpers->generateQrCode($kodeBarcode);
            }

            return new ResponseDataCollect($detailData);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function data_toko()
    {
        try {
            $tokos = Toko::whereNull('deleted_at')
            ->with('users')
            ->take(1)
            ->get();

            $tokos->transform(function ($toko) {
                $toko->koordinat = DB::select(DB::raw("SELECT ST_AsText(koordinat) as text FROM tokos WHERE id = :id"), ['id' => $toko->id])[0]->text;
                return $toko;
            });

            return response()->json([
                'success' => true,
                'message' => 'List data toko 🏦',
                'data' => $tokos
            ]);
        }catch (\Throwable $th) {
            \Log::error($th);
            return response()->json(['error' => true, 'message' => 'Terjadi kesalahan saat memproses data.'.$th->getMessage()], 500);
        }
    }
}
