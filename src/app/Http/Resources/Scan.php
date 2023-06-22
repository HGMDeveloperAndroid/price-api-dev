<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Chains;

class Scan extends JsonResource
{
    public function toArray($request)
    {
        $store = $this->store()->first();
        $mission = new Mission($this->mission()->first());
        $product = new Product($this->product()->first());
        $author = new ItemUser($this->author()->first());
        $reviewedBy = new ItemUser($this->reviewed()->first());
        $pictures = $this->pictures()->get();
        $id_chain = Chains::where('name', $store->name)->first();

        return [
            'id' => $this->id,
            'idProduct' => $this->id_product,
            'barcode' => $this->barcode,
            'price' => $this->price,
            'special_price' => $this->special_price,
            'comments' => $this->comments,
            'capture_date' => $this->capture_date,
            'reception_date' => $this->reception_date,
            'mission' => $mission,
            'product' => $product,
            'scanned_by' => $author,
            'reviewed' => $reviewedBy,
            'store' => $store,
            'pictures' => $pictures,
            'history' => [
                'recent_price' => $this->currentPrice($this->barcode),
                'max_price' => $this->maxPrice($this->barcode),
                'min_price' => $this->minPrice($this->barcode),
                'min_price_with_promotion' => $this->minPriceWithPromotion($this->barcode)
            ],
            'status' => $this->getStatus(),
            'id_chain' => $id_chain->id
        ];
    }
}
