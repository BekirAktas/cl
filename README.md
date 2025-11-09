# Football League Simulation

A professional Laravel + React.js football league simulation system with advanced match prediction algorithms and realistic team power-based gameplay.

🔗 **Live Demo:** [https://cl-production-80ad.up.railway.app](https://cl-production-80ad.up.railway.app)  

## Features

- **Round-Robin Tournament**: Automatic fixture generation with proper home/away rotation
- **Power-Based Match Simulation**: Realistic match results using team strength, home advantage, and Poisson distribution
- **Advanced Predictions**: Mathematical elimination checks and probability calculations for championship predictions
- **Interactive Match Editing**: Click on any completed match score to edit results with inline editing interface
- **Real-Time Standings**: Live league table with comprehensive statistics (P, W, D, L, GF, GA, GD, Pts)
- **Season Management**: Play week by week or simulate entire season at once
- **Predictions**: final 3 weeks with mathematical elimination detection

## Tech Stack

- **Backend**: Laravel 12, Redis for high-performance data storage
- **Frontend**: React.js with Inertia.js for SPA experience
- **Styling**: Tailwind CSS for modern UI
- **Architecture**: Service-oriented with proper exception handling and logging

## Quick Start

1. **Install dependencies:**
   ```bash
   composer install
   npm install
   ```

2. **Setup environment:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Start Redis and run seeders:**
   ```bash
   redis-server
   php artisan db:seed --class=TournamentTeamSeeder
   ```

4. **Run the application:**
   ```bash
   php artisan serve
   npm run dev
   ```

## Usage

1. **Generate Fixtures**: Click "Generate Fixtures" to create the round-robin tournament schedule
2. **Simulate Matches**: Use "Play Next Week" for step-by-step simulation or "Play All Weeks" for full season
3. **Edit Match Results**: After season completion, click on any match score to edit with inline editing interface
4. **View Live Standings**: See real-time league table with automatic sorting and statistics
5. **Championship Predictions**: Monitor probability percentages with advanced algorithms for final weeks

## Team Configuration
Add teams in `database/data/tournament_teams.txt`:
```
Liverpool,92
Manchester City,95
Chelsea,86
Arsenal,88
```

Format: `TeamName,PowerRating` (Power: 1-100)

## Key Components

- **SimulationService**: Core match simulation and prediction algorithms
- **FixtureBuilderService**: Round-robin tournament generation
- **Team Power System**: Realistic match outcomes based on team strengths
- **Redis Storage**: High-performance data persistence for real-time updates

Built with modern Laravel practices including Enums, Request Validation, API Resources, and comprehensive error handling.
