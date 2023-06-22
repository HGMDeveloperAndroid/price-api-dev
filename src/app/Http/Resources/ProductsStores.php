<?php

namespace App\Http\Resources;

use App\Products;
use App\Scans;
use Illuminate\Http\Request;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductsStores extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $start = $request->get('startdate', null);
        $end = $request->get('enddate', null);
        $stores = Products::getScansStore($this->barcode)->get();

        foreach($stores as $key => $store) {        
            $stores[$key]["prices"]= Products::getPriceForStores($this->barcode, $store->name, $start, $end)->get();
        }
 
        return [
            'id' => $this->id,
            'name' => $this->name ?? '',
            'barcode' => $this->barcode,
            'stores' => $stores  
        ];
    }
}
