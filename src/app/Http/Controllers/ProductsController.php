<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Http\Resources\Product;
use App\Http\Resources\ProductsReporting;
use App\Products;
use App\Repositories\ProductRepository;
use App\Repositories\ScanRepository;
use App\Services\UploadSpaces;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Collection;
use App\Http\Resources\Products as ProductsResource;
use App\Http\Resources\ProductsStoresCollection;

class ProductsController extends Controller
{

    /** @var ProductRepository */
    private $productRepository;
    /** @var ScanRepository */
    private $scanRepository;

    /** @var UploadSpaces $spaces */
    private $spaces;

    /**
     * ProductsController constructor.
     * @param ProductRepository $productRepository
     * @param ScanRepository $scanRepository * @param UploadSpaces $spaces
     */
    public function __construct(ProductRepository $productRepository, ScanRepository $scanRepository, UploadSpaces $spaces)
    {
        $this->productRepository = $productRepository;
        $this->scanRepository = $scanRepository;
        $this->spaces = $spaces;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param $barcode
     * @return JsonResponse
     */
    public function show($barcode)
    {
        $product = Products::where('barcode', $barcode)->first();

        if (is_null($product)) {
            return response()->json(['errors' => 'Invalid barcode'], JsonResponse::HTTP_NOT_FOUND);
        }

        $productsResource = new Product($product);

        return response()->json([
            'status' => 'success',
            'product' => $productsResource
        ]);
    }

     /**
     * Display the specified resource.
     *
     * @param $barcodes
     * @return JsonResponse
     */
    public function showProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'barcodes' => 'required',
            'startdate' => 'date',
            'enddate' => 'date|after:start_date'
        ]);

        if ($validator->fails()) {
            return response()->json(['Validation errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $barcodes = $request->get('barcodes');
        $products = $this->productRepository->Filters($barcodes, $request);
        $collection = new ProductsStoresCollection($products->paginate(50));
        return response()->json($collection);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ProductRequest $request
     * @param $barcode
     * @return JsonResponse
     */
    public function update(ProductRequest $request, $barcode)
    {
        $user = Auth::user();

        if ($user->hasRole(['Scanner'])) {
            $response['status'] = 'error';
            $response['message'] = "Role no valid";
            return response()->json($response, 401);
        }

        $product = Products::where('barcode', $barcode)->first();

        if (is_null($product)) {
            return response()->json(['errors' => 'Invalid barcode'], JsonResponse::HTTP_NOT_FOUND);
        }

        $dataValidate = $request->validated();

        if (!$dataValidate) {
            return response()->json([
                'status' => 'error',
                'message' => 'No data'
            ], 400);
        }

        $data = $dataValidate['product'];
        $exists_barcode = Products::where('barcode', $data['barcode'])
            ->where('id', '!=', $product->id)->first();

        if ($exists_barcode) {
            return response()->json(['errors' => 'the barcode already exists'], JsonResponse::HTTP_CONFLICT);
        }

        $this->productRepository->update($product, $dataValidate['product'], $barcode);

        $brand = $product->brand()->first();
        if ($brand) {
            if (isset($dataValidate['brand']['name'])) {
                $brand->name = strip_tags($dataValidate['brand']['name']);
                $brand->save();
            }
        }

        return response()->json([
            'status' => 'success',
            'scan' => new Product($product)
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $barcode
     * @return JsonResponse
     */
    public function destroy($barcode)
    {
        $product = Products::where('barcode', $barcode)->first();

        if (is_null($product)) {
            return response()->json(['errors' => 'Invalid barcode'], JsonResponse::HTTP_NOT_FOUND);
        }

        $product->delete();

        return response()->json([
            'status' => 'success'
        ], 204);
    }

    public function replacePhoto(Request $request, $barcode)
    {
        $validator = Validator::make($request->all(), [
            'picture_path' => 'file|mimes:jpeg,jpg,bmp,png|max:30000|nullable'
        ]);

        if ($validator->fails()) {
            return response()->json(['Validation errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $product = Products::where('barcode', $barcode)->first();

        if (is_null($product)) {
            return response()->json(['errors' => 'Invalid barcode'], JsonResponse::HTTP_NOT_FOUND);
        }

        $status = null;
        if (!empty($request->picture_path)) {
            $status = $this->productRepository->updatePhoto($product, $request->all());
        }

        if (!$status) {
            return response()->json([
                'status' => 'error',
                'type' => "The image cannot be loaded"
            ], 400);
        }

        return response()->json([
            'status' => 'success',
            'scan' => new Product($product)
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ProductRequest $request
     * @param $barcode
     * @return JsonResponse
     */
    public function updateProductById(ProductRequest $request, $id)
    {
        $user = Auth::user();

        if ($user->hasRole(['Scanner'])) {
            $response['status'] = 'error';
            $response['message'] = "Role no valid";
            return response()->json($response, 401);
        }

        $product = Products::where('id', $id)->first();

        if (is_null($product)) {
            return response()->json(['errors' => 'Invalid id'], JsonResponse::HTTP_NOT_FOUND);
        }

        $dataValidate = $request->validated();

        if (!$dataValidate) {
            return response()->json([
                'status' => 'error',
                'message' => 'No data'
            ], 400);
        }

        $data = $dataValidate['product'];
        $exists_barcode = Products::where('barcode', $data['barcode'])->first();

        if ($exists_barcode) {
            return response()->json([
                'status' => 'success',
                'scan' => new Product($exists_barcode)
            ], 200);
        }

        $this->productRepository->updateProductById($product, $dataValidate['product'], $id);

        $brand = $product->brand()->first();
        if ($brand) {
            if (isset($dataValidate['brand']['name'])) {
                $brand->name = strip_tags($dataValidate['brand']['name']);
                $brand->save();
            }
        }

        return response()->json([
            'status' => 'success',
            'scan' => new Product($product)
        ], 200);
    }
}
