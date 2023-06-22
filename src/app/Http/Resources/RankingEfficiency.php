<?php

namespace App\Http\Resources;

use App\Scans;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;


class RankingEfficiency extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        if($this->captures_made == 0) {
            $efficiency = 0;
        } else {
            $efficiency = ($this->validated_captures * 100) / $this->captures_made;
        }

        $zona = DB::table('zone_users')->select('zones.name')
            ->join('zones', 'zones.id', 'zone_users.id_zone')
            ->where('zone_users.id_user', $this->id)->first();
        $region = '-';
        if ($zona) {
            $region = $zona->name;
        }

        return [
            'name' => $this->first_name . " " . $this->last_name . " " . $this->mother_last_name,
            'employee_number' => $this->employee_number,
            'efficiency' => $efficiency,
            'captures_made' => $this->captures_made,
            'validated_captures' => $this->validated_captures,
            'points' => $this->points ?? 0,
            'region' => $region ?? '-',
        ];
    }
}
