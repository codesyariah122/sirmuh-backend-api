<?php

namespace App\Helpers;

use App\Models\Profile;

class ContextData
{
    public function getInfoData($type)
    {
        switch ($type):
            case 'super_admin':
                $user = Profile::whereUsername($type)->with('users')->get();
                return $user;
                break;
            default:
                return 'Type data not found!';
        endswitch;
    }
}
