<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChildSubMenu extends Model
{
    use HasFactory;
    
    protected $table = "child_sub_menus";

    public function sub_menus()
    {
    	return $this->belongsToMany("App\Models\SubMenu");
    }
}
