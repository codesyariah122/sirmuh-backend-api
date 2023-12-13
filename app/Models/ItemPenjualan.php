<?php

namespace App\Models;

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
		$tanggalMulai = now();
		$tanggalAkhir = now()->addMonth();

		$topSellingItem = DB::table('itempenjualan')
		->select('kode_barang', DB::raw('SUM(qty) as total_qty'), DB::raw('SUM(subtotal) as total_penjualan'))
		->whereBetween('expired', [$tanggalMulai, $tanggalAkhir])
		->groupBy('kode_barang')
		->orderByDesc('total_penjualan')
		->limit(1);

		$result = DB::table('barang')
		->joinSub($topSellingItem, 'top_selling', function ($join) {
			$join->on('barang.kode', '=', 'top_selling.kode_barang');
		})
		->select('barang.kode', 'barang.nama', 'barang.satuan', 'barang.satuanbeli', 'barang.toko', 'barang.supplier', 'penjualan.tanggal', 'total_qty', 'total_penjualan')
		->join('itempenjualan', 'barang.kode', '=', 'itempenjualan.kode_barang')
		->join('penjualan', 'itempenjualan.kode', '=', 'penjualan.kode')
		// ->whereBetween('itempenjualan.expired', [$tanggalMulai, $tanggalAkhir])
		->orderByDesc('total_penjualan')
		// ->distinct()
		->latest('penjualan.tanggal')
		->first();

		return $result;
	}
}
