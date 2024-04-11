<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReturnPenjualan extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'return_penjualan';
}
