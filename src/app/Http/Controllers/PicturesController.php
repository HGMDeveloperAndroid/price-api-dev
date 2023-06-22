<?php

namespace App\Http\Controllers;

use App\Http\Resources\Picture as PictureResource;
use App\Repositories\PictureRepository;
use App\ScanPictures;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Settings;
use App\Chains;

class PicturesController extends Controller
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
     * @param Request $request
     * @return JsonResponse
     */
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_picture' => 'required|file|mimes:jpeg,jpg,png|max:30000|nullable',
            'shelf_picture' => 'file|mimes:jpeg,jpg,png|max:30000|nullable',
            'promo_picture' => 'file|mimes:jpeg,jpg,png|max:30000|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json(['Validation errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $pictures = new ScanPictures();
        $this->pictureRepository->savePictures($pictures, $request->all());

        $pictureResource = new PictureResource($pictures);

        return response()->json($pictureResource, JsonResponse::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function saveUserImage(Request $request, $id)
    {
        $user = User::find($id);

        if (is_null($user)) {
            return response()->json(['status' => false, 'errors' => 'El id no se encuentra en la Base de Datos'], JsonResponse::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'image_user' => 'required|file|mimes:jpeg,jpg,png|max:30000'
        ]);

        if ($validator->fails()) {
            return response()->json(['Validation errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->pictureRepository->saveUserImage($user, $request->all());

        return response()->json($user, JsonResponse::HTTP_OK);
    }

        /**
     * @param Request $request
     * @return JsonResponse
     */
    public function saveLogoImage(Request $request)
    {
        $setting = Settings::where('id', 1)->first();
        $validator = Validator::make($request->all(), [
            'image_logo' => 'required|file|mimes:jpeg,jpg,png|max:30000'
        ]);

        if ($validator->fails()) {
            return response()->json(['Validation errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->pictureRepository->saveLogoImage($request->all());
        $setting = Settings::where('id', 1)->first();
        return response()->json($setting, JsonResponse::HTTP_OK);
    }

            /**
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function saveChainImage(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|file|mimes:jpeg,jpg,png|max:30000'
        ]);

        if ($validator->fails()) {
            return response()->json(['Validation errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $this->pictureRepository->saveChainImage($request->all(), $id);
        $chain = Chains::where('id', $id)->first();
        return response()->json($chain, JsonResponse::HTTP_OK);
    }

}
