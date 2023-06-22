<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoleCollection;
use App\Http\Resources\UnitCollection;
use App\Repositories\UnitsRepository;
use App\Units;
use App\Products;
use App\Languages;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Resources\Unit as UnitResource;

class UnitsController extends Controller
{
    /** @var UnitsRepository */
    private $unitsRepository;

    /**
     * UnitsController constructor.
     * @param UnitsRepository $unitsRepository
     */
    public function __construct(UnitsRepository $unitsRepository)
    {
        $this->unitsRepository = $unitsRepository;
    }


    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $units = $this->unitsRepository->Filters($request);
        $collection = new UnitCollection($units->paginate(5));
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
        $query = $this->unitsRepository->Filters($request);
        $units = $query->get()->pluck('name', 'id');

        return response()->json(['success' => true, 'data'=> $units]);
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
            'name' => 'required|string|max:255'
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
            $unit = Units::create([
                'name' => $request->name,
                'abbreviation' => $request->abbreviation,
                'lang_id' => $lang_id->id
            ]);
        }else{
            $unit = Units::create([
                'name' => $request->name,
                'abbreviation' => $request->abbreviation
            ]);
        }

        $success = [
            'status' => true,
            'message' => "La unidad se ha registrado con éxito",
            'data' => $unit
        ];

        return response()->json($success, JsonResponse::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param Units $units
     * @return JsonResponse
     */
    public function show(Request $request)
    {
        $units = Units::orderBy('name', 'asc');

        if($request->has('language')){
            $term = $request->language;
            $lang_name = Languages::where('name', $term)->first();
            if(!$lang_name){
                return response()->json(['status' => false, 'errros' => 'Languages '.$term.' does not exist'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $units->where('lang_id', $lang_name->id);
        }

        if ($request->input('name')) {
            $term = $request->input('name');
            $units->where('name', 'like', "%$term%");
        }

        return response()->json(['success' => true, 'data' => $units->paginate(50)], JsonResponse::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Units $units
     * @return JsonResponse
     */
    public function update(Request $request, $id)
    {
        $unit = Units::where('id', $id)->first();

        if (is_null($unit)) {
            return response()->json(['status' => false, 'errors' => 'El id no se encuentra en la Base de Datos'], JsonResponse::HTTP_NOT_FOUND);
        }

        $input = [
            'name' => 'required|string|max:255',
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
            $unit->lang_id = $lang_id->id;
        }
        $unit->name = $request->name;
        $unit->save();

        $success = [
            'status' => true,
            'message' => "La unidad se ha actualizado con éxito",
            'data' => $unit
        ];

        return response()->json($success, JsonResponse::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Units $unit
     * @return JsonResponse
     * @throws \Exception
     */
    public function destroy($id)
    {
        $produc = Products::where('id_unit', $id)->first();

        if ($produc) {
            return response()->json(['status' => false, 'errors' => 'Unidad asociada a un producto, no se puede eliminar'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $unit = Units::where('id', $id)->first();

        if (is_null($unit)) {
            return response()->json(['status' => false, 'errors' => 'El id no se encuentra en la Base de Datos'], JsonResponse::HTTP_NOT_FOUND);
        }

        $unit->delete();

        $success = [
            'status' => true,
            'message' => "La unidad se ha eliminado con éxito"
        ];

        return response()->json($success, JsonResponse::HTTP_OK);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function restore($id)
    {
        $unit = Units::onlyTrashed()->findOrFail($id);
        $unit->restore();
        return response()->json(['success' => true], JsonResponse::HTTP_ACCEPTED);
    }


}
