<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\{User, ApiKey};

class ApiKeySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::whereName('Vicky Andriani')->firstOrFail();
        $token = new ApiKey;
        $token->user_id = $user->id;
        $token->token = Str::random(32);
        $token->save();
        $this->command->info("Token has been created");
    }
}
