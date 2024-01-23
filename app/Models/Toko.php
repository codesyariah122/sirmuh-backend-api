<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Toko extends Model
{
    use HasFactory;
    
    use SoftDeletes;

    protected $table = 'tokos';

    public function users()
    {
        return $this->belongsToMany("App\Models\User");
    }

    public function setup_perusahaan()
    {
        return $this->belongsToMany("App\Models\SetupPerusahaan");
    }
}
