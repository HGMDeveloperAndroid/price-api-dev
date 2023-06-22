<?php

use Illuminate\Database\Seeder;
use App\Chains;

class ChainModSeeder extends Seeder
{
    private $chainData = [
        [
            'name' => 'Mi Bodega Aurrera',
            'alias' => 'MI BODEGA',
            'blocked_up' => true,
        ],
        [
            'name' => 'Bodega Aurrera Express',
            'alias' => 'EXPRESS',
            'blocked_up' => true,
        ],
        [
            'name' => 'Bodega Aurrera',
            'alias' => 'BODEGA',
            'blocked_up' => true,
        ],
        [
            'name' => 'Bara',
            'alias' => 'BARA',
            'blocked_up' => true,
        ],
        [
            'name' => 'Corporativo Tiendas 3B',
            'alias' => 'CORPORATIVO',
            'blocked_up' => true,
        ],
        [
            'name' => 'Exito',
            'alias' => 'EXITO',
            'blocked_up' => true,
        ],
        [
            'name' => 'La Gran Bodega',
            'alias' => 'GRAN',
            'blocked_up' => true,
        ],
        [
            'name' => 'Sumesa',
            'alias' => 'SUMESA',
            'blocked_up' => true,
        ],
        [
            'name' => 'Miscelaneas',
            'alias' => 'MISCELANEAS',
            'blocked_up' => true,
        ],
        [
            'name' => 'Chedraui',
            'alias' => 'CHEDRAUI',
            'blocked_up' => true,
        ],
        [
            'name' => 'H-E-B',
            'alias' => 'H-E-B, HEB',
            'blocked_up' => true,
        ],
        [
            'name' => 'La Comer',
            'alias' => 'COMER',
            'blocked_up' => true,
        ],
        [
            'name' => 'Mi tienda del ahorro',
            'alias' => 'AHORRO',
            'blocked_up' => true,
        ],
        [
            'name' => 'Oxxo',
            'alias' => 'OXXO',
            'blocked_up' => true,
        ],
        [
            'name' => "Sam's Club",
            'alias' => 'SAM',
            'blocked_up' => true,
        ],
        [
            'name' => 'Soriana',
            'alias' => 'SORIANA',
            'blocked_up' => true,
        ],
        [
            'name' => 'Super Che',
            'alias' => 'CHE',
            'blocked_up' => true,
        ],
        [
            'name' => 'Superama',
            'alias' => 'SUPERAMA',
            'blocked_up' => true,
        ],
        [
            'name' => 'Tienda 3B',
            'alias' => '3B',
            'blocked_up' => true,
        ],
        [
            'name' => 'Tienda D1',
            'alias' => 'D1',
            'blocked_up' => true,
        ],
        [
            'name' => 'Neto',
            'alias' => 'NETO',
            'blocked_up' => true,
        ],
        [
            'name' => 'Walmart',
            'alias' => 'WAL',
            'blocked_up' => true,
        ],
        [
            'name' => 'No store',
            'alias' => 'No store',
            'blocked_up' => true,
        ]
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->chainData as $item) {
            Chains::create($item);
        }
    }
}
