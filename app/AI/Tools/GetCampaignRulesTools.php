<?php

namespace App\AI\Tools;

use App\Models\Blueprint;

class GetCampaignRulesTool
{
    public function getCampaignRules(int $blueprintId): array
    {
        // Find the blueprint in the DB
        $blueprint = Blueprint::find($blueprintId);

        // If not found return a clear message
        if (!$blueprint) {
            return ['error' => "Blueprint {$blueprintId} not found"];
        }

        // Return only the style rules
        return [
            'name'           => $blueprint->name,
            'tone'           => $blueprint->tone,
            'max_hashtags'   => $blueprint->max_hashtags,
            'max_characters' => $blueprint->max_characters,
            'regle_supp'     => $blueprint->regle_supp ?? 'No extra rules',
        ];
    }
}