<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EspnFootballService
{
    private string $baseUrl = 'https://site.web.api.espn.com/apis/v2/sports/soccer';
    private int $cacheMinutes = 30;

    // Main leagues configuration
    private array $leagues = [
        // Main leagues (shown first)
        'eng.1' => ['name' => 'Premier League', 'country' => 'England', 'main' => true, 'order' => 1],
        'ger.1' => ['name' => 'Bundesliga', 'country' => 'Germany', 'main' => true, 'order' => 1],
        'ita.1' => ['name' => 'Serie A', 'country' => 'Italy', 'main' => true, 'order' => 1],
        'esp.1' => ['name' => 'La Liga', 'country' => 'Spain', 'main' => true, 'order' => 1],
        // European competitions
        'uefa.champions' => ['name' => 'Champions League', 'country' => 'Europe', 'main' => true, 'order' => 0],
        'uefa.europa' => ['name' => 'Europa League', 'country' => 'Europe', 'main' => true, 'order' => 0],
        // Other leagues
        'fra.1' => ['name' => 'Ligue 1', 'country' => 'France', 'main' => false, 'order' => 2],
        'ned.1' => ['name' => 'Eredivisie', 'country' => 'Netherlands', 'main' => false, 'order' => 2],
        'por.1' => ['name' => 'Primeira Liga', 'country' => 'Portugal', 'main' => false, 'order' => 2],
        'bel.1' => ['name' => 'Pro League', 'country' => 'Belgium', 'main' => false, 'order' => 2],
    ];

    public function getStandings(string $leagueCode): ?array
    {
        $cacheKey = "espn_football_{$leagueCode}";

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($leagueCode) {
            try {
                $response = Http::timeout(15)
                    ->get("{$this->baseUrl}/{$leagueCode}/standings");

                if ($response->successful()) {
                    return $this->parseStandings($response->json(), $leagueCode);
                }

                Log::error("ESPN Football API error for {$leagueCode}: " . $response->status());
                return null;
            } catch (\Exception $e) {
                Log::error("ESPN Football API exception for {$leagueCode}: " . $e->getMessage());
                return null;
            }
        });
    }

    private function parseStandings(array $data, string $leagueCode): array
    {
        $leagueInfo = $this->leagues[$leagueCode] ?? ['name' => $data['name'] ?? 'Unknown', 'country' => '', 'main' => false];

        $children = $data['children'] ?? [];
        $seasonData = $children[0] ?? [];
        $entries = $seasonData['standings']['entries'] ?? [];

        $standings = [];
        foreach ($entries as $entry) {
            $team = $entry['team'] ?? [];
            $stats = collect($entry['stats'] ?? []);

            $standings[] = [
                'rank' => (int) ($stats->firstWhere('name', 'rank')['value'] ?? 0),
                'team_name' => $team['displayName'] ?? $team['name'] ?? '',
                'played' => (int) ($stats->firstWhere('name', 'gamesPlayed')['value'] ?? 0),
                'won' => (int) ($stats->firstWhere('name', 'wins')['value'] ?? 0),
                'drawn' => (int) ($stats->firstWhere('name', 'ties')['value'] ?? 0),
                'lost' => (int) ($stats->firstWhere('name', 'losses')['value'] ?? 0),
                'goals_for' => (int) ($stats->firstWhere('name', 'pointsFor')['value'] ?? 0),
                'goals_against' => (int) ($stats->firstWhere('name', 'pointsAgainst')['value'] ?? 0),
                'goal_diff' => (int) ($stats->firstWhere('name', 'pointDifferential')['value'] ?? 0),
                'points' => (int) ($stats->firstWhere('name', 'points')['value'] ?? 0),
            ];
        }

        // Sort by rank
        usort($standings, fn($a, $b) => $a['rank'] <=> $b['rank']);

        return [
            'league' => $leagueInfo['name'],
            'country' => $leagueInfo['country'],
            'main' => $leagueInfo['main'],
            'order' => $leagueInfo['order'] ?? 2,
            'standings' => $standings,
        ];
    }

    public function getAllStandings(): array
    {
        $allStandings = [];

        foreach ($this->leagues as $code => $info) {
            $data = $this->getStandings($code);
            if ($data && !empty($data['standings'])) {
                $allStandings[] = $data;
            }
        }

        // Sort by order, then by country
        usort($allStandings, function ($a, $b) {
            if ($a['order'] !== $b['order']) {
                return $a['order'] <=> $b['order'];
            }
            return $a['country'] <=> $b['country'];
        });

        return $allStandings;
    }

    public function getAvailableLeagues(): array
    {
        return $this->leagues;
    }

    public function clearCache(?string $leagueCode = null): void
    {
        if ($leagueCode) {
            Cache::forget("espn_football_{$leagueCode}");
        } else {
            foreach (array_keys($this->leagues) as $code) {
                Cache::forget("espn_football_{$code}");
            }
        }
    }
}
