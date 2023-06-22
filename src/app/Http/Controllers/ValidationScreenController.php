<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use App\Http\Requests\UpdateScanRequest;
use App\Http\Requests\ValidationRequest;
use App\Http\Requests\ValidateScanRequest;
use App\Http\Resources\ItemScanCollection;
use App\Http\Resources\Picture as PictureResource;
use App\Http\Resources\Scan as ScanResource;
use App\Repositories\PictureRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ScanRepository;
use App\Services\UploadSpaces;
use App\ScanPictures;
use App\Scans;
use App\Products;
use App\UserPoints;
use App\User;
use App\DeviceToken;
use App\Notifications;


class ValidationScreenController extends Controller
{
    /** @var ProductRepository */
    private $productRepository;
    /** @var ScanRepository */
    private $scanRepository;

    /** @var UploadSpaces $spaces */
    private $spaces;

    /** @var PictureRepository $pictureRepository */
    private $pictureRepository;

    /**
     * ValidationScreenController constructor.
     * @param ProductRepository $productRepository
     * @param ScanRepository $scanRepository
     * @param UploadSpaces $spaces
     */
    public function __construct(ProductRepository $productRepository, ScanRepository $scanRepository, UploadSpaces $spaces, PictureRepository $pictureRepository)
    {
        $this->productRepository = $productRepository;
        $this->scanRepository = $scanRepository;
        $this->spaces = $spaces;
        $this->pictureRepository = $pictureRepository;
    }

    public function saveScan(ValidationRequest $request)
    {
        $dataScan = $request->validated();
        $place = $dataScan['place'];

        if (is_null($place) || is_null($place['name']) || is_null($place['address']) || is_null($place['lat']) || is_null($place['long'])) {
            return response()->json([
                'status' => 'error',
                'type' => 'Es imposible recibir las capturas sin información de localización. Confirma que tus servicios de Ubicación estén siempre prendidos para realizar esta acción.'
            ], 400);
        }

        $scan = new ScanResource($this->scanRepository->save($dataScan));

        return response()->json([
            'status' => 'success',
            'scan' => $scan
        ]);
    }

    public function validateScan(ValidateScanRequest $request, $scan)
    {
        $status = 200;
        $response = [];
        $user = Auth::user();
        $scan = Scans::find($scan);

        if ($user->hasRole(['Scanner'])) {
            $response['status'] = 'error';
            $response['message'] = "Role no valid";
            return response()->json($response, 401);
        }

        if ($scan->is_valid == 1) {
            return response()->json([
                'status' => 'error',
                'type' => 'scan_reviewed'
            ], 401);
        }

        $dataValidate = $request->validated();
        $result = $this->scanRepository->validate($scan, $dataValidate, auth()->user()->id);

        $mission = $scan->mission()->first();
        UserPoints::create([
            'id_mission' =>$scan->id_mission,
            'id_user' => $scan->id_scanned_by,
            'reason' => 'scans valid: '.$scan->id,
            'amount' => $mission->capture_points
        ]);

        $response['status'] = 'success';
        $response['scan'] = $result;

        if (is_null($result)) {
            $response['status'] = 'Error, scan rejected';
            $status = 400;
        } else {
            $scan_accepted = Scans::where('id_scanned_by', $scan->id_scanned_by)
                ->where('is_valid', 1)
                ->where('is_rejected', 0)
                ->count();
            $recipients = DeviceToken::where('id_user', $scan->id_scanned_by)
                ->pluck('device_token')->toArray();
            $date = date('Y-m-d H:m:s');

            if($scan_accepted == 1) {

                $payloads = [
                    'content_available' => true,
                    'data' => [
                        'title' => '¡Felicidades subiste de rango a Nivel 1!',
                        'description' => 'Sigue capturando',
                        'type' => 'LEVEL_1',
                        'dateTime' => $date
                    ],
                    'notification' => [
                        'title' => '¡Felicidades subiste de rango a Nivel 1!',
                        'body' => 'Sigue capturando'
                    ]
                ];

                $payloads['registration_ids'] = $recipients;
                $payloads['time_to_live'] = 60;
                $payloads['android_channel_id'] = env('ANDROID_CHANNEL_ID');

                $headers = [
                    'Authorization: key=' . env('FCM_SERVER_KEY'),
                    'Content-Type: application/json',
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, env('FCM_ENDPOINT'));
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloads));
                curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);

                $response = curl_exec($ch);
                curl_close($ch);

                Notifications::create([
                    'id_user' => $scan->id_scanned_by,
                    'notification_title' => '¡Felicidades subiste de rango a Nivel 1!',
                    'body' => 'Sigue capturando',
                    'data_title' => '¡Felicidades subiste de rango a Nivel 1!',
                    'description' => 'Sigue capturando',
                    'type' => 'LEVEL_1',
                    'dateTime'  => $date
                ]);
            }

            if($scan_accepted == 1000) {

                $payloads = [
                    'content_available' => true,
                    'data' => [
                        'title' => '¡Felicidades subiste de rango a Nivel 2!',
                        'description' => 'Sigue capturando',
                        'type' => 'LEVEL_2',
                        'dateTime' => $date
                    ],
                    'notification' => [
                        'title' => '¡Felicidades subiste de rango a Nivel 2!',
                        'body' => 'Sigue capturando'
                    ]
                ];

                $payloads['registration_ids'] = $recipients;
                $payloads['time_to_live'] = 60;
                $payloads['android_channel_id'] = env('ANDROID_CHANNEL_ID');

                $headers = [
                    'Authorization: key=' . env('FCM_SERVER_KEY'),
                    'Content-Type: application/json',
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, env('FCM_ENDPOINT'));
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloads));
                curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);

                $response = curl_exec($ch);
                curl_close($ch);

                Notifications::create([
                    'id_user' => $scan->id_scanned_by,
                    'notification_title' => '¡Felicidades subiste de rango a Nivel 2!',
                    'body' => 'Sigue capturando',
                    'data_title' => '¡Felicidades subiste de rango a Nivel 2!',
                    'description' => 'Sigue capturando',
                    'type' => 'LEVEL_2',
                    'dateTime'  => $date
                ]);
            }

            if($scan_accepted == 3000) {

                $payloads = [
                    'content_available' => true,
                    'data' => [
                        'title' => '¡Felicidades subiste de rango a Nivel 3!',
                        'description' => 'Sigue capturando',
                        'type' => 'LEVEL_3',
                        'dateTime' => $date
                    ],
                    'notification' => [
                        'title' => '¡Felicidades subiste de rango a Nivel 3!',
                        'body' => 'Sigue capturando'
                    ]
                ];

                $payloads['registration_ids'] = $recipients;
                $payloads['time_to_live'] = 60;
                $payloads['android_channel_id'] = env('ANDROID_CHANNEL_ID');

                $headers = [
                    'Authorization: key=' . env('FCM_SERVER_KEY'),
                    'Content-Type: application/json',
                ];

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, env('FCM_ENDPOINT'));
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloads));
                curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);

                $response = curl_exec($ch);
                curl_close($ch);

                Notifications::create([
                    'id_user' => $scan->id_scanned_by,
                    'notification_title' => '¡Felicidades subiste de rango a Nivel 3!',
                    'body' => 'Sigue capturando',
                    'data_title' => '¡Felicidades subiste de rango a Nivel 3!',
                    'description' => 'Sigue capturando',
                    'type' => 'LEVEL_3',
                    'dateTime'  => $date
                ]);
            }
        }

        return response()->json($response, $status);
    }

    public function rejectedScan(Request $request, Scans $scan)
    {
        $scan->is_rejected = 1;
        $scan->save();

        return response()->json([
            'status' => 'success'
        ], 201);
    }

    public function listScans(Request $request): JsonResponse
    {
        $scans = $this->scanRepository->listScans($request);
        $scansResource = new ItemScanCollection($scans->paginate(100));

        return response()->json([
            'status' => 'success',
            'scans' => $scansResource
        ]);
    }

    /**
     * @param Request $request
     * @param Scans $scan
     */
    public function getScan(Request $request, Scans $scan)
    {
        if ($scan->is_locked == 1) {
            return response()->json([
                'status' => 'error',
                'type' => 'scan_locked'
            ], 401);
        }

        if ($scan->is_rejected == 1 || $scan->is_valid == 1) {
            return response()->json([
                'status' => 'error',
                'type' => 'scan_reviewed'
            ], 401);
        }

        $itemScan = $this->scanRepository->findScan($request, $scan);
        $resourceScan = new ScanResource($itemScan);

        return response()->json([
            'status' => 'success',
            'scan' => $resourceScan
        ]);
    }

    public function appScanList(Request $request)
    {
        $scans = Scans::select ('scans.*', 'scan_pictures.product_picture', 'scan_pictures.shelf_picture', 'scan_pictures.promo_picture')
            ->join('scan_pictures', 'scans.id', 'scan_pictures.id_scan')
            ->where('id_scanned_by', $request->input('user_id'))
            ->where('is_valid', 0)
            ->where('is_rejected', 0)
            ->orderBy('scans.created_at', 'DESC')->get();


        return response(array(
            'status' => 'success',
            'scans' => $scans,
            'accepted' => Scans::where('id_scanned_by', $request->input('user_id'))->where('is_valid', 1)->count(),
            'rejected' => Scans::where('id_scanned_by', $request->input('user_id'))->where('is_rejected', 1)->count()
        ));
    }

    /**
     * @param Request $request
     * @param Scans $scan
     */
    public function showScan(Request $request, Scans $scan)
    {
        $resourceScan = new ScanResource($scan);
        return response()->json([
            'status' => 'success',
            'scan' => $resourceScan
        ]);
    }

    public function updateScan(UpdateScanRequest $request, Scans $scan)
    {
        $status = 200;
        $response = [];
        $user = Auth::user();

        if ($user->hasRole(['Scanner'])) {
            $response['status'] = 'error';
            $response['message'] = "Role no valid";
            return response()->json($response, 401);
        }

        $dataValidate = $request->validated();

        if (empty($dataValidate)) {
            return response()->json(['error' => 'empty params'], JsonResponse::HTTP_BAD_REQUEST);
        }

        $scanData = $dataValidate['scan'];
        $barcode = Products::where('barcode', $scanData['barcode'])->first();

        if(is_null($barcode)) {
            return response()->json(['error' => 'the barcode does not exist'], JsonResponse::HTTP_CONFLICT);
        }

        $result = $this->scanRepository->updateScan($scan, $dataValidate, $barcode->id);

        $resourceScan = new ScanResource($result);

        return response()->json([
            'status' => 'success',
            'scan' => $resourceScan
        ], $status);
    }

    public function destroy(Request $request, Scans $scan)
    {
        $scan->is_enable = 0;
        $scan->save();
        $scan->delete();

        return response()->json([
            'status' => 'success'
        ], 204);
    }

    public function restore($id)
    {
        $scan = Scans::onlyTrashed()->find($id);
        $scan->restore();
        $scan->is_enable = 1;
        $scan->save();

        return response()->json([
            'status' => 'success'
        ], JsonResponse::HTTP_ACCEPTED);
    }

    public function updatePictureProductScan(Request $request, Scans $scan)
    {
        $validator = Validator::make($request->all(), [
            'product_picture' => 'file|mimes:jpeg,jpg,bmp,png|max:30000|nullable',
            'shelf_picture' => 'file|mimes:jpeg,jpg,bmp,png|max:30000|nullable'
        ]);

        if ($validator->fails()) {
            return response()->json(['Validation errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $status = null;
        $picture = $scan->pictures()->first();

        if(is_null($picture)){
            $picture = new ScanPictures();
            $params = $request->all();
            $params['id_scan'] = $scan->id;
            $this->pictureRepository->savePictures($picture, $params);
            $status = true;
        } else {

            if (!empty($request->product_picture) || !empty($request->shelf_picture)) {

                $params = $request->all();
                $params['barCode'] = $scan->barcode;
                $this->scanRepository->updatePictures($picture, $params);
                $status = true;
            }
        }

        if (!$status) {
            return response()->json([
                'status' => 'error',
                'type' => "Can't upload image"
            ], 400);
        }

        $pictureResource = new PictureResource($picture);

        return response()->json($pictureResource, JsonResponse::HTTP_OK);
    }

    public function scanBeingValidated(Request $request)
    {
        $input = [
            'id_scan' => 'required|numeric',
            'status' => 'required|boolean'
         ];

         $messages = [
            'id_scan.numeric' => 'El id_scan debe ser numerico.',
            'status.boolean' => 'El status debe ser true, false, 1, 0, "1" ó "0".',
            'required' => 'El campo :attribute es requerido.'
        ];

        $validator = Validator::make($request->all(), $input, $messages);

        if ($validator->fails()) {
            return response()->json(['Validation errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $scan = Scans::where('id', $request->id_scan)->first();

        if(is_null($scan)){
            return response()->json(
                ['message' => 'La captura no se encuentra en la base'],
                JsonResponse::HTTP_NOT_FOUND
            );
        }

        if ($request->status) {
            if ($scan->being_validated) {
                return response()->json([
                    'isBeingValidated' => true,
                    'validatorId' => $scan->id_being_reviwed_by
                    ],JsonResponse::HTTP_OK
                );
            } else {
                $scan->being_validated = $request->status;
                $scan->id_being_reviwed_by = auth()->user()->id;
                $scan->save();

                return response()->json([
                    'isBeingValidated' => true,
                    'validatorId' => $scan->id_being_reviwed_by
                    ],JsonResponse::HTTP_OK
                );
            }
        } else {
            $scan->being_validated = $request->status;
            $scan->id_being_reviwed_by = null;
            $scan->save();

            return response()->json([
                'isBeingValidated' => false,
                'validatorId' => $scan->id_being_reviwed_by
                ],JsonResponse::HTTP_OK
            );
        }
    }

    /**
     * @param Request $request
     * @param Scans $scan
     */
    public function simulateScan(Request $request, Scans $scan, $barcode)
    {
        if ($scan->is_locked == 1) {
            return response()->json([
                'status' => 'error',
                'type' => 'scan_locked'
            ], 401);
        }

        if ($scan->is_rejected == 1 || $scan->is_valid == 1) {
            return response()->json([
                'status' => 'error',
                'type' => 'scan_reviewed'
            ], 401);
        }

        $itemScan = $this->scanRepository->findScan($request, $scan);
        $product = Products::where('barcode', $barcode)->first();
        $itemScan->barcode = $barcode;
        $itemScan->id_product = $product->id ?? null;

        $resourceScan = new ScanResource($itemScan);

        return response()->json([
            'status' => 'success',
            'scan' => $resourceScan
        ]);
    }
}
