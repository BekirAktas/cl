<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FixtureResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'week' => $this->resource['week'],
            'matches' => MatchResource::collection($this->resource['matches']),
            'match_count' => count($this->resource['matches']),
        ];
    }
}