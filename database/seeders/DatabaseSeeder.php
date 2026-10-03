<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ServiceSeeder::class);

        User::updateOrCreate(
            ['email' => 'admin@printshop.local'],
            [
                'name' => 'ผู้ดูแลระบบ',
                'password' => Hash::make('Printshop123!'),
                'role' => 'admin',
            ]
        );

        User::updateOrCreate(
            ['email' => 'staff@printshop.local'],
            [
                'name' => 'พนักงาน',
                'password' => Hash::make('Printshop123!'),
                'role' => 'staff',
            ]
        );

        User::updateOrCreate(
            ['email' => 'customer@printshop.local'],
            [
                'name' => 'ลูกค้าทดสอบ',
                'password' => Hash::make('Printshop123!'),
                'role' => 'customer',
            ]
        );
    }
}
