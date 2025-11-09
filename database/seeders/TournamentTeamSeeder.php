<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Redis;

class TournamentTeamSeeder extends Seeder
{
    public function run(): void
    {
        $teams = $this->loadTeamsFromFile();

        Redis::flushall();
        Redis::set('teams', json_encode($teams));

        $this->command?->info('✅ ' . count($teams) . ' teams loaded to Redis!');
        $this->command?->info('🎯 You can now press "Generate Fixtures" button.');
    }
    
    private function loadTeamsFromFile(): array
    {
        $filePath = database_path('data/tournament_teams.txt');
        
        if (!file_exists($filePath)) {
            throw new \Exception('Teams file not found: ' . $filePath);
        }
        
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $teams = [];
        
        foreach ($lines as $index => $line) {
            $parts = explode(',', trim($line));
            
            if (count($parts) !== 2) {
                throw new \Exception('Invalid team format in line ' . ($index + 1) . ': ' . $line);
            }
            
            $name = trim($parts[0]);
            $power = (int) trim($parts[1]);
            
            if ($power < 1 || $power > 100) {
                throw new \Exception('Invalid team power for ' . $name . ': ' . $power . ' (must be 1-100)');
            }
            
            $teams[] = [
                'id' => $index + 1,
                'name' => $name,
                'power' => $power
            ];
        }
        
        if (count($teams) < 2) {
            throw new \Exception('At least 2 teams required, found: ' . count($teams));
        }
        
        return $teams;
    }
}
