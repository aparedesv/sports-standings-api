<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EspnNflService
{
    private string $baseUrl = 'https://site.api.espn.com/apis/v2/sports/football/nfl';
    private int $cacheMinutes = 60;

    public function getStandings(): ?array
    {
        $cacheKey = "nfl_standings_espn";

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () {
            try {
                $response = Http::timeout(30)
                    ->get("{$this->baseUrl}/standings");

                if ($response->successful()) {
                    return $this->parseStandings($response->json());
                }

                Log::error('ESPN NFL API error: ' . $response->status());
                return null;
            } catch (\Exception $e) {
                Log::error('ESPN NFL API exception: ' . $e->getMessage());
                return null;
            }
        });
    }

    private function parseStandings(array $data): array
    {
        $standings = ['afc' => [], 'nfc' => []];

        $conferences = $data['children'] ?? [];

        foreach ($conferences as $conference) {
            $confName = strtolower($conference['abbreviation'] ?? '');
            $confKey = $confName === 'afc' ? 'afc' : 'nfc';

            $entries = $conference['standings']['entries'] ?? [];

            foreach ($entries as $entry) {
                $team = $entry['team'] ?? [];
                $stats = collect($entry['stats'] ?? []);

                $wins = (int) ($stats->firstWhere('name', 'wins')['value'] ?? 0);
                $losses = (int) ($stats->firstWhere('name', 'losses')['value'] ?? 0);
                $ties = (int) ($stats->firstWhere('name', 'ties')['value'] ?? 0);
                $winPct = (float) ($stats->firstWhere('name', 'winPercent')['value'] ?? 0);
                $pointsFor = (int) ($stats->firstWhere('name', 'pointsFor')['value'] ?? 0);
                $pointsAgainst = (int) ($stats->firstWhere('name', 'pointsAgainst')['value'] ?? 0);

                $standings[$confKey][] = [
                    'team_id' => $team['id'] ?? 0,
                    'team_name' => $team['name'] ?? '',
                    'team_city' => $team['location'] ?? '',
                    'wins' => $wins,
                    'losses' => $losses,
                    'ties' => $ties,
                    'win_pct' => $winPct,
                    'points_for' => $pointsFor,
                    'points_against' => $pointsAgainst,
                    'point_diff' => $pointsFor - $pointsAgainst,
                    'conference' => $conference['name'] ?? '',
                ];
            }

            // Sort by win percentage descending
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

        // NFL season runs Sept-Feb
        if ($month >= 9) {
            return (string) $year;
        } else {
            return (string) ($year - 1);
        }
    }

    public function clearCache(): void
    {
        Cache::forget("nfl_standings_espn");
    }
}
