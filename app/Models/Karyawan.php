<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Karyawan extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = "karyawan";
    protected $fillable = [
    	'id',
        'nama'
    ];

    public function users()
    {
    	return $this->belongsToMany("App\Models\User");
    }
}
