<?php

use Illuminate\Database\Seeder;

class ScannerCopySeeder extends Seeder
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
        $sql = "select DISTINCT s.address_name name,s.address,s.latitude,s.longitude,s.chain
                from `p3bPRO-2020_04_17`.scans s
                where `chain` is not null
                order by name asc "
        ;

        $scanStores = DB::connection('mysql2')->select($sql);
    }
}
