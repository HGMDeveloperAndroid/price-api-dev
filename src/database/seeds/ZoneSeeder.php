<?php

use Illuminate\Database\Seeder;
use App\Region;
use Illuminate\Support\Str;

class ZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $zones = [
            [
                'name' => '1000 – Cuautitlán',
                'alias' => '1000',
                'short_name' => Str::slug('1000 – Cuautitlán')
            ],
            [
                'name' => '1001 – Los Reyes',
                'alias' => '1001',
                'short_name' => Str::slug('1001 – Los Reyes')
            ],
            [
                'name' => '1002 – Barrientos',
                'alias' => '1002',
                'short_name' => Str::slug('1002 – Barrientos')
            ],
            [
                'name' => '1003 – Metepec',
                'alias' => '1003',
                'short_name' => Str::slug('1003 – Metepec')
            ],
            [
                'name' => '1004 – Yautepec',
                'alias' => '1004',
                'short_name' => Str::slug('1004 – Yautepec')
            ],
            [
                'name' => '1005 – Tezoyuca',
                'alias' => '1005',
                'short_name' => Str::slug('1003 – Metepec')
            ],
            [
                'name' => '1006 – Tláhuac',
                'alias' => '1006',
                'short_name' => Str::slug('1006 – Tláhuac')
            ],
            [
                'name' => '1007 – Celaya',
                'alias' => '1007',
                'short_name' => Str::slug('1007 – Celaya')
            ],
            [
                'name' => '1008 – Puebla',
                'alias' => '1008',
                'short_name' => Str::slug('1008 – Puebla')
            ],
            [
                'name' => 'Compras',
                'alias' => 'compras',
                'short_name' => 'compras'
            ]
        ];

        foreach ($zones as $zone) {
            Region::create($zone);
        }

    }
}
