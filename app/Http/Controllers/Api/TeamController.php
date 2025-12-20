<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeamController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Team::with('country');

        if ($request->has('country_id')) {
            $query->where('country_id', $request->country_id);
        }

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $teams = $query->orderBy('name')->paginate(50);

        return response()->json($teams);
    }

    public function show(Team $team): JsonResponse
    {
        $team->load('country');

        return response()->json([
            'data' => $team,
        ]);
    }
}
