<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Barang extends Model
{
	use HasFactory;
	use SoftDeletes;

	protected $table = 'barang';

	public function kategoris()
	{
		return $this->belongsToMany("App\Models\Kategori");
	}

	public function suppliers()
	{
		return $this->belongsToMany("App\Models\Supplier");
	}

	public function itempenjualans()
	{
		return $this->belongsTo("App\Models\ItemPenjualan", 'kode_barang', 'kode');
	}
 
   public function labarugi()
   {
     return $this->belongsTo("App\Models\LabaRugi", 'kode_barang', 'nama_barang');
   }
}
