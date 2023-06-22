<?php

use App\Chains;
use App\Store;
use Illuminate\Database\Seeder;
use Grimzy\LaravelMysqlSpatial\Types\Point;

class StoreCopySeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $count = 0;
        $fails = 0;
        $sql = "select DISTINCT s.address_name name,s.address,s.latitude,s.longitude
                from `p3bPRO-2020_04_17`.scans s
                where s.`chain` is not null
                order by name asc"
        ;

        $scanStores = DB::connection('mysql2')->select($sql);
        foreach ($scanStores as $item) {

            $data = [
                'name' => isset($item->name)?$item->name: 'N/D',
                'address' => $item->address,
                'location' => new Point($item->latitude, $item->longitude),
//                'phone' => $item->
            ];

            try {
                Store::create($data);
                $count++;
            } catch (Exception $e){
                echo "Error:". $e->getMessage()."\n";
                $fails++;
                dd($data);
            }
        }

        echo "Inserts: $count, fails: $fails\n";
    }
}
