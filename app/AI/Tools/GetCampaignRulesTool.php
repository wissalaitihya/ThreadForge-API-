<?php

namespace App\AI\Tools;

use App\Models\Blueprint;

class GetCampaignRulesTool
{
    public function getCampaignRules(int $blueprintId): array
    {
        $blueprint = Blueprint::find($blueprintId);

        if (!$blueprint) {
            return ['error' => "Blueprint {$blueprintId} not found"];
        }

        return [
            'name'           => $blueprint->name,
            'tone'           => $blueprint->tone,
            'max_hashtags'   => $blueprint->max_hashtags,
            'max_characters' => $blueprint->max_characters,
            'regle_supp'     => $blueprint->regle_supp ?? 'No extra rules',
        ];
    }
}