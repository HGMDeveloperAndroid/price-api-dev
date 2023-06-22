<?php

namespace App\Http\Controllers;

use App\Http\Resources\LabelCollection;
use App\Label;
use App\Http\Resources\Label as LabelResource;
use App\Repositories\LabelRepository;
use App\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class LabelController extends Controller
{

    /** @var LabelRepository $labelRepository */
    private $labelRepository;

    function __construct(LabelRepository $labelRepository)
    {
        $this->labelRepository = $labelRepository;

//        $this->middleware('permission:label-list|label-create|label-edit|label-delete', ['only' => ['index', 'show']]);
//        $this->middleware('permission:label-create', ['only' => ['store']]);
//        $this->middleware('permission:label-edit', ['only' => ['update']]);
//        $this->middleware('permission:label-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $label = $this->labelRepository->Filters($request);
        $collection = new LabelCollection($label->paginate(10));
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
        $validator = Validator::make($request->all(), [
            'name' => 'required|unique:labels|max:255',
            'alias' => 'required|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['Validation errors' => $validator->errors()]);
        }

        $label = Label::create([
            'name' => $request->name,
            'description' => $request->description,
            'short_name' => Str::slug($request->name)
        ]);

        $labelResource = new LabelResource($label);

        return response()->json(['success' => true, 'data' => $labelResource], JsonResponse::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     *
     * @param Label $label
     * @return JsonResponse
     */
    public function show(Label $label)
    {
        $labelSource = new LabelResource($label);
        return response()->json(['success' => true, 'data' => $labelSource], JsonResponse::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param Label $label
     * @return JsonResponse
     */
    public function update(Request $request, Label $label)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'max:255',
            'alias' => 'max:255'
        ]);

        if ($validator->fails()) {
            return response()->json(['Validation errors' => $validator->errors()], JsonResponse::HTTP_BAD_REQUEST);
        }

        $label->update($request->json()->all());
        $labelSource = new LabelResource($label);

        return response()->json(['success' => true, 'data' => $labelSource], JsonResponse::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Label $label
     * @return JsonResponse
     */
    public function destroy(Label $label)
    {
        $label->delete();
        return response()->json(['success' => true], JsonResponse::HTTP_ACCEPTED);
    }

    /**
     * @param $id
     * @return JsonResponse
     */
    public function restore($id)
    {
        $label = Label::onlyTrashed()->findOrFail($id);
        $label->restore();
        return response()->json(['success' => true], JsonResponse::HTTP_ACCEPTED);
    }

    /**
     * @param Request $request
     * @param User $user
     * @return JsonResponse
     */
    public function addLabelsToUser(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'labels' => ['required'],
            'labels.*' => ['required', 'numeric']
        ], ['labels.*' => "The label is required"]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()],
                JsonResponse::HTTP_BAD_REQUEST);
        }

        if ($request->has('labels') && !empty($request->labels)) {
            $user->labels()->sync($request->labels);
        }

        return response()->json(['success' => 'success'], Response::HTTP_ACCEPTED);
    }

}
