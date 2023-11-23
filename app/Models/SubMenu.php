<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubMenu extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = "sub_menus";

    protected $casts = ['roles' => 'array'];

    public function menus()
    {
        return $this->belongsToMany('App\Models\Menu');
    }

    public function logins()
    {
    	return $this->belongsToMany('App\Models\Login');
    }

    public function child_sub_menus()
    {
    	return $this->belongsToMany("App\Models\ChildSubMenu");
    }
}
