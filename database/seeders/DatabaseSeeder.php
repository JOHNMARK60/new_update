<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(['email' => 'admin@system.local'], ['first_name' => 'System', 'last_name' => 'Administrator', 'password' => 'admin123', 'role' => 'admin', 'status' => 'active']);
        foreach (['General', 'Beverages', 'Snacks', 'Personal Care', 'Household', 'School Supplies', 'Frozen Goods'] as $name) {
            Category::firstOrCreate(compact('name'));
        }
    }
}
