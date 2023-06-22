<?php

use App\Groups;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{

    private $groupsData = [
        [
            'name' => '(01) SauSpi'
        ],
        ['name' => '(02) Seeds'],
        ['name' => '(03) Flour'],
        ['name' => '(04) Canned'],
        ['name' => '(05) Breakfast'],
        ['name' => '(06) Baby'],
        ['name' => '(07) Beverages'],
        ['name' => '(08) Snacks'],
        ['name' => ' (09) Candies'],
        ['name' => '(10) Lacti Deli'],
        ['name' => '(11) Personal Care'],
        ['name' => '(12) Paper'],
        ['name' => '(13) Cleaning'],
        ['name' => '(14) General M'],
        ['name' => '(15) E-Sales'],
        ['name' => '(16) Desserts'],
        ['name' => '(17) Pasta Soup'],
        ['name' => '(18) Ice'],
        ['name' => '(19) Pharmacy'],
        ['name' => '(20) Oils'],
        ['name' => '(21) Cookies'],
        ['name' => '(22) Frozen'],
        ['name' => '(23) Bags'],
        ['name' => '(24) In&Out'],
        ['name' => '(26) Personal'],
        ['name' => '(27) Sugar'],
        ['name' => '(29) Pets'],
        ['name' => '(30) Lacti Deli NR'],
        ['name' => '(33) Dairy Products'],
        ['name' => '(34) Delicatessen'],
        ['name' => '(35) Temporales'],
        ['name' => '(36) Tortilleria'],
        ['name' => '(37) Water'],
        ['name' => '(38) Eggs'],
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
         foreach ($this->groupsData as $item) {
             Groups::create($item);
         }
    }

}
