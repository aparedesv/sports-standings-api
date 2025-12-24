<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class NbaApiService
{
    // Using ESPN API (free, no auth required)
    private string $baseUrl = 'https://site.api.espn.com/apis/v2/sports/basketball/nba';
    private int $cacheMinutes = 60;

    public function getStandings(string $season = null): ?array
    {
        $cacheKey = "nba_standings_espn";

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () {
            try {
                $response = Http::timeout(30)
                    ->get("{$this->baseUrl}/standings");

                if ($response->successful()) {
                    return $this->parseEspnStandings($response->json());
                }

                Log::error('ESPN NBA API error: ' . $response->status());
                return null;
            } catch (\Exception $e) {
                Log::error('ESPN NBA API exception: ' . $e->getMessage());
                return null;
            }
        });
    }

    private function parseEspnStandings(array $data): array
    {
        $standings = ['eastern' => [], 'western' => []];

        $conferences = $data['children'] ?? [];

        foreach ($conferences as $conference) {
            $confName = strtolower($conference['abbreviation'] ?? '');
            $confKey = $confName === 'east' ? 'eastern' : 'western';

            $entries = $conference['standings']['entries'] ?? [];

            foreach ($entries as $entry) {
                $team = $entry['team'] ?? [];
                $stats = collect($entry['stats'] ?? []);

                $wins = (int) ($stats->firstWhere('name', 'wins')['value'] ?? 0);
                $losses = (int) ($stats->firstWhere('name', 'losses')['value'] ?? 0);
                $winPct = (float) ($stats->firstWhere('name', 'winPercent')['value'] ?? 0);

                $standings[$confKey][] = [
                    'team_id' => $team['id'] ?? 0,
                    'team_name' => $team['name'] ?? '',
                    'team_city' => $team['location'] ?? '',
                    'wins' => $wins,
                    'losses' => $losses,
                    'win_pct' => $winPct,
                    'conference' => $conference['name'] ?? '',
                ];
            }

            // Sort by win percentage descending, then wins descending
            usort($standings[$confKey], function ($a, $b) {
                if ($b['win_pct'] !== $a['win_pct']) {
                    return $b['win_pct'] <=> $a['win_pct'];
                }
                return $b['wins'] <=> $a['wins'];
            });

            // Add rank after sorting
            foreach ($standings[$confKey] as $i => &$team) {
                $team['rank'] = $i + 1;
            }
        }

        return $standings;
    }

    public function getCurrentSeason(): string
    {
        $now = now();
        $year = $now->year;
        $month = $now->month;

        // NBA season runs Oct-June, so:
        // Oct-Dec: current year (e.g., Oct 2024 = 2024-25)
        // Jan-June: previous year (e.g., Jan 2025 = 2024-25)
        // July-Sept: previous year (offseason, show last completed)
        if ($month >= 10) {
            // Oct-Dec: new season starting
            return $year . '-' . substr($year + 1, -2);
        } else {
            // Jan-Sept: season started previous year
            return ($year - 1) . '-' . substr($year, -2);
        }
    }

    public function getAvailableSeasons(): array
    {
        $currentYear = (int) substr($this->getCurrentSeason(), 0, 4);
        $seasons = [];

        // Last 5 seasons
        for ($i = 0; $i < 5; $i++) {
            $year = $currentYear - $i;
            $seasons[] = $year . '-' . substr($year + 1, -2);
        }

        return $seasons;
    }

    public function clearCache(string $season = null): void
    {
        if ($season) {
            Cache::forget("nba_standings_{$season}");
        } else {
            foreach ($this->getAvailableSeasons() as $s) {
                Cache::forget("nba_standings_{$s}");
            }
        }
    }
}
