<?php

namespace App\Commons;

class CommonEnv {
    public static function getListRoutes()
    {
        $controllersNamespace = 'App\Http\Controllers\Api\\';
        $controllersDirectory = app_path('Http/Controllers/Api/');

        $routes = [];

        $path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : NULL;
        $parsedUrl = parse_url($path);
        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
        $segments = explode('/', $path);
        $segmentsPath = end($segments);
        
        foreach (glob($controllersDirectory . '*/*.php') as $filename) {
            $relativePath = str_replace([$controllersNamespace, '.php'], '', $filename);

            $controllerName = str_replace('/', '\\', $relativePath);
            var_dump($controllerName);
        }

        $routes = [
            'endPoint' => $segmentsPath,
            'method' => strtolower($method)
        ];
        
        var_dump($routes); die;

        return $routes;
    }
}



// backup dulu
<?php

namespace App\Commons;

class RouteSelection
{
    private static $listRoutes = [];

    public static function getListRoutes()
    {
        if (empty(self::$listRoutes)) {
            $controllers = self::getControllerClasses();

            foreach ($controllers as $controller) {
                $reflectionClass = new \ReflectionClass($controller);

                foreach ($reflectionClass->getMethods() as $method) {
                    if ($method->class == $controller) {
                        // Ambil metadata dari method
                        $methodMetadata = self::getMethodMetadata($method);

                        if (!empty($methodMetadata)) {
                            self::$listRoutes[] = $methodMetadata;
                        }
                    }
                }
            }
        }

        return self::$listRoutes;
    }

    private static function getControllerClasses()
    {
        // Sesuaikan namespace sesuai dengan struktur direktori controller Anda
        $controllerNamespace = 'App\Http\Controllers\Api\Dashboard\\';

        $controllers = [];
        foreach (glob(app_path('Http/Controllers/Api/Dashboard/*.php')) as $filename) {
            $controllerClass = $controllerNamespace . pathinfo($filename, PATHINFO_FILENAME);
            $controllers[] = $controllerClass;
        }

        return $controllers;
    }

    private static function getMethodMetadata(\ReflectionMethod $method)
    {
        $methodName = $method->getName();

        if ($method->isPublic() && $method->isUserDefined() && $methodName !== '__construct') {
            $endPoint = self::convertCamelCaseToKebabCase($methodName);
            $controllers = [$method->class, $methodName];

            $methodHttp = 'get';
            if (str_starts_with($methodName, 'store') || str_starts_with($methodName, 'update') || str_starts_with($methodName, 'destroy')) {
                $methodHttp = 'resource';
            }

            if ($methodHttp === 'resource') {
            // Perbarui ini untuk menangani metode resource dengan benar
                return [
                    'endPoint' => '/' . $endPoint,
                    'method' => $methodHttp,
                    'controllers' => $controllers,
                ];
            } else {
            // Perbarui ini agar $controllers berisi string, bukan array
                return [
                    'endPoint' => '/' . $endPoint,
                    'method' => $methodHttp,
                    'controllers' => $controllers[0],
                ];
            }
        }

        return [];
    }

    private static function convertCamelCaseToKebabCase($input)
    {
        return strtolower(preg_replace('/([a-zA-Z])(?=[A-Z])/', '$1-', $input));
    }
}



<?php

namespace App\Commons;

class CommonEnv {
    public static function getListRoutes()
    {
        $controllersNamespace = 'pp\Http\Controllers\Api\\';
        $controllersDirectory = app_path('Http/Controllers/Api/');

        $routes = [];

        $path = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/';
        $method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : NULL;
        $parsedUrl = parse_url($path);
        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';
        $segments = explode('/', $path);
        $segmentsPath = end($segments);

        $stringSegments = str_replace('-', ' ', $segmentsPath);

        $resultStringSegments = ucwords($stringSegments);

        $bindSegment = str_replace(' ', '', $resultStringSegments."Controller");
        $bindSegmentControllerPath = "Api\\Dashboard\\".$bindSegment;

        var_dump($bindSegmentControllerPath);

        foreach (glob($controllersDirectory . '*/*.php') as $filename) {
            $relativePath = str_replace([$controllersNamespace, '.php'], '', $filename);

            $controllerName = str_replace('/', '\\', $relativePath);

            $segments = explode('\\', $controllerName);

            $desiredString = implode('\\', array_slice($segments, -3));
            var_dump($desiredString);
        }

        $routes = [
            'endPoint' => $segmentsPath,
            'method' => strtolower($method)
        ];
        
        var_dump($routes); die;

        return $routes;
    }
}



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
    DataBankController,
    DataBiayaController,
    DataCanvasController,
    DataItemCanvasController,
    DataExpiredBarangController,
    DataItemPenjualanController,
    DataReturnPenjualanController,
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
    DataPurchaseOrderController,
    DataLaporanPembelianController
};

class RouteSelection {

    private static $listRoutes = [
        [
            'endPoint' => '/ping-test',
            'method' => 'get',
            'controllers' => [DataWebFiturController::class, 'checkInternetConnection']
        ],

        [
            'endPoint' => '/logout',
            'method' => 'post',
            'controllers' => [LoginController::class, 'logout']
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

        // Data Cabang
        [
            'endPoint' => '/data-cabang',
            'method' => 'resource',
            'controllers' => DataPelangganController::class
        ],

        // Data Bank
        [
            'endPoint' => '/data-bank',
            'method' => 'get',
            'controllers' => [DataBankController::class, 'index']
        ],

        [
            'endPoint' => '/data-biaya',
            'method' => 'get',
            'controllers' => [DataBiayaController::class, 'index']
        ],
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
            'endPoint' => '/lists-of-po',
            'method' => 'get',
            'controllers' => [DataPurchaseOrderController::class, 'list_item_po']
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
            'endPoint' => '/data-penjualan-po',
            'method' => 'resource',
            'controllers' => DataPenjualanPoController::class
        ],
        [
            'endPoint' => '/data-penjualan-partai',
            'method' => 'resource',
            'controllers' => DataPenjualanPartaiController::class
        ],
        [
            'endPoint' => '/data-return-penjualan',
            'method' => 'get',
            'controllers' => [DataReturnPenjualanController::class, 'index']
        ],
        [
            'endPoint' => '/laba-rugi/{jml_month}',
            'method' => 'get',
            'controllers' => [DataLabaRugiController::class, 'labaRugiLastMonth'],
        ],
        [
            'endPoint' => '/data-laba-rugi',
            'method' => 'resource',
            'controllers' => DataLabaRugiController::class,
        ],
        [
            'endPoint' => '/data-pemasukan',
            'method' => 'get',
            'controllers' => [DataPemasukanController::class, 'index']
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
            'controllers' => [DataPiutangController::class, 'check_bayar_hutang']
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
            'endPoint' => '/data-barang-item-pembelian/{id}',
            'method' => 'get',
            'controllers' => [DataBarangController::class, 'data_barang_with_item_pembelian']
        ],
        // Item Penjualan
        [
            'endPoint' => '/update-item-penjualan',
            'method' => 'post',
            'controllers' => [DataWebFiturController::class, 'update_item_penjualan']
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
        ]
    ];

    public static function getListRoutes()
    {
        // $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
        // $request_method = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : null;
        // $parsed_url = parse_url($request_uri);

        // $path_segments = explode('/', trim($parsed_url['path'], '/'));

        // if (isset($path_segments)) {
        //  $endPoint = end($path_segments);

        //  $controllerClassName = '';

        //  if ($endPoint === 'data-total' || $endPoint === 'data-total-trash') {
        //      $controllerClassName = 'App\Http\Controllers\Api\Dashboard\\DataWebFiturController';
        //  } else {
        //      $convertedEndPoint = str_replace('-', '', ucwords($endPoint, '-'));
        //      $namespace = 'App\Http\Controllers\Api\Dashboard\\';
        //      $controllerClassName = $namespace . 'Data' . ucfirst($convertedEndPoint) . 'Controller';
        //      $methods = $request_method === 'get' ? 'get' : 'resource';
        //      $controllers = $request_method === 'get' ? [$controllerClassName, 'index'] : $controllerClassName;              
        //      self::$listRoutes[] = [
        //          'endPoint' => "/{$endPoint}",
        //          'method' => $methods,
        //          'controllers' => $controllers
        //      ];
        //  }
        // }

        return self::$listRoutes;
    }



}
