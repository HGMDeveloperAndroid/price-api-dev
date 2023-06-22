<?php

use Illuminate\Database\Seeder;
use App\Brands;

class BrandCopySeeder extends Seeder
{

    private $brandsData = [
        'Heinz',
        'Alldays',
        'Arizona',
        'Astra',
        'Aurrera',
        'Bbtips',
        'Bebyto',
        'Bic',
        'Bio Baby',
        'Boing',
        'Bold',
        'Bold 3',
        'Brazil',
        'Capitan Marino',
        'Chicolastic',
        'Clamato',
        'Corina',
        'Cosmos',
        'Economax',
        'Frutsi',
        'Funderful',
        'Golden Bull',
        'Granvita',
        'Great Day',
        'Great Value',
        'Guadalupe',
        'Heb Baby',
        'Hill Country',
        'Ideal',
        'Kikolastic',
        'Klara',
        'La Costena',
        'La Fina',
        'La Moderna',
        'Lala',
        'Lirio',
        'Maruchan',
        'Maya',
        'Medimart',
        'Movie Pop',
        'Niagara',
        'Nissin',
        'Ocean Spray',
        'Paloma',
        'Premier',
        'Purina',
        'Revlon',
        'Royal',
        'Royal Pine',
        'Sabritas',
        'Sanchez Y Marti­N',
        'Silk',
        'Tecate',
        'Toyo Suisan',
        'Util',
        'Valentina',
        'Yavaros',
        'Zest',
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $count = 1;

        foreach ($this->brandsData as $item) {
            Brands::create([
                'name' => $item
            ]);
            $count++;
        }

        echo "Inserts brands: $count\n";
    }
}
