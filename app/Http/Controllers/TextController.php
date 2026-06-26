<?php

namespace App\Http\Controllers;

use App\Http\Requests\RawContentRequest;
use App\Http\Resources\TextResource;
use App\Jobs\ProcessRawContentJob;
use App\Models\Text;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TextController extends Controller
{

//list all the generated texts for the auth user
public function index(Request $request): JsonResponse
    {
        $texts = Text::where('user_id', $request->user()->id)
            ->with('post') //to avoid N+1
            ->latest()
            ->get();

        return response()->json(TextResource::collection($texts));
    }

    
    public function store(RawContentRequest $request)
    {
        $text = Text::create([
            'user_id'      => $request->user()->id,
            'blueprint_id' => $request->input('blueprint_id'),
            'content'      => $request->input('content'),
            'status'       => 'pending',
        ]);

        ProcessRawContentJob::dispatch($text);

         return response()->json([
            'message' => 'Content submitted. Processing in background.',
            'data'    => new TextResource($text),
        ], 202);
    }
 //get a single generated text with its post
    public function show(Text $text): JsonResponse
    {
        $this->authorize('view', $text);
        $text->load('post');
        return response()->json(new TextResource($text));
    }
}
