<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DetailProductController;
use App\Http\Controllers\Api\Dashboard\{
	DataPembelianLangsungController,
	DataPenjualanTokoController
};

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/transaksi/beli/cetak-nota/{type}/{kode}/{id_perusahaan}', [DataPembelianLangsungController::class, 'cetak_nota']);
Route::get('/transaksi/jual/cetak-nota/{type}/{kode}/{id_perusahaan}', [DataPenjualanTokoController::class, 'cetak_nota']);

Route::get('/detail/{barcode}', [DetailProductController::class, 'index']);