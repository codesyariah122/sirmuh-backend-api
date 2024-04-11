<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\{User, Roles, Karyawan};
use App\Helpers\{WebFeatureHelpers};

class UsersAdminTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function initials($name)
    {
        preg_match('/(?:\w+\. )?(\w+).*?(\w+)(?: \w+\.)?$/', $name, $result);
        $initial = strtoupper($result[1][0] . $result[2][0]);
        return $initial;
    }

    public function run()
    {
        $administrator = new User;
        $administrator->name = "komarasari";
        $administrator->email = "komarasari5@gmail.com";
        $administrator->password = Hash::make("Admin@123");
        $administrator->is_login = 0;
        $initial = $this->initials($administrator->name);
        $path = public_path().'/thumbnail_images/users/';
        $fontPath = public_path('fonts/Oliciy.ttf');
        $char = $initial;
        $newAvatarName = rand(12, 34353) . time() . '_avatar.png';
        $dest = $path . $newAvatarName;

        $createAvatar = WebFeatureHelpers::makeAvatar($fontPath, $dest, $char);
        $administrator->photo = 'thumbnail_images/users/' . $newAvatarName;
        $administrator->save();
        $roles = Roles::findOrFail(2);

        $data_karyawan = new Karyawan;
        $data_karyawan->kode = "ADM0001";
        $data_karyawan->nama = $administrator->name;
        $data_karyawan->level = $roles->name;
        $data_karyawan->save();

        $administrator->roles()->sync($roles->id);
        $administrator->karyawans()->sync($administrator->id);
        
        $update_user_role = User::findOrFail($administrator->id);
        $update_user_role->role = $roles->id;
        $update_user_role->save();
        $this->command->info("User admin created successfully");
    }
}
