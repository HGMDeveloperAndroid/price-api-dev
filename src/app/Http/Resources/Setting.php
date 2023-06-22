<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Languages;
use Illuminate\Support\Facades\DB;

class Setting extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $languague = Languages::where('id', $this->lang_id)->first();
        return [
            'logo_path' => $this->logo_path,
            'language' => new Language($languague)
        ];
    }
}
