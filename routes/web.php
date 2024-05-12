<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\DetailProductController;
use App\Http\Controllers\Api\Dashboard\{
	DataPembelianLangsungController,
	DataPenjualanTokoController,
	DataPenjualanPartaiController,
    DataPenjualanPoController,
    DataHutangController,
    DataPiutangController,
    DataReturnPembelianController,
    DataReturnPenjualanController
};
use App\Http\Controllers\Web\{DataLaporanView};
// use BeyondCode\LaravelWebSockets\Facades\WebSocketsRouter;

// WebSocketsRouter::dashboard('/laravel-websockets');

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

Route::get('/config-clear', function() {
    Artisan::call('config:clear');

    return 'Config cache cleared!';
});

Route::get('/cache-clear', function() {
    Artisan::call('cache:clear');

    return 'Cache cleared!';
});

Route::get('/config-cache', function() {
    Artisan::call('config:cache');

    return 'Config cached!';
});

Route::get('/route-cache', function() {
    Artisan::call('route:cache');

    return 'Route cached!';
});
// Print transaksi
Route::get('/transaksi/beli/cetak-nota/{type}/{kode}/{id_perusahaan}', [DataPembelianLangsungController::class, 'cetak_nota']);
Route::get('/transaksi/jual/toko/cetak-nota/{type}/{kode}/{id_perusahaan}', [DataPenjualanTokoController::class, 'cetak_nota']);
Route::get('/transaksi/jual/partai/cetak-nota/{type}/{kode}/{id_perusahaan}', [DataPenjualanPartaiController::class, 'cetak_nota']);
Route::get('/transaksi/jual/po/cetak-nota/{type}/{kode}/{id_perusahaan}', [DataPenjualanPoController::class, 'cetak_nota']);
Route::get('/transaksi/bayar-hutang/cetak-nota/{type}/{kode}/{id_perusahaan}', [DataHutangController::class, 'cetak_nota']);
Route::get('/transaksi/terima-piutang/cetak-nota/{type}/{kode}/{id_perusahaan}', [DataPiutangController::class, 'cetak_nota']);
Route::get('/transaksi/return-pembelian/cetak-nota/{type}/{kode}/{id_perusahaan}', [DataReturnPembelianController::class, 'cetak_nota']);
Route::get('/transaksi/return-penjualan/cetak-nota/{type}/{kode}/{id_perusahaan}', [DataReturnPenjualanController::class, 'cetak_nota']);

// Print laporan
Route::get('/laporan/pembelian/laporan-pembelian-periode/{id_perusahaan}/{start_date}/{end_date}', [DataLaporanView::class, 'laporan_pembelian_periode']);
Route::get('/laporan/hutang/{id_perusahaan}/{start_date}/{end_date}', [DataLaporanView::class, 'laporan_hutang']);
Route::get('/laporan/penjualan/laporan-penjualan-periode/{id_perusahaan}/{start_date}/{end_date}', [DataLaporanView::class, 'laporan_penjualan_periode']);
Route::get('/laporan/kas/cash-flow/{id_perusahaan}/{start_date}/{end_date}', [DataLaporanView::class, 'laporan_cash_flow']);

Route::get('/test', function () {
    return view('test');
});
Route::get('/detail/{barcode}', [DetailProductController::class, 'index']);