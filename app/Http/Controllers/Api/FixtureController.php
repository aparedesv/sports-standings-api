<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Fixture;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FixtureController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Fixture::with(['league', 'homeTeam', 'awayTeam']);

        if ($request->has('league_id')) {
            $query->where('league_id', $request->league_id);
        }

        if ($request->has('season_id')) {
            $query->where('season_id', $request->season_id);
        }

        if ($request->has('team_id')) {
            $teamId = $request->team_id;
            $query->where(function ($q) use ($teamId) {
                $q->where('home_team_id', $teamId)
                    ->orWhere('away_team_id', $teamId);
            });
        }

        if ($request->has('date')) {
            $query->whereDate('date', $request->date);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('from')) {
            $query->whereDate('date', '>=', $request->from);
        }

        if ($request->has('to')) {
            $query->whereDate('date', '<=', $request->to);
        }

        $fixtures = $query->orderBy('date', 'desc')->paginate(50);

        return response()->json($fixtures);
    }

    public function show(Fixture $fixture): JsonResponse
    {
        $fixture->load(['league', 'season', 'homeTeam', 'awayTeam']);

        return response()->json([
            'data' => $fixture,
        ]);
    }

    public function live(): JsonResponse
    {
        $fixtures = Fixture::with(['league', 'homeTeam', 'awayTeam'])
            ->whereIn('status', ['1H', 'HT', '2H', 'ET', 'P'])
            ->orderBy('date')
            ->get();

        return response()->json([
            'data' => $fixtures,
        ]);
    }

    public function today(): JsonResponse
    {
        $fixtures = Fixture::with(['league', 'homeTeam', 'awayTeam'])
            ->whereDate('date', today())
            ->orderBy('date')
            ->get();

        return response()->json([
            'data' => $fixtures,
        ]);
    }
}
