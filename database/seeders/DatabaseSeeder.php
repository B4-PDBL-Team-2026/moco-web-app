<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Domains\User\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->create([
            'name' => 'Administrator',
            'email' => config('services.accounts.admin_mail'),
            'password' => bcrypt(config('services.accounts.admin_pass')),
            'role' => 'admin',
        ]);
        $this->call(CategorySeeder::class);
    }
}
