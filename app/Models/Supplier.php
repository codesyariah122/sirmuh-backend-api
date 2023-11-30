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
}
