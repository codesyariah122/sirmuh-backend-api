<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function api_keys()
    {
        return $this->belongsToMany('App\Models\ApiKeys');
    }

    public function logins()
    {
        return $this->belongsToMany('App\Models\Login');
    }

    public function roles()
    {
        return $this->belongsToMany('App\Models\Roles', 'roles_user', 'user_id', 'roles_id');
    }
    
    public function karyawans()
    {
      return $this->belongsToMany("App\Models\Karyawan");
    }
    
    public function tokos()
    {
      return $this->belongsToMany("App\Models\Toko");
    }
}
