<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ItemScan extends JsonResource
{
    public function toArray($request)
    {
        $product = $this->product()->pluck('name', 'id');

        return [
            'id' => $this->id,
            'barcode' => $this->barcode,
            'id_mission' => $this->id_mission,
            'product' => $product,
        ];
    }


}
