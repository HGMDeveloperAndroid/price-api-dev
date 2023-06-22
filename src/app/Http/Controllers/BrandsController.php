<?php

namespace App\Http\Controllers;

use App\Brands;
use App\Products;
use App\Languages;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use League\Csv\Writer;

class BrandsController extends Controller
{
    private $headings = [[
        'Id',
        'Nombre'
    ]];

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        //
    }

    public function list(Request $request)
    {
        $query = Brands::query();
        if ($request->has('textSearch')) {
            $term = $request->textSearch;
            $query->where('name', 'like', "%$term%");
        }

        $brands = $query->orderBy('name', 'asc')
            ->pluck('name', 'id');

        return response()->json(['success' => true, 'data'=> $brands]);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $input = [
            'name' => 'required|string|max:255',
            'description' => 'string|max:255|nullable',
            'language' => 'string|nullable'
        ];

        $messages = [
            'required' => 'El campo :attribute es requerido',
            'max' => 'El campo :attribute no puede ser mayor que :max.',
            'string' => 'El campo :attribute debe ser una cadena'
        ];

        $validator = Validator::make($request->all(), $input, $messages);


        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        if($request->has('language')){
            $lang_id = Languages::where('name', $request->language)->first();
            if(!$lang_id){
                return response()->json(['status' => false, 'errros' => 'Languages '.$request->language.' does not exist'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $brand = Brands::create([
                'name' => $request->name,
                'description' => $request->description,
                'lang_id' => $lang_id->id
            ]);                
            

        }else{
            $brand = Brands::create([
                'name' => $request->name,
                'description' => $request->description
            ]);
        }

        $success = [
            'status' => true,
            'message' => "La marca se ha registrado con éxito",
            'data' => $brand
        ];

        return response()->json($success, JsonResponse::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param Brands $brands
     * @return Response
     */
    public function show(Request $request)
    {
        $brands = Brands::orderBy('name', 'asc');

        if($request->has('language')){
            $term = $request->language;
            $lang_name = Languages::where('name', $term)->first();
            if(!$lang_name){
                return response()->json(['status' => false, 'errros' => 'Languages '.$term.' does not exist'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $brands->where('lang_id', $lang_name->id);
        }

        if ($request->input('name')) {
            $term = $request->input('name');
            $brands->where('name', 'like', "%$term%");
        }

        return response()->json(['success' => true, 'data' => $brands->paginate(50)], JsonResponse::HTTP_OK);
    }

    /**
     * Download CSV.
     *
     * @param Brands $brands
     * @return Response
     */
    public function brandsToCsv(Request $request)
    {
        $brands = Brands::orderBy('name', 'asc');

        if ($request->input('name')) {
            $term = $request->input('name');
            $brands->where('name', 'like', "%$term%");
        }

        $result = [];

        foreach ($brands->get() as $brand) {
            $result[] = [
                'id' => $brand->id,
                'name' => $brand->name
            ];
        }

        $result = array_merge($this->headings, $result);

        $callback = $this->writerContentCallBack($result);

        $time = time();
        $fileName = "marcas-$time.csv";
        return response()->streamDownload($callback, $fileName);
    }

    /**
     * Write CSV content
     *
     * @param Brands $array
     * @return Response
     */
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

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Brands $brands
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $brand = Brands::where('id', $id)->first();

        if (is_null($brand)) {
            return response()->json(['status' => false, 'errors' => 'El id no se encuentra en la Base de Datos'], JsonResponse::HTTP_NOT_FOUND);
        }

        $input = [
            'name' => 'required|string|max:255',
            'description' => 'string|max:255|nullable'
        ];

        $messages = [
            'required' => 'El campo :attribute es requerido',
            'max' => 'El campo :attribute no puede ser mayor que :max.',
            'string' => 'El campo :attribute debe ser una cadena'
        ];

        $validator = Validator::make($request->all(), $input, $messages);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        if($request->has('language')){
            $lang_id = Languages::where('name', $request->language)->first();
            if(!$lang_id){
                return response()->json(['status' => false, 'errros' => 'Languages '.$request->language.' does not exist'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $brand->lang_id = $lang_id->id;
        }

        $brand->name = $request->name;
        $brand->description = $request->description;
        $brand->save();

        $success = [
            'status' => true,
            'message' => "La marca se ha actualizado con éxito",
            'data' => $brand
        ];

        return response()->json($success, JsonResponse::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Brands $brands
     * @return Response
     */
    public function destroy($id)
    {
        $produc = Products::where('id_brand', $id)->first();

        if ($produc) {
            return response()->json(['status' => false, 'errors' => 'Marca asociada a un producto, no se puede eliminar'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $brand = Brands::where('id', $id)->first();

        if (is_null($brand)) {
            return response()->json(['status' => false, 'errors' => 'El id no se encuentra en la Base de Datos'], JsonResponse::HTTP_NOT_FOUND);
        }

        $brand->delete();

        $success = [
            'status' => true,
            'message' => "La marca se ha eliminado con éxito"
        ];

        return response()->json($success, JsonResponse::HTTP_OK);
    }
}
