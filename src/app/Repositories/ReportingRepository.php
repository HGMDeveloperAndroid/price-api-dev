<?php
/**
 * @author Pedro Rojas <pedro.rojas@gmail.com>
 */

namespace App\Repositories;

use App\Products;
use App\Scans;
use App\User;
use App\Chains;
use App\Store;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use League\Csv\Writer;
use mysqli_result;
use PHPUnit\Util\Exception;
use Grimzy\LaravelMysqlSpatial\Connectors\ConnectionFactory;
use Illuminate\Container\Container;
//use Stubs\PDOStub;
use PDO;
use PDOException;

class ReportingRepository
{
    public function ScansView(Request $request)
    {
        $query = Scans::query();

        if ($request->filled('items')) {
            $query->whereIn('scans.id', $request->items);

            return $query;
        }

        $query->when($request->textSearch, function ($q, $textSearch) {
            $q->leftJoin('products', 'products.id', 'scans.id_product')
                ->where('scans.id', $textSearch)
                ->orWhere('scans.barcode', 'like', "%{$textSearch}%")
                ->orWhere('products.name', 'like', "%{$textSearch}%");
        });

        if ($request->filled(['from', 'to'])) {
            $query->whereDate('capture_date', '>=', $request->from)
                ->whereDate('capture_date', '<=', $request->to);
        }

        $query->when($request->withPhoto, function ($q, $withPhoto) {
            if ($withPhoto === 'yes') {
                $q->has('pictures');
            } elseif ($withPhoto === 'no') {
                $q->doesnthave('pictures');
            }
        });

        $query->when($request->is_promotion, function ($q, $isPromotion) {
            if ($isPromotion === 'yes') {
                $q->where('special_price', 1);
            }
            if ($isPromotion === 'no') {
                $q->where('special_price', 0);
            }
        });

        if ($request->filled(['type'])) {
            $type = $request->type;
            $query->whereHas('product', function ($q) use ($type) {
                if ($type === 'MC') {
                    $q->where('type', 'MC');
                }
                if ($type === 'MP') {
                    $q->where('type', 'MP');
                }
            });
        }

        $query->when($request->status, function ($q, $status) {
            if ($status === 'valid') {
                $q->where('is_rejected', 0)->where('is_valid', 1);
            } elseif ($status === 'rejected') {
                $q->where('is_rejected', 1)->where('is_valid', 0);
            } elseif ($status === 'pending') {
                $q->where('is_rejected', 0)->where('is_valid', 0);
            }
        });

        if ($request->input('scanned_by') && !empty($request->input('scanned_by'))) {
            $values = $request->input('scanned_by');
            $query->where('id_scanned_by', $values);
        }

        if ($request->input('reviewed') && !empty($request->input('reviewed'))) {
            $value = $request->input('reviewed');
            $query->where('id_reviewed_by', $value);
        }

        if ($request->input('chain') && !empty($request->input('chain'))) {
            $values = $request->input('chain');
            $chains = Chains::select('name')->whereIn('id', $values)->get();
            $stores = Store::select('id')->whereIn('name', $chains)->get();
            $query->whereIn('id_store', $stores);
        }

        if ($request->input('branch') && !empty($request->input('branch'))) {
            $values = $request->input('branch');
            $query->whereHas('store', function ($qq) use ($values) {
                $qq->whereLike('store.address', $values);
            });
        }
      
        if ($request->filled('brand')) {
            $brand = $request->brand;
            $query->whereHas('product', function ($q) use ($brand) {
                $q->whereIn('id_brand', $brand);
            });
        }

        if ($request->filled('group')) {
            $group = $request->group;
            $query->whereHas('product', function ($q) use ($group) {
                $q->whereIn('id_group', $group);
            });
        }

        if ($request->filled('line')) {
            $line = $request->line;
            $query->whereHas('product', function ($q) use ($line) {
                $q->whereIn('id_line', $line);
            });
        }

        if ($request->filled('unit')) {
            $unit = $request->unit;
            $query->whereHas('product', function ($q) use ($unit) {
                $q->whereIn('id_unit', $unit);
            });
        }

        $query->select('scans.*')->orderBy('scans.id', 'DESC');
        return $query;
    }

    /**
     * @param Collection $scans
     */
    public function scansData(Collection $scans)
    {
        $result = [];
        foreach ($scans as $scan) {
            $product = $scan->product()->first();
            $brand = null !== $product ? $product->brand()->first() : null;
            $store = $scan->store()->first();
            $scannedBy = DB::table('users')->where('id', $scan->id_scanned_by)->first();
            $reviewedBy = DB::table('users')->where('id', $scan->id_reviewed_by)->first();
            $pictures = $scan->pictures()->first();

            $unitPrice = 0.0;
            $unit = null;
            $group = null;
            $line = null;
            if (isset($product)) {
                $unitPrice = $product->unit_quantity != 0 ? $scan->price / $product->unit_quantity : 0;
                $unit = $product->unit()->first();
                $group = $product->group()->first();
                $line = $product->line()->first();
            }

            $author = '-';
            $employee_number = '-';
            $region = '';
            if (isset($scannedBy)) {
                $author = $scannedBy->first_name . ' ' . $scannedBy->last_name . ' ' . $scannedBy->mother_last_name;
                $employee_number = $scannedBy->employee_number;
                $zona = DB::table('zone_users')->select('zones.name')
                    ->join('zones', 'zones.id', 'zone_users.id_zone')
                    ->where('zone_users.id_user', $scannedBy->id)->first();

                $region = '-';
                if ($zona) {
                    $region = $zona->name;
                }
            }

            $reviewed = '-';
            if (isset($reviewedBy)) {
                $reviewed = $reviewedBy->first_name . ' ' . $reviewedBy->last_name . ' ' . $reviewedBy->mother_last_name;
            }

            $product_creation_date = Products::select('created_at')->where('barcode', $scan->barcode)->first();

            $product_created_at = '-';
            if ($product_creation_date) {
                 $product_created_at = $product_creation_date->created_at->format('d/M/Y H:i:s');
            }

            $result[] = [
                'scan' => $scan->id,
                'barcode' => $scan->barcode,
                'chain' => $store->name ?? '',
                'branch' => $store->address ?? '',
                'scanned_employee_number' => $employee_number,
                'scanned_by' => $author,
                'scanned_region' => $region,
                'reviewed' => $reviewed,
                'status' => $scan->getStatus(),
                'comments' => $scan->comments,
                'product' => $product->name ?? '',
                'grammage_quantity' => $product->unit_quantity ?? 0,
                'unit' => $unit->name ?? '',
                'unit_price' => $unitPrice,
                'brand' => $brand->name ?? '',
                'type' => $product->type ?? '',
                'group' => $group->name ?? '',
                'line' => $line->name ?? '',
                //'created_date' => $scan->created_at->format('d/M/Y'),
                'product_created_date' =>  $product_created_at ?? '',
                'created_price' => $scan->price,
                'lower_price' => $scan->where('barcode', $scan->barcode)->where('is_valid', 1)->where('is_rejected', 0)->min('price'),
                'highest_price' => $scan->where('barcode', $scan->barcode)->where('is_valid', 1)->where('is_rejected', 0)->max('price'),
                'capture_date' => date('d/M/Y H:i:s', strtotime($scan->capture_date)),
                'capture_price' => $scan->price,
                'promotion' => $scan->special_price ? 'Si' : 'No'
            ];
        }

        return $result;
    }

    /**
     * Filters for scan report for analyst users.
     *
     * @param Request $request
     * @return $query
     */
    public function scansAnalyst(Request $request)
    {
        $query = Scans::query();

        if ($request->filled('region')) {
            $regions =  DB::table('zone_users')->where('id_zone', $request->region)->get();
            $result = '';
            foreach($regions as $region){
                $result .= $region->id_user . ',';
            }
            $result = explode(',', $result);
            $query->whereIn('id_scanned_by', $result);
        }

        if ($request->filled('mission')) {
            $query->where('id_mission', $request->mission);
        }

        if ($request->filled(['from', 'to'])) {
            $query->whereDate('capture_date', '>=', $request->from)
                ->whereDate('capture_date', '<=', $request->to);
        }

        $query->when($request->status, function ($q, $status) {
            if ($status === 'valid') {
                $q->where('is_rejected', 0)->where('is_valid', 1);
            } elseif ($status === 'rejected') {
                $q->where('is_rejected', 1)->where('is_valid', 0);
            } elseif ($status === 'pending') {
                $q->where('is_rejected', 0)->where('is_valid', 0);
            }
        });

        if ($request->input('scanned_by') && !empty($request->input('scanned_by'))) {
            $values = $request->input('scanned_by');
            $query->where('id_scanned_by', $values);
        }

        $query->select('scans.*')->orderBy('scans.id', 'DESC');
        return $query;
    }

    public function productFilters(Request $request)
    {
        $query = Products::query();

        if ($request->filled('items')) {
            $query->whereIn('id', $request->items);

            return $query;
        }

        $query->when($request->textSearch, function ($q, $textSearch) {
            $q->where('barcode', 'like', "%{$textSearch}%")->whereLike('name', $textSearch);
        });

        if ($request->filled(['from', 'to'])) {
            $query->whereDate('created_at', '>=', $request->from)
                ->whereDate('created_at', '<=', $request->to);
        }

        if ($request->filled(['modifyFrom', 'modifyTo'])) {
            $query->whereDate('updated_at', '>=', $request->modifyFrom)
                ->whereDate('updated_at', '<=', $request->modifyTo);
        }

        $query->when($request->type, function ($q, $type) {
            $q->where('type', $type);
        });

        $query->when($request->brand, function ($qq, $brand) {
            $qq->whereIn('id_brand', $brand);
        });

        $query->when($request->group, function ($qq, $group) {
            $qq->whereIn('id_group', $group);
        });

        $query->when($request->line, function ($qq, $line) {
            $qq->whereIn('id_line', $line);
        });

        $query->when($request->unit, function ($qq, $unit) {
            $qq->whereIn('id_unit', $unit);
        });

        $query->orderBy('name', 'ASC');
        return $query;
    }

    /**
     * Filters for products report for analyst users.
     *
     * @param Request $request
     * @return $query
     */
    public function productFiltersAnalyst(Request $request)
    {
        $query = Products::query();

        $query->when($request->textSearch, function ($q, $textSearch) {
            $q->whereLike('barcode', "%{$textSearch}%")
                ->whereLike('name', "%{$textSearch}%");
        });

        if ($request->filled(['from', 'to'])) {
            $query->whereDate('created_at', '>=', $request->from)
                ->whereDate('created_at', '<=', $request->to);
        }

        $query->when($request->brand, function ($qq, $brand) {
            $qq->where('id_brand', $brand);
        });

        $query->when($request->group, function ($qq, $group) {
            $qq->where('id_group', $group);
        });

        $query->when($request->line, function ($qq, $line) {
            $qq->where('id_line', $line);
        });

        $query->when($request->unit, function ($qq, $unit) {
            $qq->where('id_unit', $unit);
        });

        $query->when($request->type, function ($q, $type) {
            $q->where('type', $type);
        });

        $query->orderBy('name', 'ASC');

        return $query;
    }

    public function productFiltersByBarcode($barcode)
    {
        $query = Products::query();

        $query->where('barcode', $barcode);

        return $query;
    }

    public function productsData(Collection $products)
    {
        $result = [];
        foreach ($products as $product) {
            $brand = $product->brand()->first();
            $unit = $product->unit()->first();
            $group = $product->group()->first();
            $line = $product->line()->first();

            $result[] = [
                'id' => $product->id,
                'photo' => $product->picture_path ?? '-',
                'product' => $product->name ?? '',
                'barcode' => $product->barcode,
                'created_at' => $product->created_at->format('d/M/Y H:i:s'),
                'updated_at' => !$product->updated_at ?? $product->updated_at->format('d/M/Y H:i:s'),
                'grammage_quantity' => $product->unit_quantity ?? 0,
                'unit' => $unit->name ?? '',
                'brand' => $brand->name ?? '',
                'type' => $product->type ?? '',
                'group' => $group->name ?? '',
                'line' => $line->name ?? '',
                'highest_price' => $product->getMaxPrice($product->barcode),
                'lower_price' => $product->getMinPrice($product->barcode),
                'promotion_lower_price' => $product->getMinPriceWithPromotion($product->barcode),
                'last_price' => $product->getLastPrice($product->barcode),
                'date_last_price' => date('d/M/Y H:i:s', strtotime($product->getDateLastPrice($product->barcode)))
            ];
        }

        return $result;
    }

    public function writerContentCallBack(array $content)
    {
        return function () use ($content) {
            $csv = Writer::createFromPath("php://temp", "r+");
            foreach ($content as $item) {
                $csv->insertOne($item);
            }
            echo $csv->getContent();

            flush();
        };
    }

    public function dailyAverage(string $barcode)
    {
        $scans = Scans::selectRaw('AVG(price) avg_price, date(capture_date) capture_day')
            ->where('barcode', $barcode)
            ->groupBy('capture_day');

        return $scans->get();
    }

    public function historySummary($barcode, Request $request)
    {
        $resumeData = [];
        $scans = Scans::select('scans.barcode', 'scans.id_store')
            ->where('barcode', $barcode)
            ->where('scans.is_valid', 1)
            ->where('scans.is_rejected', 0)
            ->groupBy('scans.barcode', 'scans.id_store')
            ->orderBy('capture_date', 'DESC');

        if ($request->filled(['from', 'to'])) {
            $scans->whereDate('capture_date', '>=', $request->from)
                ->whereDate('capture_date', '<=', $request->to);
        }

        foreach ($scans->get() as $item) {
            $store = $item->store()->first();
            $max = $item->maxPriceByStore($item->barcode, $store->id);
            $min = $item->minPriceByStore($item->barcode, $store->id);
            $current = $item->currentPriceByStore($item->barcode, $store->id);
            $avg = ($max->price + $min->price + $current->price) / 3;

            $resumeData[]['summary'] = [
                "storeName" => $store->name,
                "storeId" => $store->id,
                "max" => $max,
                "min" => $min,
                "current" => $current,
                "average" => number_format($avg, 2)
            ];
        }

        return $resumeData;
    }

    public function historyDetails(Request $request, $id)
    {
        $id_stores = explode(",", $id);
        $query = Scans::query();
        $query->selectRaw('scans.id as scan, scans.capture_date as capture_day, scans.price as price, TRUNCATE((scans.price/products.unit_quantity),2) as unit_price')
            ->join('products', 'products.id', 'scans.id_product')
            ->where('scans.is_valid', 1)
            ->where('scans.is_rejected', 0)
            ->where('scans.special_price', 0)
            ->whereIn('scans.id_store', $id_stores);

        $query->when($request->barcode, function ($q, $barcode) {
            $q->where('scans.barcode', $barcode);
        });

        if ($request->filled(['from', 'to'])) {
            $query->whereDate('scans.capture_date', '>=', $request->from)
                ->whereDate('scans.capture_date', '<=', $request->to);
        }

        $query->orderBy('capture_day', 'DESC');

        return $query;
    }

    public function getDetailsPrice($barcode)
    {
        $query = Scans::query();
        $query->where('barcode', $barcode);

        return $query;
    }

    public function getHistoryDetails($barcode)
    {
        $scans = $this->getDetailsPrice($barcode)->get();
        $data = [];
        foreach ($scans as $scan) {
            $store = $scan->store()->first();
            $data[] = [
                'id' => $scan->id,
                'capture_date' => $scan->capture_date,
                'price' => $scan->price,
                'chain' => $store->name ?? '-'
            ];
        }

        return $data;
    }

    public function rankings(Request $request)
    {
        $query = User::query();
        $query
            ->select(
                DB::raw("users.id, users.employee_number, CONCAT(users.first_name, ' ', users.last_name, ' ', users.mother_last_name) AS fullname, COUNT(*) AS total, missions.title, missions.id as id_mission"
                ))
            ->join('scans', 'users.id', 'scans.id_scanned_by')
            ->join('missions', 'scans.id_mission', 'missions.id')
            ->groupByRaw(DB::raw(
                "CONCAT(users.first_name, ' ', users.last_name, ' ', users.mother_last_name), users.id, users.employee_number, missions.title, missions.id"
            ));

        $query->when($request->textSearch, function ($q, $textSearch) {
            $q->where('first_name', $textSearch)
                ->whereLike('last_name', $textSearch)
                ->whereLike('mother_last_name', $textSearch)
                ->whereLike('employee_number', $textSearch);
        });

        if ($request->filled(['from', 'to'])) {
            $query->whereDate('scans.capture_date', '>=', $request->from)
                ->whereDate('scans.capture_date', '<=', $request->to);
        }

        $query->whereHas('regions', function ($q) use ($request) {
            if ($request->filled('region')) {
                $q->where('zones.id', $request->region);
            }
        });

        if ($request->filled('mission')) {
            $query->where('missions.id', $request->mission);
        }

        $query->when('Scanner', function ($q, $role) {
            $q->role($role);
        });
        $query->orderBy('total', 'asc');
//dd($query->toSql());
        return $query;
    }

    public function rankingsValidator(Request $request)
    {
        $query = User::query();

        $sqlTextSearch = "is_rejected = 0 and is_valid = 1 and users.id not in(370, 371) and users.deleted_at is null";

        if ($request->filled('textSearch')) {
           $sqlTextSearch = $sqlTextSearch . " and (first_name like '%" . $request->textSearch . "%' or last_name like '%" . $request->textSearch . "%' or mother_last_name like '%". $request->textSearch . "%' or employee_number like '%". $request->textSearch ."%')";
        }

        $query->select(
                DB::raw("users.id, users.employee_number, CONCAT(users.first_name, ' ', COALESCE(users.last_name, ' '), ' ', COALESCE(users.mother_last_name, '')) AS fullname, COUNT(*) AS valids"
                ))
            ->join('scans', 'users.id', 'scans.id_reviewed_by')
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

        $query->orderBy('valids', 'desc');

        return $query;
    }

    public function countingScansFilter(Request $request)
    {
        $query = Scans::query();

        $query->when($request->textSearch, function ($q, $textSearch) {
            $q->whereHas('author', function ($qq) use ($textSearch) {
                $qq->where('first_name', $textSearch)
                    ->whereLike('last_name', $textSearch)
                    ->whereLike('mother_last_name', $textSearch)
                    ->whereLike('employee_number', $textSearch);
            });
        });

        if ($request->filled(['from', 'to'])) {
            $query->whereDate('capture_date', '>=', $request->from)
                ->whereDate('capture_date', '<=', $request->to);
        }

//        dd($query->toSql());
        return $query;
    }

    public function countingValidatorScans(Request $request)
    {
        $query = Scans::query();
        $query->where('is_rejected', 0)->where('is_valid', 1);

        if ($request->filled(['from', 'to'])) {

            $query->whereHas('scans', function ($q) use ($request) {

                $q->whereDate('scans.capture_date', '>=', $request->from)
                    ->whereDate('scans.capture_date', '<=', $request->to);
            });
        }

        $query->when($request->textSearch, function ($q, $textSearch) {
            $q->whereHas('author', function ($qq) use ($textSearch) {
                $qq->where('first_name', $textSearch)
                    ->whereLike('last_name', $textSearch)
                    ->whereLike('mother_last_name', $textSearch)
                    ->whereLike('employee_number', $textSearch);
            });
        });

        return $query;
    }

    public function appScanList(Request $request)
    {
        $scans = Scans::where('scanned_by', $request->input('user_id'))
            ->where('is_valid', 0)
            ->where('is_rejected', 0)
            ->orderBy('created_at', 'DESC')->get();

        return response([
            'status' => 'success',
            'scans' => $scans,
            'accepted' => Scans::where('scanned_by', $request->input('user_id'))->where('is_valid', 1)->count(),
            'rejected' => Scans::where('scanned_by', $request->input('user_id'))->where('is_rejected', 1)->count()
        ]);
    }

    public function rankingEfficiency(Request $request)
    {
        
        $last = date("2000-01-01");
           //echo "Ultima fecha: " . $last;
        $morning = date("Y-m-d", strtotime("+1 days"));
        $querys = User::query();
           //echo "Fecha actual: " . $morning;
        
        if ($request->filled(['from', 'to'])) {
            $from = $request->from;
            $to = $request->to;
            $filterFechas = "AND DATE(ss.capture_date) >= '$from'
            AND DATE(ss.capture_date) <= '$to'";
            $filterFechasPrincipal = "((`scans`.`capture_date` <= '$from'
            AND `scans`.`capture_date` >= '$to')
            OR `scans`.`capture_date` BETWEEN '$from' AND '$to') AND"; 
        }else{
            $filterFechas = "AND DATE(ss.capture_date) >= '$last'
            AND DATE(ss.capture_date) <= '$morning'";
            $filterFechasPrincipal = "((`scans`.`capture_date` <= '$last'
            AND `scans`.`capture_date` >= '$morning')
            OR `scans`.`capture_date` BETWEEN '$last' AND '$morning') AND"; 
        }

        if ($request->filled(['textSearch'])) {
            $sqlTextSearch = $request ->textSearch;
            $textSearch = "first_name like '%$sqlTextSearch%' or last_name like '%$sqlTextSearch%' or mother_last_name like '%$sqlTextSearch%' or employee_number like '%$sqlTextSearch%' AND";
        }else{
            $textSearch = "";
        }

        if ($request->filled(['mission'])) {
            $reqMission = $request ->mission;
            $missionSubSql = "AND `ss`.`id_mission` = $reqMission";
            $missionWhereSubSql = "AND `up`.`id_mission` = $reqMission";
            $missionWhereSql = "AND `scans`.`id_mission` = $reqMission";
        }else{
            $missionSubSql = "";
            $missionWhereSubSql = "";
            $missionWhereSql = "";
        }
         /**Se agrego */

    $query = "SELECT 
    `users`.`id`,
    `users`.`first_name`,
    `users`.`last_name`,
    `users`.`mother_last_name`,
    `users`.`employee_number`,
    CONCAT(users.first_name, ' ', users.last_name, ' ', users.mother_last_name) AS name,
    (SELECT COUNT(ss.id)
        FROM
            scans ss
                JOIN
            missions mi ON mi.id = ss.id_mission
        WHERE
            ss.id_scanned_by = users.id AND 
            ss.deleted_at IS NULL $filterFechas) AS captures_made,
    (SELECT 
            COUNT(ss.id)
        FROM
            scans ss
                JOIN
            missions mi ON mi.id = ss.id_mission
        WHERE
            ss.id_scanned_by = users.id
                AND ss.is_valid = 1
                AND ss.is_rejected = 0 $missionSubSql
                AND ss.deleted_at IS NULL $filterFechas) AS validated_captures,
    (SELECT 
            (mm.mission_points + COUNT(ss.id)) AS points
        FROM
            scans ss
                JOIN
            missions mm ON mm.id = ss.id_mission
        WHERE
            ss.id_scanned_by = users.id AND
            ss.is_valid = 1
                AND ss.is_rejected = 0
                AND ss.deleted_at IS NULL $filterFechas GROUP BY mm.mission_points ) + (SELECT 
            COUNT(up.id)
        FROM
            user_points up 
            WHERE users.id = up.id_user $missionWhereSubSql) AS points
FROM
    `users`
        INNER JOIN
    `scans` ON `users`.`id` = `scans`.`id_scanned_by`
        INNER JOIN
    `model_has_roles` ON `users`.`id` = `model_has_roles`.`model_id`
WHERE $textSearch $filterFechasPrincipal users.id NOT IN (370 , 371) AND `users`.`deleted_at` IS NULL $missionWhereSql /**Se agrego */ 
GROUP BY users.id
ORDER BY `captures_made` DESC , `validated_captures` DESC , points ASC, `users`.`last_name` ASC;";

    //echo "La conexión ha fallado: " . $query;
        
    try {    
        $host = env('DB_HOST');
        $dbname = env('DB_DATABASE');
        $userDB = env('DB_USERNAME');
        $passDB = env('DB_PASSWORD');
        //echo "La conexión ha fallado: " . env('DB_HOST');

        $conn = new PDO("mysql:host=$host;dbname=$dbname", "$userDB", "$passDB");      
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        //echo "Conexión realizada Satisfactoriamente";
        $result = $conn->prepare($query);
        $result->execute();
        $extraido = $result->fetchAll(PDO::FETCH_ASSOC);
        $querys = $extraido;
    
        //Cerrar conexion PDO - HGM - 03-07-2023
        $result->closeCursor(); // opcional en MySQL, dependiendo del controlador de base de datos puede ser obligatorio
        $result = null; // obligado para cerrar la conexión
        $conn = null;
        //

    }catch(Exception $sp){
        die("Error occurred:" . $sp->getMessage());
        $querys = null; 
    }catch(PDOException $e){
        echo "La conexión ha fallado: " . $e->getMessage();
        $conn = null;
    }
    return $querys;
    }



    public function rankingFirst3Places(Request $request)
    {
        $query = "SELECT 
        CONCAT(users.first_name,
                ' ',
                users.last_name,
                ' ',
                users.mother_last_name) AS name,
        users.employee_number,
        (SELECT 
                COUNT(ss.id)
            FROM
                scans ss
            WHERE
                ss.id_scanned_by = ss.id_scanned_by
                    AND ss.is_valid = 1
                    AND ss.is_rejected = 0) AS validated_captures
    FROM
        users
            JOIN
        scans ss ON ss.id_scanned_by = users.id
    GROUP BY users.first_name , users.last_name , ss.id_scanned_by , users.employee_number , users.mother_last_name , users.id
    ORDER BY validated_captures DESC;";
        try {    
            $host = env('DB_HOST');
            $dbname = env('DB_DATABASE');
            $userDB = env('DB_USERNAME');
            $passDB = env('DB_PASSWORD');
            //echo "La conexión ha fallado: " . env('DB_HOST');
    
            $conn = new PDO("mysql:host=$host;dbname=$dbname", "$userDB", "$passDB");      
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            //echo "Conexión realizada Satisfactoriamente";
            $result = $conn->prepare($query);
            $result->execute();
            $extraido = $result->fetchAll(PDO::FETCH_ASSOC);
            $querys = $extraido;
        
            //Cerrar conexion PDO - HGM - 03-07-2023
            $result->closeCursor(); // opcional en MySQL, dependiendo del controlador de base de datos puede ser obligatorio
            $result = null; // obligado para cerrar la conexión
            $conn = null;
            //
    
        }catch(Exception $sp){
            die("Error occurred:" . $sp->getMessage());
            $querys = null; 
        }catch(PDOException $e){
            echo "La conexión ha fallado: " . $e->getMessage();
            $conn = null;
        }
        return $querys;
        /*
        $query = User::query();

        $query->select(
            DB::raw(
            "CONCAT(users.first_name, ' ', users.last_name, ' ', users.mother_last_name) AS name, users.employee_number, (select count(ss.id) from scans ss where ss.id_scanned_by = scans.id_scanned_by and ss.is_valid = 1 and ss.is_rejected = 0) as validated_captures"))
            ->join('scans', 'users.id', 'scans.id_scanned_by')
            ->groupBy(
                'users.first_name', 'users.last_name', 'scans.id_scanned_by',
                'users.employee_number', 'users.mother_last_name', 'users.id')
            ->orderBy('validated_captures', 'DESC');

        return $query;*/
    }
}
