<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\JsonResponse;

class CountryController extends Controller
{
    public function index(): JsonResponse
    {
        $countries = Country::withCount('leagues')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $countries,
        ]);
    }

    public function show(Country $country): JsonResponse
    {
        $country->load('leagues');

        return response()->json([
            'data' => $country,
        ]);
    }
}
