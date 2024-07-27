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
use App\Http\Controllers\Api\{PublicFeatureController};
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

// Hutang & Piutang
Route::get('/laporan/bayar-hutang-by-date/{id_perusahaan}/{start_date}/{end_date}', [DataLaporanView::class, 'laporan_bayar_hutang_by_date']);
Route::get('/laporan/bayar-hutang-by-supplier/{id_perusahaan}/{supplier}', [DataLaporanView::class, 'laporan_bayar_hutang_by_supplier']);
Route::get('/laporan/piutang-by-date/{id_perusahaan}/{start_date}/{end_date}', [DataLaporanView::class, 'laporan_piutang_by_date']);
Route::get('/laporan/piutang-by-pelanggan/{id_perusahaan}/{pelanggan}', [DataLaporanView::class, 'laporan_piutang_by_pelanggan']);

// Laporan kas
Route::get('/laporan/kas/by-kode/{id_perusahaan}/{kode_kas}', [DataLaporanView::class, 'laporan_kas_by_kode']);
Route::get('/laporan/kas/{id_perusahaan}/{param}', [DataLaporanView::class, 'laporan_kas_all']);

Route::get('/laporan/kas/cash-flow/{id_perusahaan}/{start_date}/{end_date}', [DataLaporanView::class, 'laporan_cash_flow']);

// Laporan stok barang & Barang
Route::get('/laporan/barang/keywords/{id_perusahaan}/{keywords}', [DataLaporanView::class, 'laporan_barang_by_keywords']);
Route::get('/laporan/barang/{id_perusahaan}/{param}', [DataLaporanView::class, 'laporan_barang_all']);


Route::get('/test', function () {
    return view('test');
});
Route::get('/detail', [PublicFeatureController::class, 'detail_data_view']);