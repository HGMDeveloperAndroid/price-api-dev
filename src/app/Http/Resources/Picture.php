<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class Picture extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'id_scan' => $this->id_scan,
            'product_picture' => $this->product_picture,
            'shelf_picture' => $this->shelf_picture,
            'promo_picture' => $this->promo_picture
        ];
    }
}
