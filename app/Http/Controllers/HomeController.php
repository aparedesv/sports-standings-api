<?php

namespace App\Http\Controllers;

use App\Models\League;
use App\Models\Season;

class HomeController extends Controller
{
    public function index()
    {
        // Structure: sports -> seasons -> leagues
        $sports = [
            'football' => [
                'name' => 'Football',
                'seasons' => $this->getFootballSeasons(),
            ],
            // Future sports:
            // 'basketball' => ['name' => 'Basketball', 'seasons' => $this->getBasketballSeasons()],
            // 'tennis' => ['name' => 'Tennis', 'seasons' => $this->getTennisSeasons()],
        ];

        return view('home', compact('sports'));
    }

    private function getFootballSeasons()
    {
        $mainLeagues = ['La Liga', 'Premier League', 'Serie A', 'Bundesliga'];

        return Season::with(['league.country', 'standings.team'])
            ->has('standings')
            ->orderBy('year', 'desc')
            ->get()
            ->groupBy('year')
            ->map(function ($group) use ($mainLeagues) {
                // Separate main leagues and others
                $main = $group->filter(function ($s) use ($mainLeagues) {
                    $name = $s->league->name ?? '';
                    if ($name === 'Bundesliga') {
                        return ($s->league->country->name ?? '') === 'Germany';
                    }
                    return in_array($name, $mainLeagues);
                })->sortBy([
                    fn($a, $b) => ($a->league->country->name ?? '') <=> ($b->league->country->name ?? ''),
                    fn($a, $b) => ($a->league->name ?? '') <=> ($b->league->name ?? ''),
                ]);

                $others = $group->filter(function ($s) use ($mainLeagues) {
                    $name = $s->league->name ?? '';
                    if ($name === 'Bundesliga') {
                        return ($s->league->country->name ?? '') !== 'Germany';
                    }
                    return !in_array($name, $mainLeagues);
                })->sortBy([
                    fn($a, $b) => $this->getDivision($a->league->name ?? '') <=> $this->getDivision($b->league->name ?? ''),
                    fn($a, $b) => ($a->league->country->name ?? '') <=> ($b->league->country->name ?? ''),
                    fn($a, $b) => ($a->league->name ?? '') <=> ($b->league->name ?? ''),
                ]);

                return $main->concat($others);
            });
    }

    private function getDivision(string $leagueName): int
    {
        $secondDivision = ['2.', 'Segunda', 'Championship', 'Serie B', 'Ligue 2'];
        foreach ($secondDivision as $pattern) {
            if (str_contains($leagueName, $pattern)) {
                return 2;
            }
        }
        return 1;
    }
}
