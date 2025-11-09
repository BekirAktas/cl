<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeagueStateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'current_week' => $this->resource['currentWeek'],
            'total_weeks' => $this->resource['totalWeeks'],
            'remaining_weeks' => $this->resource['remainingWeeks'],
            'season_complete' => $this->resource['seasonComplete'],
            'standings' => StandingResource::collection($this->addPositionToStandings($this->resource['standings'])),
            'upcoming_matches' => MatchResource::collection($this->formatMatches($this->resource['upcomingMatches'])),
            'recent_results' => MatchResource::collection($this->formatMatches($this->resource['recentResults'])),
            'weekly_results' => $this->when(
                $this->resource['seasonComplete'],
                function() {
                    return collect($this->resource['weeklyResults'])->map(function($week) {
                        return [
                            'week' => $week['week'],
                            'matches' => MatchResource::collection($this->formatWeeklyMatches($week['matches']))
                        ];
                    });
                }
            ),
        ];
    }

    private function addPositionToStandings(array $standings): array
    {
        return collect($standings)->map(function($standing, $index) {
            return array_merge($standing, ['position' => $index + 1]);
        })->toArray();
    }

    private function formatMatches(array $matches): array
    {
        return collect($matches)->map(function($match) {
            return [
                'home' => ['id' => null, 'name' => $match['home']],
                'away' => ['id' => null, 'name' => $match['away']],
                'home_score' => $match['home_score'],
                'away_score' => $match['away_score'],
                'status' => $match['status']
            ];
        })->toArray();
    }

    private function formatWeeklyMatches(array $matches): array
    {
        return collect($matches)->map(function($match) {
            return [
                'home' => ['id' => null, 'name' => $match['home']],
                'away' => ['id' => null, 'name' => $match['away']],
                'home_score' => $match['home_score'],
                'away_score' => $match['away_score'],
                'status' => 'completed'
            ];
        })->toArray();
    }
}