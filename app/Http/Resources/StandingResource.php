<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StandingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'position' => $this->resource['position'] ?? null,
            'team' => TeamResource::make([
                'id' => $this->resource['teamId'],
                'name' => $this->resource['team']
            ]),
            'matches_played' => $this->resource['played'],
            'wins' => $this->resource['win'],
            'draws' => $this->resource['draw'],
            'losses' => $this->resource['loss'],
            'goals_for' => $this->resource['gf'],
            'goals_against' => $this->resource['ga'],
            'goal_difference' => $this->resource['goalDifference'],
            'points' => $this->resource['points'],
            'championship_probability' => $this->resource['prediction'] ?? 0,
        ];
    }
}