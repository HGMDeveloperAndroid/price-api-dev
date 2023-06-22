<?php
/**
 * @author Pedro Rojas Reyes <pedro.rojas@gmail.com>
 */

namespace App\Http\Controllers;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

use App\Http\Resources\ProductsReportingCollection;
use App\Http\Resources\RankingCollection;
use App\Http\Resources\RankingEfficiencyCollection;
use App\Http\Resources\RankingValidatorsCollection;
use App\Http\Resources\ScanReportingCollection;
use App\Repositories\ReportingRepository;
use App\User;
use App\Store;
use App\ListProducts;
use App\Products;
use App\Scans;

use App\Exports\ProductsExport;
use App\Exports\ScansExport;
use Maatwebsite\Excel\Facades\Excel;


class ReportingController extends Controller
{
    private $headings = [[
        'Captura',
        'Código de Barras',
        'Cadena Comercial',
        'Dirección',
        'Num. Empleado',
        'Capturista',
        'Región',
        'Validador',
        'Estado',
        'Comentarios',
        'Producto',
        'Cantidad',
        'Unidad',
        'Precio Unitario',
        'Marca',
        'Tipo',
        'Grupo',
        'Línea',
        'Fecha de Alta Producto',
        'Precio de Alta',
        'Precio Min',
        'Precio Max',
        'Fecha de Captura',
        'Precio de Captura',
        'Promoción'
    ]];

    private $headingsProducts = [[
        'id',
        'Foto de producto',
        'Nombre',
        'Código',
        'Fecha de alta',
        'Fecha de modificación',
        'Gramaje',
        'Unidad',
        'Marca',
        'Tipo',
        'Grupo',
        'Línea',
        'Precio más alto',
        'Precio más bajo',
        'Precio más bajo con promoción',
        'Último precio de capturado',
        'Fecha del último precio capturado'
    ]];

    private $reportingRepository;

    public function __construct(ReportingRepository $reportingRepository)
    {
        $this->reportingRepository = $reportingRepository;
    }

    public function Scans(Request $request)
    {
        $pages = 50;
        if ($request->filled('perPage')) {
            $pages = $request->perPage;
        }

        $scansView = $this->reportingRepository->ScansView($request);
        return new ScanReportingCollection($scansView->paginate($pages));
    }

    public function ScansToCsv(Request $request)
    {
        ini_set("memory_limit", "-1");
        set_time_limit(0);
        $scansView = $this->reportingRepository->ScansView($request);
        $itemsScan = $this->reportingRepository->scansData($scansView->get());
        $result = array_merge($this->headings, $itemsScan);

        $callback = $this->reportingRepository->writerContentCallBack($result);

        $time = time();
        $fileName = "scans-report-$time.csv";
        return response()->streamDownload($callback, $fileName);
    }

    /**
     * Scan report for analyst users.
     *
     * @param Request $request
     * @return Collection
     */
    public function scansAnalyst(Request $request)
    {
        $pages = 50;
        if ($request->filled('perPage')) {
            $pages = $request->perPage;
        }

        $scansView = $this->reportingRepository->scansAnalyst($request);
        return new ScanReportingCollection($scansView->paginate($pages));
    }

    /**
     * Scan report for analyst users in CSV.
     *
     * @param Request $request
     * @return Collection
     */
    public function scansToCsvAnalyst(Request $request)
    {
        ini_set("memory_limit", "-1");
        set_time_limit(0);
        $scansView = $this->reportingRepository->scansAnalyst($request);
        $itemsScan = $this->reportingRepository->scansData($scansView->get());
        $result = array_merge($this->headings, $itemsScan);

        $callback = $this->reportingRepository->writerContentCallBack($result);

        $time = time();
        $fileName = "scans-report-$time.csv";
        return response()->streamDownload($callback, $fileName);
    }

    public function Products(Request $request)
    {
        $pages = 50;
        if ($request->filled('perPage')) {
            $pages = $request->perPage;
        }

        $productFilters = $this->reportingRepository->productFilters($request);
        return new ProductsReportingCollection($productFilters->paginate($pages));
    }

    /**
     * Products report for analyst users.
     *
     * @param Request $request
     * @return Collection
     */
    public function productsAnalyst(Request $request)
    {
        $pages = 50;
        if ($request->filled('perPage')) {
            $pages = $request->perPage;
        }

        $productFilters = $this->reportingRepository->productFiltersAnalyst($request);
        return new ProductsReportingCollection($productFilters->paginate($pages));
    }

    /**
     * Products report for analyst users in CSV.
     *
     * @param Request $request
     * @return Collection
     */
    public function productsToCsvAnalyst(Request $request)
    {
        ini_set("memory_limit", "-1");
        set_time_limit(0);
        $productFilters = $this->reportingRepository->productFiltersAnalyst($request);
        $itemsProduct = $this->reportingRepository->productsData($productFilters->get());
        $result = array_merge($this->headingsProducts, $itemsProduct);

        $callback = $this->reportingRepository->writerContentCallBack($result);

        $time = time();
        $fileName = "products-report-$time.csv";
        return response()->streamDownload($callback, $fileName);
    }

    /**
     * List Products report for analyst users.
     *
     * @param Request $request
     * @return Collection
     */
    public function productsAnalystList(Request $request)
    {
        $pages = 50;
        if ($request->filled('perPage')) {
            $pages = $request->perPage;
        }

        $list_prduct = ListProducts::where('id_user', Auth::user()->id)->get();
        $list = array();

        foreach ($list_prduct as $product) {
            array_push($list, $product->id_product);
        }

        $productFilters = Products::whereIn('id', $list);

        if ($request->filled('id_list')) {
            $productFilters->where('id', $request->id_list);
        }

        return new ProductsReportingCollection($productFilters->paginate($pages));
    }

    public function productByBarcode($barcode)
    {
        $productFilters = $this->reportingRepository->productFiltersByBarcode($barcode);

        if (is_null($productFilters->first())) {
            return response()->json(['errors' => 'Invalid barcode'], JsonResponse::HTTP_NOT_FOUND);
        }

        $product = new ProductsReportingCollection($productFilters->paginate(1));
        return response()->json(['data' => $product->all()], JsonResponse::HTTP_OK);
    }

    public function ProductsToCsv(Request $request)
    {
        ini_set("memory_limit", "-1");
        set_time_limit(0);
        $productFilters = $this->reportingRepository->productFilters($request);
        $itemsProduct = $this->reportingRepository->productsData($productFilters->get());
        $result = array_merge($this->headingsProducts, $itemsProduct);

        $callback = $this->reportingRepository->writerContentCallBack($result);

        $time = time();
        $fileName = "products-report-$time.csv";
        return response()->streamDownload($callback, $fileName);
    }

    public function dataGraphAverage($barcode)
    {
        $data = [];
        if ($barcode) {
            $data = $this->reportingRepository->dailyAverage($barcode);
        }

        return response()->json($data, 200);
    }

    public function historySummary($barcode, Request $request)
    {
        $data = [];
        if ($barcode) {
            $data = $this->reportingRepository->historySummary($barcode, $request);
        }

        return response()->json($data, 200);
    }

    public function historyDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'barcode' => 'required|string',
            'store' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $id ='';
        $stores = Store::select('stores.id')
        ->join('scans', 'scans.id_store', 'stores.id')
        ->where('stores.name', $request->store)
        ->where('scans.barcode', $request->barcode)->get();

        foreach ($stores as $store) {
            $id =  $id . $store->id. ',';
        }

        $id = trim($id, ',');
        $pages = 50;
        if ($request->filled('perPage')) {
            $pages = $request->perPage;
        }

        $query = $this->reportingRepository->historyDetails($request, $id);
        $data = $query->paginate($pages);

        return response()->json($data, 200);
    }

    public function historyDetailsByBarcode($barcode)
    {
        $response = null;
        if (!empty($barcode)) {
            $itemsScan = $this->reportingRepository->getHistoryDetails($barcode);
            $result = array_merge([['id captura', 'fecha', 'Precio de captura', 'Cadena']], $itemsScan);
            $callback = $this->reportingRepository->writerContentCallBack($result);

            $time = time();
            $fileName = "history-report-$time.csv";
            return response()->streamDownload($callback, $fileName);
        }

        return null;
    }

    public function ranking(Request $request)
    {
        $pages = 50;
        if ($request->filled('perPage')) {
            $pages = $request->perPage;
        }

        $users = $this->reportingRepository->rankings($request);
        return new RankingCollection($users->paginate($pages));
    }

    public function rankingValidator(Request $request)
    {
        $pages = 50;
        if ($request->filled('perPage')) {
            $pages = $request->perPage;
        }

        $users = $this->reportingRepository->rankingsValidator($request);
        return new RankingValidatorsCollection($users->paginate($pages));
    }

    public function scoreScans(Request $request)
    {
        $queryCaptures = $this->reportingRepository->countingScansFilter($request);
        $queryValidate = $this->reportingRepository->countingValidatorScans($request);
        $data = [
            'captures' => $queryCaptures->count(),
            'validates' => $queryValidate->count()
        ];

        return response()->json($data, 200);
    }

    public function rankingByEfficiency(Request $request)
    {
        // Force return null until we optimize this query
        return null;

        $pages = 50;
        if ($request->filled('perPage')) {
            $pages = $request->perPage;
        }
        $ranking = $this->reportingRepository->rankingEfficiency($request);
        $collection = new RankingEfficiencyCollection($ranking->paginate($pages));
        return $collection;
    }

    public function rankingFirst3Places(Request $request)
    {
        $ranking = $this->reportingRepository->rankingFirst3Places($request)->get()->take(3);

        return [
            'data' => $ranking
        ];
    }

    /**
     * Scanner users by region.
     *
     * @param Request $request
     * @return Response
     */
    public function usersScanner(Request $request)
    {
        $users = User::select('users.id', 'users.first_name', 'users.last_name', 'users.mother_last_name', 'users.employee_number')
            ->join('model_has_roles', 'model_has_roles.model_id', 'users.id')
            ->where('model_has_roles.role_id', 3);

        if ($request->filled('region')) {
            $users->join('zone_users', 'zone_users.id_user', 'users.id')
                ->where('zone_users.id_zone', $request->region);
        }

        return response()->json($users->get(), 200);
    }

    /**
     * Missions by region.
     *
     * @param Request $request
     * @return Response
     */
    public function missionsByRegion(Request $request)
    {
        $mission =  DB::table('missions')->select('missions.*');

        if ($request->filled('region')) {
            $mission->join('zone_missions', 'zone_missions.id_mission', 'missions.id')
                ->where('zone_missions.id_zone', $request->region);
        }

        return response()->json($mission->get(), 200);
    }

    /**
     * Mission participation details.
     *
     * @param Request $request
     * @return Response
     */
    public function userMissions($id)
    {
        $participated_missions = Scans::select('id_mission')->where('id_scanned_by', $id)
            ->groupBy('id_mission')
            ->get();
        $count_missions = 0;

        foreach ($participated_missions as $participated) {
            $count_missions ++;
        }

        $captures_made = Scans::where('id_scanned_by', $id)->count();
        $validated_captures = Scans::where('id_scanned_by', $id)
            ->where('is_valid', 1)
            ->where('is_rejected', 0)
            ->count();
        $scan_rejected = Scans::select(DB::raw('rejection_criteria.criterion, count(*) as total'))
            ->where('id_scanned_by', $id)
            ->join('rejection_criteria', 'rejection_criteria.id', 'scans.id_criterion')
            ->where('is_valid', 0)
            ->where('is_rejected', 1)
            ->groupBy('rejection_criteria.criterion')
            ->orderBy('rejection_criteria.id')
            ->get();
        $scan_rejected_count = Scans::where('id_scanned_by', $id)
            ->where('is_valid', 0)
            ->where('is_rejected', 1)
            ->count();

        $data = [
            'count_missions' => $count_missions,
            'captures_made' => $captures_made,
            'validated_captures' => $validated_captures,
            'rejected_count' => $scan_rejected_count,
            'rejected' => $scan_rejected
        ];

        return response()->json($data, 200);
    }
}
