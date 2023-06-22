<?php

namespace App\Http\Controllers;

use App\Lines;
use App\Products;
use App\Languages;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use League\Csv\Writer;


class LinesController extends Controller
{
    private $headings = [[
        'Id Linea',
        'Nombre Linea',
        'Id Grupo',
        'Nombre Grupo'
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
     * @param Request $request
     * @return Application|ResponseFactory|Response
     */
    public function searchLinesByGroup(Request $request)
    {
        $query = Lines::select('id','id_group', 'name');

        if ($request->has('textSearch')) {
            $term = $request->textSearch;
            if (strlen($term) >= 3) {
                $query->where('name', 'like', "%$term%");
            }
        }

        if ($request->has('idGroup')) {
            if (intval($request->idGroup) > 0) {
                $idGroup = intval($request->idGroup);
                $query->where('id_group', $idGroup);
            } else {
                abort(404, 'Group invalid');
            }
        }

        $query->orderBy('name', 'ASC');

        $lines = $query->get();
        $total = $query->count();

        return response([
            'lines' => $lines,
            'total' => $total
        ]);
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
            'id_group' => 'required|exists:groups,id',
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
            $lines = Lines::create([
                'id_group' => $request->id_group,
                'name' => $request->name,
                'description' => $request->description,
                'lang_id' => $lang_id->id
            ]);
        }else{
            $lines = Lines::create([
                'id_group' => $request->id_group,
                'name' => $request->name,
                'description' => $request->description
            ]);
        }

        $success = [
            'status' => true,
            'message' => "La linea se ha registrado con éxito",
            'data' => $lines
        ];

        return response()->json($success, JsonResponse::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Lines  $lines
     * @return Response
     */
    public function show(Request $request)
    {
        $lines = Lines::select('lines.id as id_line','id_group', 'lines.name as name_line', 'groups.name as name_group', 'lines.description', 'lines.created_at', 'lines.updated_at', 'lines.lang_id')
            ->join('groups', 'groups.id', 'lines.id_group')
            ->orderBy('lines.name', 'asc');
        if($request->has('language')){
            $term = $request->language;
            $lang_name = Languages::where('name', $term)->first();
            if(!$lang_name){
                return response()->json(['status' => false, 'errros' => 'Languages '.$term.' does not exist'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $lines->where('lines.lang_id', $lang_name->id);
        }
        if ($request->input('name')) {
            $term = $request->input('name');
            $lines->where('lines.name', 'like', "%$term%");
        }

        return response()->json(['success' => true, 'data' => $lines->paginate(50)], JsonResponse::HTTP_OK);
    }

    /**
     * Download CSV.
     *
     * @param  \App\Lines  $lines
     * @return Response
     */
    public function linesToCsv(Request $request)
    {
        $lines = Lines::select('lines.id as id_line','id_group', 'lines.name as name_line', 'groups.name as name_group', 'lines.description', 'lines.created_at', 'lines.updated_at')
            ->join('groups', 'groups.id', 'lines.id_group')
            ->orderBy('lines.name', 'asc');

        if ($request->input('name')) {
            $term = $request->input('name');
            $lines->where('lines.name', 'like', "%$term%");
        }

        $result = [];

        foreach ($lines->get() as $line) {
            $result[] = [
                'id_line' => $line->id_line,
                'name_line' => $line->name_line,
                'id_group' => $line->id_group,
                'name_group' => $line->name_group
            ];
        }

        $result = array_merge($this->headings, $result);

        $callback = $this->writerContentCallBack($result);

        $time = time();
        $fileName = "lineas-$time.csv";
        return response()->streamDownload($callback, $fileName);
    }

    /**
     * Write CSV content
     *
     * @param Lines $array
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
     * @param  \App\Lines  $lines
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $lines = Lines::where('id', $id)->first();

        if (is_null($lines)) {
            return response()->json(['status' => false, 'errors' => 'El id no se encuentra en la Base de Datos'], JsonResponse::HTTP_NOT_FOUND);
        }

        $input = [
            'id_group' => 'required|exists:groups,id',
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
                return response()->json(['status' => false, 'errros' => 'Languages '.$term.' does not exist'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $lines->lang_id = $lang_id->id;
        }
        $lines->id_group = $request->id_group;
        $lines->name = $request->name;
        $lines->description = $request->description;
        $lines->save();

        $success = [
            'status' => true,
            'message' => "La linea se ha actualizado con éxito",
            'data' => $lines
        ];

        return response()->json($success, JsonResponse::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Lines  $lines
     * @return Response
     */
    public function destroy($id)
    {
        $produc = Products::where('id_line', $id)->first();

        if ($produc) {
            return response()->json(['status' => false, 'errors' => 'Linea asociada a un producto, no se puede eliminar'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $lines = Lines::where('id', $id)->first();

        if (is_null($lines)) {
            return response()->json(['status' => false, 'errors' => 'El id no se encuentra en la Base de Datos'], JsonResponse::HTTP_NOT_FOUND);
        }

        $lines->delete();

        $success = [
            'status' => true,
            'message' => "La linea se ha eliminado con éxito"
        ];

        return response()->json($success, JsonResponse::HTTP_OK);
    }
}
