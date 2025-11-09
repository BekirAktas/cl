<?php

namespace App\Http\Controllers\Team;

use App\Http\Controllers\Controller;
use App\Services\FixtureBuilderService;
use App\Services\SimulationService;
use App\Http\Requests\GenerateFixturesRequest;
use App\Http\Requests\SimulationActionRequest;
use App\Http\Requests\EditMatchRequest;
use App\Http\Responses\ApiResponse;
use App\Enums\ResponseStatusEnum;
use Exception;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class TeamController extends Controller
{
    public function __construct(
        private readonly FixtureBuilderService $fixtureBuilder,
        private readonly SimulationService $simulationService
    ) {
    }

    public function index(): Response
    {
        $fixtures = $this->fixtureBuilder->getFixtures();
        $currentWeek = $this->fixtureBuilder->getCurrentWeek();

        return Inertia::render('Tournament', [
            'teams' => $this->fixtureBuilder->getTeams(),
            'league' => [
                'currentWeek' => $currentWeek,
                'totalWeeks' => count($fixtures)
            ]
        ]);
    }

    public function fixtures(): Response
    {
        return Inertia::render('Fixtures', [
            'fixtures' => $this->fixtureBuilder->getFixtures(),
        ]);
    }

    public function generateFixtures(GenerateFixturesRequest $_request): JsonResponse
    {
        try {
            $result = $this->fixtureBuilder->buildFixtures();

            return ApiResponse::success($result, 'Fixtures generated successfully', ResponseStatusEnum::CREATED);
        } catch (\Exception $e) {
            Log::error('Failed to generate fixtures', ['error' => $e->getMessage()]);
            return ApiResponse::error('Failed to generate fixtures', ResponseStatusEnum::INTERNAL_SERVER_ERROR);
        }
    }

    public function simulation(): Response
    {
        return Inertia::render('Simulation', [
            'league' => $this->simulationService->getState(),
        ]);
    }

    public function playWeek(SimulationActionRequest $_request): RedirectResponse
    {
        try {
            $this->simulationService->playNextWeek();
            return redirect()->route('simulation');
        } catch (\Exception $e) {
            Log::error('Failed to play week', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to simulate week']);
        }
    }

    public function playAll(SimulationActionRequest $_request): RedirectResponse
    {
        try {
            $this->simulationService->playAllRemaining();
            return redirect()->route('simulation');
        } catch (\Exception $e) {
            Log::error('Failed to play all weeks', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to simulate all weeks']);
        }
    }

    public function reset(SimulationActionRequest $_request): RedirectResponse
    {
        try {
            $this->simulationService->resetLeague();
            return redirect()->route('simulation');
        } catch (\Exception $e) {
            Log::error('Failed to reset league', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to reset league']);
        }
    }
    
    public function editMatch(EditMatchRequest $request): RedirectResponse
    {
        try {
            $this->simulationService->editMatch(
                $request->validated('week'),
                $request->validated('match_index'),
                $request->validated('home_score'),
                $request->validated('away_score')
            );
            
            return redirect()->route('simulation')->with('success', 'Match result updated successfully');
        } catch (\Exception $e) {
            Log::error('Failed to edit match', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Failed to update match result']);
        }
    }
}
