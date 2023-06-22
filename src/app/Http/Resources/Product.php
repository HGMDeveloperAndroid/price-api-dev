<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Unit as UnitResource;
use App\Http\Resources\Group as GroupResource;
use App\Http\Resources\Line as LineResource;
use App\Http\Resources\Brand as BrandResource;
//use App\Http\Resources\Unit as UnitResource;

class Product extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        $unit = new UnitResource($this->unit()->first());
        $group = new GroupResource($this->group()->first());
        $line = new LineResource($this->line()->first());
        $brand = new BrandResource($this->brand()->first());

        return [
            'id' => $this->id,
            'name' => $this->name,
            'barcode' => $this->barcode,
            'quantity' => $this->unit_quantity,
            'unit' => $unit,
            'group' => $group,
            'line' => $line,
            'brand' => $brand,
            'type' => $this->type,
            'is_enable' => $this->is_enable,
            'picture_path' => $this->picture_path,
            'highest_price' => $this->getMaxPrice($this->barcode),
            'lower_price' => $this->getMinPrice($this->barcode),
        ];
    }
}
