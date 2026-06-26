<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TextResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
         'id' => $this->id,
         'blueprint_id' => $this->blueprint_id,
         'content'=> $this->content,
         'status' => $this->status,
         'post' => new PostResource($this->whenLoaded('post')),
         'created_at' => $this->created_at->ToDateTimeString(),
        ];
    }
}
