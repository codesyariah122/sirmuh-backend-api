<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penjualan extends Model
{
    use HasFactory;

    protected $table = "penjualan";
    
    public function pelanggans()
	  {
		  return $this->belongsToMany("App\Models\Pelanggan");
	  }
}
