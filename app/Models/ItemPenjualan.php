<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemPenjualan extends Model
{
	use HasFactory;
	use SoftDeletes;

	protected $table = 'itempenjualan';

	public function barangs()
	{
		return $this->belongsTo("App\Models\Barang", 'kode', 'kode_barang');
	}

	public static function penjualanTerbaikSatuBulanKedepan()
	{
		$cachedResult = Cache::get('top_selling_item');

		if ($cachedResult) {
			return $cachedResult;
		}

		$tanggalMulai = now();
		$tanggalAkhir = now()->addMonth();

		$topSellingItemQuery = DB::table('itempenjualan')
		->select('kode_barang', DB::raw('SUM(qty) as total_qty'), DB::raw('SUM(subtotal) as total_penjualan'))
		->groupBy('kode_barang')
		->orderByDesc('total_penjualan')
		->limit(1);

		$topSellingItem = DB::table('barang')
		->joinSub(function ($query) use ($topSellingItemQuery) {
			$query->from(DB::raw("({$topSellingItemQuery->toSql()}) as top_selling_subquery"))
			->mergeBindings($topSellingItemQuery);
		}, 'top_selling', function ($join) {
			$join->on('barang.kode', '=', 'top_selling.kode_barang');
		})
		->select('barang.kode', 'barang.nama', 'barang.satuan', 'barang.satuanbeli', 'barang.toko', 'barang.supplier', 'penjualan.tanggal', 'total_qty', 'total_penjualan', 'supplier.kode as kode_supplier', 'supplier.nama as nama_supplier')
		->join('itempenjualan', 'barang.kode', '=', 'itempenjualan.kode_barang')
		->join('penjualan', 'itempenjualan.kode', '=', 'penjualan.kode')
		->leftJoin('supplier', 'barang.supplier', 'supplier.kode')
		->orderByDesc('total_penjualan')
		->latest('penjualan.tanggal')
		->first();

		// Cache::put('top_selling_item', $topSellingItem, now()->addHours(1));
		Cache::put('top_selling_item', $topSellingItem, now()->addMinutes(10));

		return $topSellingItem;
	}

	// public static function penjualanTerbaikSatuBulanKedepan()
	// {
	// 	$tanggalMulai = now();
	// 	$tanggalAkhir = now()->addMonth();

	// 	$topSellingItem = DB::table('itempenjualan')
	// 	->select('kode_barang', DB::raw('SUM(qty) as total_qty'), DB::raw('SUM(subtotal) as total_penjualan'))
	// 	// ->whereBetween('expired', [$tanggalMulai, $tanggalAkhir])
	// 	->groupBy('kode_barang')
	// 	->orderByDesc('total_penjualan')
	// 	->limit(1);

	// 	$result = DB::table('barang')
	// 	->joinSub($topSellingItem, 'top_selling', function ($join) {
	// 		$join->on('barang.kode', '=', 'top_selling.kode_barang');
	// 	})
	// 	->select('barang.kode', 'barang.nama', 'barang.satuan', 'barang.satuanbeli', 'barang.toko', 'barang.supplier', 'penjualan.tanggal', 'total_qty', 'total_penjualan')
	// 	->join('itempenjualan', 'barang.kode', '=', 'itempenjualan.kode_barang')
	// 	->join('penjualan', 'itempenjualan.kode', '=', 'penjualan.kode')
	// 	->orderByDesc('total_penjualan')
	// 	->latest('penjualan.tanggal')
	// 	->first();

	// 	return $result;
	// }

	public static function barangTerlaris()
	{
		$tanggalMulai = now();
		$tanggalAkhir = now()->addMonth();

		$barangTerlaris = DB::table('itempenjualan')
		->select('kode_barang',  DB::raw('SUM(qty) as total_qty'), DB::raw('SUM(subtotal) as total_penjualan'))
		// ->whereBetween('expired', [$tanggalMulai, $tanggalAkhir])
		->groupBy('kode_barang')
		->orderByDesc('total_penjualan')
		->limit(10)
		->get();

		$listBarangTerlaris = [];

		foreach ($barangTerlaris as $barang) {
			$kodeBarang = $barang->kode_barang;
			$totalQty = $barang->total_qty;
			$totalPenjualan = $barang->total_penjualan;

			$barangDetail = DB::table('barang')
			->select('barang.kode', 'barang.nama', 'barang.satuan', 'barang.satuanbeli', 'barang.toko', 'barang.supplier', 'supplier.nama as nama_supplier')
			->leftJoin('supplier', 'barang.supplier', '=', 'supplier.kode')
			->where('barang.kode', $kodeBarang)
			->first();

			$listBarangTerlaris[] = [
				'kode' => $barangDetail->kode,
				'nama' => $barangDetail->nama,
				'satuan' => $barangDetail->satuan,
				'satuanbeli' => $barangDetail->satuanbeli,
				'toko' => $barangDetail->toko,
				'supplier' => $barangDetail->nama_supplier,
				'total_qty' => $totalQty,
				'total_penjualan' => $totalPenjualan,
			];
		}

		return $listBarangTerlaris;
	}
}
