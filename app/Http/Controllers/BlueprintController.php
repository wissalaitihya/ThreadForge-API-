<?php

namespace App\Http\Controllers;

use App\Http\Requests\BlueprintRequest;
use App\Http\Resources\BlueprintResource;
use App\Models\Blueprint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BlueprintController extends Controller
{
    //lists all blueprints for the auth user
    public function index(Request $request) :AnonymousResourceCollection {
        $blueprints = Blueprint::where('user_id', $request->user()->id)->withCount([
            'text as posts_count'=> fn($q)=>$q->whereHas('post')
        ])->get();

        return BlueprintResource::collection($blueprints);
    }
    //create a  new blueprint
    public function store(BlueprintRequest $request): JsonResponse
    {
        $blueprint = Blueprint::create([
            ... $request->validated(),
            'user_id' => $request->user()->id,
        ]);

        return response()->json(new BlueprintResource($blueprint),
         201); // Created
    }

    //get single blueprint 
    public function show(Blueprint $blueprint): JsonResponse
    {
        $this->authorize('view',$blueprint);
        $blueprint->loadCount([
            'text as posts_count'=> fn($q)=>$q->whereHas('post')
        ]);
        return response()->json(new BlueprintResource($blueprint));
    }

    //Update a blueprint
    public function update()(BlueprintRequest $request, Blueprint $blueprint): JsonResponse
    {
        $this->authorize('update',$blueprint);
        $blueprint->update($request->validated());

        return response()->json(new BlueprintResource($blueprint));
    }

    //Delete a blueprint
    public function destroy(Blueprint $blueprint): JsonResponse
    {
        $this->authorize('delete',$blueprint);
        $blueprint->delete();

        return response()->json(null, 204); // No Content
    }

}
