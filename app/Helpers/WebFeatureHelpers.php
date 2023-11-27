<?php

/**
 * @author: pujiermanto@gmail.com
 * */

namespace App\Helpers;

use Illuminate\Support\Facades\Gate;
use \Milon\Barcode\DNS1D;
use \Milon\Barcode\DNS2D;
use Illuminate\Support\Facades\File;
use App\Models\{User, Roles, Login};

class WebFeatureHelpers
{

    protected $data = [];

    public function __construct($data=null)
    {
        $this->data = $data;
    }

    public static function initials($name)
    {
        preg_match('/(?:\w+\. )?(\w+).*?(\w+)(?: \w+\.)?$/', $name, $result);
        $initial = strtoupper($result[1][0] . $result[2][0]);
        return $initial;
    }

    public function get_total_user($role)
    {
        switch ($role):
            case 'ADMIN':
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

            case 'GUDANG':
            $total = User::whereNull('deleted_at')
            ->whereRole(4)
            ->get();
            return count($total);
            break;

            case 'PRODUKSI':
            $total = User::whereNull('deleted_at')
            ->whereRole(5)
            ->get();
            return count($total);
            break;

            case 'KASIR_GUDANG':
            $total = User::whereNull('deleted_at')
            ->whereRole(6)
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
                $rolesUser = User::whereId($user_id)->with('roles')->get();
                $role = $rolesUser[0]->roles[0]->name;
                return $role === "MASTER" ? true :  false;
            });
        endforeach;
    }

    public function generateBarcode($data)
    {
        $generator = new BarcodeGeneratorHTML();
        $barcodeHtml = $generator->getBarcode($data, $generator::TYPE_CODE_128);

        return $barcodeHtml;
    }


    public function generateQrCode($data)
    {
        $qr = new DNS2D;

        $base64QrCode = $qr->getBarcodePNG($data, 'QRCODE', 4, 4);

        $binaryQrCode = base64_decode($base64QrCode);

        $fileName = $data . '.png';

        $filePath = public_path("qr-codes/{$fileName}");

        File::put($filePath, $binaryQrCode);

        return $filePath;
    }

}
