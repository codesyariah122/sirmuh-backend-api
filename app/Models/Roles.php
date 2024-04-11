<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Roles extends Model
{
	use HasFactory;
	use SoftDeletes;

	public function users()
	{
		return $this->belongsToMany('App\Models\User');
	}
}
