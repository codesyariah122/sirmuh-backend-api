<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SetupPerusahaan extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'setup_perusahaan';

    public function tokos()
    {
        return $this->belongsToMany('App\Models\Toko');
    }
    
}
