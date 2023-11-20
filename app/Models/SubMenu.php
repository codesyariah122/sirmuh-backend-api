<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubMenu extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $casts = ['roles' => 'array'];

    public function menus()
    {
        return $this->belongsToMany('App\Models\Menu');
    }
}
