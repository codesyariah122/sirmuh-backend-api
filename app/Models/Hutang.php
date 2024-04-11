<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hutang extends Model
{
    use HasFactory;
    // use SoftDeletes;

    protected $table = 'hutang';

    public function suppliers()
    {
    	return $this->hasMany("App\Models\Supplier");
    }
}
