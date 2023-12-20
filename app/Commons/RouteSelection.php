<?php
namespace App\Commons;

use App\Http\Controllers\Api\{
	LoginController
};

use App\Http\Controllers\Api\Dashboard\{
	DataUserDataController,
	DataBarangController,
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
	DataMenuManagementController,
	DataSubMenuManagementController,
	DataChildSubMenuManagementController,
	DataWebFiturController,
	DataSupplierController,
	DataLaporanUtangPiutangPelangganController,
	DataPerusahaanController,
	DataLabaRugiController
};

class RouteSelection {

	private static $listRoutes = [
		[
			'endPoint' => '/logout',
			'method' => 'post',
			'controllers' => [LoginController::class, 'logout']
		],
		// User data management
		[
			'endPoint' => '/user-data',
			'method' => 'get',
			'controllers' => [DataUserDataController::class, 'index']
		],
		[
			'endPoint' => '/user-data',
			'method' => 'resource',
			'controllers' => DataUserDataController::class
		],

		// Data Barang Management
		[
			'endPoint' => '/data-barang',
			'method' => 'get',
			'controllers' => [DataBarangController::class, 'index']
		],
		
		[
			'endPoint' => '/data-barang',
			'method' => 'resource',
			'controllers' => DataBarangController::class
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
			'endPoint' => '/data-kategori',
			'method' => 'resource',
			'controllers' => DataKategoriBarangController::class
		],
		//End Data Kategori Barang 

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

		// Item Penjualan
		[
			'endPoint' => '/data-item-penjualan',
			'method' => 'get',
			'controllers' => [DataItemPenjualanController::class, 'index']
		],
		[
			'endPoint' => '/penjualan-terbaik',
			'method' => 'get',
			'controllers' => [DataItemPenjualanController::class, 'penjualanTerbaik']
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
			'method' => 'get',
			'controllers' => [DataHutangController::class, 'index']
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
		[
			'endPoint' => '/data-child-sub-menu',
			'method' => 'get',
			'controllers' => [DataChildSubMenuManagementController::class, 'index']
		],
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

		// Data Perusahaan
		[
			'endPoint' => '/data-perusahaan',
			'method' => 'resource',
			'controllers' => DataPerusahaanController::class
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
			'endPoint' => '/laporan-utangpiutang-pelanggan',
			'method' => 'get',
			'controllers' => [DataLaporanUtangPiutangPelangganController::class, 'laporanHutangPiutang']
		],
		[
			'endPoint' => '/to-the-best/{type}',
			'method' => 'get',
			'controllers' => [DataWebFiturController::class, 'toTheBest']
		],
	];

	public static function getListRoutes()
	{
		// $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
		// $request_method = isset($_SERVER['REQUEST_METHOD']) ? strtolower($_SERVER['REQUEST_METHOD']) : null;
		// $parsed_url = parse_url($request_uri);

		// $path_segments = explode('/', trim($parsed_url['path'], '/'));

		// if (isset($path_segments)) {
		// 	$endPoint = end($path_segments);

		// 	$controllerClassName = '';

		// 	if ($endPoint === 'data-total' || $endPoint === 'data-total-trash') {
		// 		$controllerClassName = 'App\Http\Controllers\Api\Dashboard\\DataWebFiturController';
		// 	} else {
		// 		$convertedEndPoint = str_replace('-', '', ucwords($endPoint, '-'));
		// 		$namespace = 'App\Http\Controllers\Api\Dashboard\\';
		// 		$controllerClassName = $namespace . 'Data' . ucfirst($convertedEndPoint) . 'Controller';
		// 		$methods = $request_method === 'get' ? 'get' : 'resource';
		// 		$controllers = $request_method === 'get' ? [$controllerClassName, 'index'] : $controllerClassName;				
		// 		self::$listRoutes[] = [
		// 			'endPoint' => "/{$endPoint}",
		// 			'method' => $methods,
		// 			'controllers' => $controllers
		// 		];
		// 	}
		// }

		return self::$listRoutes;
	}



}
