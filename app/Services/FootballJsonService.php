<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FootballJsonService
{
    protected string $baseUrl = 'https://raw.githubusercontent.com/openfootball/football.json/master';

    /**
     * Lligues disponibles amb els seus codis i noms.
     */
    public const LEAGUES = [
        'es.1' => ['name' => 'La Liga', 'country' => 'Spain'],
        'es.2' => ['name' => 'Segunda División', 'country' => 'Spain'],
        'en.1' => ['name' => 'Premier League', 'country' => 'England'],
        'en.2' => ['name' => 'Championship', 'country' => 'England'],
        'de.1' => ['name' => 'Bundesliga', 'country' => 'Germany'],
        'de.2' => ['name' => '2. Bundesliga', 'country' => 'Germany'],
        'it.1' => ['name' => 'Serie A', 'country' => 'Italy'],
        'it.2' => ['name' => 'Serie B', 'country' => 'Italy'],
        'fr.1' => ['name' => 'Ligue 1', 'country' => 'France'],
        'fr.2' => ['name' => 'Ligue 2', 'country' => 'France'],
        'pt.1' => ['name' => 'Primeira Liga', 'country' => 'Portugal'],
        'nl.1' => ['name' => 'Eredivisie', 'country' => 'Netherlands'],
        'be.1' => ['name' => 'First Division A', 'country' => 'Belgium'],
        'at.1' => ['name' => 'Bundesliga', 'country' => 'Austria'],
        'ch.1' => ['name' => 'Super League', 'country' => 'Switzerland'],
    ];

    /**
     * Temporades disponibles.
     */
    public const SEASONS = [
        '2025-26',
        '2024-25',
        '2023-24',
        '2022-23',
        '2021-22',
        '2020-21',
    ];

    /**
     * Obté les dades d'una lliga i temporada.
     */
    public function getLeagueData(string $leagueCode, string $season): ?array
    {
        $cacheKey = "football_json_{$leagueCode}_{$season}";

        return Cache::remember($cacheKey, 3600, function () use ($leagueCode, $season) {
            try {
                $url = "{$this->baseUrl}/{$season}/{$leagueCode}.json";

                $response = Http::timeout(30)->get($url);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::warning("Failed to fetch football.json data", [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error("Exception fetching football.json data", [
                    'league' => $leagueCode,
                    'season' => $season,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        });
    }

    /**
     * Obté els equips d'una lliga.
     */
    public function getTeams(string $leagueCode, string $season): array
    {
        $data = $this->getLeagueData($leagueCode, $season);

        if (!$data || !isset($data['matches'])) {
            return [];
        }

        $teams = [];

        foreach ($data['matches'] as $match) {
            if (!empty($match['team1']) && !in_array($match['team1'], $teams)) {
                $teams[] = $match['team1'];
            }
            if (!empty($match['team2']) && !in_array($match['team2'], $teams)) {
                $teams[] = $match['team2'];
            }
        }

        sort($teams);

        return $teams;
    }

    /**
     * Obté els partits d'una lliga.
     */
    public function getMatches(string $leagueCode, string $season): array
    {
        $data = $this->getLeagueData($leagueCode, $season);

        if (!$data || !isset($data['matches'])) {
            return [];
        }

        return $data['matches'];
    }

    /**
     * Calcula la classificació a partir dels partits.
     */
    public function calculateStandings(string $leagueCode, string $season): array
    {
        $matches = $this->getMatches($leagueCode, $season);

        if (empty($matches)) {
            return [];
        }

        $standings = [];

        foreach ($matches as $match) {
            // Només processar partits amb resultat
            if (empty($match['score']) || !isset($match['score']['ft'])) {
                continue;
            }

            $team1 = $match['team1'];
            $team2 = $match['team2'];
            $score = $match['score']['ft'];
            $goalsHome = $score[0];
            $goalsAway = $score[1];

            // Inicialitzar equips si no existeixen
            foreach ([$team1, $team2] as $team) {
                if (!isset($standings[$team])) {
                    $standings[$team] = [
                        'team' => $team,
                        'played' => 0,
                        'won' => 0,
                        'drawn' => 0,
                        'lost' => 0,
                        'goals_for' => 0,
                        'goals_against' => 0,
                        'goal_diff' => 0,
                        'points' => 0,
                    ];
                }
            }

            // Actualitzar estadístiques
            $standings[$team1]['played']++;
            $standings[$team2]['played']++;
            $standings[$team1]['goals_for'] += $goalsHome;
            $standings[$team1]['goals_against'] += $goalsAway;
            $standings[$team2]['goals_for'] += $goalsAway;
            $standings[$team2]['goals_against'] += $goalsHome;

            if ($goalsHome > $goalsAway) {
                // Victòria local
                $standings[$team1]['won']++;
                $standings[$team1]['points'] += 3;
                $standings[$team2]['lost']++;
            } elseif ($goalsHome < $goalsAway) {
                // Victòria visitant
                $standings[$team2]['won']++;
                $standings[$team2]['points'] += 3;
                $standings[$team1]['lost']++;
            } else {
                // Empat
                $standings[$team1]['drawn']++;
                $standings[$team2]['drawn']++;
                $standings[$team1]['points']++;
                $standings[$team2]['points']++;
            }
        }

        // Calcular diferència de gols
        foreach ($standings as &$team) {
            $team['goal_diff'] = $team['goals_for'] - $team['goals_against'];
        }

        // Ordenar per punts, diferència de gols, gols a favor
        usort($standings, function ($a, $b) {
            if ($a['points'] !== $b['points']) {
                return $b['points'] - $a['points'];
            }
            if ($a['goal_diff'] !== $b['goal_diff']) {
                return $b['goal_diff'] - $a['goal_diff'];
            }
            return $b['goals_for'] - $a['goals_for'];
        });

        // Afegir posició
        $rank = 1;
        foreach ($standings as &$team) {
            $team['rank'] = $rank++;
        }

        return $standings;
    }

    /**
     * Obté la llista de lligues disponibles.
     */
    public function getAvailableLeagues(): array
    {
        return self::LEAGUES;
    }

    /**
     * Obté la llista de temporades disponibles.
     */
    public function getAvailableSeasons(): array
    {
        return self::SEASONS;
    }

    /**
     * Converteix el codi de temporada a any (per a la BD).
     */
    public function seasonToYear(string $season): int
    {
        // "2024-25" -> 2024
        return (int) substr($season, 0, 4);
    }

    /**
     * Converteix l'any a codi de temporada.
     */
    public function yearToSeason(int $year): string
    {
        $nextYear = substr((string) ($year + 1), -2);
        return "{$year}-{$nextYear}";
    }

    /**
     * Neteja la cache d'una lliga/temporada.
     */
    public function clearCache(string $leagueCode, string $season): void
    {
        Cache::forget("football_json_{$leagueCode}_{$season}");
    }

    /**
     * Neteja tota la cache de football.json.
     */
    public function clearAllCache(): void
    {
        foreach (self::LEAGUES as $code => $info) {
            foreach (self::SEASONS as $season) {
                $this->clearCache($code, $season);
            }
        }
    }
}
