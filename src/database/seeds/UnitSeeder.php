<?php

use Illuminate\Database\Seeder;
use App\Units;


class UnitSeeder extends Seeder
{

    private $unitsData = [
        [
            'name' => 'milimetro',
            'abbreviation' => 'mm'
        ],
        [
            'name' => 'centimetro',
            'abbreviation' => 'cm',
        ],
        [
            'name' => 'metro',
            'abbreviation' => 'm',
        ],
        [
            'name' => 'mililitro',
            'abbreviation' => 'ml',
        ],
        [
            'name' => 'litro',
            'abbreviation' => 'lt',
        ],
        [
            'name' => 'miligramo',
            'abbreviation' => 'mg',
        ],
        [
            'name' => 'gramo',
            'abbreviation' => 'g',
        ],
        [
            'name' => 'kilogramo',
            'abbreviation' => 'kg',
        ],
        [
            'name' => 'pieza',
            'abbreviation' => 'pieza',
        ]
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->unitsData as $item) {
            Units::create($item);
        }
    }
}
