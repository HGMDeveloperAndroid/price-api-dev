<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class Scans extends Model
{
    use SoftDeletes;

    protected $dates = ['deleted_at'];
    protected $fillable = ['is_rejected', 'is_valid', 'is_enable'];

    public function mission()
    {
        return $this->belongsTo('App\Missions', 'id_mission', 'id');
    }

    public function product()
    {
        return $this->belongsTo('App\Products', 'barcode', 'barcode');
    }

    public function author()
    {
        return $this->belongsTo('App\User', 'id_scanned_by', 'id');
    }

    public function reviewed()
    {
        return $this->belongsTo('App\User', 'id_reviewed_by', 'id');
    }

    public function store()
    {
        return $this->belongsTo('App\Store', 'id_store', 'id');
    }

    public function pictures()
    {
        return $this->hasOne('App\ScanPictures', 'id_scan', 'id');
    }

    public function scopegetStatus()
    {
        $status = '';
        if ($this->is_rejected == 0 && $this->is_valid == 1) {
            $status = 'Validada';
        } elseif ($this->is_rejected == 0 && $this->is_valid == 0) {
            $status = 'Pendiente';
        } elseif ($this->is_rejected == 1 || $this->is_valid == 0) {
            $status = 'Rechazada';
        }

        return $status;
    }

    public function scopeCurrentPrice($query, $barcode)
    {
        $query->select('price', 'capture_date')
            ->where('barcode', $barcode)
            ->where('is_valid', 1)
            ->where('is_rejected', 0);
        $query->orderBy('capture_date', 'desc');
        return $query->first();
    }

    public function scopeMaxPrice($query, $barcode)
    {
        $query->select('price', 'capture_date')
            ->where('barcode', $barcode)
            ->where('is_valid', 1)
            ->where('is_rejected', 0);
        $query->orderBy('price', 'desc');
        return $query->first();
    }

    public function scopeMinPrice($query, $barcode)
    {
        $query->select('price', 'capture_date')
            ->where('barcode', $barcode)
            ->where('is_valid', 1)
            ->where('is_rejected', 0);
        $query->orderBy('price', 'asc');

        return $query->first();
    }

    public function scopeMinPriceWithPromotion($query, $barcode)
    {
        $query->select('price', 'capture_date')
            ->where('barcode', $barcode)
            ->where('special_price', 1)
            ->where('is_valid', 1)
            ->where('is_rejected', 0);
        $query->orderBy('price', 'asc');

        return $query->first();
    }


    public function scopeCurrentPriceByStore($query, $barcode, $idStore)
    {
        $query->select('price', 'capture_date')
            ->where('barcode', $barcode)
            ->where('id_store', $idStore)
            ->where('scans.is_valid', 1)
            ->where('scans.is_rejected', 0)
            ->orderBy('capture_date', 'desc');
        return $query->first();
    }

    public function scopeMaxPriceByStore($query, $barcode, $idStore)
    {
        $query->select('price', 'capture_date')
            ->where('barcode', $barcode)
            ->where('id_store', $idStore)
            ->where('scans.is_valid', 1)
            ->where('scans.is_rejected', 0)
            ->orderBy('price', 'desc');
        return $query->first();
    }

    public function scopeMinPriceByStore($query, $barcode, $idStore)
    {
        $query->select('price', 'capture_date')
            ->where('barcode', $barcode)
            ->where('id_store', $idStore)
            ->where('scans.is_valid', 1)
            ->where('scans.is_rejected', 0)
            ->orderBy('price', 'asc');

        return $query->first();
    }

    public function scopeValidates($query, $request)
    {
        $query->where('is_rejected', 0)->where('is_valid', 1);
        
        if ($request->filled(['from', 'to'])) {
            $query->whereDate('capture_date', '>=', $request->from)
                ->whereDate('capture_date', '<=', $request->to);
        }

        return $query->count();
    }

    public function scopeAllValidates($query, $request)
    {
        $query->where('is_rejected', 0)->where('is_valid', 1);

        if ($request->filled(['from', 'to'])) {
            $query->whereDate('capture_date', '>=', $request->from)
                ->whereDate('capture_date', '<=', $request->to);
        }

        return $query->count();
    }

    public function scopeTotalDate($query, $request)
    {
        if ($request->filled('textSearch')) {
           $query->join('users', 'users.id', 'scans.id_reviewed_by')
               ->whereRaw("(first_name = '" . $request->textSearch . "' or last_name like '" . $request->textSearch . "' or mother_last_name like '". $request->textSearch . "' or employee_number like '". $request->textSearch ."')");
        }

        if ($request->filled(['from', 'to'])) {
            $query->whereDate('validation_date', '>=', $request->from)
                ->whereDate('validation_date', '<=', $request->to);
        }

        if ($request->filled(['mission'])) {
            $query->where('id_mission', $request->mission);
        }

        return $query->count();
    }

    public function scopeValidDate($query, $request)
    {
        $query->where('is_valid', 1);

        if ($request->filled(['from', 'to'])) {
            $query->whereDate('validation_date', '>=', $request->from)
                ->whereDate('validation_date', '<=', $request->to);
        }

        if ($request->filled(['mission'])) {
            $query->where('id_mission', $request->mission);
        }

        return $query->count();
    }

    public function scopeTotalUser($query, $id)
    {
        $query->where('id_reviewed_by', $id);
        
        return $query->count();
    }

    public function scopeValidUser($query, $id)
    {
        $query->where('id_reviewed_by', $id)->where('is_valid', 1);
        
        return $query->count();
    }

    public function scopeValidTotal($query)
    {
        $query->where('is_valid', 1);
        
        return $query->count();
    }

    public function scopevalidatedCaptures($query, $request, $id_user, $id_mission)
    {
        $query->where('is_valid', 1)
            ->where('id_scanned_by', $id_user)
            ->where('id_mission', $id_mission)
            ->where('is_rejected', 0);

        if ($request->filled(['from', 'to'])) {
            $query->whereDate('capture_date', '>=', $request->from)
                ->whereDate('capture_date', '<=', $request->to);
        }

        return $query->count();
    }

    public function scopeUsers($query, $request, $validScanCountDynamic)
    {
        $sqlTextSearch = "is_rejected = 0 and is_valid = 1 and users.id not in(370, 371) and users.deleted_at is null";

        if ($request->filled('textSearch')) {
           $sqlTextSearch = $sqlTextSearch . " and (first_name like '%" . $request->textSearch . "%' or last_name like '%" . $request->textSearch . "%' or mother_last_name like '%". $request->textSearch . "%' or employee_number like '%". $request->textSearch ."%')";
        }

        $query->select(
            DB::raw("DISTINCT users.id, CONCAT(users.first_name, ' ', COALESCE(users.last_name, ' '), ' ', COALESCE(users.mother_last_name, '')) AS fullname, users.employee_number, COUNT(*) AS userValidates, (COUNT(*) * 100) / " . $validScanCountDynamic ." AS efficiency, (select count(*) from scans sc where sc.id_reviewed_by = users.id) as totalUser, (select count(*) from scans sc where sc.id_reviewed_by = users.id and sc.is_valid = 1 and sc.deleted_at IS NULL) as validUser"
            ))
            ->join('users', 'users.id', 'scans.id_reviewed_by')
            ->join('missions', 'scans.id_mission', 'missions.id')
            ->whereRaw($sqlTextSearch)
            ->groupByRaw(DB::raw(
                "CONCAT(users.first_name, ' ', COALESCE(users.last_name, ' '), ' ', COALESCE(users.mother_last_name, '')), users.id, users.employee_number"
            ));

        if ($request->filled(['from', 'to'])) {
            $query->whereDate('scans.validation_date', '>=', $request->from)
                ->whereDate('scans.validation_date', '<=', $request->to);
        }

        if ($request->filled('mission')) {
            $query->where('missions.id', $request->mission);
        }

        $query->orderBy('userValidates', 'desc');

        return $query->get();
    }

    public function scopePoints($query, $request, $id_user)
    {
        if(!is_null($request->mission)) {
            $query->select(DB::raw('((COUNT(scans.id) * missions.capture_points) + missions.mission_points) as point'))
                ->join('missions', 'scans.id_mission', 'missions.id')
                ->where('is_valid', 1)
                ->where('id_scanned_by', $id_user)
                ->where('id_mission', $request->mission)
                ->where('is_rejected', 0);

            if ($request->filled(['from', 'to'])) {
                $query->whereDate('capture_date', '>=', $request->from)
                    ->whereDate('capture_date', '<=', $request->to);
            }

            return $query->count();
        } else {
            $query->select('scans.id_mission')
            ->where('id_scanned_by', $id_user)
            ->where('is_valid', 1)
            ->where('is_rejected', 0)
            ->groupBy('scans.id_mission');

            $count_scan = 0;

            foreach ($query->get() as $point) {
                $scan = Scans::select(DB::raw('((COUNT(scans.id) * missions.capture_points) + missions.mission_points) as point'))
                    ->join('missions', 'scans.id_mission', 'missions.id')
                    ->where('is_valid', 1)
                    ->where('id_scanned_by', $id_user)
                    ->where('id_mission', $point->id_mission)
                    ->where('is_rejected', 0);

                    if ($request->filled(['from', 'to'])) {
                        $scan->whereDate('capture_date', '>=', $request->from)
                            ->whereDate('capture_date', '<=', $request->to);
                    }

                $count_scan = $scan->count() + $count_scan;
            }

            return $count_scan;
        }
    }

    public function scopeUserPoints($query, $request, $id_user)
    {
        if(!is_null($request->mission)) {
            $user_point = UserPoints::where('id_user', $id_user);

            return $user_point->count();
        } else {
            $query->select('scans.id_mission')
            ->where('id_scanned_by', $id_user)
            ->where('is_valid', 1)
            ->where('is_rejected', 0)
            ->groupBy('scans.id_mission');

            $count_point = 0;

            foreach ($query->get() as $point) {
                $user_point = UserPoints::where('id_user', $id_user)
                    ->where('id_mission', $point->id_mission);

                $count_point = $user_point->count() + $count_point;
            }

            return $count_point;
        }
    }

    public function scopeTotalPending($query)
    {
        $query->where('is_valid', 0)
            ->where('is_rejected', 0);

        return $query->count();
    }

    public function scopeTotalPendingProduct($query, $param_withProduct)
    {
        $query->where('is_valid', 0)
            ->where('is_rejected', 0);

        $query->when($param_withProduct, function ($q, $withProduct) {
            if ($withProduct === 'yes') {
                $q->has('product');
            } elseif ($withProduct === 'no') {
                $q->doesnthave('product');
            }
        });

        return $query->count();
    }
}
