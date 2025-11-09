<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use App\Exceptions\FixtureGenerationException;
use App\Exceptions\RedisConnectionException;
use App\Enums\MatchStatusEnum;
use Illuminate\Support\Facades\Log;

class FixtureBuilderService
{
    public function buildFixtures(): array
    {
        try {
            $teams = $this->getTeams();
            
            if (count($teams) < 2) {
                throw new FixtureGenerationException('At least 2 teams required to generate fixtures');
            }
            
            $fixtures = $this->generateFixtures($teams);
            
            if (empty($fixtures)) {
                throw new FixtureGenerationException('Failed to generate fixture schedule');
            }
            
            Redis::set('fixtures', json_encode($fixtures));
            Redis::set('current_week', 1);
            Redis::del('standings');
            
            foreach ($teams as $team) {
                Redis::zadd('standings', 0, $team['id']);
            }
            
            Log::info('Fixtures generated successfully', ['total_weeks' => count($fixtures)]);
            
            return [
                'fixtures' => $fixtures,
                'total_weeks' => count($fixtures)
            ];
            
        } catch (\Exception $e) {
            Log::error('Failed to build fixtures', ['error' => $e->getMessage()]);
            
            if ($e instanceof FixtureGenerationException) {
                throw $e;
            }
            
            throw new RedisConnectionException('Database connection failed while generating fixtures');
        }
    }
    
    public function getTeams(): array
    {
        try {
            $teams = Redis::get('teams');
            
            return $teams ? json_decode($teams, true) : [];
        } catch (\Exception $e) {
            Log::error('Failed to get teams from Redis', ['error' => $e->getMessage()]);
            throw new RedisConnectionException('Failed to retrieve teams data');
        }
    }
    
    public function getFixtures(): array
    {
        try {
            $fixtures = Redis::get('fixtures');

            return $fixtures ? json_decode($fixtures, true) : [];
        } catch (\Exception $e) {
            Log::error('Failed to get fixtures from Redis', ['error' => $e->getMessage()]);
            throw new RedisConnectionException('Failed to retrieve fixtures data');
        }
    }
    
    public function getCurrentWeek(): int
    {
        try {
            return (int) Redis::get('current_week') ?: 1;
        } catch (\Exception $e) {
            Log::error('Failed to get current week from Redis', ['error' => $e->getMessage()]);
            return 1;
        }
    }
    
    private function generateFixtures(array $teams): array
    {
        $teamCount = count($teams);
        
        if ($teamCount < 2) {
            return [];
        }
        
        $placeholder = null;
        if ($teamCount % 2 === 1) {
            $placeholder = ['id' => 'bye', 'name' => 'BYE'];
            $teams[] = $placeholder;
            $teamCount++;
        }
        
        $rounds = $teamCount - 1;
        $half = $teamCount / 2;
        $first = array_shift($teams);
        $rotating = $teams;
        $fixtures = [];
        
        for ($round = 0; $round < $rounds; $round++) {
            $current = array_merge([$first], $rotating);
            $weekMatches = [];
            
            for ($i = 0; $i < $half; $i++) {
                $home = $current[$i];
                $away = $current[$teamCount - 1 - $i];
                
                if ($home === $placeholder || $away === $placeholder) {
                    continue;
                }
                
                if (($round + $i) % 2 === 1) {
                    [$home, $away] = [$away, $home];
                }
                
                $weekMatches[] = [
                    'home' => $home,
                    'away' => $away,
                    'status' => MatchStatusEnum::SCHEDULED->value,
                    'home_score' => null,
                    'away_score' => null
                ];
            }
            
            if (!empty($weekMatches)) {
                $fixtures[] = $weekMatches;
            }
            
            array_unshift($rotating, array_pop($rotating));
        }
        
        $firstRoundCount = count($fixtures);
        for ($week = 0; $week < $firstRoundCount; $week++) {
            $returnMatches = [];
            foreach ($fixtures[$week] as $match) {
                $returnMatches[] = [
                    'home' => $match['away'],
                    'away' => $match['home'],
                    'status' => MatchStatusEnum::SCHEDULED->value,
                    'home_score' => null,
                    'away_score' => null
                ];
            }
            $fixtures[] = $returnMatches;
        }
        
        return $fixtures;
    }
}