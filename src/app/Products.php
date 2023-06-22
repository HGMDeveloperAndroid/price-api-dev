<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use app\stores;

class Products extends Model
{
    use softDeletes;

    protected $fillable = ['name',
        'barcode', 'price', 'min_price', 'max_price',
        'id_unit', 'id_group', 'id_line', 'id_brand',
        'type', 'is_enable', 'unit_quantity'
    ];

    public function unit()
    {
        return $this->belongsTo('App\Units', 'id_unit', 'id');
    }

    public function group()
    {
        return $this->belongsTo('App\Groups', 'id_group', 'id');
    }

    public function line()
    {
        return $this->belongsTo('App\Lines', 'id_line', 'id');
    }

    public function brand()
    {
        return $this->belongsTo('App\Brands', 'id_brand', 'id');
    }

    public function scans()
    {
        return $this->hasMany('App\Scans', 'barcode', 'barcode');
    }

    public function getMaxPrice($barcode)
    {
        $query = Scans::where('is_rejected', 0)
            ->where('is_valid', 1)
            ->where('barcode', $barcode);

        return $query->max('price')?? rand(14.99, 349);
    }

    public function getMinPrice($barcode)
    {
        $query = Scans::where('is_rejected', 0)
            ->where('is_valid', 1)
            ->where('barcode', $barcode);

//        dd($query->toSql());
        return $query->min('price') ?? rand(14.99, 349.00);
    }

    public function getMinPriceWithPromotion($barcode)
    {
        $query = Scans::where('is_rejected', 0)
            ->where('is_valid', 1)
            ->where('special_price', 1)
            ->where('barcode', $barcode);

        return $query->min('price') ?? '-';
    }

    public function getLastPrice($barcode)
    {
        $query = Scans::select('price')
            ->where('is_rejected', 0)
            ->where('is_valid', 1)
            ->where('barcode', $barcode)
        ->orderBy('capture_date', 'desc');

        $result = $query->first();
        return  $result->price ?? rand(14.99, 349.00);
    }

    public function getDateLastPrice($barcode)
    {
        $query = Scans::select('capture_date')
            ->where('is_rejected', 0)
            ->where('is_valid', 1)
            ->where('barcode', $barcode)
            ->orderBy('capture_date', 'desc');

        $result = $query->first();
        return $result->capture_date ?? '-';
    }

    public static function getScansProducts($barcodes)
    {
        $scans = explode(',', $barcodes);
        $query = Scans::
            select(
                'scans.special_price',
                'scans.barcode',
                'scans.price',
                'scans.unit_price',
                'scans.id_store',
                'stores.name as store',
                'scans.capture_date',
                'products.name',
                'products.id'
            )
            ->join('products', 'scans.barcode', '=', 'products.barcode' )
            ->join('stores', 'scans.id_store', '=', 'stores.id' )
            ->whereIn('scans.barcode', $scans)
            ->groupBy(
                'scans.special_price',
                'scans.barcode',
                'scans.price',
                'scans.unit_price',
                'scans.id_store',
                'stores.name',
                'scans.capture_date',
                'products.name',
                'products.id'
            );

        return $query;
    }

    public static function getScansStore($barcodes)
    {
        $scans = explode(',', $barcodes);
        $query = Scans::
            addselect(
                'stores.name'
            )
            ->join('stores', 'scans.id_store', '=', 'stores.id' )
            ->whereIn('scans.barcode', $scans)
            ->groupBy(
                'stores.name'
            );

        return $query;
    }  

    public static function getScansPrice($barcodes)
    {
        $scans = explode(',', $barcodes);
        $query = Scans::
            select(
                'scans.id_store',
                'scans.special_price',
                'scans.barcode',
                'scans.price',
                'scans.unit_price',
                'scans.capture_date'
            )
            ->whereIn('scans.barcode', $scans)
            ->groupBy(
                'scans.id_store',
                'scans.special_price',
                'scans.barcode',
                'scans.price',
                'scans.unit_price',
                'scans.capture_date'
            )
            ->orderBy('scans.id_store', 'ASC');

        return $query;
    }

    public static function getPriceForStores($barcodes, $name, $start, $end)
    {
        $barcodes_array = explode(',', $barcodes);

        $query = Scans::
            select(
                'scans.id AS scan_id',
                'scans.special_price',
                'scans.price',
                DB::raw('(scans.price / products.unit_quantity) as unit_price'),
                'scans.capture_date'
            )
            ->join('stores', 'stores.id', 'scans.id_store')
            ->join('products', 'products.barcode', 'scans.barcode')
            ->whereIn('scans.barcode', $barcodes_array)
            ->where('stores.name', $name)
            ->where('scans.is_valid', 1)
            ->where('scans.is_rejected', 0)
            ->orderBy('stores.name', 'ASC')
            ->orderBy('scans.capture_date', 'DESC');

        if ($start && $end) {
            $start_date = \Carbon\Carbon::parse($start)->startOfDay();
            $end_date = \Carbon\Carbon::parse($end)->endOfDay();
            $query->whereBetween('scans.capture_date', [$start_date, $end_date]);
        }

        return $query;
    }
}
