<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EspnF1Service
{
    private string $baseUrl = 'https://site.web.api.espn.com/apis/v2/sports/racing/f1';
    private int $cacheMinutes = 60;

    public function getStandings(): ?array
    {
        $cacheKey = "f1_standings_espn";

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () {
            try {
                $response = Http::timeout(30)
                    ->get("{$this->baseUrl}/standings");

                if ($response->successful()) {
                    return $this->parseStandings($response->json());
                }

                Log::error('ESPN F1 API error: ' . $response->status());
                return null;
            } catch (\Exception $e) {
                Log::error('ESPN F1 API exception: ' . $e->getMessage());
                return null;
            }
        });
    }

    private function parseStandings(array $data): array
    {
        $standings = ['drivers' => [], 'constructors' => []];

        $children = $data['children'] ?? [];

        foreach ($children as $child) {
            $name = strtolower($child['name'] ?? '');
            $entries = $child['standings']['entries'] ?? [];

            if (str_contains($name, 'driver')) {
                foreach ($entries as $entry) {
                    $athlete = $entry['athlete'] ?? [];
                    $stats = collect($entry['stats'] ?? []);

                    $rank = (int) ($stats->firstWhere('name', 'rank')['value'] ?? 0);
                    $points = (int) ($stats->firstWhere('name', 'championshipPts')['value'] ?? 0);

                    $standings['drivers'][] = [
                        'rank' => $rank,
                        'name' => $athlete['displayName'] ?? $athlete['name'] ?? '',
                        'country' => $athlete['flag']['alt'] ?? '',
                        'points' => $points,
                    ];
                }

                usort($standings['drivers'], fn($a, $b) => $a['rank'] <=> $b['rank']);
            } elseif (str_contains($name, 'constructor')) {
                foreach ($entries as $entry) {
                    $team = $entry['team'] ?? [];
                    $stats = collect($entry['stats'] ?? []);

                    $rank = (int) ($stats->firstWhere('name', 'rank')['value'] ?? 0);
                    $points = (int) ($stats->firstWhere('name', 'points')['value'] ?? 0);

                    $standings['constructors'][] = [
                        'rank' => $rank,
                        'name' => $team['displayName'] ?? $team['name'] ?? '',
                        'points' => $points,
                    ];
                }

                usort($standings['constructors'], fn($a, $b) => $a['rank'] <=> $b['rank']);
            }
        }

        return $standings;
    }

    public function getCurrentSeason(): string
    {
        return (string) now()->year;
    }

    public function clearCache(): void
    {
        Cache::forget("f1_standings_espn");
    }
}
