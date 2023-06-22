<?php

use App\Chains;
use Illuminate\Database\Seeder;

class ChainSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $count = 0;
        $sql = "select s.`chain`
                from `p3bPRO-2020_04_17`.scans s
                where s.is_enable =1
                group by s.`chain`";
        $scanChains = DB::connection('mysql2')->select($sql);

        foreach ($scanChains as $chain) {
            $data = [
                'name' => is_null($chain->chain)? 'N/D': $chain->chain,
                'alias' => is_null($chain->chain)? 'N/D': $chain->chain,
                'description' => is_null($chain->chain)? 'N/D': $chain->chain
            ];
//            dd($data);
            Chains::create($data);
            $count++;
        }

        echo 'Inserts: '.$count."\n";
    }
}
