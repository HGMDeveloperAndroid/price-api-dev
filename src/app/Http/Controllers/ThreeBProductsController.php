<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ThreeBProducts;
use App\ThreeBProductChain;
use App\Products;
use App\Scans;
use App\Chains;
use App\Brands;
use App\Units;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use League\Csv\Writer;

class ThreeBProductsController extends Controller
{
    private $threeb_headings = [[
        'ITEM',
        'Clave de producto 3B',
        'Código de barras',
        'Descripción 3B',
        'Contenido',
        'Unidad',
        'Tipo',
        'Estatus',
        'Precio de venta 3B'
    ]];

    private $chains_headings = [
        'Precio de venta',
        'Gramaje',
        'Unidad',
        'Descripción',
        'Código de barras',
        'Precio comparado',
        'Diferencia en pesos',
        'Porcentaje de diferencia',
        'Capturas',
        'Promoción'
    ];

    /**
     * Get date information from uploaded csv
     *
     * @return Array
     */
    public function isThereAnImportedCSV() {
        $three_b_products = ThreeBProducts::join(
            'three_b_products_chains',
            'three_b_products.id',
            '=',
            'three_b_products_chains.three_b_product_id'
        )->select('three_b_products_chains.chain_id', 'three_b_products.created_at')
        ->groupBy('three_b_products_chains.chain_id')->get();

        $chains = [];
        $date_created_at = '';
        foreach($three_b_products as $three_b_product) {
            $date_created_at = $three_b_product->created_at;
            $chain = Chains::where('id', '=', $three_b_product->chain_id)->first();
            array_push($chains, $chain);
        }

        if (count($three_b_products) === 0 || count(array($three_b_products)) === 0 || $three_b_products === NULL) {
            return response()->json([
                'status' => false,
                'message' => 'No existe csv cargado actualmente',
                'date' => null
            ]);
        }

        $path_file = '';
        if (file_exists(storage_path('app/statisticalreport'))) {
            $file = scandir(storage_path('app/statisticalreport'), 1);
            $path_file = storage_path('app/statisticalreport') . '/' . $file[0];
        }

        return response()->json([
            'status' => true,
            'message' => 'Existe un csv cargado',
            'data' => [
                'date' => $date_created_at->format('d-m-Y'),
                'chains' => $chains,
                'csv_path' => $path_file
            ]
        ]);
    }

    /**
     * Get the 3B product data from the CSV file to save it for future processing
     *
     * @return Response
    */
    public function importMasterFile(Request $request){
        $input = [
            'csv' => 'required|mimetypes:text/csv,text/plain,application/csv,text/comma-separated-values,text/anytext,application/octet-stream,application/txt',
        ];

        $messages = [
            'required' => 'El campo csv es requerido.',
            'mimetypes' => 'El archivo debe ser un csv.'
        ];

        $validator = Validator::make($request->all(), $input, $messages);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $rows = file($request->file('csv'));
        $headers_csv = ['ITEM', 'Clave', 'Código de Barras', 'Descripción', 'Contenido', 'Unidad', 'Tipo', 'Precio 3B', 'Estatus'];
        foreach($rows as $key => $value) {
            $values = str_getcsv($value);
            if ($key === 0) {
                $outputValues = array_slice($values, 0, 9);
                foreach($headers_csv as $text) {
                    if (!in_array($text, $outputValues)) {
                        return response()->json(['status' => false, 'errors' => 'La columna ' . $text . ' es requerida en el csv.'], JsonResponse::HTTP_BAD_REQUEST);
                    }
                }

                if (count($values) <= 9) {
                    return response()->json(['status' => false, 'errors' => 'Debe agregar al menos un id de cadena para comparar'], JsonResponse::HTTP_BAD_REQUEST);
                }
            }
            break;
        }

        //Truncate the table in order to fill it again with new data
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        ThreeBProductChain::truncate();
        ThreeBProducts::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        $chains = [];

        foreach($rows as $key => $value) {
            $value = str_getcsv($value);
            if ($key === 0) {
                array_push($chains, isset($value[9]) ? $value[9] : 0 );
                array_push($chains, isset($value[10]) ? $value[10] : 0 );
                array_push($chains, isset($value[11]) ? $value[11] : 0 );
                array_push($chains, isset($value[12]) ? $value[12] : 0 );
                array_push($chains, isset($value[13]) ? $value[13] : 0 );
            }
        }

        foreach($chains as $key => $chain) {
            if ($chain === 0) {
                unset($chains[$key]);
            }
        }

        foreach($rows as $key => $value){
            $value_row = str_getcsv($value);

            if($key !== 0){
                $unit_id = Units::select('id')->where('name', '=', $value_row[5])->first();
                $master_file = ThreeBProducts::create([
                    'item' => $value_row[0],
                    'keycode' => $value_row[1],
                    'barcode' => $value_row[2],
                    'description' => $value_row[3],
                    'unit_quantity' => $value_row[4],
                    'unit_id' => $unit_id->id,
                    'type' => $value_row[6],
                    'price' => $value_row[7],
                    'status' => $value_row[8]
                ]);
                $position_chain = 9;
                foreach($chains as $chain) {
                    if ($chain != 0 && isset($value_row[$position_chain]) && $value_row[$position_chain] != 0) {
                        ThreeBProductChain::create([
                            'three_b_product_id' => $master_file->id,
                            'chain_id' => $chain,
                            'barcode' => $value_row[$position_chain]
                        ]);
                    }
                    if ($value_row[$position_chain] === 0 || !$value_row[$position_chain]) {
                        ThreeBProductChain::create([
                            'three_b_product_id' => $master_file->id,
                            'chain_id' => $chain,
                            'barcode' => -1
                        ]);
                    }
                    $position_chain++;
                }
            }
        }
        $master_file = ThreeBProducts::all();
        $chains = Chains::where('is_notificable', 1)->whereIn('id', $chains)->get();

        if (file_exists(storage_path('app/statisticalreport'))) {
            $file = scandir(storage_path('app/statisticalreport'), 1);
            $path_file = storage_path('app/statisticalreport') . '/' . $file[0];
            unlink($path_file);
        }

        $originName = $request->file('csv')->getClientOriginalName();
        $path = $request->file('csv')->storeAs('statisticalreport', $originName);

        $success = [
            'status' => true,
            'message' => "Los productos se registraron con éxito",
            'products' => $master_file,
            'chains' => $chains
        ];
        return response()->json($success, JsonResponse::HTTP_CREATED);
    }

    public function getMasterFile(){
        $master_file = ThreeBProducts::all();
        return response()->json($master_file, JsonResponse::HTTP_OK);
    }

    /**
     * Truncate a double
     *
     * @return double
    */
    private function truncate($number, $decimals) {
        $power = pow(10, $decimals); 
        if($number > 0)return floor($number * $power) / $power; 
        return ceil($number * $power) / $power; 
    }

    /**
     * Compares prices between 3B products and the given chains
     *
     * @return double
    */
    public function comparePrices(Request $request){
        $product_differences = array();
        $chains = $request->input('chains');
        sort($chains);
        $three_b_products = ThreeBProducts::all();

        $comparations = new \stdClass();
        foreach($three_b_products as $product) {
            $barcode_chains = ThreeBProductChain::where(
                'three_b_product_id', '=', $product->id
            )->whereIn(
                'chain_id', $chains
            )->orderBy('chain_id', 'asc')->get();

            if (count($barcode_chains) > 0) {
                $comparations->equivalences[$product->barcode] = [];
                foreach($barcode_chains as $barcode_chain) {
                    array_push($comparations->equivalences[$product->barcode], $barcode_chain->barcode);
                }
            }
        }
        $equivalences = $comparations->equivalences;

        $fromDate = $request->input('fromDate');
        $toDate = $request->input('toDate');

        if($request->input('fromDate') == null and $request->input('toDate') == null){
            $fromDate = date('Y-m-d', strtotime('-7 days'));
            $toDate = date('Y-m-d');
        }

        $index = 0;
        $complete_comparations = 0;

        // info for cheapest totals at other chains
        $total_cheaper_products_another_chain = array_fill(0, count($chains), 0);
        $total_cheaper_price_another_chain = array_fill(0, count($chains), 0);
        $total_more_expensive_price_3b = array_fill(0, count($chains), 0);
        $chains_cheaper = array_fill(0, count($chains), '');

        // info for cheapest totals at 3b
        $total_cheaper_products_3b = array_fill(0, count($chains), 0);
        $total_cheaper_price_3b = array_fill(0, count($chains), 0);
        $total_cheaper_3b = array_fill(0, count($chains), 0);
        $chains_more_expensive = array_fill(0, count($chains), '');

        // info for some prices
        $total_products_some_prices = array_fill(0, count($chains), 0);
        $total_price_some_prices = array_fill(0, count($chains), 0);
        $total_some_prices_3b = array_fill(0, count($chains), 0);
        $chains_some_prices = array_fill(0, count($chains), '');

        foreach($three_b_products as $product3b){
            //If the product has unit quantity already use these unit quantity otherwise use the default unit quantity
            $unit_price_3b = $product3b->price/($product3b->unit_quantity != 0 ? $product3b->unit_quantity : 1);
            $unit_price_3b = $this->truncate($unit_price_3b, 3);
            //Add the product to the array of differences
            $abbreviation = Units::where('id', '=', $product3b->unit_id)->first()->abbreviation;
            array_push(
                $product_differences,
                array(
                    "item" => $product3b->item,
                    "keycode" => $product3b->keycode,
                    "barcode" => $product3b->barcode,
                    "description" => $product3b->description,
                    "gr" => $product3b->unit_quantity . ' ' . $abbreviation,
                    "unit" => $abbreviation,
                    "unit_quantity" => $product3b->unit_quantity,
                    "type" => $product3b->type,
                    "price" => $product3b->price,
                    "status" => $product3b->status,
                    "unit_price" => $unit_price_3b,
                    "comparations" => array()
                )
            );
            //If the product has equivalences use them.
            if(isset($equivalences[$product3b->barcode])){
                $flag = false;

                $total_price_cheaper = array_fill(0, count($chains), 0);
                $total_products_cheaper = array_fill(0, count($chains), 0);

                $total_price_3b = array_fill(0, count($chains), 0);
                $total_products_3b = array_fill(0, count($chains), 0);

                $total_price_some_price = array_fill(0, count($chains), 0);
                $total_products_some_price = array_fill(0, count($chains), 0);

                foreach($equivalences[$product3b->barcode] as $key => $barcode){
                    if (isset($chains[$key]) && $chains[$key] !== -1) {
                        $current_chain = Chains::where("id", $chains[$key])->first();

                        if($barcode > 0){
                            //Get the corresponding scan by date range.
                            $scans_o = Scans::select('*')
                                ->where('barcode', '=', $barcode)
                                ->where('id_store', '=', $current_chain->id)
                                ->whereRaw(
                                    "(scans.capture_date >= ? AND scans.capture_date <= ?)",
                                    [
                                        $fromDate." 00:00:00",
                                        $toDate." 23:59:59"
                                    ]
                                )->orderBy('scans.capture_date', 'desc')->get();

                            if(count($scans_o) > 0){
                                //Get the corresponding product.
                                $product = Products::select('*')->where('barcode', '=', $barcode)->first();
                                //If the product exists in the chain and the scan exists.
                                if($product){
                                    // Price more frequently and special price
                                    $chain_price = "";
                                    $special_price_more_frequently = 0;
                                    $more_frequently = [];
                                    $total_scans = 0;
                                    if (count($scans_o) > 2) {
                                        $prices_scan = [];
                                        foreach($scans_o as $scan_o) {
                                            array_push($prices_scan, $scan_o->price);
                                        }
                                        // Price more frequently
                                        $more_frequently = array_count_values($prices_scan);
                                        arsort($more_frequently);
                                        $chain_price = key($more_frequently);

                                        // Special price
                                        $special_prices = [];
                                        foreach($scans_o as $scan_o) {
                                            if ($scan_o->price == $chain_price) {
                                                array_push($special_prices, $scan_o->special_price);
                                            }
                                        }
                                        $special_price_more_frequently = $special_prices[0];
                                    } else {
                                        $special_price_more_frequently = $scans_o->first()->special_price;
                                        $chain_price = $scans_o->first()->price;
                                        $total_scans = count($scans_o);
                                    }

                                    // Get the brand to concatenate in the description
                                    $brand = Brands::select('name')->where('id', '=', $product->id_brand)->first();

                                    $unit = Units::select('name')->where('id', '=', $product->id_unit)->first();

                                    // compared price
                                    $price_scan_decimals = $this->truncate($chain_price, 3);
                                    $unit_price_chain = $price_scan_decimals / ($product->unit_quantity != 0 ? $product->unit_quantity : 1);
                                    $compared_price = $unit_price_chain * ($product3b->unit_quantity != 0 ? $product3b->unit_quantity : 1);

                                    // If the unit id is equal to the unit id of the product in the chain then the difference is the difference in price.
                                    $difference = NULL;
                                    if($product->id_unit === $product3b->unit_id) {
                                        $difference = $compared_price - $product3b->price;
                                    }

                                    //If the difference is negative then the product is cheaper than the chain.
                                    array_push(
                                        $product_differences[$index]['comparations'],
                                        array(
                                            "chain_id" => $chains[$key],
                                            "chain" => $current_chain->name,
                                            "chain_price" => $chain_price,
                                            "grammage" => $product->unit_quantity,
                                            "unit" => $unit->name,
                                            "description" => $product->name . ' - ' . $brand->name,
                                            "barcode" => $barcode,
                                            "compared_price" => $this->truncate($compared_price, 2),
                                            "difference" => number_format($difference,2),
                                            "percentage" => $this->truncate($difference/$product3b->price*100, 2) . "%",
                                            "total_of_scans" => count($more_frequently) > 0 ? max($more_frequently) : $total_scans,
                                            "special_price" => $special_price_more_frequently
                                        )
                                    );
                                    // some prices
                                    if (floatval($product3b->price) === floatval($chain_price)) {
                                        $total_products_some_price[$key] += 1;
                                        $total_price_some_price[$key] += $chain_price;
                                        $total_some_prices_3b[$key] += $product3b->price;
                                        $chains_some_prices[$key] = $current_chain->name;
                                    }

                                    // cheaper prices in another chain (more expensive in 3b)
                                    if (floatval($product3b->price) > floatval($chain_price)) {
                                        $total_price_cheaper[$key] += $chain_price;
                                        $total_products_cheaper[$key] += 1;
                                        $total_more_expensive_price_3b[$key] += $product3b->price;
                                        $chains_cheaper[$key] = $current_chain->name;
                                    }

                                    // cheaper prices in 3b
                                    if (floatval($product3b->price) < floatval($chain_price)) {
                                        $total_price_3b[$key] += $chain_price;
                                        $total_products_3b[$key] += 1;
                                        $total_cheaper_3b[$key] += $product3b->price;
                                        $chains_more_expensive[$key] = $current_chain->name;
                                    }
                                } else {
                                    array_push(
                                        $product_differences[$index]['comparations'],
                                        array(
                                            "chain_id" => $chains[$key],
                                            "chain" => $current_chain->name,
                                            "chain_price" => NULL,
                                            "grammage" => NULL,
                                            "unit" => NULL,
                                            "description" => NULL,
                                            "barcode" => NULL,
                                            "compared_price" => NULL,
                                            "difference" => NULL,
                                            "percentage" => NULL,
                                            "total_of_scans" => NULL,
                                            "special_price" => NULL
                                        )
                                    );
                                    $flag = true;
                                }
                            } else {
                                array_push(
                                    $product_differences[$index]['comparations'],
                                    array(
                                        "chain_id" => $chains[$key],
                                        "chain" => $current_chain->name,
                                        "chain_price" => NULL,
                                        "grammage" => NULL,
                                        "unit" => NULL,
                                        "description" => NULL,
                                        "barcode" => NULL,
                                        "compared_price" => NULL,
                                        "difference" => NULL,
                                        "percentage" => NULL,
                                        "total_of_scans" => NULL,
                                        "special_price" => NULL
                                    )
                                );
                                $flag = true;
                            }
                        } else {
                            //If the product is not registered in the chain.
                            array_push(
                                $product_differences[$index]['comparations'],
                                array(
                                    "chain_id" => $chains[$key],
                                    "chain" => $current_chain->name,
                                    "chain_price" => NULL,
                                    "grammage" => NULL,
                                    "unit" => NULL,
                                    "description" => NULL,
                                    "barcode" => NULL,
                                    "compared_price" => NULL,
                                    "difference" => NULL,
                                    "percentage" => NULL,
                                    "total_of_scans" => NULL,
                                    "special_price" => NULL
                                )
                            );
                            $flag = true;
                        }
                    } else {
                        //If the product is not registered in the chain.
                            array_push(
                                $product_differences[$index]['comparations'],
                                array(
                                    "chain_id" => NULL,
                                    "chain" => NULL,
                                    "chain_price" => NULL,
                                    "grammage" => NULL,
                                    "unit" => NULL,
                                    "description" => NULL,
                                    "barcode" => NULL,
                                    "compared_price" => NULL,
                                    "difference" => NULL,
                                    "percentage" => NULL,
                                    "total_of_scans" => NULL,
                                    "special_price" => NULL
                                )
                            );
                            $flag = true;
                    }
                }

                //Sum all the prices for each product
                if($flag == false){
                    $complete_comparations += 1;
                    // totals some prices
                    foreach($total_products_some_prices as $key => $value) {
                        $total_products_some_prices[$key] += $total_products_some_price[$key];
                    }

                    foreach($total_price_some_prices as $key => $value) {
                        $total_price_some_prices[$key] += $total_price_some_price[$key];
                    }

                    // totals cheaper 3b
                    foreach($total_cheaper_products_3b as $key => $value) {
                        $total_cheaper_products_3b[$key] += $total_products_3b[$key];
                    }

                    foreach($total_cheaper_price_3b as $key => $value) {
                        $total_cheaper_price_3b[$key] += $total_price_3b[$key];
                    }

                    // totals cheaper other chain
                    foreach($total_cheaper_products_another_chain as $key => $value) {
                        $total_cheaper_products_another_chain[$key] += $total_products_cheaper[$key];
                    }
                    foreach($total_cheaper_price_another_chain as $key => $value) {
                        $total_cheaper_price_another_chain[$key] += $total_price_cheaper[$key];
                    }
                }
            }
            $index++;
        }

        if(null !== $request->input('csv') && $request->input('csv') === true) {
            $result = $this->prepareDataCSVReport($chains, $product_differences);
            $callback = $this->writerContentCallBack($result);

            $time = time();
            $fileName = "marcas-$time.csv";
            return response()->streamDownload($callback, $fileName);
        }

        return response()->json(array(
            "resume" => array(
                "complete_comparations" => $complete_comparations,
                "cheaper_3b" => [
                    "chains" => $chains_more_expensive,
                    "total_products" => $total_cheaper_products_3b,
                    "total_price" => $total_cheaper_price_3b,
                    "total_price_3b" => $total_cheaper_3b[0] !== 0 ? $total_cheaper_3b[0] : (isset($total_cheaper_3b[1]) ? $total_cheaper_3b[1] : 0)
                ],
                "cheaper_another_chain" => [
                    "chains" => $chains_cheaper,
                    "total_products" => $total_cheaper_products_another_chain,
                    "total_price" => $total_cheaper_price_another_chain,
                    "total_price_3b" => $total_more_expensive_price_3b[0] !== 0 ? $total_more_expensive_price_3b[0] : (isset($total_more_expensive_price_3b[1]) ? $total_more_expensive_price_3b[1] : 0)
                ],
                "some_prime" => [
                    "chains" => $chains_some_prices,
                    "total_products" => $total_products_some_prices,
                    "total_price" => $total_price_some_prices,
                    "total_price_3b" => $total_some_prices_3b[0] !== 0 ? $total_some_prices_3b[0] : (isset($total_some_prices_3b[1]) ? $total_some_prices_3b[1] : 0)
                ]
            ),
            "count" => count($product_differences),
            "product_differences" => $product_differences
        ));
    }

    /**
     * Prepare data to generate csv report
     *
     * @param $chains Array Chains in comparation
     * @param $product_differences Array Products comparated
     * @return Array
     */
    public function prepareDataCSVReport($chains, $product_differences)
    {
        $result = [];
        $comparation_results = [];

        foreach($chains as $chain) {
            foreach($this->chains_headings as $chain_header) {
                $current_chain = Chains::where("id", $chain)->first();
                if ($chain_header === 'Precio de venta') {
                    $chain_header = $chain_header . ' ' . $current_chain->name;
                }
                array_push($this->threeb_headings[0], $chain_header);
            }
        }

        foreach ($product_differences as $key_product => $product) {
            $result[] = [
                'item' => $product['item'],
                'keycode' => $product['keycode'],
                'barcode' => $product['barcode'],
                'description' => $product['description'],
                'gr' => $product['gr'],
                'unit_quantity' => $product['unit_quantity'],
                'type' => $product['type'],
                'status' => $product['status'],
                'price' => '$ ' . number_format($product['price'], 2)
            ];

            foreach($product['comparations'] as $key => $comparation) {
                $result[$key_product]['price_chain_' . $key] = '$ ' . number_format($comparation['chain_price'],2);
                $result[$key_product]['grammage_' . $key] = $comparation['grammage'];
                $result[$key_product]['unit_' . $key] = $comparation['unit'];
                $result[$key_product]['description_' . $key] = $comparation['description'];
                $result[$key_product]['barcode_' . $key] = $comparation['barcode'];
                $result[$key_product]['compared_price_' . $key] = $comparation['compared_price'] ? '$ ' . $comparation['compared_price'] : '$ 0.00';
                $result[$key_product]['difference_' . $key] = $comparation['difference'] ? '$ ' . $comparation['difference'] : '$ 0.00';
                $result[$key_product]['percentage_' . $key] = $comparation['percentage'];
                $result[$key_product]['total_of_scans_' . $key] = $comparation['total_of_scans'];
                $result[$key_product]['special_price_' . $key] = $comparation['special_price'];
            }
        }
        $result = array_merge($this->threeb_headings, $result);

        return $result;
    }

    /**
     * Write CSV content
     *
     * @param Equivalences $array
     * @return Response
     */
    public function writerContentCallBack(array $content)
    {
        return function () use ($content) {
            $csv = Writer::createFromPath("php://temp", "r+");
            foreach ($content as $item) {
                $csv->insertOne($item);
            }
            echo $csv->getContent();

            flush();
        };
    }
}
