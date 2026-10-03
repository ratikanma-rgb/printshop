<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name' => 'พิมพ์ขาวดำ',
                'description' => 'บริการพิมพ์เอกสารขาวดำ',
                'price' => 1.00,
                'unit' => 'หน้า',
                'is_active' => true,
            ],
            [
                'name' => 'พิมพ์สี',
                'description' => 'บริการพิมพ์เอกสารสี',
                'price' => 5.00,
                'unit' => 'หน้า',
                'is_active' => true,
            ],
            [
                'name' => 'ถ่ายเอกสาร',
                'description' => 'บริการถ่ายเอกสารทั่วไป',
                'price' => 1.00,
                'unit' => 'หน้า',
                'is_active' => true,
            ],
            [
                'name' => 'สแกนเอกสาร',
                'description' => 'บริการสแกนเอกสารเป็นไฟล์',
                'price' => 5.00,
                'unit' => 'หน้า',
                'is_active' => true,
            ],
            [
                'name' => 'เข้าเล่ม',
                'description' => 'บริการเข้าเล่มเอกสาร',
                'price' => 30.00,
                'unit' => 'เล่ม',
                'is_active' => true,
            ],
            [
                'name' => 'เคลือบเอกสาร',
                'description' => 'บริการเคลือบเอกสาร',
                'price' => 20.00,
                'unit' => 'แผ่น',
                'is_active' => true,
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(
                ['name' => $service['name']],
                $service
            );
        }
    }
}