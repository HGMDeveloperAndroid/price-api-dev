<?php

namespace App\Http\Controllers;

use App\Scans;
use App\Missions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;
use App\Products;

class ScansController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return void
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Scans  $scans
     * @return Response
     */
    public function show(Scans $scans)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param  \App\Scans  $scans
     * @return Response
     */
    public function update(Request $request, Scans $scans)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Scans  $scans
     * @return Response
     */
    public function destroy(Scans $scans)
    {
        //
    }

    /**
     * Update sacn barcode by Id.
     *
     * @param Request $request
     * @param $id
     * @return JsonResponse
     */
    public function updateScanById(Request $request, $id)
    {
        $user = Auth::user();

        if ($user->hasRole(['Scanner'])) {
            $response['status'] = 'error';
            $response['message'] = "Role no valid";
            return response()->json($response, 401);
        }

        $scan = Scans::find($id);

        if (is_null($scan)) {
            return response()->json(['errors' => 'Invalid id'], JsonResponse::HTTP_NOT_FOUND);
        }

        $validate = Validator::make($request->all(), [
                'barcode' => 'required|max:255'
        ]);

        if ($validate->fails()) {
            return response()->json(['Validation errors' => $validate->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $product = Products::where('barcode', $request->barcode)->first();

        $scan->barcode = $request->barcode;
        $scan->id_product = $product->id ?? null;
        $scan->save();

        return response()->json([
            'status' => 'success',
            'scan' => $scan
        ], 200);
    }

    /**
     * Rankin by user .
     *
     * @param  \App\Scans  $scans
     * @return Response
     */
    public function rankingByUser()
    {
        $id = Auth::user()->id;
        $scan = Scans::where('id_scanned_by', $id)
            ->count();
        $scan_accepted = Scans::where('id_scanned_by', $id)
            ->where('is_valid', 1)
            ->where('is_rejected', 0)
            ->count();
        $scan_rejected = Scans::where('id_scanned_by', $id)
            ->where('is_valid', 0)
            ->where('is_rejected', 1)
            ->count();
        $scan_pending = Scans::where('id_scanned_by', $id)
            ->where('is_valid', 0)
            ->where('is_rejected', 0)
            ->count();

        $result[] = [
            'accepted' => $scan_accepted,
            'rejected' => $scan_rejected,
            'pending' => $scan_pending,
            'total' => $scan
        ];

        return response()->json([
            'status' => 'success',
            'scans' => $result
        ], 200);
    }

    /**
     * Rankin by missions .
     *
     * @param  \App\Scans  $scans
     * @return Response
     */
    public function rankingByMission()
    {
        $id = Auth::user()->id;

        $missions = Scans::select('id_mission')
            ->where('id_scanned_by', $id)
            ->groupBy('id_mission')
            ->get();

        foreach ($missions as $mission) {
            $scan = Scans::where('id_scanned_by', $id)
                ->where('id_mission', $mission->id_mission)
                ->count();
            $scan_accepted = Scans::where('id_scanned_by', $id)
                ->where('is_valid', 1)
                ->where('is_rejected', 0)
                ->where('id_mission', $mission->id_mission)
                ->count();
            $scan_rejected_count = Scans::where('id_scanned_by', $id)
                ->where('is_valid', 0)
                ->where('is_rejected', 1)
                ->where('id_mission', $mission->id_mission)
                ->count();
            $scan_rejected = Scans::select('scans.id as id_scan','rejection_criteria.criterion')
                ->where('id_scanned_by', $id)
                ->join('rejection_criteria', 'rejection_criteria.id', 'scans.id_criterion')
                ->where('is_valid', 0)
                ->where('is_rejected', 1)
                ->where('id_mission', $mission->id_mission)
                ->get();
            $scan_pending = Scans::where('id_scanned_by', $id)
                ->where('is_valid', 0)
                ->where('is_rejected', 0)
                ->where('id_mission', $mission->id_mission)
                ->count();

            $mission_details = Missions::where('id', $mission->id_mission)->first();

             $result[] = [
                'id' => $mission_details->id,
                'title' => $mission_details->title,
                'description' => $mission_details->description,
                'mission_points' => $mission_details->mission_points,
                'capture_points' => $mission_details->capture_points,
                'start_date' => $mission_details->start_date,
                'end_date' => $mission_details->end_date,
                'accepted' => $scan_accepted,
                'rejected_count' => $scan_rejected_count,
                'rejected' => $scan_rejected,
                'pending' => $scan_pending,
                'total' => $scan
            ];

        }

        return response()->json([
            'status' => 'success',
            'missions' => $result
        ], 200);
    }

    /**
     * Pending scans by user.
     *
     * @param  \App\Scans  $scans
     * @return Response
     */
    public function pendingScansByUser($id)
    {
        $scans = Scans::select('scans.*', 'scan_pictures.product_picture', 'scan_pictures.shelf_picture', 'scan_pictures.promo_picture')
            ->where('id_scanned_by', $id)
            ->join('scan_pictures', 'scan_pictures.id_scan', 'scans.id')
            ->where('is_valid', 0)
            ->where('is_rejected', 0)
            ->get();

        if (is_null($scans)) {
            return response()->json(['status' => false, 'errors' => 'El id no se encuentra en la Base de Datos'], JsonResponse::HTTP_NOT_FOUND);
        }

        return response()->json([
            'status' => 'success',
            'scans' => $scans
        ], 200);
    }
}
