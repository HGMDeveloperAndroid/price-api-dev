<?php

namespace App\Http\Controllers;

use App\Groups;
use App\Products;
use App\Lines;
use App\Languages;
use App\Http\Resources\Group;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use League\Csv\Writer;

class GroupsController extends Controller
{
    private $headings = [[
        'Id',
        'Nombre'
    ]];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * @param Request $request
     */
    public function list(Request $request)
    {
        $query = Groups::select('id', 'name');

        if ($request->has('textSearch')) {
            $textSearch = $request->textSearch;
            $query->where('name', 'like', "%$textSearch%");
        }

        $group = $query->orderBy('name', 'ASC')->get();

        return response([
            'group' => $group,
            'total' => $group->count()
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
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
            $group = Groups::create([
                'name' => $request->name,
                'description' => $request->description,
                'lang_id' => $lang_id->id
            ]);
        }else{
            $group = Groups::create([
                'name' => $request->name,
                'description' => $request->description
            ]);
        }

        $success = [
            'status' => true,
            'message' => "El grupo se ha registrado con éxito",
            'data' => $group,
        ];

        return response()->json($success, JsonResponse::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Groups $groups
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $group = Groups::select('id', 'name', 'description', 'created_at', 'updated_at')
            ->orderBy('name', 'asc');

        if($request->has('language')){
            $term = $request->language;
            $lang_name = Languages::where('name', $term)->first();
            if(!$lang_name){
                return response()->json(['status' => false, 'errros' => 'Languages '.$term.' does not exist'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $group->where('lang_id', $lang_name->id);
        }

        if ($request->input('name')) {
            $term = $request->input('name');
            $group->where('name', 'like', "%$term%");
        }

        return response()->json(['success' => true, 'data' => $group->paginate(50)], JsonResponse::HTTP_OK);
    }

    /**
     * Download CSV.
     *
     * @param \App\Groups $groups
     * @return \Illuminate\Http\Response
     */
    public function groupsToCsv(Request $request)
    {
        $groups = Groups::select('id', 'name', 'description', 'created_at', 'updated_at')
            ->orderBy('name', 'asc');

        if ($request->input('name')) {
            $term = $request->input('name');
            $groups->where('name', 'like', "%$term%");
        }

        $result = [];

        foreach ($groups->get() as $group) {
            $result[] = [
                'id' => $group->id,
                'name' => $group->name
            ];
        }

        $result = array_merge($this->headings, $result);

        $callback = $this->writerContentCallBack($result);

        $time = time();
        $fileName = "grupos-$time.csv";
        return response()->streamDownload($callback, $fileName);
    }

    /**
     * Write CSV content
     *
     * @param Groups $array
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
     * @param \Illuminate\Http\Request $request
     * @param \App\Groups $groups
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $group = Groups::where('id', $id)->first();

        if (is_null($group)) {
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
            $group->lang_id = $lang_id->id;
        }
        $group->name = $request->name;
        $group->description = $request->description;
        $group->save();

        $success = [
            'status' => true,
            'message' => "El grupo se ha actualizado con éxito",
            'data' => $group
        ];

        return response()->json($success, JsonResponse::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Groups $groups
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $produc = Products::where('id_group', $id)->first();

        if ($produc) {
            return response()->json(['status' => false, 'errors' => 'Grupo asociado a un producto, no se puede eliminar'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $line = Lines::where('id_group', $id)->first();

        if ($line) {
            return response()->json(['status' => false, 'errors' => 'Grupo asociado a una liena, no se puede eliminar'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $group = Groups::where('id', $id)->first();

        if (is_null($group)) {
            return response()->json(['status' => false, 'errors' => 'El id no se encuentra en la Base de Datos'], JsonResponse::HTTP_NOT_FOUND);
        }

        $group->delete();

        $success = [
            'status' => true,
            'message' => "El grupo se ha eliminado con éxito"
        ];

        return response()->json($success, JsonResponse::HTTP_OK);
    }
}
