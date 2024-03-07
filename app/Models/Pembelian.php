<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pembelian extends Model
{
	use HasFactory;
	// use SoftDeletes;
	
	protected $fillable = [
        'kode',
        'tanggal',
        'supplier',
    ];

	public $table = 'pembelian';
	
	public function suppliers()
	{
		return $this->belongsToMany("App\Models\Supplier");
	}
}
