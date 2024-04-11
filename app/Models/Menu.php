<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = "menus";
    protected $casts = ['roles' => 'array'];

    public function sub_menus()
    {
        return $this->belongsToMany('App\Models\SubMenu');
    }

    public function logins()
    {
    	return $this->belongsToMany('App\Models\Login');
    }
}
