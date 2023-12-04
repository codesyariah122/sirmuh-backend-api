<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\{WebFeatureHelpers};
use App\Http\Resources\BarangCollection;
use App\Models\{Barang};

class PublicFeatureController extends Controller
{
    public function detail_data(Request $request)
    {
        try {
        	$type = $request->query('type');
        	$query = $request->query('query');

            switch($type) {
            	case "barang":
            	$detailData = Barang::select('kode', 'nama', 'kategori', 'satuanbeli', 'satuan', 'isi', 'toko',  'hpp', 'harga_toko', 'diskon', 'jenis', 'supplier', 'kode_barcode', 'tgl_terakhir', 'harga_terakhir')
            		->whereKodeBarcode($query)
            		->get();
            	break;

            	default:
            	$detailData = [];
            }

            return new BarangCollection($detailData);
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
