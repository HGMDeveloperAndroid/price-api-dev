<?php

namespace App\Http\Controllers;

use App\RejectionCriteria;
use App\Products;
use App\Scans;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class RejectionCriteriaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request)
    {
        $query = RejectionCriteria::query();

        if ($request->has('criterion')) {
            $term = $request->criterion;
            $query->where('criterion', 'like', "%$term%");
        }

        $rejection_criteria = $query->orderBy('criterion', 'asc')
            ->pluck('criterion', 'id');
        return response()->json(['success' => true, 'data'=> $rejection_criteria]);
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
            'criterion' => 'required|string|max:255',
        ];

        $messages = [
            'required' => 'El campo :attribute es requerido',
            'string' => 'El campo :attribute debe ser una cadena',
        ];

        $validator = Validator::make($request->all(), $input, $messages);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $rejection_criteria = RejectionCriteria::create([
            'criterion' => $request->criterion
        ]);

        $success = [
            'status' => true,
            'message' => "El criterio se ha registrado con éxito",
            'data' => $rejection_criteria,
        ];

        return response()->json($success, JsonResponse::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param RejectionCriteria $RejectionCriteria
     * @return Response
     */
    public function show(Request $request)
    {
        $rejection_criteria = RejectionCriteria::orderBy('criterion', 'asc');

        if ($request->input('criterion')) {
            $term = $request->input('criterion');
            $rejection_criteria->where('criterion', 'like', "%$term%");
        }

        return response()->json(['success' => true, 'data' => $rejection_criteria->paginate(50)], JsonResponse::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param RejectionCriteria $RejectionCriteria
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $rejection_criteria = RejectionCriteria::where('id', $id)->first();

        if (is_null($rejection_criteria)) {
            return response()->json(['status' => false, 'errors' => 'El id no se encuentra en la Base de Datos'], JsonResponse::HTTP_NOT_FOUND);
        }

        $input = [
            'criterion' => 'required|string|max:255',
        ];

        $messages = [
            'required' => 'El campo :attribute es requerido',
            'string' => 'El campo :attribute debe ser una cadena',
        ];

        $validator = Validator::make($request->all(), $input, $messages);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $rejection_criteria->criterion = $request->criterion;
        $rejection_criteria->save();

        $success = [
            'status' => true,
            'message' => "El criterio se ha actualizado con éxito",
            'data' => $rejection_criteria,
        ];

        return response()->json($success, JsonResponse::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param RejectionCriteria $RejectionCriteria
     * @return Response
     */
    public function destroy($id)
    {
        $produc = Products::where('id_rejection_criteria', $id)->first();

        if ($produc) {
            return response()->json(['status' => false, 'errors' => 'Marca asociada a un producto, no se puede eliminar'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $rejection_criteria = RejectionCriteria::where('id', $id)->first();

        if (is_null($rejection_criteria)) {
            return response()->json(['status' => false, 'errors' => 'El id no se encuentra en la Base de Datos'], JsonResponse::HTTP_NOT_FOUND);
        }

        $rejection_criteria->delete();

        $success = [
            'status' => true,
            'message' => "La marca se ha eliminado con éxito",
        ];

        return response()->json($success, JsonResponse::HTTP_OK);
    }

    /**
     * Assign criteria to scan.
     *
     * @param Request $request
     * @param RejectionCriteria $id
     * @return Response
     */
    public function assignCriteria(Request $request, $id)
    {
        $scan = Scans::where('id', $id)->first();

        if (is_null($scan)) {
            return response()->json(['status' => false, 'errors' => 'El id no se encuentra en la Base de Datos'], JsonResponse::HTTP_NOT_FOUND);
        }

        $input = [
            'id_criterion' => 'required|integer|exists:rejection_criteria,id',
        ];

        $messages = [
            'required' => 'El campo :attribute es requerido',
            'integer' => 'El campo :attribute debe ser un entero',
            'exists' => 'El campo :attribute no existe en la tabla'
        ];

        $validator = Validator::make($request->all(), $input, $messages);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $scan->id_criterion = $request->id_criterion;
        $scan->save();

        $success = [
            'status' => true,
            'message' => "El criterio de rechazo se ha guardado con éxito"
        ];

        return response()->json($success, JsonResponse::HTTP_OK);
    }
}
