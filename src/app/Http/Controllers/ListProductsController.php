<?php

namespace App\Http\Controllers;

use App\ListProducts;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ListProductsController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $input = [
            'products' => 'required|array'
        ];

        $messages = [
            'required' => 'El campo :attribute es requerido',
            'array' => 'El campo :attribute debe ser un arreglo',
        ];

        $validator = Validator::make($request->all(), $input, $messages);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $id = Auth::user()->id;

        try {
            foreach ($request->products as $product) {
                $exists = ListProducts::where('id_user', $id)->where('id_product', $product)->first();

                if ($exists) {
                    continue;
                }

                $list_prduct = ListProducts::create([
                    'id_user' => $id,
                    'id_product' => $product
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'errors' => 'Ocurrió un error al crear la lista, por favor, inténtalo nuevamente.'
            ], 400);
        }

        $list = ListProducts::where('id_user', $id)->get();

        $success = [
            'status' => true,
            'message' => "La lista se creó con éxito.",
            'data' => $list,
        ];

        return response()->json($success, JsonResponse::HTTP_CREATED);
    }

    /**
     * List of products.
     *
     * @param Request $request
     * @return Response
     */
    public function list(Request $request)
    {
        $list = ListProducts::select('list_of_products.id', 'products.name')
            ->join('products', 'products.id', 'list_of_products.id_product')
            ->where('id_user', Auth::user()->id)
            ->pluck('name', 'id');

        return response()->json(['success' => true, 'data'=> $list]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @return Response
     */
    public function destroy(Request $request)
    {
        $input = [
            'products' => 'required|array'
        ];

        $messages = [
            'required' => 'El campo :attribute es requerido',
            'array' => 'El campo :attribute debe ser un arreglo',
        ];

        $validator = Validator::make($request->all(), $input, $messages);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $id = Auth::user()->id;

        try {
            foreach ($request->products as $product) {
                $list = ListProducts::where('id_user', $id)->where('id_product', $product)->delete();
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'errors' => 'Ocurrió un error al eliminar la lista, por favor, inténtalo nuevamente.'
            ], 400);
        }

        $success = [
            'status' => true,
            'message' => "La lista se eliminó con éxito."
        ];

        return response()->json($success, JsonResponse::HTTP_OK);
    }
}
