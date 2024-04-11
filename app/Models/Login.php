<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Login extends Model
{
    use HasFactory;

    protected $table = 'logins';

    public function users()
    {
        return $this->belongsToMany('App\Models\User');
    }

    public function sub_menus()
    {
    	return $this->belongsToMany('App\Models\SubMenu');
    }
}
