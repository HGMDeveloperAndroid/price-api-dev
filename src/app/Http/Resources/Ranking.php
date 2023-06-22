<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Ranking extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $scanCaptures = $this->scans();
        $regions = $this->regions()->select('zones.id', 'zones.name')->get();
        $totalValid = $scanCaptures->Validates($request);
        $efficiency = number_format($totalValid/ $this->total, 2);
        return [
            'id' => $this->id,
            'fullname' => $this->fullname,
            'employee_number' => $this->employee_number,
            'regions' => $regions,
            'missions' => [
                'id' => $this->id_mission,
                'title' => $this->title
            ],    
            'totalScanners' => $this->total,
            'totalValid' => $totalValid,
            'efficiency' => $efficiency * 100
        ];
    }
}
