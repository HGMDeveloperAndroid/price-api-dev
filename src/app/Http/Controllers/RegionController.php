<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoleCollection;
use App\Region;
use App\Languages;
use App\Http\Resources\Region as RegionResource;
use App\Repositories\RegionRepository;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use League\Csv\Writer;

class RegionController extends Controller
{
    private $headings = [[
        'Id',
        'Nombre'
    ]];

    /** @var RegionRepository $regionRepository */
    private $regionRepository;

    function __construct(RegionRepository $regionRepository)
    {
        $this->regionRepository = $regionRepository;

//        $this->middleware('permission:label-list|label-create|label-edit|label-delete', ['only' => ['index', 'show']]);
//        $this->middleware('permission:label-create', ['only' => ['store']]);
//        $this->middleware('permission:label-edit', ['only' => ['update']]);
//        $this->middleware('permission:label-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $regions = $this->regionRepository->Filters($request);
        $collection = new RoleCollection($regions->paginate(50));
        return response()->json($collection);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $input = [
            'name' => 'required|unique:zones|max:255'
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
            $region = Region::create([
                'name' => $request->name,
                'description' => $request->description,
                'alias' => $request->alias,
                'short_name' => Str::slug($request->name),
                'lang_id' => $lang_id->id
            ]);
        }else{
            $region = Region::create([
                'name' => $request->name,
                'description' => $request->description,
                'alias' => $request->alias,
                'short_name' => Str::slug($request->name)
            ]);
        }

        $success = [
            'status' => true,
            'message' => "La region se ha registrado con éxito",
            'data' => $region
        ];

        return response()->json($success, JsonResponse::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param Region $region
     * @return JsonResponse
     */
    public function show(Request $request)
    {
        $region = Region::orderBy('name', 'asc');
        
        if($request->has('language')){
            $term = $request->language;
            $lang_name = Languages::where('name', $term)->first();
            if(!$lang_name){
                return response()->json(['status' => false, 'errros' => 'Languages '.$term.' does not exist'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $region->where('lang_id', $lang_name->id);
        }

        if ($request->input('name')) {
            $term = $request->input('name');
            $region->where('name', 'like', "%$term%");
        }

        return response()->json(['success' => true, 'data' => $region->paginate(50)], JsonResponse::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Region $region
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        $region = Region::where('id', $id)->first();

        if (is_null($region)) {
            return response()->json(['status' => false, 'errors' => 'El id no se encuentra en la Base de Datos'], JsonResponse::HTTP_NOT_FOUND);
        }

        $input = [
            'name' => 'required|max:255|unique:zones,name,' . $id,
            'alias' => 'required|max:255'
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
            $region->lang_id = $lang_id->id;
        }
        $region->name = $request->name;
        $region->alias = $request->alias;
        $region->description = $request->description;
        $region->short_name = Str::slug($request->name);
        $region->save();

        $success = [
            'status' => true,
            'message' => "La region se ha actualizado con éxito",
            'data' => $region
        ];

        return response()->json($success, JsonResponse::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Region $region
     * @return JsonResponse
     */
    public function destroy($id)
    {
        $users = DB::table('zone_users')
            ->join('users', 'users.id', 'zone_users.id_user')
            ->whereNull('users.deleted_at')
            ->where('id_zone', $id)->first();

        if ($users) {
            return response()->json(['status' => false, 'errors' => 'Región asociada a un usuario, no se puede eliminar'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $mission = DB::table('zone_missions')
            ->join('missions', 'missions.id', 'zone_missions.id_mission')
            ->whereNull('missions.deleted_at')
            ->where('id_zone', $id)->first();

        if ($mission) {
            return response()->json(['status' => false, 'errors' => 'Región asociada a una misión, no se puede eliminar'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $region = Region::where('id', $id)->first();

        if (is_null($region)) {
            return response()->json(['status' => false, 'errors' => 'El id no se encuentra en la Base de Datos'], JsonResponse::HTTP_NOT_FOUND);
        }

        $region->delete();

        $success = [
            'status' => true,
            'message' => "La region se ha eliminado con éxito"
        ];

        return response()->json($success, JsonResponse::HTTP_OK);
    }


    /**
     * @param $id
     * @return JsonResponse
     */
    public function restore($id)
    {
        $region = Region::onlyTrashed()->findOrFail($id);
        $region->restore();
        return response()->json(['success' => true], JsonResponse::HTTP_ACCEPTED);
    }

    /**
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function addRegionsToUser(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'regions' => ['required'],
            'regions.*' => ['required', 'numeric']
        ], ['regions.*' => "The region is required"]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()],
                JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($request->has('regions') && !empty($request->regions)) {
            $user->regions()->sync($request->regions);
        }

        return response()->json(['success' => 'success'], Response::HTTP_ACCEPTED);
    }

    /**
     * Download CSV.
     *
     * @param \App\Groups $groups
     * @return \Illuminate\Http\Response
     */
    public function regionToCsv(Request $request)
    {
        $regions = Region::orderBy('name', 'asc');

        if ($request->input('name')) {
            $term = $request->input('name');
            $regions->where('name', 'like', "%$term%");
        }

        $result = [];

        foreach ($regions->get() as $region) {
            $result[] = [
                'id' => $region->id,
                'name' => $region->name
            ];
        }

        $result = array_merge($this->headings, $result);

        $callback = $this->writerContentCallBack($result);

        $time = time();
        $fileName = "regions-$time.csv";
        return response()->streamDownload($callback, $fileName);
    }

    /**
     * Write CSV content
     *
     * @param Region $array
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
}
