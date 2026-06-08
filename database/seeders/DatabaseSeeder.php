<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(LocationSeeder::class);

        // User::factory(10)->create();

        User::firstOrCreate([
            'email' => 'test@example.com',
        ], [
            'name' => 'Test User',
            'username' => 'admin',
            'role' => 'Administrator',
            'phone' => '+0000000000',
            'password' => Hash::make('password'),
            'must_change_password' => false,
        ]);
    }
}
