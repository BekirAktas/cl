<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Enums\MatchStatusEnum;

class MatchResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'home_team' => TeamResource::make($this->resource['home']),
            'away_team' => TeamResource::make($this->resource['away']),
            'home_score' => $this->resource['home_score'],
            'away_score' => $this->resource['away_score'],
            'status' => $this->resource['status'] ?? MatchStatusEnum::SCHEDULED->value,
            'result' => $this->when(
                ($this->resource['status'] ?? null) === MatchStatusEnum::COMPLETED->value,
                function() {
                    $homeScore = $this->resource['home_score'];
                    $awayScore = $this->resource['away_score'];
                    
                    if ($homeScore > $awayScore) {
                        return 'home_win';
                    } elseif ($homeScore < $awayScore) {
                        return 'away_win';
                    } else {
                        return 'draw';
                    }
                }
            ),
        ];
    }
}