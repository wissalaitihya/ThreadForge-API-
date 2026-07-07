<?php

namespace App\AI\Tools;

use App\Models\Post;

class GetPostHistoryTool
{
   
    public function getPostHistory(int $postId): array
    {
        // Find the post with its related text
        $post = Post::with('text')->find($postId);

        // If not found return a clear message
        if (!$post) {
            return ['error' => "Post {$postId} not found"];
        }

        return [
            'hook_propose'                  => $post->hook_propose,
            'body_points'                   => $post->body_points,
            'technical_readability_score'   => $post->technical_readability_score,
            'suggested_hashtags'            => $post->suggested_hashtags,
            'tone_compliance_justification' => $post->tone_compliance_justification,
            'status'                        => $post->status,
            'original_content'              => $post->payload_brut,
        ];
    }
}