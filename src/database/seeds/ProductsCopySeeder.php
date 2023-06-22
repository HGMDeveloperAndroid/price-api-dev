<?php

use App\Brands;
use App\Products;
use App\Units;
use Illuminate\Database\Seeder;

class ProductsCopySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $duplicates = 0;
        $products = DB::connection('mysql2')->select('select * from products');
        $count = 0;
        foreach ($products as $product) {

            $unit = Units::where('name', $product->unit)->first();
            $brand = Brands::where('name', $product->brand)->first();
//            dd(['unit'=>$unit->id, 'brand' => $brand->id, $product->type]);
            $type = trim($product->type);
            $type = strtoupper($type);
            $data = [
                'name' => $product->name,
                'barcode' => $product->barcode,
                'price' => $product->price,
                'min_price' => $product->min_price,
                'unit_quantity' => $product->unit_quantity,
                'max_price' => $product->max_price,
                'id_unit' => isset($unit->id) ? $unit->id : 8,
                'id_group' => $product->id_group,
                'id_line' => $product->id_line,
                'id_brand' => $brand->id,
                'type' => in_array($type, ['MC', 'MP']) ? $type : 'N/A',
                'is_enable' => $product->is_enable,
                'created_at' => $this->validateDate($product->created_at) ? $product->created_at : new DateTime()
            ];

            try {
                Products::create($data);
                $count++;
            } catch (Exception $e) {
                $duplicates++;
                echo '****Error:' . $e->getMessage() . "\n";
                print_r($data);

            }


        }

        echo "Inserts: $count, duplicates: $duplicates\n";
    }

    private function validateDate($date, $format = 'Y-m-d H:i:s')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }
}
