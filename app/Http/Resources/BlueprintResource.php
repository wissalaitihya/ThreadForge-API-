<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BlueprintResource extends JsonResource
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
            'name' => $this->name,
            'tone' => $this->tone,
            'max_hashtags' => $this->max_hashtags,
            'max_words' => $this->max_words,
            'regle_supp' => $this->regle_supp,
            'posts_count'    => $this->whenLoaded('texts', fn() => $this->texts->sum(fn($text) => $text->post ? 1 : 0)),
            'created_at' => $this->created_at->ToDateTimeString(),  

        ];
    }
}
