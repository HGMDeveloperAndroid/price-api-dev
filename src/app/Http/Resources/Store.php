<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Chain as ChainResource;
use App\Chains;

class Store extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        $originalName = $this->name;
        $storeName = Chains::select('name')->where('alias', 'like', "%{$originalName}%")->first();
        $Nostore = Chains::select('name')->where('alias', 'like', "%No store%")->first();

        if ($storeName) {
            $storeName = $storeName->name;
        } else {
            $storeName = $Nostore->name;
        }

        return [
            'id' => $this->id,
            'name' => $storeName,
            'address' => $this->address,
            'location' => $this->location,
        ];
    }
}
