<?php

namespace App\Http\Controllers;

use App\Chains;
use App\Store;
use App\Languages;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use League\Csv\Writer;

class ChainsController extends Controller
{
    private $headings = [[
        'Id',
        'Nombre',
        'Alias'
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function store(Request $request)
    {
        $input = [
            'name' => 'required|string|max:255',
            'alias' => 'required|string|max:255',
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
            $chain = Chains::create([
                'name' => $request->name,
                'alias' => $request->alias,
                'description' => $request->description,
                'lang_id' => $lang_id->id
            ]);
        }else{
            $chain = Chains::create([
                'name' => $request->name,
                'alias' => $request->alias,
                'description' => $request->description
            ]);
        }

        $success = [
            'status' => true,
            'message' => "La cadena se ha registrado con éxito",
            'data' => $chain
        ];

        return response()->json($success, JsonResponse::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Chains  $chains
     * @return Response
     */
    public function show(Request $request)
    {
        $chain = Chains::orderBy('name', 'asc');

        if($request->has('language')){
            $term = $request->language;
            $lang_name = Languages::where('name', $term)->first();
            if(!$lang_name){
                return response()->json(['status' => false, 'errros' => 'Languages '.$term.' does not exist'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $chain->where('lang_id', $lang_name->id);
        }
        
        if ($request->input('name')) {
            $term = $request->input('name');
            $chain->where('name', 'like', "%$term%");
        }

        return response()->json(['success' => true, 'data' => $chain->paginate(50)], JsonResponse::HTTP_OK);
    }

    /**
     * Download CSV.
     *
     * @param  \App\Chains  $chains
     * @return Response
     */
    public function chainsToCsv(Request $request)
    {
        $chains = Chains::orderBy('name', 'asc');

        if ($request->input('name')) {
            $term = $request->input('name');
            $chains->where('name', 'like', "%$term%");
        }

        $result = [];

        foreach ($chains->get() as $chain) {
            $result[] = [
                'id' => $chain->id,
                'name' => $chain->name,
                'alias' => $chain->alias
            ];
        }

        $result = array_merge($this->headings, $result);

        $callback = $this->writerContentCallBack($result);

        $time = time();
        $fileName = "cadenas-$time.csv";
        return response()->streamDownload($callback, $fileName);
    }

    /**
     * Write CSV content
     *
     * @param Chains $array
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
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Chains  $chains
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $chain = Chains::where('id', $id)->first();

        if (is_null($chain)) {
            return response()->json(['status' => false, 'errors' => 'El id no se encuentra en la Base de Datos o se encuentra bloqueado'], JsonResponse::HTTP_NOT_FOUND);
        }

        $input = [
            'name' => 'string|max:255',
            'alias' => 'string|max:255',
            'description' => 'string|max:255|nullable',
            'is_notificable' => 'boolean|nullable'
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

        $stores = Store::where('name', $chain->name)->get();

        if ($stores) {
            foreach ($stores as $store) {
                $store->name = $request->name;
                $store->save();
            }
        }
        
        if($request->has('language')){
            $lang_id = Languages::where('name', $request->language)->first();
            if(!$lang_id){
                return response()->json(['status' => false, 'errros' => 'Languages '.$request->language.' does not exist'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $chain->lang_id = $lang_id->id;
        }
        if($request->has('is_notificable')){
            $chain->is_notificable = $request->is_notificable;
        }
        $chain->name = $request->name ? $request->name : $chain->name;
        $chain->alias = $request->alias ? $request->alias : $chain->alias;
        $chain->description = $request->description ? $request->description : $chain->description;
        $chain->save();

        $success = [
            'status' => true,
            'message' => "La cadena se ha actualizado con éxito",
            'data' => $chain
        ];

        return response()->json($success, JsonResponse::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Chains  $chains
     * @return Response
     */
    public function destroy($id)
    {
        $chain = Chains::where('id', $id)->first();

        if (is_null($chain)) {
            return response()->json(['status' => false, 'errors' => 'El id no se encuentra en la Base de Datos o se encuentra bloqueado'], JsonResponse::HTTP_NOT_FOUND);
        }

        $store = Store::where('name', $chain->name)->first();

        if ($store) {
            return response()->json(['status' => false, 'errors' => 'Cadena asociada a una tienda, no se puede eliminar'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $chain->delete();

        $success = [
            'status' => true,
            'message' => "La cadena se ha eliminado con éxito"
        ];

        return response()->json($success, JsonResponse::HTTP_OK);
    }
}
