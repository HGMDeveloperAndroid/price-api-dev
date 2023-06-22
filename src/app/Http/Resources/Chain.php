<?php


namespace App\Http\Resources;


use Illuminate\Http\Resources\Json\JsonResource;

class Chain extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'alias' => $this->alias,
            'logo_path' => $this->logo_path
        ];
    }

}
