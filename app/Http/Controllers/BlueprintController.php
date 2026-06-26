<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Models\Blueprint;
use App\Http\Requests\BlueprintRequest;
use App\Http\Resources\BlueprintResource;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

class BlueprintController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $blueprints = Blueprint::where('user_id', $request->user()->id)->withCount([
                'texts as posts_count' => fn($q) => $q->whereHas('post')
            ])
            ->get();

        return BlueprintResource::collection($blueprints);
    }
    //create a blueprint 
    public function store(BlueprintRequest $request): JsonResponse{
        $blueprint = Blueprint::create([
        ... $request->validated(),
        'user_id' => $request->user()->id,
        ]);
        return response()->json(new BlueprintResource($blueprint), 201);
    }
//get a single blueprint
    public function show (Blueprint $blueprint): JsonResponse
    {
        if($blueprint->user_id !== auth()->id()){
           return response()->json(['message' => 'Unauthorized'], 403);
        }   
        $blueprint->loadCount([
            'texts as posts_count' => fn($q) => $q->whereHas('post')
        ]);
        return response()->json(new BlueprintResource($blueprint));
    }

    public function update(BlueprintRequest $request, Blueprint $blueprint): JsonResponse
    {
        if($blueprint->user_id !== auth()->id()){
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $blueprint->update($request->validated());
        return response()->json(new BlueprintResource($blueprint));
    }

    public function destroy(Blueprint $blueprint): JsonResponse
    {
        if($blueprint->user_id !== auth()->id()){
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $blueprint->delete();
        return response()->json(null, 204);
    }
}
