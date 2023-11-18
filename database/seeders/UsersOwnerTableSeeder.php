<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\{User, Roles};

class UsersOwnerTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
     public function run()
    {
        $administrator = new User;
        $administrator->name = "owner sirmuh";
        $administrator->email = "owner@digitalkreatifsolusindo.tk";
        $administrator->password = Hash::make("Bismillah_123654");
        $administrator->is_login = 0;
        $administrator->save();
        $roles = Roles::findOrFail(1);
        $administrator->roles()->sync($roles->id);
        $update_user_role = User::findOrFail($administrator->id);
        $update_user_role->role = $roles->id;
        $update_user_role->save();
        $this->command->info("User admin created successfully");
    }
}
