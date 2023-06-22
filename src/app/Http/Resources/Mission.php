<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class Mission extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request)
    {
        $createdBy = new ItemUser($this->createdBy()->first());
        $regions = $this->regions()->get();

        if ($this->scans()->count()) {
            $scans = true;
        } else {
            $scans = false;
        } 
        
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'mission_points' => $this->mission_points,
            'capture_points' => $this->capture_points,
            'start_date' => date('d/M/Y', strtotime($this->start_date)),
            'end_date' => date('d/M/Y', strtotime($this->end_date)),
            'regions' => $regions,
            'scans' => $scans,
            'created_by' => $createdBy
        ];
    }
}
