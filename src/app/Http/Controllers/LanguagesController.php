<?php

namespace App\Http\Controllers;
use App\Languages;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;


class LanguagesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
    */
    public function list(Request $request)
    {
        $query = Languages::query();
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
            'name' => 'required|string|max:30',
            'abbreviation' => 'string|max:5|nullable'
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


        $language = Languages::create([
            'name' => $request->name,
            'abbreviation' => $request->abbreviation
        ]);
        

        $success = [
            'status' => true,
            'message' => "El lenguaje se ha registrado con éxito",
            'data' => $language
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
        $language = Languages::orderBy('name', 'asc');

        if ($request->input('name')) {
            $term = $request->input('name');
            $language->where('name', 'like', "%$term%");
        }

        return response()->json(['success' => true, 'data' => $language->paginate(50)], JsonResponse::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Language $language
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $language = Languages::where('id', $id)->first();

        if (is_null($language)) {
            return response()->json(['status' => false, 'errors' => 'El id no se encuentra en la Base de Datos'], JsonResponse::HTTP_NOT_FOUND);
        }

        $input = [
            'name' => 'required|string|max:255',
            'abbreviation' => 'string|max:255|nullable'
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


        $language->name = $request->name;
        $language->abbreviation = $request->abbreviation;
        $language->save();

        $success = [
            'status' => true,
            'message' => "El lenguaje se ha actualizado con éxito",
            'data' => $language
        ];

        return response()->json($success, JsonResponse::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Language $language
     * @return Response
     */
    public function destroy($id)
    {
        $language = Languages::where('id', $id)->first();

        if (is_null($language)) {
            return response()->json(['status' => false, 'errors' => 'El id no se encuentra en la Base de Datos'], JsonResponse::HTTP_NOT_FOUND);
        }

        $language->delete();

        $success = [
            'status' => true,
            'message' => "La lenguaje se ha eliminado con éxito"
        ];

        return response()->json($success, JsonResponse::HTTP_OK);
    }
}
