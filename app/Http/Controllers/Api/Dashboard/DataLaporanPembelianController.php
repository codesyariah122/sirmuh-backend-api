<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Exports\CampaignDataExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Helpers\ContextData;
use App\Models\{Pembelian};
use App\Events\{EventNotification};
use App\Helpers\{UserHelpers, WebFeatureHelpers};
use App\Http\Resources\ResponseDataCollect;
use Image;
use Auth;
use PDF;

class DataLaporanPembelianController extends Controller
{

	public function laporan_pembelian_periode(Request $request)
	{
		try {
			$keywords = $request->keywords;
			$startDate = $request->start_date;
			$endDate = $request->end_date;

			// var_dump($startDate); var_dump($endDate); die;
			$downloadData = $request->download_data;

			$query = Pembelian::query()
			->select(
				'pembelian.id',
				'pembelian.tanggal', 'pembelian.kode', 'pembelian.supplier', 'pembelian.operator','pembelian.jumlah','pembelian.bayar','pembelian.diskon','pembelian.tax','pembelian.lunas','pembelian.visa',
				'supplier.nama as nama_supplier',
				'supplier.alamat as alamat_supplier'
			)
			->leftJoin('supplier', 'pembelian.supplier', '=', 'supplier.kode')
			->groupBy(
				'pembelian.id',
				'pembelian.tanggal',
				'pembelian.kode',
				'pembelian.supplier',
				'pembelian.operator',
				'pembelian.jumlah',
				'pembelian.bayar',
				'pembelian.diskon',
				'pembelian.tax',
				'pembelian.lunas',
				'pembelian.visa',
				'supplier.nama',
				'supplier.alamat'
			)
			->orderByDesc('pembelian.tanggal')
			->limit(10);

			if ($startDate || $endDate) {
				$query->whereBetween('pembelian.tanggal', [$startDate, $endDate]);
			}

			$pembelians = $query
			->orderByDesc('pembelian.id')
			->paginate(10);

			return new ResponseDataCollect($pembelians);

		} catch (\Throwable $th) {
			throw $th;
		}
	}

	public function laporan_pembelian_supplier(Request $request)
	{
		try {
			$keywords = $request->keywords;
			$startDate = $request->start_date;
			$endDate = $request->end_date;

			$query = Pembelian::query()
			->select(
				'pembelian.id',
				'pembelian.jumlah',
				'supplier.nama as nama_supplier',
				'supplier.kode as kode_supplier',
				'supplier.alamat as alamat_supplier',
			)
			->leftJoin('supplier', 'pembelian.supplier', '=', 'supplier.kode')
			->orderByDesc('pembelian.tanggal')
			->limit(10);

			if ($keywords) {
				$query->where(function ($query) use ($keywords) {
					$query->where('pembelian.kode', 'like', '%' . $keywords . '%')
					->orWhere('pembelian.supplier', 'like', '%' . $keywords . '%')
					->orWhere('pembelian.kode_kas', 'like', '%' . $keywords . '%')
					->orWhere('pembelian.operator', 'like', '%' . $keywords . '%');
				});
			}

			if ($startDate && $endDate) {
				$query->whereBetween('pembelian.tanggal', [$startDate, $endDate]);
			}

			$pembelians = $query
			->orderByDesc('pembelian.id')
			->paginate(10);

			return new ResponseDataCollect($pembelians);

		} catch (\Throwable $th) {
			throw $th;
		}
	}

	public function laporan_pembelian_barang(Request $request)
	{
		try {
			$keywords = $request->keywords;
			$startDate = $request->start_date;
			$endDate = $request->end_date;

			$query = Pembelian::query()
			->select(
				'pembelian.id',
				'pembelian.tanggal', 'pembelian.kode', 'pembelian.supplier', 'pembelian.operator','pembelian.jumlah','pembelian.bayar','pembelian.diskon','pembelian.tax',
				'itempembelian.qty','itempembelian.subtotal', 'itempembelian.harga_setelah_diskon',
				'supplier.nama as nama_supplier',
				'supplier.alamat as alamat_supplier',
				'barang.nama','barang.kode','barang.hpp'
			)
			->leftJoin('itempembelian', 'pembelian.kode', '=', 'itempembelian.kode')
			->leftJoin('supplier', 'pembelian.supplier', '=', 'supplier.kode')
			->leftJoin('barang', 'itempembelian.kode_barang', '=', 'barang.kode')
			->orderByDesc('pembelian.tanggal')
			->limit(10);

			if ($keywords) {
				$query->where(function ($query) use ($keywords) {
					$query->where('pembelian.kode', 'like', '%' . $keywords . '%')
					->orWhere('pembelian.supplier', 'like', '%' . $keywords . '%')
					->orWhere('pembelian.kode_kas', 'like', '%' . $keywords . '%')
					->orWhere('pembelian.operator', 'like', '%' . $keywords . '%');
				});
			}

			if ($startDate && $endDate) {
				$query->whereBetween('pembelian.tanggal', [$startDate, $endDate]);
			}

			$pembelians = $query
			->orderByDesc('pembelian.id')
			->paginate(10);

			return new ResponseDataCollect($pembelians);

		} catch (\Throwable $th) {
			throw $th;
		}
	}

}
