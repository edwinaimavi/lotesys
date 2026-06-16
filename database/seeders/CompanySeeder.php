<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Company;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::updateOrCreate(
            ['ruc' => '20607752312'],
            [
                'business_name' => 'MS KREA S.A.C.',
                'trade_name'    => 'GRUPO KREA',
                'address'       => 'AV. CIRCUNVALACION NRO. 452 SAN MARTIN - SAN MARTIN - TARAPOTO',
                'email'         => 'krea@gmail.com',
                'phone'         => '972873511',
                'status'        => 1,
                'created_by'    => 1,
                'updated_by'    => 1,
            ]
        );

        Company::updateOrCreate(
            ['ruc' => '20610686665'],
            [
                'business_name' => 'FARJE INGENIEROS E.I.R.L.',
                'trade_name'    => 'FARJE INGENIEROS E.I.R.L.',
                'address'       => 'AV. CIRCUNVALACION NRO. 452 (AL COSTADO DE FERRETERIA DR OBRA) SAN MARTIN - SAN MARTIN - TARAPOTO',
                'email'         => 'farje@gmail.com',
                'phone'         => '987963852',
                'status'        => 1,
                'created_by'    => 1,
                'updated_by'    => 1,
            ]
        );
    }
}