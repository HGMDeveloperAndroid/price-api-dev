<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductsReporting extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $brand = $this->brand()->first();
        $unit = $this->unit()->first();
        $group = $this->group()->first();
        $line = $this->line()->first();

        return [
            'id' => $this->id,
            'photo' => $this->picture_path ?? '-',
            'product' => $this->name ?? '',
            'barcode' => $this->barcode,
            'created_at' => $this->created_at ? $this->created_at->format('d/M/Y') : null,
            'updated_at' => $this->updated_at ? $this->updated_at->format('d/M/Y') : null,
            'grammage_quantity' => $this->unit_quantity ?? 0,
            'unit' => $unit->name ?? '',
            'id_unit' => $unit->id ?? '',
            'type' => $this->type ?? '',
            'brand' => $brand->name ?? '',
            'id_brand' => $brand->id ?? '',
            'group' => $group->name ?? '',
            'id_group' => $group->id ?? '',
            'line' => $line->name ?? '',
            'id_line' => $line->id ?? '',
            'highest_price' => $this->getMaxPrice($this->barcode),
            'lower_price' => $this->getMinPrice($this->barcode),
            'promotion_lower_price' => $this->getMinPriceWithPromotion($this->barcode),
            'last_price' => $this->getLastPrice($this->barcode),
            'date_last_price' => date('d/M/Y', strtotime($this->getDateLastPrice($this->barcode))),
        ];
    }
}
