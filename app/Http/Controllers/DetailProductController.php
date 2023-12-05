<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{Barang};

class DetailProductController extends Controller
{
    public function index($barcode)
    {
    	try {
    		$detailBarang = Barang::where('kode_barcode', $barcode)
    		->with('suppliers')
    		->with('kategoris')
    		->first();
    		return view('detail')->with($detailBarang);
    	} catch (\Throwable $th) {
    		throw $th;
    	}
    }
}
