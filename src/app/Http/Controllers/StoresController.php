<?php

namespace App\Http\Controllers;

use App\Http\Resources\LabelCollection;
use App\Http\Resources\LocationCollection;
use App\Http\Resources\StoreCollection;
use App\Repositories\StoreRepository;
use App\Store;
use App\Http\Resources\Store as storeResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StoresController extends Controller
{

    /** @var StoreRepository */
    private $storeRepository;

    /**
     * StoresController constructor.
     * @param StoreRepository $storeRepository
     */
    public function __construct(StoreRepository $storeRepository)
    {
        $this->storeRepository = $storeRepository;
    }


    public function findByLocation(Request $request)
    {
        $query = $this->storeRepository->findLocation($request->latitude, $request->longitude);
        $storeResource = new LocationCollection($query->get());
        return response()->json($storeResource, JsonResponse::HTTP_OK);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $storesList = $this->storeRepository->Filters($request);
        $collection = new StoreCollection($storesList->paginate(10));
        return response()->json($collection);

    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request)
    {
        $query = $this->storeRepository->FiltersChains($request);
        $stores = $query->get()->unique('name')->pluck('name', 'id');
        return response()->json($stores);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3',
            'address' => 'required|min:10',
            'phone' => 'min:10|max:15'
        ]);
//        dd($request->all());
        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $store = $this->storeRepository->save($request->all());

        $success = [
            'status' => true,
            'message' => "You have registered successfully",
            'data' => $store,
        ];

        return response()->json($success, JsonResponse::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Store $store
     * @return \Illuminate\Http\Response
     */
    public function show(Store $store)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Store $store
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Store $store)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Store $store
     * @return \Illuminate\Http\Response
     */
    public function destroy(Store $store)
    {
        //
    }
}
