<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\{
    LoginController
};
use App\Http\Controllers\Api\Dashboard\{
    DataUserDataController,
    DataBarangController,
    DataPelangganController,
    DataKategoriBarangController,
    DataBiayaController,
    DataCanvasController,
    DataItemCanvasController,
    DataExpiredBarangController,
    DataItemPenjualanController,
    DataReturnPenjualanController,
    DataReturnPembelianController,
    DataPemasukanController,
    DataItemHutangController,
    DataHutangController,
    DataPiutangController,
    DataMenuManagementController,
    DataSubMenuManagementController,
    DataChildSubMenuManagementController,
    DataWebFiturController,
    DataSupplierController,
    DataLaporanUtangPiutangPelangganController,
    DataLaporanCashFlowController,
    DataPerusahaanController,
    DataLabaRugiController,
    DataKaryawanController,
    DataKasController,
    DataRoleUserManagementController,
    DataPembelianLangsungController,
    DataPenjualanTokoController,
    DataPenjualanPoController,
    DataPenjualanPartaiController,
    DataPengeluaranController,
    DataMutasiKasController,
    DataItemPembelianController,
    DataKoreksiStokController,
    DataPemakaianBarangController,
    DataItemPemakaianOriginBarangController,
    DataItemPemakaianDestBarangController,
    DataPurchaseOrderController,
    DataLaporanPembelianController,
    DataLaporanPenjualanController,
    RajaOngkirController,
    DataJenisPemasukanController,
    DataJenisKeperluanController,
    DataHistoryController
};

class DashboardServiceProvider extends ServiceProvider
{
    public function register()
    {
        $controllers = [
            LoginController::class,
            DataUserDataController::class,
            DataBarangController::class,
            DataPelangganController::class,
            DataKategoriBarangController::class,
            DataBankController::class,
            DataBiayaController::class,
            DataCanvasController::class,
            DataItemCanvasController::class,
            DataExpiredBarangController::class,
            DataItemPenjualanController::class,
            DataReturnPenjualanController::class,
            DataReturnPembelianController::class,
            DataPemasukanController::class,
            DataItemHutangController::class,
            DataHutangController::class,
            DataPiutangController::class,
            DataMenuManagementController::class,
            DataSubMenuManagementController::class,
            DataChildSubMenuManagementController::class,
            DataWebFiturController::class,
            DataSupplierController::class,
            DataLaporanUtangPiutangPelangganController::class,
            DataLaporanCashFlowController::class,
            DataPerusahaanController::class,
            DataLabaRugiController::class,
            DataKaryawanController::class,
            DataKasController::class,
            DataRoleUserManagementController::class,
            DataPembelianLangsungController::class,
            DataPenjualanTokoController::class,
            DataPenjualanPoController::class,
            DataPenjualanPartaiController::class,
            DataPengeluaranController::class,
            DataMutasiKasController::class,
            DataItemPembelianController::class,
            DataKoreksiStokController::class,
            DataPemakaianBarangController::class,
            DataItemPemakaianOriginBarangController::class,
            DataItemPemakaianDestBarangController::class,
            DataPurchaseOrderController::class,
            DataLaporanPembelianController::class,
            DataLaporanPenjualanController::class,
            RajaOngkirController::class,
            DataJenisPemasukanController::class,
            DataJenisKeperluanController::class,
            DataHistoryController::class
        ];

        foreach ($controllers as $controller) {
            $this->app->singleton($controller);
        }
    }

    public function boot()
    {
        $this->registerApiRoutes();
    }


    protected function registerApiRoutes()
    {
        Route::middleware(['auth:api', 'cors', 'json.response', 'session.expired'])
        ->prefix('v1')
        ->namespace('App\Http\Controllers\Api\Dashboard')
        ->group(function () {
            Route::get('/ping-test', [DataWebFiturController::class, 'checkInternetConnection']);
            Route::post('/logout', [LoginController::class, 'logout']);

            // User data management
            Route::resource('/user-data', DataUserDataController::class);

            // Data Barang Management
            Route::resource('/data-barang', DataBarangController::class);
            Route::get('/detail-barang-by-kode', [DataBarangController::class, 'detail_barang_by_kode']);

            
        });
    }
}
