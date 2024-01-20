<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasFactory;
    use SoftDeletes;

	protected $table = 'supplier';

	public function barangs()
	{
		return $this->belongsToMany("App\Models\Barang");
	}

	public function hutangs()
	{
		return $this->hasMany("App\Models\Hutang");
	}
 
   public function pembelians()
	{
		return $this->hasMany("App\Models\Pembelian");
	}
}
