<?php

namespace App\Http\Controllers;
use App\Languages;
use App\Settings;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function update(Request $request)
    {
        $setting = Settings::where('id', 1)->first();

        if (is_null($setting)) {
            return response()->json(['status' => false, 'errors' => 'El id no se encuentra en la Base de Datos'], JsonResponse::HTTP_NOT_FOUND);
        }

        $input = [
            'logo_path' => 'string|max:255|nullable',
            'language' => 'string|max:255|nullable'
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
            $setting->lang_id = $lang_id->id;
        }
        if($request->has('language')){
            $setting->logo_path = $request->logo_path;
        }
        $setting->save();

        $success = [
            'status' => true,
            'message' => "Los ajustes se han actualizado con éxito",
            'data' => $setting
        ];

        return response()->json($success, JsonResponse::HTTP_OK);
    }
}
