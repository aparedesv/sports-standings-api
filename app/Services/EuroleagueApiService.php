<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EuroleagueApiService
{
    private string $baseUrl = 'https://api-live.euroleague.net/v1';
    private int $cacheMinutes = 30;

    // Competition codes: E = Euroleague, U = Eurocup
    private array $competitions = [
        'euroleague' => [
            'code' => 'E',
            'name' => 'Euroleague',
            'order' => 1,
        ],
        'eurocup' => [
            'code' => 'U',
            'name' => 'Eurocup',
            'order' => 2,
        ],
    ];

    /**
     * Get current season code (e.g., E2024 for Euroleague 2024-25)
     */
    public function getCurrentSeasonCode(string $competition = 'euroleague'): string
    {
        $now = now();
        $year = $now->year;
        $month = $now->month;

        // European basketball season runs Oct-June
        // Oct-Dec: current year (e.g., Oct 2024 = 2024)
        // Jan-June: previous year (e.g., Jan 2025 = 2024)
        if ($month >= 10) {
            $seasonYear = $year;
        } else {
            $seasonYear = $year - 1;
        }

        $prefix = $this->competitions[$competition]['code'] ?? 'E';
        return $prefix . $seasonYear;
    }

    /**
     * Get standings for a specific competition
     */
    public function getStandings(string $competition = 'euroleague', ?string $seasonCode = null): ?array
    {
        $seasonCode = $seasonCode ?? $this->getCurrentSeasonCode($competition);
        $cacheKey = "euroleague_{$competition}_{$seasonCode}";

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($competition, $seasonCode) {
            try {
                $response = Http::timeout(15)
                    ->accept('application/xml')
                    ->get("{$this->baseUrl}/standings", [
                        'seasonCode' => $seasonCode,
                    ]);

                if ($response->successful()) {
                    return $this->parseStandings($response->body(), $competition, $seasonCode);
                }

                Log::error("Euroleague API error for {$competition}: " . $response->status());
                return null;
            } catch (\Exception $e) {
                Log::error("Euroleague API exception for {$competition}: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Parse XML standings response
     */
    private function parseStandings(string $xmlContent, string $competition, string $seasonCode): array
    {
        $competitionInfo = $this->competitions[$competition] ?? [
            'name' => ucfirst($competition),
            'order' => 99,
        ];

        try {
            $xml = simplexml_load_string($xmlContent);
            if ($xml === false) {
                Log::error("Failed to parse Euroleague XML for {$competition}");
                return $this->emptyResponse($competitionInfo, $seasonCode);
            }

            $groups = [];

            // Handle multiple groups (Eurocup has 2 groups)
            foreach ($xml->group as $group) {
                $groupName = (string) ($group['name'] ?? 'Regular Season');
                $teams = [];

                foreach ($group->team as $team) {
                    $teams[] = [
                        'rank' => (int) ((string) $team->ranking),
                        'team_code' => (string) $team->code,
                        'team_name' => (string) $team->name,
                        'played' => (int) ((string) $team->totalgames),
                        'won' => (int) ((string) $team->wins),
                        'lost' => (int) ((string) $team->losses),
                        'points_for' => (int) ((string) $team->ptsfavour),
                        'points_against' => (int) ((string) $team->ptsagainst),
                        'point_diff' => (int) ((string) $team->difference),
                    ];
                }

                // Sort by rank
                usort($teams, fn($a, $b) => $a['rank'] <=> $b['rank']);

                $groups[] = [
                    'name' => $groupName,
                    'teams' => $teams,
                ];
            }

            // If only one group, flatten the structure
            if (count($groups) === 1) {
                return [
                    'competition' => $competitionInfo['name'],
                    'season' => $this->formatSeasonDisplay($seasonCode),
                    'season_code' => $seasonCode,
                    'order' => $competitionInfo['order'],
                    'has_groups' => false,
                    'standings' => $groups[0]['teams'],
                ];
            }

            return [
                'competition' => $competitionInfo['name'],
                'season' => $this->formatSeasonDisplay($seasonCode),
                'season_code' => $seasonCode,
                'order' => $competitionInfo['order'],
                'has_groups' => true,
                'groups' => $groups,
            ];

        } catch (\Exception $e) {
            Log::error("Error parsing Euroleague standings: " . $e->getMessage());
            return $this->emptyResponse($competitionInfo, $seasonCode);
        }
    }

    /**
     * Get all teams for a competition
     */
    public function getTeams(string $competition = 'euroleague', ?string $seasonCode = null): ?array
    {
        $seasonCode = $seasonCode ?? $this->getCurrentSeasonCode($competition);
        $cacheKey = "euroleague_teams_{$competition}_{$seasonCode}";

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($competition, $seasonCode) {
            try {
                $response = Http::timeout(15)
                    ->accept('application/xml')
                    ->get("{$this->baseUrl}/teams", [
                        'seasonCode' => $seasonCode,
                    ]);

                if ($response->successful()) {
                    return $this->parseTeams($response->body(), $competition, $seasonCode);
                }

                Log::error("Euroleague Teams API error for {$competition}: " . $response->status());
                return null;
            } catch (\Exception $e) {
                Log::error("Euroleague Teams API exception for {$competition}: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Parse XML teams response
     */
    private function parseTeams(string $xmlContent, string $competition, string $seasonCode): array
    {
        $competitionInfo = $this->competitions[$competition] ?? ['name' => ucfirst($competition)];

        try {
            $xml = simplexml_load_string($xmlContent);
            if ($xml === false) {
                return ['competition' => $competitionInfo['name'], 'teams' => []];
            }

            $teams = [];
            foreach ($xml->club as $club) {
                $teams[] = [
                    'code' => (string) ($club['code'] ?? ''),
                    'name' => (string) $club->name,
                    'country' => (string) $club->countryname,
                    'city' => (string) ($club->clubaddress ?? ''),
                    'arena' => (string) ($club->arena['name'] ?? ''),
                    'website' => (string) ($club->website ?? ''),
                ];
            }

            // Sort alphabetically by name
            usort($teams, fn($a, $b) => $a['name'] <=> $b['name']);

            return [
                'competition' => $competitionInfo['name'],
                'season' => $this->formatSeasonDisplay($seasonCode),
                'teams' => $teams,
            ];

        } catch (\Exception $e) {
            Log::error("Error parsing Euroleague teams: " . $e->getMessage());
            return ['competition' => $competitionInfo['name'], 'teams' => []];
        }
    }

    /**
     * Get all standings for both competitions
     */
    public function getAllStandings(): array
    {
        $allStandings = [];

        foreach (array_keys($this->competitions) as $competition) {
            $data = $this->getStandings($competition);
            if ($data && (!empty($data['standings']) || !empty($data['groups']))) {
                $allStandings[] = $data;
            }
        }

        // Sort by order
        usort($allStandings, fn($a, $b) => ($a['order'] ?? 99) <=> ($b['order'] ?? 99));

        return $allStandings;
    }

    /**
     * Get available competitions
     */
    public function getAvailableCompetitions(): array
    {
        return $this->competitions;
    }

    /**
     * Get available seasons for a competition
     */
    public function getAvailableSeasons(string $competition = 'euroleague'): array
    {
        $prefix = $this->competitions[$competition]['code'] ?? 'E';
        $currentYear = (int) substr($this->getCurrentSeasonCode($competition), 1);
        $seasons = [];

        // Last 5 seasons
        for ($i = 0; $i < 5; $i++) {
            $year = $currentYear - $i;
            $seasons[] = [
                'code' => $prefix . $year,
                'display' => $year . '-' . substr($year + 1, -2),
            ];
        }

        return $seasons;
    }

    /**
     * Format season code for display (E2024 -> 2024-25)
     */
    private function formatSeasonDisplay(string $seasonCode): string
    {
        $year = (int) substr($seasonCode, 1);
        return $year . '-' . substr($year + 1, -2);
    }

    /**
     * Empty response structure
     */
    private function emptyResponse(array $competitionInfo, string $seasonCode): array
    {
        return [
            'competition' => $competitionInfo['name'],
            'season' => $this->formatSeasonDisplay($seasonCode),
            'season_code' => $seasonCode,
            'order' => $competitionInfo['order'] ?? 99,
            'has_groups' => false,
            'standings' => [],
        ];
    }

    /**
     * Clear cache for a specific competition or all
     */
    public function clearCache(?string $competition = null, ?string $seasonCode = null): void
    {
        if ($competition && $seasonCode) {
            Cache::forget("euroleague_{$competition}_{$seasonCode}");
            Cache::forget("euroleague_teams_{$competition}_{$seasonCode}");
        } elseif ($competition) {
            foreach ($this->getAvailableSeasons($competition) as $season) {
                Cache::forget("euroleague_{$competition}_{$season['code']}");
                Cache::forget("euroleague_teams_{$competition}_{$season['code']}");
            }
        } else {
            foreach (array_keys($this->competitions) as $comp) {
                foreach ($this->getAvailableSeasons($comp) as $season) {
                    Cache::forget("euroleague_{$comp}_{$season['code']}");
                    Cache::forget("euroleague_teams_{$comp}_{$season['code']}");
                }
            }
        }
    }
}
