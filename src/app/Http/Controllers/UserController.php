<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\HeaderBag;

use App\Http\Resources\User as UserResource;
use App\Http\Resources\UserCollection;
use App\Mail\TestMail;
use App\Repositories\UserRepository;
use App\Services\UploadSpaces;
use App\User;
use App\UserPoints;
use App\Scans;
use App\Settings;
use League\Csv\Writer;


class UserController extends Controller
{
    private $headings = [[
        'Id',
        'Nombre',
        'Apellido paterno',
        'Apellido materno',
        'Usuario',
        'Correo electrónico',
        'No. de empleado',
        'Rol'
    ]];

    private $successStatus = 200;

    /** @var UserRepository */
    private $userRepository;

    /** @var UploadSpaces $spaces */
    private $spaces;

    /**
     * UserController constructor.
     * @param UserRepository $userRepository
     * @param UploadSpaces $spaces
     */
    public function __construct(UserRepository $userRepository, UploadSpaces $spaces)
    {
        $this->userRepository = $userRepository;
        $this->spaces = $spaces;

//        $this->middleware('permission:user-list|user-register|user-update|user-trash|user-restore',
//            ['only' => ['listing', 'registerUser', 'update', 'trash', 'restore']]
//        );
    }

    public function registerUser(Request $request)
    {
        $input = [
            'username' => 'required|min:3|unique:users',
            'password' => 'required|min:2',
            'first_name' => 'required|min:3',
            'last_name' => 'required|min:3',
            'employee_number' => 'min:3|unique:users|nullable',
            'cellphone' => 'min:10|max:15',
            'role' => 'required|exists:roles,name',
            'region' => 'required|exists:zones,id'
        ];

        $messages = [
            'username.unique' => 'Este usuario no está disponible',
            'employee_number.unique' => 'Este número de empleado ya está registrado en la base',
            'required' => 'El campo :attribute es requerido',
            'min' => 'El campo :attribute debe ser por lo menos :min.',
            'max' => 'El campo :attribute no puede ser mayor que :max.',
            'string' => 'El campo :attribute debe ser una cadena'
        ];

        $validator = Validator::make($request->all(), $input, $messages);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $password = bcrypt($request->password);
        $setting = Settings::where('id', 1)->first();
        $input = [
            'username' => $request->username,
            'password' => $password,
            'default_password' => $password,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'mother_last_name' => $request->mother_last_name,
            'employee_number' => $request->employee_number,
            'email' => $request->email,
            'cellphone' => $request->cellphone,
            'lang_id' => $setting->lang_id
        ];

        $user = User::where('username', $request->username)->first();
        if (!is_null($user)) {
            $data['message'] = "Sorry! this username is already registered";
            return response()->json(['status' => false, 'data' => $data], JsonResponse::HTTP_BAD_REQUEST);
        }

        /** @var User $user */
        $user = User::create($input);

        if ($user) {

            if ($request->has('role') && !empty($request->role)) {
                $user->assignRole($request->role);
            }

            if ($request->has('region') && !empty($request->region)) {
                $user->regions()->sync($request->region);
            }

            if ($request->has('photo') && !empty($request->photo)) {

                $picturePath = $this->spaces->uploadPicture($request->photo, $user->username);

                $user->picture_path = $picturePath;
                $user->save();
            }
            if ($request->has('lang_id')) {
                $user->lang_id = $request->lang_id;
            }
        }

        $resourceUser = new UserResource($user);

        $success = [
            'status' => true,
            'message' => "You have registered successfully",
            'user' => $resourceUser,
        ];

        return response()->json($success, JsonResponse::HTTP_CREATED);
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'remember_me' => 'boolean',
        ]);


        if (Auth::attempt(['username' => request('username'), 'password' => request('password')])) {
            $user = Auth::user();

            $token = $user->createToken('token')->accessToken;
            $success['message'] = "Success. You are logged in successfully!";
            $success['token'] = $token;

            $resourceUser = new UserResource($user);
            $success['user'] = $resourceUser;

            return response()->json($success, $this->successStatus);
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
    }

    public function setupPassword(Request $request)
    {
        $user = Auth::user();

        if ($user) {

            $validate = Validator::make($request->all(), [
                'old_password' => 'required|password',
                'new_password' => 'required|min:6|different:old_password',
                'new_confirm_password' => 'required|same:new_password',
            ]);

            if ($validate->fails()) {
                return response()->json(['Validation errors' => $validate->errors()], JsonResponse::HTTP_BAD_REQUEST);
            }

            $user->password = bcrypt($request->new_password);
            $user->default_password = '';
            $user->save();

            return response()->json(['success' => true, 'message' => 'Your password is ready.']);
        }
    }

    public function listing(Request $request)
    {
        $users = $this->userRepository->Filters($request);
        $collection = new UserCollection($users->paginate(10));
        return response()->json($collection);
    }

    /**
     * Download CSV.
     *
     * @param \App\User $users
     * @return \Illuminate\Http\Response
     */
    public function usersToCsv(Request $request)
    {
        $users = $this->userRepository->Filters($request);

        $result = [];

        foreach ($users->get() as $user) {
            $rol = User::select('roles.name')
                ->where('users.id', $user->id)
                ->join('model_has_roles', 'model_has_roles.model_id', 'users.id')
                ->join('roles', 'roles.id', 'model_has_roles.role_id')->first();

            if (is_null($rol)) {
                $roles = '-';
            } else {
                $roles = $rol['name'];
            }

            $result[] = [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'mother_last_name' => $user->mother_last_name ?? '-',
                'username' => $user->username,
                'email' => $user->email ?? '-',
                'employee_number' => $user->employee_number ?? '-',
                'roles' => $roles,
            ];
        }

        $result = array_merge($this->headings, $result);

        $callback = $this->writerContentCallBack($result);

        $time = time();
        $fileName = "usuarios-$time.csv";
        return response()->streamDownload($callback, $fileName);
    }

    /**
     * Write CSV content
     *
     * @param User $array
     * @return Response
     */
    public function writerContentCallBack(array $content)
    {
        return function () use ($content) {
            $csv = Writer::createFromPath("php://temp", "r+");
            foreach ($content as $item) {
                $csv->insertOne($item);
            }
            echo $csv->getContent();

            flush();
        };
    }

    public function listById(Request $request)
    {
        $users = $this->userRepository->filterId($request);
        $collection = new UserCollection($users->paginate(10));

        if (count($collection) == 0) {
            return response()->json(['errors' => 'Invalid id'], JsonResponse::HTTP_NOT_FOUND);
        }

        return response()->json($collection);
    }

    public function update(Request $request, $id)
    {
        $user = User::select('users.*', 'roles.name AS rol')
            ->join('model_has_roles', 'model_has_roles.model_id', 'users.id')
            ->join('roles', 'roles.id', 'model_has_roles.role_id')->find($id);

        if (is_null($user)) {
            return response()->json(['errors' => 'User ID does not exist'], JsonResponse::HTTP_NOT_FOUND);
        }

        $validator = Validator::make($request->all(),
            [
                'username' => 'min:3|unique:users,username,' . $id,
                'first_name' => 'min:3',
                'last_name' => 'min:3',
                'employee_number' => 'min:3|unique:users,employee_number,' . $id . '|nullable',
                'cellphone' => 'min:10|max:15|nullable',
                'password' => 'min:2|nullable',
                'photo' => 'file|mimes:jpeg,jpg,bmp,png|max:5243|nullable'
            ]);

        if ($validator->fails()) {
            return response()->json(['Validation errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($request->filled('username')) {
            $user->username = $request->username;
        }

        if ($request->filled('first_name')) {
            $user->first_name = $request->first_name;
        }

        if ($request->filled('last_name')) {
            $user->last_name = $request->last_name;
        }

        if ($request->has('mother_last_name')) {
            $user->mother_last_name = $request->mother_last_name;
        }

        if ($request->has('employee_number')) {
            $user->employee_number = $request->employee_number;
        }

        if ($request->has('email')) {
            $user->email = $request->email;
        }

        if($request->filled('cellphone')) {
            $user->cellphone = $request->cellphone;
        }

        if ($request->filled('password')) {
            $password = bcrypt($request->password);
            $user->password = $password;
        }

        if ($request->has('role') && !empty($request->role)) {
                $user->syncRoles($request->role);
        }

        if ($request->has('region') && !empty($request->region)) {
                $user->regions()->sync($request->region);
        }

        if ($request->has('photo')) {
            if (!empty($request->photo)) {

                $picturePath = $this->spaces->uploadPicture($request->photo, $user->username);
                $user->picture_path = $picturePath;
            } else {

                $picturePath = $user->picture_path;
                if ($this->spaces->deletePicture($picturePath)) {
                    $user->picture_path = '';
                }
            }
        }
        if ($request->has('dark_theme')) {
            $user->dark_theme = $request->dark_theme;
        }

        if ($request->has('lang_id')) {
            $user->lang_id = $request->lang_id;
        }
        
        $user->save();

        $resourceUser = new UserResource($user);
        return response()->json($resourceUser, 200);
    }

    public function trash($id)
    {
        User::findOrFail($id)->delete();
        $scanned = Scans::where('id_scanned_by', $id)
            ->where('is_valid', 1)
            ->where('is_rejected', 0)->get();

        $reviewed = Scans::where('id_reviewed_by', $id)
            ->where('is_valid', 1)
            ->where('is_rejected', 0)->get();

        if($scanned) {
            foreach ($scanned as $value) {
                $value->id_scanned_by = 370;
                $value->save();
            }
        }

        if($reviewed) {
            foreach ($reviewed as $value) {
                $value->id_reviewed_by = 370;
                $value->save();
            }
        }

        return response()->json(['success' => true], JsonResponse::HTTP_ACCEPTED);
    }

    public function restore($id)
    {
        User::onlyTrashed()->find($id)->restore();
        return response()->json(['success' => true], JsonResponse::HTTP_ACCEPTED);
    }

    /**
     * @param User $user
     * @return JsonResponse
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        $resourceUser = new UserResource($user);

        return response()->json(['success' => true, 'user' => $resourceUser]);
    }

    /**
     * @api {get} leaderboard Get leaderboard
     * @apiGroup User
     * @apiDescription Returns a leaderboard of user points, with optional date filters.
     *
     * @apiParam {Date} [start_date] The start of a date range to filter by.
     * @apiParam {Date} [end_date] The end of a date range to filter by.
     * @apiParam {Integer} [mission_id] The database ID of the mission to get a leaderboard for.
     *
     * @apiSuccess (Success Response (JSON)) {Object} leaderboard An array of objects for the leaderboard, ordered from highest to lowest score.
     */
    public function leaderboard(Request $request)
    {
        $leaderboard = UserPoints::where(function ($query) use ($request) {
            if ($request->has('start_date') && !empty($request->start_date)) {
                $query->where('created_at', '>=', $request->start_date);
            }

            if ($request->has('end_date') && !empty($request->end_date)) {
                $query->where('created_at', '<=', $request->end_date);
            }

            if ($request->has('id_mission') && !empty($request->id_mission)) {
                $query->where('id_mission', $request->id_mission);
            }
        })->select('id_user', DB::raw('SUM(amount) as total, (SELECT CONCAT(first_name, " ", last_name) FROM users WHERE users.id = user_points.id_user) as name, (SELECT COUNT(distinct(id_mission)) FROM user_points up1 WHERE up1.id_user = user_points.id_user) as "complete"'))->groupBy('id_user')->orderBy('total', 'DESC')->get();

        return response(array(
            'leaderboard' => $leaderboard
        ));
    }

    /**
     * @api {get} user/{id} Single User
     * @apiGroup User
     * @apiDescription Gets the information for a single user, inculding points and total scans.
     *
     * @apiParam {Integer} id (In the URL) The database ID of the user to retrieve info for.
     *
     * @apiSuccess (Success Response (JSON)) {Object} user The user object with his given information.
     */
    public function singleUser($id)
    {
        $user = User::find($id);
        $user->points = $user->points($id)->sum('amount') + $user->pointsMission($id)->get()->sum('points');
        $user->valid_scans = $user->scansUser($id)->where('is_valid', 1)->count();
        $user->rejected_scans = $user->scansUser($id)->where('is_rejected', 1)->count();

        return response(array(
            'user' => $user,
        ));
    }
}
