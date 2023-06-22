<?php

namespace App\Http\Resources;

use App\Scans;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RankingValidators extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $totalScanCountDynamic = Scans::TotalDate($request);
        $validScanCountDynamic = Scans::ValidDate($request);
        $totalScanCountHist = Scans::count();
        $validScanCountHist = Scans::ValidTotal();
        $user = Scans::Users($request, $validScanCountDynamic);

        return [
            'users' => [
                $user
            ],
            'scans' => [
                'totalScanCountDynamic' => $totalScanCountDynamic,
                'validScanCountDynamic' => $validScanCountDynamic,
                'totalScanCountHist' => $totalScanCountHist,
                'validScanCountHist' => $validScanCountHist
            ] 
        ];
    }
}
