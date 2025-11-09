<?php

return [
    'prefix' => 'tournament',
    'teams_key' => 'tournament:teams',
    'teams_file' => database_path('data/tournament_teams.txt'),
    'matches_per_week' => 2,
    'lock_timeout_ms' => 5000,
    'teams' => [
        'Liverpool',
        'Manchester City',
        'Chelsea',
        'Arsenal',
    ],
];
