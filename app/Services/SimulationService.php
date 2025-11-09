<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;
use App\Exceptions\SimulationException;
use App\Exceptions\RedisConnectionException;
use App\Enums\MatchStatusEnum;
use App\Enums\MatchResultEnum;
use Illuminate\Support\Facades\Log;

class SimulationService
{
    public function getState(): array
    {
        try {
            $teams = $this->getTeams();
            $fixtures = $this->getFixtures();
            $currentWeek = $this->getCurrentWeek();
            $totalWeeks = count($fixtures);
            
            $seasonComplete = $currentWeek > $totalWeeks;
            
            $displayWeek = $seasonComplete ? $totalWeeks : $currentWeek;
            
            return [
                'currentWeek' => $displayWeek,
                'totalWeeks' => $totalWeeks,
                'standings' => $this->getStandings($teams),
                'upcomingMatches' => $this->getUpcomingMatches($fixtures, $currentWeek),
                'recentResults' => $this->getRecentResults($fixtures, $currentWeek),
                'predictions' => $this->getPredictions($teams),
                'seasonComplete' => $seasonComplete,
                'remainingWeeks' => max(0, $totalWeeks - $currentWeek + 1),
                'weeklyResults' => $seasonComplete ? $this->getWeeklyResults($fixtures) : []
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get simulation state', ['error' => $e->getMessage()]);
            throw new SimulationException('Failed to retrieve league state: ' . $e->getMessage());
        }
    }
    
    private function getTeams(): array
    {
        try {
            $teams = Redis::get('teams');

            return $teams ? json_decode($teams, true) : [];
        } catch (\Exception $e) {
            Log::error('Failed to get teams from Redis', ['error' => $e->getMessage()]);
            throw new RedisConnectionException('Failed to retrieve teams data');
        }
    }
    
    private function getFixtures(): array
    {
        try {
            $fixtures = Redis::get('fixtures');
            
            return $fixtures ? json_decode($fixtures, true) : [];
        } catch (\Exception $e) {
            Log::error('Failed to get fixtures from Redis', ['error' => $e->getMessage()]);
            throw new RedisConnectionException('Failed to retrieve fixtures data');
        }
    }
    
    private function getCurrentWeek(): int
    {
        try {
            return (int) Redis::get('current_week') ?: 1;
        } catch (\Exception $e) {
            Log::error('Failed to get current week from Redis', ['error' => $e->getMessage()]);
            return 1;
        }
    }
    
    private function getStandings(array $teams): array
    {
        $standingsData = Redis::zrevrange('standings', 0, -1, 'WITHSCORES');
        $standings = [];
        
        foreach ($teams as $team) {
            $teamId = $team['id'];
            
            $points = Redis::zscore('standings', $teamId) ?? 0;
            $stats = $this->getTeamStats($teamId);
            
            $standings[] = [
                'teamId' => $teamId,
                'team' => $team['name'],
                'played' => $stats['played'],
                'win' => $stats['win'],
                'draw' => $stats['draw'], 
                'loss' => $stats['loss'],
                'gf' => $stats['gf'],
                'ga' => $stats['ga'],
                'goalDifference' => $stats['gf'] - $stats['ga'],
                'points' => (int)$points,
                'prediction' => 0
            ];
        }
        
        usort($standings, function($a, $b) {
            return [$b['points'], $b['goalDifference'], $b['gf'], $a['team']] 
                <=> [$a['points'], $a['goalDifference'], $a['gf'], $b['team']];
        });
        
        return $standings;
    }
    
    private function getTeamStats(int $teamId): array
    {
        $stats = Redis::hgetall("team_stats:$teamId");
        
        return [
            'played' => (int)($stats['played'] ?? 0),
            'win' => (int)($stats['win'] ?? 0),
            'draw' => (int)($stats['draw'] ?? 0),
            'loss' => (int)($stats['loss'] ?? 0),
            'gf' => (int)($stats['gf'] ?? 0),
            'ga' => (int)($stats['ga'] ?? 0),
        ];
    }
    
    private function getUpcomingMatches(array $fixtures, int $currentWeek): array
    {
        if (!isset($fixtures[$currentWeek - 1])) {
            return [];
        }
        
        return collect($fixtures[$currentWeek - 1])
            ->where('status', MatchStatusEnum::SCHEDULED->value)
            ->map(function($match, $index) use ($currentWeek) {
                return [
                    'id' => $index,
                    'week' => $currentWeek,
                    'home' => $match['home']['name'],
                    'away' => $match['away']['name'],
                    'home_score' => $match['home_score'],
                    'away_score' => $match['away_score'],
                    'status' => $match['status'] ?? MatchStatusEnum::SCHEDULED->value
                ];
            })
            ->values()
            ->toArray();
    }
    
    private function getRecentResults(array $fixtures, int $currentWeek): array
    {
        $lastWeek = $currentWeek - 1;
        
        if ($lastWeek < 1 || !isset($fixtures[$lastWeek - 1])) {
            return [];
        }
        
        return collect($fixtures[$lastWeek - 1])
            ->where('status', MatchStatusEnum::COMPLETED->value)
            ->map(function($match, $index) use ($lastWeek) {
                return [
                    'id' => $index,
                    'week' => $lastWeek,
                    'home' => $match['home']['name'],
                    'away' => $match['away']['name'],
                    'home_score' => $match['home_score'],
                    'away_score' => $match['away_score'],
                    'status' => $match['status']
                ];
            })
            ->values()
            ->toArray();
    }
    
    private function getPredictions(array $teams): array
    {
        $fixtures = $this->getFixtures();
        $currentWeek = $this->getCurrentWeek();
        $seasonComplete = $currentWeek > count($fixtures);
        
        if ($seasonComplete) {
            $standings = $this->getStandings($teams);
            $predictions = [];
            
            foreach ($teams as $team) {
                $teamId = $team['id'];
                $isWinner = $standings[0]['teamId'] == $teamId;
                $predictions[$teamId] = $isWinner ? 100 : 0;
            }
            
            return $predictions;
        }
        
        $totalWeeks = count($fixtures);
        $remainingWeeks = $totalWeeks - $currentWeek + 1;
        
        if ($remainingWeeks <= 3) {
            return $this->getAdvancedPredictions($teams, $fixtures, $currentWeek);
        }
        $totalScore = 0;
        $teamScores = [];
        
        foreach ($teams as $team) {
            $teamId = $team['id'];
            $teamPower = $team['power'] ?? 75;
            $currentPoints = Redis::zscore('standings', $teamId) ?? 0;
            $stats = $this->getTeamStats($teamId);
            $goalDiff = $stats['gf'] - $stats['ga'];
            $matchesPlayed = $stats['played'];
            
            $powerScore = $teamPower;
            
            $formScore = $matchesPlayed > 0 ? ($currentPoints / $matchesPlayed) * 33.33 : $teamPower * 0.33;
            
            $goalScore = $goalDiff * 2 + 50;
            
            $finalScore = ($powerScore * 0.3) + ($formScore * 0.5) + ($goalScore * 0.2);
            
            $teamScores[$teamId] = max(10, $finalScore);
            $totalScore += $teamScores[$teamId];
        }
        
        $predictions = [];
        foreach ($teamScores as $teamId => $score) {
            if ($totalScore > 0) {
                $percentage = round(($score / $totalScore) * 100, 1);
                $predictions[$teamId] = $percentage;
            } else {
                $predictions[$teamId] = 25.0;
            }
        }
        
        return $predictions;
    }
    
    private function getAdvancedPredictions(array $teams, array $fixtures, int $currentWeek): array
    {
        $standings = $this->getStandings($teams);
        $remainingWeeks = count($fixtures) - $currentWeek + 1;
        $maxPossiblePoints = $remainingWeeks * 3;
        
        $predictions = [];
        
        foreach ($teams as $team) {
            $teamId = $team['id'];
            $currentPoints = Redis::zscore('standings', $teamId) ?? 0;
            $maxPossibleTotal = $currentPoints + $maxPossiblePoints;
            
            $mathematicallyEliminated = false;
            
            foreach ($standings as $otherTeam) {
                if ($otherTeam['teamId'] === $teamId) continue;
                
                $otherCurrentPoints = $otherTeam['points'];
                
                if ($maxPossibleTotal < $otherCurrentPoints) {
                    $mathematicallyEliminated = true;
                    break;
                }
            }
            
            
            if ($mathematicallyEliminated) {
                $predictions[$teamId] = 0.0;
            } else if ($this->isMathematicallyGuaranteed($teamId, $standings, $maxPossiblePoints)) {
                $predictions[$teamId] = 100.0;
            } else {
                $probability = $this->calculateRemainingMatchesProbability($teamId, $teams, $fixtures, $currentWeek, $standings);
                $predictions[$teamId] = $probability;
            }
        }
        
        $totalPrediction = array_sum($predictions);
        if ($totalPrediction > 0) {
            foreach ($predictions as $teamId => $prediction) {
                $predictions[$teamId] = round(($prediction / $totalPrediction) * 100, 1);
            }
        }
        
        return $predictions;
    }
    
    private function isMathematicallyGuaranteed(int $teamId, array $standings, int $maxPossiblePoints): bool
    {
        $teamCurrentPoints = 0;
        
        foreach ($standings as $team) {
            if ($team['teamId'] === $teamId) {
                $teamCurrentPoints = $team['points'];
                break;
            }
        }
        
        foreach ($standings as $otherTeam) {
            if ($otherTeam['teamId'] === $teamId) continue;
            
            $otherMaxPossible = $otherTeam['points'] + $maxPossiblePoints;
            
            if ($otherMaxPossible >= $teamCurrentPoints) {
                return false;
            }
        }
        
        return true;
    }
    
    private function calculateRemainingMatchesProbability(int $teamId, array $teams, array $fixtures, int $currentWeek, array $standings): float
    {
        $teamPower = 75;
        $currentPoints = 0;
        
        foreach ($teams as $team) {
            if ($team['id'] === $teamId) {
                $teamPower = $team['power'] ?? 75;
                break;
            }
        }
        
        foreach ($standings as $standing) {
            if ($standing['teamId'] === $teamId) {
                $currentPoints = $standing['points'];
                break;
            }
        }
        
        $expectedPoints = 0;
        $remainingMatches = 0;
        
        for ($week = $currentWeek; $week <= count($fixtures); $week++) {
            if (!isset($fixtures[$week - 1])) continue;
            
            foreach ($fixtures[$week - 1] as $match) {
                if (($match['status'] ?? null) !== MatchStatusEnum::SCHEDULED->value) continue;
                
                $isHomeTeam = $match['home']['id'] === $teamId;
                $isAwayTeam = $match['away']['id'] === $teamId;
                
                if (!$isHomeTeam && !$isAwayTeam) continue;
                
                $remainingMatches++;
                
                $opponentId = $isHomeTeam ? $match['away']['id'] : $match['home']['id'];
                $opponentPower = 75;
                
                foreach ($teams as $team) {
                    if ($team['id'] === $opponentId) {
                        $opponentPower = $team['power'] ?? 75;
                        break;
                    }
                }
                
                $myEffectivePower = $teamPower + ($isHomeTeam ? 5 : 0);
                $powerDiff = $myEffectivePower - $opponentPower;
                
                $winProb = 0.5 + ($powerDiff * 0.01);
                $winProb = max(0.1, min(0.9, $winProb));
                
                $drawProb = 0.25;
                $loseProb = 1 - $winProb - $drawProb;
                
                $expectedPoints += ($winProb * 3) + ($drawProb * 1) + ($loseProb * 0);
            }
        }
        
        $currentPosition = 1;
        foreach ($standings as $index => $standing) {
            if ($standing['teamId'] === $teamId) {
                $currentPosition = $index + 1;
                break;
            }
        }
        
        $positionBonus = (5 - $currentPosition) * 10;
        $powerBonus = ($teamPower - 75) * 0.5;
        $expectedBonus = $remainingMatches > 0 ? (($expectedPoints / $remainingMatches) - 1.5) * 20 : 0;
        
        $probability = 25 + $positionBonus + $powerBonus + $expectedBonus;
        
        return max(5, min(95, $probability));
    }
    
    private function getWeeklyResults(array $fixtures): array
    {
        $weeklyResults = [];
        
        foreach ($fixtures as $weekIndex => $weekMatches) {
            $week = $weekIndex + 1;
            $results = [];
            
            foreach ($weekMatches as $match) {
                if (($match['status'] ?? null) === MatchStatusEnum::COMPLETED->value && 
                    $match['home_score'] !== null && $match['away_score'] !== null) {
                    $results[] = [
                        'home' => $match['home']['name'],
                        'away' => $match['away']['name'],
                        'home_score' => $match['home_score'],
                        'away_score' => $match['away_score']
                    ];
                }
            }
            
            if (!empty($results)) {
                $weeklyResults[] = [
                    'week' => $week,
                    'matches' => $results
                ];
            }
        }
        
        return $weeklyResults;
    }
    
    public function playNextWeek(): array
    {
        try {
            $fixtures = $this->getFixtures();
            $currentWeek = $this->getCurrentWeek();
            $totalWeeks = count($fixtures);
            
            if ($currentWeek > $totalWeeks) {
                Log::info('Season already completed', ['current_week' => $currentWeek, 'total_weeks' => $totalWeeks]);
                return $this->getState();
            }
            
            $this->playWeek($currentWeek, $fixtures);
            
            Redis::set('current_week', min($currentWeek + 1, $totalWeeks + 1));
            
            Log::info('Week played successfully', ['week' => $currentWeek]);
            
            return $this->getState();
        } catch (\Exception $e) {
            Log::error('Failed to play next week', ['error' => $e->getMessage()]);
            throw new SimulationException('Failed to simulate next week: ' . $e->getMessage());
        }
    }
    
    public function playAllRemaining(): array
    {
        try {
            $currentWeek = $this->getCurrentWeek();
            $totalWeeks = count($this->getFixtures());
            
            if ($currentWeek > $totalWeeks) {
                Log::info('Season already completed for play all');
                return $this->getState();
            }
            
            $weeksPlayed = 0;
            while ($currentWeek <= $totalWeeks) {
                $fixtures = $this->getFixtures();
                $this->playWeek($currentWeek, $fixtures);
                $currentWeek++;
                $weeksPlayed++;
            }
            
            Redis::set('current_week', min($currentWeek, $totalWeeks + 1));
            
            Log::info('All remaining weeks played', ['weeks_played' => $weeksPlayed]);
            
            return $this->getState();
        } catch (\Exception $e) {
            Log::error('Failed to play all remaining weeks', ['error' => $e->getMessage()]);
            throw new SimulationException('Failed to simulate remaining weeks: ' . $e->getMessage());
        }
    }
    
    public function resetLeague(): void
    {
        try {
            $teams = $this->getTeams();
            
            Redis::del('standings');
            foreach ($teams as $team) {
                Redis::zadd('standings', 0, $team['id']);
            }
            
            foreach ($teams as $team) {
                Redis::del("team_stats:" . $team['id']);
            }
            
            Redis::set('current_week', 1);
            
            $fixtures = $this->getFixtures();
            
            foreach ($fixtures as $weekIndex => $weekMatches) {
                foreach ($weekMatches as $matchIndex => $match) {
                    $fixtures[$weekIndex][$matchIndex]['status'] = MatchStatusEnum::SCHEDULED->value;
                    $fixtures[$weekIndex][$matchIndex]['home_score'] = null;
                    $fixtures[$weekIndex][$matchIndex]['away_score'] = null;
                    unset($fixtures[$weekIndex][$matchIndex]['played']);
                }
            }
            
            Redis::set('fixtures', json_encode($fixtures));
            
            Log::info('League reset successfully');
        } catch (\Exception $e) {
            Log::error('Failed to reset league', ['error' => $e->getMessage()]);
            throw new SimulationException('Failed to reset league: ' . $e->getMessage());
        }
    }
    
    private function playWeek(int $week, array $fixtures): void
    {
        if (!isset($fixtures[$week - 1])) {
            return;
        }
        
        $weekMatches = $fixtures[$week - 1];
        
        foreach ($weekMatches as $matchIndex => $match) {
            if (($match['status'] ?? MatchStatusEnum::SCHEDULED->value) === MatchStatusEnum::COMPLETED->value) {
                continue;
            }
            
            $homeTeam = $match['home'];
            $awayTeam = $match['away'];
            
            $homeGoals = $this->generatePowerBasedGoals($homeTeam, $awayTeam, true);
            $awayGoals = $this->generatePowerBasedGoals($awayTeam, $homeTeam, false);
            
            $fixtures[$week - 1][$matchIndex]['status'] = MatchStatusEnum::COMPLETED->value;
            $fixtures[$week - 1][$matchIndex]['home_score'] = $homeGoals;
            $fixtures[$week - 1][$matchIndex]['away_score'] = $awayGoals;
            unset($fixtures[$week - 1][$matchIndex]['played']);
            
            $this->updateStandings($match['home']['id'], $match['away']['id'], $homeGoals, $awayGoals);
        }
        
        Redis::set('fixtures', json_encode($fixtures));
    }
    
    private function generateGoals(bool $homeAdvantage = false): int
    {
        $base = random_int(0, 2);
        $advantage = $homeAdvantage ? 1 : 0;
        $swing = random_int(-1, 2);
        $goals = max(0, $base + $advantage + $swing);
        
        return min($goals, 6);
    }
    
    private function generatePowerBasedGoals(array $attackingTeam, array $defendingTeam, bool $isHome): int
    {
        $attackPower = $attackingTeam['power'] ?? 75;
        $defensePower = $defendingTeam['power'] ?? 75;
        
        if ($isHome) {
            $attackPower += 5;
        }
        
        $powerDifference = $attackPower - $defensePower;
        
        $baseExpectancy = 1.5;
        
        $powerFactor = $powerDifference * 0.02;
        
        $goalExpectancy = $baseExpectancy + $powerFactor;
        $goalExpectancy = max(0.3, min(4.0, $goalExpectancy));
        
        $randomFactor = (random_int(0, 100) / 100.0);
        
        if ($randomFactor < 0.2) {
            $goals = random_int(0, 3);
        } else {
            $goals = $this->poissonRandom($goalExpectancy);
        }
        
        return min($goals, 8);
    }
    
    private function poissonRandom(float $lambda): int
    {
        $L = exp(-$lambda);
        $k = 0;
        $p = 1.0;
        
        do {
            $k++;
            $p *= (random_int(1, 10000) / 10000.0);
        } while ($p > $L);
        
        return $k - 1;
    }
    
    private function updateStandings(int $homeTeamId, int $awayTeamId, int $homeGoals, int $awayGoals): void
    {
        $this->updateTeamStats($homeTeamId, $homeGoals, $awayGoals);
        $this->updateTeamStats($awayTeamId, $awayGoals, $homeGoals);
        
        if ($homeGoals > $awayGoals) {
            Redis::zincrby('standings', MatchResultEnum::WIN->getPoints(), $homeTeamId);
        } elseif ($homeGoals < $awayGoals) {
            Redis::zincrby('standings', MatchResultEnum::WIN->getPoints(), $awayTeamId);
        } else {
            Redis::zincrby('standings', MatchResultEnum::DRAW->getPoints(), $homeTeamId);
            Redis::zincrby('standings', MatchResultEnum::DRAW->getPoints(), $awayTeamId);
        }
    }
    
    private function updateTeamStats(int $teamId, int $scored, int $conceded): void
    {
        $statsKey = "team_stats:$teamId";
        
        Redis::hincrby($statsKey, 'played', 1);
        Redis::hincrby($statsKey, 'gf', $scored);
        Redis::hincrby($statsKey, 'ga', $conceded);
        
        if ($scored > $conceded) {
            Redis::hincrby($statsKey, MatchResultEnum::WIN->value, 1);
        } elseif ($scored < $conceded) {
            Redis::hincrby($statsKey, MatchResultEnum::LOSS->value, 1);
        } else {
            Redis::hincrby($statsKey, MatchResultEnum::DRAW->value, 1);
        }
    }
    
    public function editMatch(int $week, int $matchIndex, int $homeScore, int $awayScore): array
    {
        try {
            $fixtures = $this->getFixtures();
            
            if (!isset($fixtures[$week - 1][$matchIndex])) {
                throw new SimulationException('Match not found');
            }
            
            $match = $fixtures[$week - 1][$matchIndex];
            
            if (($match['status'] ?? null) !== MatchStatusEnum::COMPLETED->value) {
                throw new SimulationException('Only completed matches can be edited');
            }
            
            $oldHomeScore = $match['home_score'];
            $oldAwayScore = $match['away_score'];
            $homeTeamId = $match['home']['id'];
            $awayTeamId = $match['away']['id'];
            
            $fixtures[$week - 1][$matchIndex]['home_score'] = $homeScore;
            $fixtures[$week - 1][$matchIndex]['away_score'] = $awayScore;
            
            Redis::set('fixtures', json_encode($fixtures));
            
            $this->recalculateStandings();
            
            Log::info('Match edited successfully', [
                'week' => $week,
                'match_index' => $matchIndex,
                'old_score' => "$oldHomeScore-$oldAwayScore",
                'new_score' => "$homeScore-$awayScore"
            ]);
            
            return $this->getState();
            
        } catch (\Exception $e) {
            Log::error('Failed to edit match', [
                'week' => $week,
                'match_index' => $matchIndex,
                'error' => $e->getMessage()
            ]);
            
            if ($e instanceof SimulationException) {
                throw $e;
            }
            
            throw new SimulationException('Failed to edit match: ' . $e->getMessage());
        }
    }
    
    private function recalculateStandings(): void
    {
        $teams = $this->getTeams();
        $fixtures = $this->getFixtures();
        
        Redis::del('standings');
        foreach ($teams as $team) {
            Redis::zadd('standings', 0, $team['id']);
            Redis::del("team_stats:" . $team['id']);
        }
        
        foreach ($fixtures as $weekMatches) {
            foreach ($weekMatches as $match) {
                if (($match['status'] ?? null) === MatchStatusEnum::COMPLETED->value &&
                    $match['home_score'] !== null && $match['away_score'] !== null) {
                    
                    $this->updateStandings(
                        $match['home']['id'],
                        $match['away']['id'],
                        $match['home_score'],
                        $match['away_score']
                    );
                }
            }
        }
    }
}