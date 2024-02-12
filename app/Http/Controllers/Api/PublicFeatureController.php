<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helpers\{WebFeatureHelpers};
use App\Http\Resources\ResponseDataCollect;
use App\Models\{Barang, User, Toko, Pembelian};

class PublicFeatureController extends Controller
{
    public function detail_data(Request $request)
    {
        try {
        	$type = $request->query('type');
        	$query = $request->query('query');

            switch($type) {
            	case "barang":
            	$detailData = Barang::whereNull('deleted_at')
                ->select('id', 'kode', 'nama', 'photo', 'kategori', 'satuanbeli', 'satuan', 'isi', 'toko',  'hpp', 'harga_toko', 'diskon', 'jenis', 'supplier', 'kode_barcode', 'tgl_terakhir', 'harga_terakhir', 'ket')
                ->whereKodeBarcode($query)
                ->with("kategoris")
                ->with('suppliers')
                ->get();
                break;

                default:
                $detailData = [];
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
