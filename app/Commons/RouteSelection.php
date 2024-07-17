<?php

namespace App\Commons;

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

class RouteSelection {

    private static $listRoutes = [
        [
            'endPoint' => '/logout',
            'method' => 'post',
            'controllers' => [LoginController::class, 'logout']
        ],
        [
            'endPoint' => '/ping-test',
            'method' => 'get',
            'controllers' => [DataWebFiturController::class, 'checkInternetConnection']
        ],

        // User data management
        [
            'endPoint' => '/user-data',
            'method' => 'resource',
            'controllers' => DataUserDataController::class
        ],

        // Data Barang Management
        [
            'endPoint' => '/data-barang',
            'method' => 'resource',
            'controllers' => DataBarangController::class
        ],

        [
            'endPoint' => '/detail-barang-by-kode',
            'method' => 'get',
            'controllers' => [DataBarangController::class, 'detail_barang_by_kode']
        ],

        [
            'endPoint' => '/data-barang-by-suppliers/{id_supplier}',
            'method' => 'get',
            'controllers' => [DataBarangController::class, 'barang_by_suppliers']
        ],
        [
            'endPoint' => '/barang-by-warehouse',
            'method' => 'get',
            'controllers' => [DataBarangController::class, 'barang_by_warehouse']
        ],

        [
            'endPoint' => '/barang-list-pemakaian',
            'method' => 'get',
            'controllers' => [DataBarangController::class, 'barang_pemakaian_list']
        ],

        [
            'endPoint' => '/barang-cetak-pemakaian',
            'method' => 'get',
            'controllers' => [DataBarangController::class, 'barang_cetak_pemakaian']
        ],

        [
            'endPoint' => '/barang-all',
            'method' => 'get',
            'controllers' => [DataBarangController::class, 'barang_all']
        ],

        [
            'endPoint' => '/update-photo-barang/{kode}',
            'method' => 'post',
            'controllers' => [DataBarangController::class, 'update_photo_barang']
        ],

        [
            'endPoint' => '/data-lists-category-barang',
            'method' => 'get',
            'controllers' => [DataBarangController::class, 'category_lists']
        ],
        
        // End Data Barang Management
        // Data Kategori Barang
        [
            'endPoint' => '/data-kategori-supplier',
            'method' => 'get',
            'controllers' => [DataKategoriBarangController::class, 'kategori_supplier_lists']
        ],
        [
            'endPoint' => '/data-kategori-barang',
            'method' => 'resource',
            'controllers' => DataKategoriBarangController::class
        ],
        //End Data Kategori Barang 

        // Data Pelanggan
        [
            'endPoint' => '/data-pelanggan',
            'method' => 'resource',
            'controllers' => DataPelangganController::class
        ],
        [
            'endPoint' => '/list-pelanggan-normal',
            'method' => 'get',
            'controllers' => [DataPelangganController::class, 'list_normal']
        ],
        [
            'endPoint' => '/list-pelanggan-partai',
            'method' => 'get',
            'controllers' => [DataPelangganController::class, 'list_partai']
        ],

        // Data Cabang
        [
            'endPoint' => '/data-cabang',
            'method' => 'resource',
            'controllers' => DataPelangganController::class
        ],

        // Data Bank
        [
            'endPoint' => '/data-biaya',
            'method' => 'resource',
            'controllers' => DataBiayaController::class
        ],
        [
            'endPoint' => '/data-canvas',
            'method' => 'get',
            'controllers' => [DataCanvasController::class, 'index']
        ],
        [
            'endPoint' => '/data-item-canvas',
            'method' => 'get',
            'controllers' => [DataItemCanvasController::class, 'index']
        ],
        [
            'endPoint' => '/data-barang-expired',
            'method' => 'get',
            'controllers' => [DataExpiredBarangController::class, 'index']
        ],

        // Karyawan
        [
            'endPoint' => '/data-karyawan',
            'method' => 'resource',
            'controllers' => DataKaryawanController::class
        ],
        [
            'endPoint' => '/update-password-karyawan-user/{id}',
            'method' => 'put',
            'controllers' => [DataKaryawanController::class, 'update_password_user_karyawan']
        ],
        [
            'endPoint' => '/update-user-data-karyawan/{id}',
            'method' => 'put',
            'controllers' => [DataWebFiturController::class, 'update_user_profile_karyawan']
        ],
        // End Karyawan

        // User & role Management
        [
            'endPoint' => '/data-role-management',
            'method' => 'resource',
            'controllers' => DataRoleUserManagementController::class
        ],
        // End User & Role Management

        // Kas
        [
            'endPoint' => '/data-kas',
            'method' => 'resource',
            'controllers' => DataKasController::class
        ],
        // End Kas

        // Pembelian
        [
            'endPoint' => '/data-pembelian-langsung',
            'method' => 'resource',
            'controllers' => DataPembelianLangsungController::class
        ],
        [
            'endPoint' => '/cetak-pembelian-langsung/{type}/{kode}',
            'method' => 'get',
            'controllers' => [DataPembelianLangsungController::class, 'cetak_nota']
        ],

        // Purchase order
        [
            'endPoint' => '/data-purchase-order',
            'method' => 'resource',
            'controllers' => DataPurchaseOrderController::class
        ],
        [
            'endPoint' => '/multiple-input-po/{id}',
            'method' => 'put',
            'controllers' => [DataPurchaseOrderController::class, 'updateMultipleInput']
        ],
        [
            'endPoint' => '/lists-of-po',
            'method' => 'get',
            'controllers' => [DataPurchaseOrderController::class, 'list_item_po']
        ],
        [
            'endPoint' => '/tambah-dp/{kode}',
            'method' => 'put',
            'controllers' => [DataPurchaseOrderController::class, 'tambah_dp_pembelian']
        ],
        [
            'endPoint' => '/tambah-dp-awal/{kode}',
            'method' => 'put',
            'controllers' => [DataPurchaseOrderController::class, 'tambah_dp_awal']
        ],
        // End of pembelian

        // Item Pembelian
        [
            'endPoint' => '/data-item-pembelian',
            'method' => 'resource',
            'controllers' => DataItemPembelianController::class
        ],
        // End of itempembelian

        // Item purchase orders
        [
            'endPoint' => '/update-item-po/{id}',
            'method' => 'put',
            'controllers' => [DataItemPembelianController::class, 'update_po_item']
        ],
        // End of item purchase orders

        // Item Penjualan
        [
            'endPoint' => '/data-item-penjualan',
            'method' => 'resource',
            'controllers' => DataItemPenjualanController::class
        ],


        [
            'endPoint' => '/penjualan-terbaik',
            'method' => 'get',
            'controllers' => [DataItemPenjualanController::class, 'penjualanTerbaik']
        ],

        [
            'endPoint' => '/penjualan-daily',
            'method' => 'get',
            'controllers' => [DataWebFiturController::class, 'penjualanDaily']
        ],

        [
            'endPoint' => '/penjualan-weekly',
            'method' => 'get',
            'controllers' => [DataWebFiturController::class, 'penjualanWeekly']
        ],

        [
            'endPoint' => '/check-stok-barang/{id}',
            'method' => 'get',
            'controllers' => [DataWebFiturController::class, 'check_stok_barang']
        ],

        // Penjualan Toko
        [
            'endPoint' => '/data-penjualan-toko',
            'method' => 'resource',
            'controllers' => DataPenjualanTokoController::class
        ],
        [
            'endPoint' => '/status-kirim/{id}',
            'method' => 'put',
            'controllers' => [DataWebFiturController::class, 'update_status_kirim']
        ],
        [
            'endPoint' => '/data-penjualan-po',
            'method' => 'resource',
            'controllers' => DataPenjualanPoController::class
        ],
        [
            'endPoint' => '/data-penjualan-partai',
            'method' => 'resource',
            'controllers' => DataPenjualanPartaiController::class
        ],

        // Return
        [
            'endPoint' => '/data-return-penjualan',
            'method' => 'resource',
            'controllers' => DataReturnPenjualanController::class
        ],

        [
            'endPoint' => '/data-return-pembelian',
            'method' => 'resource',
            'controllers' => DataReturnPembelianController::class
        ],

        // End return

        [
            'endPoint' => '/laba-rugi/{jml_month}',
            'method' => 'get',
            'controllers' => [DataLabaRugiController::class, 'labaRugiLastMonth'],
        ],
        [
            'endPoint' => '/laba-rugi-daily/{day}',
            'method' => 'get',
            'controllers' => [DataLabaRugiController::class, 'labaRugiDaily'],
        ],
        [
            'endPoint' => '/laba-rugi-weekly',
            'method' => 'get',
            'controllers' => [DataLabaRugiController::class, 'labaRugiWeekly'],
        ],
        [
            'endPoint' => '/data-laba-rugi',
            'method' => 'resource',
            'controllers' => DataLabaRugiController::class,
        ],
        [
            'endPoint' => '/data-pemasukan',
            'method' => 'resource',
            'controllers' => DataPemasukanController::class, 'index'
        ],
        [
            'endPoint' => '/pemasukan-weekly',
            'method' => 'get',
            'controllers' => [DataPemasukanController::class, 'pemasukanWeekly']
        ],
        [
            'endPoint' => '/data-jenis-pemasukan',
            'method' => 'resource',
            'controllers' => DataJenisPemasukanController::class
        ],
        [
            'endPoint' => '/data-item-hutang',
            'method' => 'get',
            'controllers' => [DataItemHutangController::class, 'index']
        ],
        [
            'endPoint' => '/data-hutang',
            'method' => 'resource',
            'controllers' => DataHutangController::class
        ],

        [
            'endPoint' => '/check-bayar-hutang/{id}',
            'method' => 'get',
            'controllers' => [DataHutangController::class, 'check_bayar_hutang']
        ],

        [
            'endPoint' => '/data-piutang',
            'method' => 'resource',
            'controllers' => DataPiutangController::class
        ],

        [
            'endPoint' => '/check-bayar-piutang/{id}',
            'method' => 'get',
            'controllers' => [DataPiutangController::class, 'check_bayar_piutang']
        ],

        // Pemakaian barang
        [
            'endPoint' => '/item-pemakaian',
            'method' => 'resource',
            'controllers' => DataItemPemakaianOriginBarangController::class
        ],

        [
            'endPoint' => '/item-pemakaian-dest',
            'method' => 'resource',
            'controllers' => DataItemPemakaianDestBarangController::class
        ],

        [
            'endPoint' => '/item-pemakaian-dest-result/{id}',
            'method' => 'get',
            'controllers' => [DataItemPemakaianDestBarangController::class, 'item_pemakaian_result']
        ],

        [
            'endPoint' => '/item-pemakaian-result/{id}',
            'method' => 'get',
            'controllers' => [DataItemPemakaianOriginBarangController::class, 'item_pemakaian_result']
        ],

        [

            'endPoint' => '/item-pemakaian-result-before/{id}',
            'method' => 'get',
            'controllers' => [DataItemPemakaianOriginBarangController::class, 'item_pemakaian_result_before']
        ],

        [
            'endPoint' => '/pemakaian-barang',
            'method' => 'resource',
            'controllers' => DataPemakaianBarangController::class
        ],

        /**
         * Menu Management
         * */
        // Main Menu
        [
            'endPoint' => '/data-menu',
            'method' => 'get',
            'controllers' => [DataMenuManagementController::class, 'index']
        ],
        [
            'endPoint' => '/data-menu',
            'method' => 'resource',
            'controllers' => DataMenuManagementController::class
        ],
        // Sub Menu
        [
            'endPoint' => '/data-sub-menu',
            'method' => 'get',
            'controllers' => [DataSubMenuManagementController::class, 'index']
        ],
        [
            'endPoint' => '/data-sub-menu',
            'method' => 'resource',
            'controllers' => DataSubMenuManagementController::class
        ],
        // Child Sub Menu
        // [
        //  'endPoint' => '/data-child-sub-menu',
        //  'method' => 'get',
        //  'controllers' => [DataChildSubMenuManagementController::class, 'index']
        // ],
        [
            'endPoint' => '/data-child-sub-menu',
            'method' => 'resource',
            'controllers' => DataChildSubMenuManagementController::class
        ],

        // Data supplier
        [
            'endPoint' => '/data-supplier',
            'method' => 'resource',
            'controllers' => DataSupplierController::class
        ],
        [
            'endPoint' => '/supplier-for-lists',
            'method' => 'get',
            'controllers' => [DataSupplierController::class, 'supplier_for_lists']
        ],
        [
            'endPoint' => '/list-of-suppliers',
            'method' => 'get',
            'controllers' => [DataSupplierController::class, 'list_suppliers']
        ],

        // Data Perusahaan
        [
            'endPoint' => '/data-perusahaan',
            'method' => 'resource',
            'controllers' => DataPerusahaanController::class
        ],

        // Data Pengeluaran
        [
            'endPoint' => '/data-pengeluaran',
            'method' => 'resource',
            'controllers' => DataPengeluaranController::class
        ],

        // Mutasi kas
        [
            'endPoint' => '/mutasi-kas',
            'method' => 'resource',
            'controllers' => DataMutasiKasController::class
        ],

        // Fitur Data
        [
            'endPoint' => '/data-total-trash',
            'method' => 'get',
            'controllers' => [DataWebFiturController::class,  'totalTrash']
        ],
        [
            'endPoint' => '/data-trash',
            'method' => 'get',
            'controllers' => [DataWebFiturController::class,  'trash']
        ],
        [
            'endPoint' => '/data-trash/{id}',
            'method' => 'put',
            'controllers' => [DataWebFiturController::class,  'restoreTrash']
        ],
        [
            'endPoint' => '/data-trash/{id}',
            'method' => 'delete',
            'controllers' => [DataWebFiturController::class,  'deletePermanently']
        ],
        [
            'endPoint' => '/data-total',
            'method' => 'get',
            'controllers' => [DataWebFiturController::class, 'totalData']
        ],
        [
            'endPoint' => '/satuan-beli',
            'method' => 'get',
            'controllers' => [DataWebFiturController::class, 'satuanBeli']
        ],
        [
            'endPoint' => '/satuan-jual',
            'method' => 'get',
            'controllers' => [DataWebFiturController::class, 'satuanJual']
        ],
        
        [
            'endPoint' => '/to-the-best/{type}',
            'method' => 'get',
            'controllers' => [DataWebFiturController::class, 'toTheBest']
        ],

        [
            'endPoint' => '/barangterlaris-weekly',
            'method' => 'get',
            'controllers' => [DataWebFiturController::class, 'barangTerlarisWeekly']
        ],

        [
            'endPoint' => '/load-form/{diskon}/{ppn}/{total}',
            'method' => 'get',
            'controllers' => [DataWebFiturController::class, 'loadForm']
        ],
        [
            'endPoint' => '/load-form-penjualan/{diskon}/{total}/{bayar}',
            'method' => 'get',
            'controllers' => [DataWebFiturController::class, 'loadFormPenjualan']
        ],

        [
            'endPoint' => '/generate-reference-code/{type}',
            'method' => 'get',
            'controllers' => [DataWebFiturController::class, 'generateReference']
        ],
        [
            'endPoint' => '/update-stok-barang/{id}',
            'method' => 'put',
            'controllers' => [DataWebFiturController::class, 'update_stok_barang']
        ],
        [
            'endPoint' => '/update-stok-barang-all',
            'method' => 'post',
            'controllers' => [DataWebFiturController::class, 'update_stok_barang_all']
        ],

        [
            'endPoint' => '/edit-stok-data-barang',
            'method' => 'post',
            'controllers' => [DataWebFiturController::class, 'edit_stok_data_barang']
        ],

        [
            'endPoint' => '/edited-update-stok-barang',
            'method' => 'post',
            'controllers' => [DataWebFiturController::class, 'edited_update_stok_barang']
        ],
        [
            'endPoint' => '/updated-stok-barang-po',
            'method' => 'post',
            'controllers' => [DataWebFiturController::class, 'update_stok_barang_po']
        ],
        [
            'endPoint' => '/update-stok-barang-transaksi/{id}',
            'method' => 'put',
            'controllers' => [DataWebFiturController::class, 'stok_barang_update_inside_transaction']
        ],
        [
            'endPoint' => '/generate-terbilang',
            'method' => 'get',
            'controllers' => [DataWebFiturController::class, 'generate_terbilang']
        ],
        // ItemPembelian
        [
            'endPoint' => '/update-item-pembelian',
            'method' => 'post',
            'controllers' => [DataWebFiturController::class, 'update_item_pembelian']
        ],
        [
            'endPoint' => '/update-item-pembelian-po-qty/{id}',
            'method' => 'put',
            'controllers' => [DataItemPembelianController::class, 'update_item_pembelian_po_qty']
        ],
        [
            'endPoint' => '/update-item-pembelian-po-harga/{id}',
            'method' => 'put',
            'controllers' => [DataItemPembelianController::class, 'update_item_harga_po']
        ],
        [
            'endPoint' => '/draft-item-pembelian/{kode}',
            'method' => 'get',
            'controllers' => [DataWebFiturController::class, 'list_draft_itempembelian']
        ],
        [
            'endPoint' => '/delete-item-pembelian/{id}',
            'method' => 'delete',
            'controllers' => [DataWebFiturController::class, 'delete_item_pembelian']
        ],
        [
            'endPoint' => '/delete-item-pembelian-po/{id}',
            'method' => 'delete',
            'controllers' => [DataWebFiturController::class, 'delete_item_pembelian_po']
        ],
        [
            'endPoint' => '/data-barang-item-pembelian/{id}',
            'method' => 'get',
            'controllers' => [DataBarangController::class, 'data_barang_with_item_pembelian']
        ],

        [
            'endPoint' => '/lists-barang',
            'method' => 'get',
            'controllers' => [DataBarangController::class, 'list_barangs']
        ],
        // Item Penjualan
        [
            'endPoint' => '/update-item-penjualan',
            'method' => 'post',
            'controllers' => [DataWebFiturController::class, 'update_item_penjualan']
        ],
        [
            'endPoint' => '/update-item-penjualan-po-qty/{id}',
            'method' => 'put',
            'controllers' => [DataItemPenjualanController::class, 'update_item_penjualan_po_qty']
        ],
        [
            'endPoint' => '/update-item-penjualan-po-harga/{id}',
            'method' => 'put',
            'controllers' => [DataItemPenjualanController::class, 'update_item_harga_po']
        ],
        [
            'endPoint' => '/draft-item-penjualan/{kode}',
            'method' => 'get',
            'controllers' => [DataWebFiturController::class, 'list_draft_itempenjualan']
        ],

        [
            'endPoint' => '/delete-item-penjualan/{id}',
            'method' => 'delete',
            'controllers' => [DataWebFiturController::class, 'delete_item_penjualan']
        ],
        [
            'endPoint' => '/delete-item-penjualan-po/{id}',
            'method' => 'delete',
            'controllers' => [DataWebFiturController::class, 'delete_item_penjualan_po']
        ],

        // Koreksi stok
        [
            'endPoint' => '/koreksi-stok',
            'method' => 'resource',
            'controllers' => DataKoreksiStokController::class
        ],
        [
            'endPoint' => '/check-saldo/{id}',
            'method' => 'get',
            'controllers' => [DataWebFiturController::class, 'check_saldo']
        ],
        [
            'endPoint' => '/update-faktur-terakhir',
            'method' => 'post',
            'controllers' => [DataWebFiturController::class, 'update_faktur_terakhir']
        ],

        // Laporan
        [
            'endPoint' => '/laporan-utangpiutang-pelanggan',
            'method' => 'get',
            'controllers' => [DataLaporanUtangPiutangPelangganController::class, 'laporanHutangPiutang']
        ],
        [
            'endPoint' => '/laporan-pembelian-periode',
            'method' => 'get',
            'controllers' => [DataLaporanPembelianController::class, 'laporan_pembelian_periode']
        ],
        [
            'endPoint' => '/laporan-pembelian-supplier',
            'method' => 'get',
            'controllers' => [DataLaporanPembelianController::class, 'laporan_pembelian_supplier']
        ],
        [
            'endPoint' => '/laporan-pembelian-barang',
            'method' => 'get',
            'controllers' => [DataLaporanPembelianController::class, 'laporan_pembelian_barang']
        ],
        [
            'endPoint' => '/data-laporan-hutang',
            'method' => 'resource',
            'controllers' => DataHutangController::class
        ],

        [
            'endPoint' => '/laporan-penjualan',
            'method' => 'resource',
            'controllers' => DataLaporanPenjualanController::class
        ],

        [
            'endPoint' => '/laporan-cash-flow',
            'method' => 'get',
            'controllers' => [DataLaporanCashFlowController::class, 'all']
        ],

        // User update
        [
            'endPoint' => '/update-user-data/{id}',
            'method' => 'put',
            'controllers' => [DataWebFiturController::class, 'update_user_profile']
        ],
        [
            'endPoint' => '/change-password',
            'method' => 'put',
            'controllers' => [DataWebFiturController::class, 'change_password']
        ],
        [
            'endPoint' => '/update-profile-photo',
            'method' => 'post',
            'controllers' => [DataWebFiturController::class, 'upload_profile_picture']
        ],
        [
            'endPoint' => '/check-roles-access',
            'method' => 'get',
            'controllers' => [DataWebFiturController::class, 'check_roles_access']
        ],
        [
            'endPoint' => '/check-password-access',
            'method' => 'get',
            'controllers' => [DataWebFiturController::class, 'check_password_access']
        ],

        // Raja ongkir api
        [
            'endPoint' => '/province-lists',
            'method' => 'get',
            'controllers' => [RajaOngkirController::class, 'provinces']
        ],
        [
            'endPoint' => '/citys/{id}',
            'method' => 'get',
            'controllers' => [RajaOngkirController::class, 'citys']
        ],
        [
            'endPoint' => '/ekspedisi-lists',
            'method' => 'get',
            'controllers' => [RajaOngkirController::class, 'ekspeditions']
        ],
        [
            'endPoint' => '/check-ongkir',
            'method' => 'post',
            'controllers' => [RajaOngkirController::class, 'checkOngkir']
        ],

        // jenis kepeluan
        [
            'endPoint' => '/jenis-keperluan',
            'method' => 'resource',
            'controllers' => DataJenisKeperluanController::class
        ],

        // Histori Programm
        [
            'endPoint' => '/history-programm',
            'method' => 'resource',
            'controllers' => DataHistoryController::class
        ]
    ];

    public static function getListRoutes()
    {
        return self::$listRoutes;
    }

}