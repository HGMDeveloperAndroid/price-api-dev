<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use App\User;
use App\DeviceToken;
use App\Notifications;

class FirebaseController extends Controller
{
    /**
     * Save device token.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function saveToken(Request $request)
    {
        $input = [
            'device_token' => 'required|string'
        ];

        $messages = [
            'required' => 'El campo :attribute es requerido',
            'string' => 'El campo :attribute debe ser una cadena'
        ];

        $validator = Validator::make($request->all(), $input, $messages);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $token = DeviceToken::where('device_token', $request->device_token)->first();

        if ($token) {
            $token->id_user = Auth::user()->id;
            $token->save();
            $action = 'actualizado';
        } else {
            $device_token = DeviceToken::create([
                'id_user' => Auth::user()->id,
                'device_token' => $request->device_token
            ]);
            $action = 'registrado';
        }

        $success = [
            'status' => true,
            'message' => "El token se ha ". $action . " con éxito"
        ];

        return response()->json($success, JsonResponse::HTTP_CREATED);
    }

    /**
     * Send general notification.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function sendGeneralNotification(Request $request)
    {
        $input = [
            'title' => 'required|string',
            'description' => 'required|string'
        ];

        $messages = [
            'required' => 'El campo :attribute es requerido',
            'string' => 'El campo :attribute debe ser una cadena'
        ];

        $validator = Validator::make($request->all(), $input, $messages);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $recipients = DeviceToken::pluck('device_token')->toArray();
        $date = date('Y-m-d H:m:s');

        fcm()
            ->to($recipients)
            ->notification([
                'title' => $request->title,
                'body' => 'Nueva notificacion'
            ])
            ->data([
                'title' => $request->title,
                'description' => $request->description,
                'type' => 'GENERAL_NOTIFICATION',
                'dateTime' => $date
            ])
            ->send();

        $users = DeviceToken::select('id_user')->groupBy('id_user')->get();

        foreach ($users as $user) {
            Notifications::create([
                'id_user' => $user->id_user,
                'notification_title' => $request->title,
                'body' => 'Nueva notificacion',
                'data_title' => $request->title,
                'description' => $request->description,
                'type' => 'GENERAL_NOTIFICATION',
                'dateTime'  => $date
            ]);
        }

        return response()->json('Notificación enviada a todos los usuarios (Android).', JsonResponse::HTTP_OK);
    }

    /**
     * Show active notifications by user.
     *
     * @param  id user  $id
     * @return Response
     */
    public function show($id)
    {
        $notifications = Notifications::where('id_user', $id)->where('active', 1)->get();

        foreach ($notifications as $notification) {
            $notification->active = 0;
            $notification->save();
        }

        return response()->json(['success' => true, 'data' => $notifications], JsonResponse::HTTP_OK);
    }
}
