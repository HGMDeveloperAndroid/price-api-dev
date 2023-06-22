<?php

namespace App\Http\Controllers;

use App\Http\Resources\Mission as MissionResource;
use App\Http\Resources\MissionCollection;
use App\Missions;
use App\Models\Mission;
use App\Repositories\MissionRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\User;
use App\Scans;
use App\DeviceToken;
use App\Notifications;

class MissionsController extends Controller
{

    /** @var MissionRepository $missionRepository */
    private $missionRepository;

    /**
     * MissionsController constructor.
     * @param MissionRepository $missionRepository
     */
    public function __construct(MissionRepository $missionRepository)
    {
        $this->missionRepository = $missionRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $mission = $this->missionRepository->Filters($request);
        $collection = new MissionCollection($mission->paginate(50));
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
        $mission = $this->missionRepository->Filters($request);
        $collection = $mission->paginate(10)->pluck('title', 'id');
        return response()->json($collection);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request)
    {
        $yesterday = date("Y-m-d", strtotime("-1 days"));
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|unique:missions,title,NULL,id,deleted_at,NULL|max:90',
            'description' => 'required|string|min:5',
            'mission_points' => 'numeric',
            'capture_points' => 'numeric',
            'start_date' => 'date|date_format:Y-m-d|after:' . $yesterday,
            'end_date' => 'date|date_format:Y-m-d|after:start_date',
            'regions' => 'required|array|min:1'
        ]);

        if ($validator->fails()) {
            return response()->json(['Validation errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = Auth::user();

        $mission = new Missions();
        $mission->created_by = $user->id;
        $mission->title = $request->title;
        $mission->description = $request->description;
        $mission->mission_points = $request->mission_points;
        $mission->capture_points = $request->capture_points;
        $mission->start_date = $request->start_date;
        $mission->end_date = $request->end_date;

        if ($mission->save()) {
            if ($request->has('regions') && !empty($request->regions)) {
                $mission->regions()->sync($request->regions);
            }
        }

        $missionResource = new MissionResource($mission);

        $zone_missions =  DB::table('zone_missions')
            ->where('id_mission', $mission->id)->get();
        $id_zone = '';

        foreach ($zone_missions as $zone) {
            $id_zone .= $zone->id_zone . ',';
        }

        $id_zone = explode(',', $id_zone);
        $zone_users =  DB::table('zone_users')->whereIn('id_zone', $id_zone)->get();
        $id_user = '';

        foreach ($zone_users as $zone) {
            $id_user .= $zone->id_user . ',';
        }

        $id_user = explode(',', $id_user);
        $recipients = DeviceToken::whereIn('id_user', $id_user)->pluck('device_token')->toArray();

        $payloads = [
            'content_available' => true,
            'data' => [
                'title' => $mission->title,
                'description' => $mission->description,
                'type' => 'NEW_MISSION',
                'dateTime' => $mission->created_at
            ],
            'notification' => [
                'title' => 'Nueva misión',
                'body' => $mission->title
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
        curl_setopt($ch, CURLOPT_URL,env('FCM_ENDPOINT'));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloads));
        curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);

        $response = curl_exec($ch);
        curl_close($ch);


        $users = DeviceToken::select('id_user')->whereIn('id_user', $id_user)->groupBy('id_user')->get();

        foreach ($users as $user) {
            Notifications::create([
                'id_user' => $user->id_user,
                'notification_title' => 'Nueva misión',
                'body' => $mission->title,
                'data_title' => $mission->title,
                'description' => $mission->description,
                'type' => 'NEW_MISSION',
                'dateTime'  => $mission->created_at
            ]);
        }

        return response()->json(['success' => true, 'data' => $missionResource], JsonResponse::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param Missions $mission
     * @return JsonResponse
     */
    public function show(Missions $mission)
    {
        $missionResource = new MissionResource($mission);
        return response()->json(['success' => true, 'data' => $missionResource], 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Missions $mission
     * @return JsonResponse
     */
    public function update(Request $request, Missions $mission)
    {
        $user = Auth::user();

        if ($user->hasRole(['Scanner'])) {
            $response['status'] = 'error';
            $response['message'] = "Role no valid";
            return response()->json($response, 401);
        }
//        dd($request->all());

        $validator = Validator::make($request->all(), [
            'title' => 'string|unique:missions,title,'.$mission->id.',id,deleted_at,NULL|max:90',
            'description' => 'string|min:5',
            'mission_points' => 'numeric',
            'capture_points' => 'numeric',
            'start_date' => 'date|date_format:Y-m-d',
            'end_date' => 'date|date_format:Y-m-d'
        ]);

        if ($validator->fails()) {
            return response()->json(['Validation errors' => $validator->errors()]);
        }

        if ($mission->fill($request->all())->save()) {
            if ($request->has('regions') && !empty($request->regions)) {
                $mission->regions()->sync($request->regions);
            }
        }

        $missionResource = new MissionResource($mission);
        return response()->json([
            'status' => 'success',
            'data' => $missionResource
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Missions $missions
     * @return JsonResponse
     */
    public function destroy(Missions $mission)
    {
        $user = Auth::user();

        if ($user->hasRole(['Scanner'])) {
            $response['status'] = 'error';
            $response['message'] = "Role no valid";
            return response()->json($response, 401);
        }

        $totalScans = $mission->scans()->count();
        if ($totalScans == 0) {
            $mission->delete();

            return response()->json([
                'status' => 'success'
            ], 204);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'This missions has scans'
            ], 400);
        }
    }

    public function all(Request $request)
    {
        $missions = Missions::where(function ($query) use ($request) {
            if ($request->has('available') && $request->available == 1) {
                $query->where('start_date', '<=', \Carbon\Carbon::now()->format('Y-m-d 00:00:00'))
                    ->where('end_date', '>=', \Carbon\Carbon::now()->format('Y-m-d 23:59:59'));
            }
        })->select('missions.*', DB::raw('(SELECT COUNT(distinct id_user) FROM user_points WHERE id_mission = missions.id) as "completed"'))
            ->where('title', '<>', 'Misión 0')
            ->orderBy('created_at', 'DESC')->get();

        return response()->json([
            'missions' => $missions
        ], 200);
    }

    public function listValidation()
    {
        $mission = $this->missionRepository->FiltersValidation();
        $collection = $mission->paginate(10)->pluck('title', 'id');
        return response()->json($collection);
    }

    /**
     * active missions.
     *
     * @param User $id
     * @return JsonResponse
     */
    public function activeMissions()
    {
        $id = Auth::user()->id;
        $result = [];

        $zone_users =  DB::table('zone_users')->where('id_user', $id)->first();
        $zone_missions =  DB::table('zone_missions')
            ->join('missions', 'missions.id', 'zone_missions.id_mission')
            ->where('id_zone', $zone_users->id_zone)
            ->whereNull('missions.deleted_at')
            ->orderBy('id_mission', 'DESC')->get();

        foreach ($zone_missions as $zone) {
            $mission_details = Missions::where('id', $zone->id_mission)->first();
            $zones = DB::table('zones')->where('id', $zone->id_zone)->first();
            $scan = Scans::where('id_scanned_by', $id)
                ->where('id_mission', $mission_details->id)
                ->count();
            $scan_accepted = Scans::where('id_scanned_by', $id)
                ->where('is_valid', 1)
                ->where('is_rejected', 0)
                ->where('id_mission', $mission_details->id)
                ->count();
            $scan_rejected_count = Scans::where('id_scanned_by', $id)
                ->where('is_valid', 0)
                ->where('is_rejected', 1)
                ->where('id_mission', $mission_details->id)
                ->count();
            $scan_rejected = Scans::select(DB::raw('rejection_criteria.criterion, count(*) as total'))
                ->where('id_scanned_by', $id)
                ->join('rejection_criteria', 'rejection_criteria.id', 'scans.id_criterion')
                ->where('is_valid', 0)
                ->where('is_rejected', 1)
                ->where('id_mission', $mission_details->id)
                ->groupBy('rejection_criteria.criterion')
                ->orderBy('rejection_criteria.id')
                ->get();
            $scan_pending = Scans::where('id_scanned_by', $id)
                ->where('is_valid', 0)
                ->where('is_rejected', 0)
                ->where('id_mission', $mission_details->id)
                ->count();

            $result[] = [
                'id' => $mission_details->id,
                'title' => $mission_details->title,
                'description' => $mission_details->description,
                'mission_points' => $mission_details->mission_points,
                'capture_points' => $mission_details->capture_points,
                'start_date' => $mission_details->start_date,
                'end_date' => $mission_details->end_date,
                'region' => $zones->name,
                'accepted' => $scan_accepted,
                'rejected_count' => $scan_rejected_count,
                'rejected' => $scan_rejected,
                'pending' => $scan_pending,
                'total' => $scan
            ];
        }

        $scan = Scans::where('id_scanned_by', $id)
            ->where('is_valid', 1)
            ->where('is_rejected', 0)
            ->count();

        return response()->json([
            'total_scans' => $scan,
            'missions' => $result
        ], 200);
    }
}
