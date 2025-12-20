<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\League;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeagueController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = League::with('country', 'seasons');

        if ($request->has('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $leagues = $query->orderBy('name')->get();

        return response()->json([
            'data' => $leagues,
        ]);
    }

    public function show(League $league): JsonResponse
    {
        $league->load(['country', 'seasons' => function ($q) {
            $q->orderByDesc('year');
        }]);

        return response()->json([
            'data' => $league,
        ]);
    }
}
