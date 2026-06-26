<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePostStatusRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    //list all the generated posts for the auth user 
     public function index(Request $request): JsonResponse
    {
        $posts = Post::whereHas('text', function ($query) use ($request) {
                // Only return posts that belong to the auth user
                $query->where('user_id', $request->user()->id);
            })
            ->with('text') //to avoid N+1
            ->latest()
            ->get();

        return response()->json(PostResource::collection($posts));
    }

    //get a single generated post 
    public function show(Post $post): JsonResponse
    {
        $this->authorize('view', $post); //policies check if the post belong to the auth user
        $post->load('text');
        return response()->json(new PostResource($post));
    }

    public function updateStatus(UpdatePostStatusRequest $request, Post $post): JsonResponse
    {
        $this->authorize('update', $post);
        $post->update($request->validated());

        return response()->json([
            'message' => 'Status updated successfully',
            'data'    =>  new PostResource($post)
        ]);
    }
}
