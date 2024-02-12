<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LabaRugi extends Model
{
    use HasFactory;
    use SoftDeletes;
    
    protected $table = 'labarugi';
    
    public function barangs()
    {
    	return $this->belongsTo("App\Models\Barang", 'kode', 'nama');
    }
    
}
