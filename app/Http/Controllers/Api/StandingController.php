<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\League;
use App\Models\Standing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StandingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Standing::with(['team', 'league']);

        if ($request->has('league_id')) {
            $query->where('league_id', $request->league_id);
        }

        if ($request->has('season_id')) {
            $query->where('season_id', $request->season_id);
        }

        $standings = $query->orderBy('rank')->get();

        return response()->json([
            'data' => $standings,
        ]);
    }

    public function byLeague(League $league, Request $request): JsonResponse
    {
        $seasonId = $request->season_id;

        if (!$seasonId) {
            $currentSeason = $league->currentSeason();
            $seasonId = $currentSeason?->id;
        }

        if (!$seasonId) {
            return response()->json([
                'data' => [],
                'message' => 'No season found',
            ]);
        }

        $standings = Standing::with('team')
            ->where('league_id', $league->id)
            ->where('season_id', $seasonId)
            ->orderBy('rank')
            ->get();

        return response()->json([
            'data' => $standings,
            'league' => $league->load('country'),
        ]);
    }
}
