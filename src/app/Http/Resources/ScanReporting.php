<?php

namespace App\Http\Resources;

use App\Repositories\ReportingRepository;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Products;
use Illuminate\Support\Facades\DB;

class ScanReporting extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        $product = $this->product()->first();
        $brand = null !== $product ? $product->brand()->first() : null;
        $store = $this->store()->first();
        $scannedBy = DB::table('users')->where('id', $this->id_scanned_by)->first();
        $reviewedBy = DB::table('users')->where('id', $this->id_reviewed_by)->first();
        $pictures = $this->pictures()->first();

        $unitPrice = 0.0;
        $unit = null;
        $group = null;
        $line = null;
        if (isset($product)) {
            $unitPrice =  $product->unit_quantity != 0 ? $this->price / $product->unit_quantity : 0;
            $unit = $product->unit()->first();
            $group = $product->group()->first();
            $line = $product->line()->first();
        }

        $author = '';
        if (isset($scannedBy)) {
            $author = $scannedBy->first_name . ' ' . $scannedBy->last_name . ' ' . $scannedBy->mother_last_name;
        }

        $reviewed = '-';
        if (isset($reviewedBy)) {
            $reviewed = $reviewedBy->first_name . ' ' . $reviewedBy->last_name . ' ' . $reviewedBy->mother_last_name;
        }

        if($unitPrice == 0) {
            $unitPrice = 0.01;
        }

        $product_creation_date = Products::select('created_at')->where('barcode', $this->barcode)->first();

        if ($product_creation_date) {
            $product_creation_date = $product_creation_date->created_at->format('d/M/Y');
        } else {
            $product_creation_date = '-';
        }

        return [
            'scan' => $this->id,
            'photo_main' => $pictures->product_picture ?? '-',
            'photo_price' => $pictures->shelf_picture ?? '-',
            'capture_date' => date('d/M/Y', strtotime($this->capture_date)),
            'barcode' => $this->barcode,
            'product' => $product->name ?? '',
            'brand' => $brand->name ?? '',
            'chain' => $store->name ?? '',
            'branch' => $store->address ?? '',
            'capture_price' => $this->price,
            'unit_price' => $unitPrice,
            'grammage_quantity' => $product->unit_quantity ?? 0,
            'unit' => $unit->name ?? '',
            'type' => $product->type ?? '',
            'group' => $group->name ?? '',
            'line' => $line->name ?? '',
            'created_date' => date('d/M/Y', strtotime($this->created_at)),
            'highest_price' => $this->where('barcode', $this->barcode)->where('is_valid', 1)->where('is_rejected', 0)->max('price') ?? rand(14, 349),
            'lower_price' => $this->where('barcode', $this->barcode)->where('is_valid', 1)->where('is_rejected', 0)->min('price') ?? rand(14, 349),
            'promotion_lower_price' => $this->where('barcode', $this->barcode)->where('special_price', 1)->min('price') ?? rand(14, 349),
            'scanned_by' => $author,
            'reviewed' => $reviewed,
            'status' => $this->getStatus(),
            'is_promotion' => $this->special_price,
            'product_created_date' =>  $product_creation_date,
        ];
    }
}
