<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Penjualan extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = "penjualan";
    
    public function pelanggans()
	  {
		  return $this->belongsToMany("App\Models\Pelanggan");
	  }
}
