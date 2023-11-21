<?php

/**
 * @author: pujiermanto@gmail.com
 * */

namespace App\Helpers;

use Illuminate\Support\Facades\Gate;
use App\Models\{User, Roles};

class WebFeatureHelpers
{

    protected $data = [];

    public function __construct($data=null)
    {
        $this->data = $data;
    }

    public function get_total_user($role)
    {
        switch ($role):
            case 'OWNER':
            $total = User::whereNull('deleted_at')
            ->whereRole(1)
            ->get();
            return count($total);
            break;
            case 'ADMIN`':
            $total = User::whereNull('deleted_at')
            ->whereRole(2)
            ->get();
            return count($total);
            break;

            case 'KASIR':
            $total = User::whereNull('deleted_at')
            ->whereRole(3)
            ->get();
            return count($total);
            break;
            default:
            return 0;
        endswitch;
    }

    public function user_online()
    {
        $user_is_online = User::whereIsLogin(1)
        ->get();
        return count($user_is_online);
    }

    public function createThumbnail($path, $width, $height)
    {
        $img = Image::make($path)->resize($width, $height, function ($constraint) {
            $constraint->aspectRatio();
        });
        $img->save($path);
    }

    public static function makeAvatar($fontPath, $dest, $char)
    {
        $path = $dest;
        $image = imagecreate(200, 200);
        $red = rand(0, 255);
        $green = rand(0, 255);
        $blue = rand(0, 255);
        imagecolorallocate($image, $red, $green, $blue);
        $textcolor = imagecolorallocate($image, 255, 255, 255);
        imagettftext($image, 50, 0, 50, 125, $textcolor, $fontPath, $char);
        imagepng($image, $path);
        imagedestroy($image);
        return $path;
    }

    public function GatesAccess()
    {
        foreach ($this->data as $data) :
            Gate::define($data, function ($user) {
                $user_id = $user->id;
                $roles = User::whereId($user_id)->with('roles')->get();
                $role = json_decode($roles[0]->roles[0]->name);

                return "OWNER" && "ADMIN" ? true :  false;
            });
        endforeach;
    }

}
