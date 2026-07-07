<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
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
        'text_id' => $this->text_id,
        'hook_propose' => $this->hook_propose,
        'body_points' => $this->body_points,
        'technical_readability_score' => $this->technical_readability_score,
        'suggested_hashtags' => $this->suggested_hashtags,
        'tone_compliance_justification' => $this->tone_compliance_justification,
        'payload_brut' => $this->payload_brut,
        'status' => $this->status,
        'created_at' => $this->created_at->ToDateTimeString(),

        ];
    }
}
