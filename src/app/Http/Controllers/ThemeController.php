<?php

namespace App\Http\Controllers;

use App\Theme;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Repositories\PictureRepository;

class ThemeController extends Controller
{
    /** @var PictureRepository $pictureRepository */
    private $pictureRepository;

    /**
     * PicturesController constructor.
     * @param PictureRepository $pictureRepository
     */
    public function __construct(PictureRepository $pictureRepository)
    {
        $this->pictureRepository = $pictureRepository;
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
            'dark_theme' => 'required|boolean',
            'text' => 'required_if:dark_theme,false,0|string',
            'wallpaper' => 'required_if:dark_theme,false,0|string',
            'primary_button' => 'required_if:dark_theme,false,0|string',
            'secondary_button' => 'required_if:dark_theme,false,0|string',
            'primary_text' => 'required_if:dark_theme,false,0|string',
            'secondary_text' => 'required_if:dark_theme,false,0|string',
            'font' => 'required_if:dark_theme,false,0|string'
        ];

        $messages = [
            'required' => 'El campo :attribute es requerido',
            'string' => 'El campo :attribute debe ser una cadena'
        ];

        $validator = Validator::make($request->all(), $input, $messages);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $theme = Theme::first();

        if ($theme) {
            if ($request->filled('dark_theme')) {
                $theme->dark_theme = $request->dark_theme;
            }

            if ($request->filled('text')) {
                $theme->text = $request->text;
            }

            if ($request->filled('wallpaper')) {
                $theme->wallpaper = $request->wallpaper;
            }

            if ($request->filled('primary_button')) {
                $theme->primary_button = $request->primary_button;
            }

            if ($request->filled('secondary_button')) {
                $theme->secondary_button = $request->secondary_button;
            }

            if ($request->filled('primary_text')) {
                $theme->primary_text = $request->primary_text;
            }

            if ($request->filled('secondary_text')) {
                $theme->secondary_text = $request->secondary_text;
            }

            if ($request->filled('font')) {
                $theme->font = $request->font;
            }

            $theme->save();
            $crud = 'actualizado';
        } else {
            $theme = Theme::create([
                'dark_theme' => $request->dark_theme,
                'text' => $request->text,
                'wallpaper' => $request->wallpaper,
                'primary_button' => $request->primary_button,
                'secondary_button' => $request->secondary_button,
                'primary_text' => $request->primary_text,
                'secondary_text' => $request->secondary_text,
                'font' => $request->font
            ]);

            $crud = 'registrado';
        }

        $theme = Theme::first();

        $success = [
            'status' => true,
            'message' => "El tema se ha " . $crud . " con éxito",
            'data' => $theme
        ];

        return response()->json($success, JsonResponse::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param Theme $theme
     * @return Response
     */
    public function show(Request $request)
    {
        $theme = Theme::first();

        return response()->json(['success' => true, 'data'=> $theme]);
    }

    /**
     * Upload company logo.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Theme  $theme
     * @return Response
     */
    public function uploadLogo(Request $request)
    {
        $input = [
            'logo_path' => 'required|file|mimes:jpeg,jpg,png|max:30000'
        ];

        $messages = [
            'required' => 'El campo :attribute es requerido',
            'file' => 'El campo :attribute debe ser una archivo'
        ];

        $validator = Validator::make($request->all(), $input, $messages);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $theme = Theme::first();

        if ($theme) {
            if ($request->has('logo_path')) {
                $this->pictureRepository->saveThemeLogo($request->all());
            }

            $theme->save();
            $crud = 'actualizado';
        } else {
            $theme = Theme::create([
                'dark_theme' => false
            ]);

            $this->pictureRepository->saveThemeLogo($request->all());
            $crud = 'registrado';
        }

        $theme = Theme::first();

        $success = [
            'status' => true,
            'message' => "El logo se ha " . $crud . " con éxito",
            'data' => $theme
        ];

        return response()->json($success, JsonResponse::HTTP_CREATED);
    }
}
