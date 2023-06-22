<?php

namespace App\Http\Controllers;

use App\Http\Resources\RoleCollection;
use App\Repositories\RoleRepository;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{

    /** @var RoleRepository $roleRepository */
    private $roleRepository;

    /**
     * RoleController constructor.
     * @param $roleRepository
     */
    public function __construct(RoleRepository $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    /**
     * Display a listing of the resource.
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $roles = $this->roleRepository->Filters($request);
        $collection = new RoleCollection($roles->paginate(10));
        return response()->json($collection);
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
     * @param Role $role
     * @return Response
     */
    public function show(Role $role)
    {
        //
    }


    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Role $role
     * @return Response
     */
    public function update(Request $request, Role $role)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Role $role
     * @return Response
     */
    public function destroy(Role $role)
    {
        //
    }


    /**
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function assignRoleToUser(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'role' => ['required'],
            'role.*' => ['required', 'string']
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()],
                JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($request->has('role')) {
            $user->syncRoles($request->role);
        }

        return response()->json(['success' => 'success'], Response::HTTP_ACCEPTED);
    }
}
