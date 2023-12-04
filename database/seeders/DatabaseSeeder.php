<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
        // $this->call(ApiKeySeeder::class);
        $this->call(UsersMasterTableSeeder::class);
        $this->call(UsersKasirTableSeeder::class);
        $this->call(UsersKasirGudangTableSeeder::class);
        $this->call(UsersAdminTableSeeder::class);
    }
}
