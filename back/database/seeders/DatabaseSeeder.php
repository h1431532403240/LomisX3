<?php

namespace Database\Seeders;

use App\Models\{User, Store};
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 先創建測試門市
        $store = Store::create([
            'name' => '測試門市',
            'code' => 'TEST01',
            'status' => 'active',
            'address' => '台北市信義區',
            'phone' => '02-12345678',
            'email' => 'store@example.com',
            'created_by' => 1,
        ]);

        // 再創建測試使用者
        User::factory()->create([
            'username' => 'testuser',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'store_id' => $store->id,
        ]);
    }
}
