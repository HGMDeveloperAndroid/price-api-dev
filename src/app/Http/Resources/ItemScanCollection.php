<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use App\Scans;


class ItemScanCollection extends ResourceCollection
{
    private $pagination;

    /**
     * RoleCollection constructor.
     * @param $resource
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
        $total_pending = Scans::TotalPending();
        $with_product = Scans::TotalPendingProduct('yes');
        $without_product = Scans::TotalPendingProduct('no');

        return [
            'data' => $this->collection,
            'total_pending' => $total_pending,
            'with_product' => $with_product,
            'without_product' => $without_product,
            'pagination' => $this->pagination
        ];
    }
}
