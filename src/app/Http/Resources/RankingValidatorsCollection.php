<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

use App\Scans;


class RankingValidatorsCollection extends ResourceCollection
{
    private $pagination;

    /**
     * RankingCollection constructor.
     */
    public function __construct($resource)
    {
        $this->pagination = [
            'total' => $resource->total(),
            'count' => $resource->count(),
            'per_page' => $resource->perPage(),
            'current_page' => $resource->currentPage(),
            'total_pages' => $resource->lastPage()
        ];

        $resource = $resource->getCollection();
        parent::__construct($resource);
    }

    /**
     * Transform the resource collection into an array.
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
            'data' => [
                'users' => $user,
                'scans' => [
                    'total' => $totalScanCountHist,
                    'validated' => $validScanCountHist,
                    'filtered' => $totalScanCountDynamic,
                    'filtered_validated' => $validScanCountDynamic
                ] 
            ],
            'pagination' => $this->pagination
        ];
    }
}
