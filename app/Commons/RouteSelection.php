<?php
namespace App\Commons;

use App\Http\Controllers\Api\{
	LoginController, 
	UserDataController
};

use App\Http\Controllers\Api\Dashboard\{ 
	DataBarangController,
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
	DataWebFiturController
};

class RouteSelection {
	private static $listRoutes = [
		[
			'endPoint' => '/logout',
			'method' => 'post',
			'controllers' => [LoginController::class, 'logout']
		],
		[
			'endPoint' => '/user-data',
			'method' => 'get',
			'controllers' => [UserDataController::class, 'index']
		],
		[
			'endPoint' => '/data-barang',
			'method' => 'get',
			'controllers' => [DataBarangController::class, 'index']
		],
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
		[
			'endPoint' => '/data-item-penjualan',
			'method' => 'get',
			'controllers' => [DataItemPenjualanController::class, 'index']
		],
		[
			'endPoint' => '/data-return-penjualan',
			'method' => 'get',
			'controllers' => [DataReturnPenjualanController::class, 'index']
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

		// Fitur Data
		[
			'endPoint' => '/data-total-trash',
			'method' => 'get',
			'controllers' => [DataWebFiturController::class,  'totalTrash']
		],
		[
			'endPoint' => '/data-total',
			'method' => 'get',
			'controllers' => [DataWebFiturController::class, 'totalData']
		],
	];

	public static function getListRoutes()
	{
		return self::$listRoutes;
	}
}
