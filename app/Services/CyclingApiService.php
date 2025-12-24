<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CyclingApiService
{
    private string $sportsDbUrl = 'https://www.thesportsdb.com/api/v1/json/3';
    private string $tourDataUrl = 'https://raw.githubusercontent.com/thomascamminady/LeTourDataSet/master/data';
    private string $localDataPath;
    private int $cacheMinutes = 60;

    // UCI World Tour league ID in TheSportsDB
    private string $uciWorldTourId = '4465';

    public function __construct()
    {
        $this->localDataPath = storage_path('app/cycling');
    }

    /**
     * Get UCI World Tour calendar/events for a season
     */
    public function getWorldTourCalendar(int $year = 2025): ?array
    {
        $cacheKey = "cycling_worldtour_calendar_{$year}";

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($year) {
            try {
                $response = Http::timeout(30)
                    ->get("{$this->sportsDbUrl}/eventsseason.php", [
                        'id' => $this->uciWorldTourId,
                        's' => $year,
                    ]);

                if ($response->successful()) {
                    return $this->parseCalendar($response->json(), $year);
                }

                Log::error("TheSportsDB cycling calendar error: " . $response->status());
                return null;
            } catch (\Exception $e) {
                Log::error("TheSportsDB cycling calendar exception: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Parse calendar events into structured format
     */
    private function parseCalendar(array $data, int $year): array
    {
        $events = $data['events'] ?? [];
        $races = [];
        $currentRace = null;

        foreach ($events as $event) {
            $eventName = $event['strEvent'] ?? '';
            $date = $event['dateEvent'] ?? '';
            $country = $event['strCountry'] ?? '';
            $status = $event['strStatus'] ?? '';
            $video = $event['strVideo'] ?? '';
            $thumb = $event['strThumb'] ?? '';

            // Group stages by race name
            $raceName = $this->extractRaceName($eventName);

            if (!isset($races[$raceName])) {
                $races[$raceName] = [
                    'name' => $raceName,
                    'country' => $country,
                    'stages' => [],
                    'start_date' => $date,
                    'end_date' => $date,
                    'thumb' => $thumb,
                ];
            }

            // Update end date
            if ($date > $races[$raceName]['end_date']) {
                $races[$raceName]['end_date'] = $date;
            }

            $races[$raceName]['stages'][] = [
                'name' => $eventName,
                'date' => $date,
                'status' => $status,
                'video' => $video,
            ];
        }

        // Sort races by start date
        uasort($races, fn($a, $b) => $a['start_date'] <=> $b['start_date']);

        return [
            'year' => $year,
            'competition' => 'UCI World Tour',
            'races' => array_values($races),
            'total_events' => count($events),
        ];
    }

    /**
     * Extract race name from event name (remove "Stage X" suffix)
     */
    private function extractRaceName(string $eventName): string
    {
        // Remove "Stage X" or "Etapa X" or similar
        $name = preg_replace('/\s*(Stage|Etapa|Etape|Tappa)\s*\d+\s*$/i', '', $eventName);
        // Remove "Santos " prefix
        $name = preg_replace('/^Santos\s+/i', '', $name);
        return trim($name);
    }

    /**
     * Get Tour de France general classification
     */
    public function getTourDeFranceGC(int $year = 2025): ?array
    {
        $cacheKey = "cycling_tdf_gc_{$year}";

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($year) {
            try {
                $response = Http::timeout(30)
                    ->get("{$this->tourDataUrl}/men/TDF_Riders_History.csv");

                if ($response->successful()) {
                    return $this->parseTdfGC($response->body(), $year);
                }

                Log::error("LeTourDataSet GC error: " . $response->status());
                return null;
            } catch (\Exception $e) {
                Log::error("LeTourDataSet GC exception: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Parse Tour de France general classification CSV
     */
    private function parseTdfGC(string $csv, int $year): array
    {
        $lines = explode("\n", $csv);
        $headers = str_getcsv(array_shift($lines));

        $riders = [];
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;

            $data = str_getcsv($line);
            if (count($data) < count($headers)) continue;

            $row = array_combine($headers, $data);

            // Filter by year
            if ((int)($row['Year'] ?? 0) !== $year) continue;

            $riders[] = [
                'rank' => (int)($row['Rank'] ?? 0),
                'rider' => $this->formatRiderName($row['Rider'] ?? ''),
                'team' => $row['Team'] ?? '',
                'time' => $row['Times'] ?? '',
                'gap' => $row['Gap'] ?? '',
                'bonus' => $row['B'] ?? '',
            ];
        }

        // Sort by rank
        usort($riders, fn($a, $b) => $a['rank'] <=> $b['rank']);

        return [
            'year' => $year,
            'competition' => 'Tour de France',
            'type' => 'General Classification',
            'standings' => $riders,
        ];
    }

    /**
     * Get Tour de France stages results
     */
    public function getTourDeFranceStages(int $year = 2025): ?array
    {
        $cacheKey = "cycling_tdf_stages_{$year}";

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($year) {
            try {
                $response = Http::timeout(30)
                    ->get("{$this->tourDataUrl}/men/TDF_Stages_History.csv");

                if ($response->successful()) {
                    return $this->parseTdfStages($response->body(), $year);
                }

                Log::error("LeTourDataSet stages error: " . $response->status());
                return null;
            } catch (\Exception $e) {
                Log::error("LeTourDataSet stages exception: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Parse Tour de France stages CSV
     */
    private function parseTdfStages(string $csv, int $year): array
    {
        $lines = explode("\n", $csv);
        $headers = str_getcsv(array_shift($lines));

        $stages = [];
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;

            $data = str_getcsv($line);
            if (count($data) < count($headers)) continue;

            $row = array_combine($headers, $data);

            // Filter by year
            if ((int)($row['Year'] ?? 0) !== $year) continue;

            $stageNum = (int)($row['Stages'] ?? 0);
            if ($stageNum === 0) continue;

            $stages[] = [
                'stage' => $stageNum,
                'start' => $row['Start'] ?? '',
                'end' => $row['End'] ?? '',
                'winner' => $this->formatRiderName($row['Winner of stage'] ?? ''),
                'yellow_jersey' => $this->formatRiderName($row['Yellow Jersey'] ?? ''),
                'green_jersey' => $this->formatRiderName($row['Green jersey'] ?? ''),
                'polka_jersey' => $this->formatRiderName($row['Polka-dot jersey'] ?? ''),
                'white_jersey' => $this->formatRiderName($row['White jersey'] ?? ''),
            ];
        }

        // Sort by stage number
        usort($stages, fn($a, $b) => $a['stage'] <=> $b['stage']);

        $distance = 0;
        if (!empty($stages)) {
            // Get distance from first row
            foreach (explode("\n", $csv) as $line) {
                if (strpos($line, (string)$year) !== false) {
                    $data = str_getcsv($line);
                    $distance = (int)($data[1] ?? 0);
                    break;
                }
            }
        }

        return [
            'year' => $year,
            'competition' => 'Tour de France',
            'distance_km' => $distance,
            'total_stages' => count($stages),
            'stages' => $stages,
        ];
    }

    /**
     * Format rider name (remove team suffix in parentheses, normalize case)
     */
    private function formatRiderName(string $name): string
    {
        // Remove team info in parentheses
        $name = preg_replace('/\s*\([^)]+\)\s*/', '', $name);
        // Convert from ALL CAPS to Title Case
        $name = mb_convert_case(mb_strtolower(trim($name)), MB_CASE_TITLE, 'UTF-8');
        return $name;
    }

    /**
     * Get all Tour de France data for a year
     */
    public function getTourDeFrance(int $year = 2025): array
    {
        return [
            'gc' => $this->getTourDeFranceGC($year),
            'stages' => $this->getTourDeFranceStages($year),
        ];
    }

    /**
     * Get Giro d'Italia general classification from local JSON
     */
    public function getGiroGC(int $year = 2025): ?array
    {
        $cacheKey = "cycling_giro_gc_{$year}";

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($year) {
            try {
                $filePath = "{$this->localDataPath}/giro_gc.json";

                if (!file_exists($filePath)) {
                    Log::warning("Giro GC data file not found: {$filePath}");
                    return null;
                }

                $data = json_decode(file_get_contents($filePath), true);

                if (!isset($data[(string)$year])) {
                    Log::info("No Giro GC data for year {$year}");
                    return null;
                }

                $yearData = $data[(string)$year];

                return [
                    'year' => $yearData['year'],
                    'competition' => $yearData['competition'],
                    'type' => 'General Classification',
                    'standings' => array_map(fn($r) => [
                        'rank' => $r['rank'],
                        'rider' => $r['rider'],
                        'team' => $r['team'],
                        'time' => $r['time'] ?? '',
                        'gap' => $r['gap'] ?? '',
                    ], $yearData['standings']),
                ];
            } catch (\Exception $e) {
                Log::error("Giro GC exception: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Get Vuelta a España general classification from local JSON
     */
    public function getVueltaGC(int $year = 2025): ?array
    {
        $cacheKey = "cycling_vuelta_gc_{$year}";

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($year) {
            try {
                $filePath = "{$this->localDataPath}/vuelta_gc.json";

                if (!file_exists($filePath)) {
                    Log::warning("Vuelta GC data file not found: {$filePath}");
                    return null;
                }

                $data = json_decode(file_get_contents($filePath), true);

                if (!isset($data[(string)$year])) {
                    Log::info("No Vuelta GC data for year {$year}");
                    return null;
                }

                $yearData = $data[(string)$year];

                return [
                    'year' => $yearData['year'],
                    'competition' => $yearData['competition'],
                    'type' => 'General Classification',
                    'standings' => array_map(fn($r) => [
                        'rank' => $r['rank'],
                        'rider' => $r['rider'],
                        'team' => $r['team'],
                        'time' => $r['time'] ?? '',
                        'gap' => $r['gap'] ?? '',
                    ], $yearData['standings']),
                ];
            } catch (\Exception $e) {
                Log::error("Vuelta GC exception: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Get all Grand Tours GC for a year
     */
    public function getAllGrandToursGC(int $year = 2025): array
    {
        return [
            'giro' => $this->getGiroGC($year),
            'tour' => $this->getTourDeFranceGC($year),
            'vuelta' => $this->getVueltaGC($year),
        ];
    }

    /**
     * Get Giro d'Italia stages from local JSON
     */
    public function getGiroStages(int $year = 2025): ?array
    {
        $cacheKey = "cycling_giro_stages_{$year}";

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($year) {
            try {
                $filePath = "{$this->localDataPath}/giro_stages.json";

                if (!file_exists($filePath)) {
                    Log::warning("Giro stages data file not found: {$filePath}");
                    return null;
                }

                $data = json_decode(file_get_contents($filePath), true);

                if (!isset($data[(string)$year])) {
                    Log::info("No Giro stages data for year {$year}");
                    return null;
                }

                $yearData = $data[(string)$year];

                return [
                    'year' => $yearData['year'],
                    'competition' => $yearData['competition'],
                    'distance_km' => $yearData['distance_km'] ?? 0,
                    'total_stages' => $yearData['total_stages'] ?? count($yearData['stages']),
                    'stages' => array_map(fn($s) => [
                        'stage' => $s['stage'],
                        'start' => $s['start'] ?? '',
                        'end' => $s['end'] ?? '',
                        'winner' => $s['winner'] ?? '',
                        'pink_jersey' => $s['pink_jersey'] ?? '',
                        'type' => $s['type'] ?? 'road',
                    ], $yearData['stages']),
                ];
            } catch (\Exception $e) {
                Log::error("Giro stages exception: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Get Vuelta a España stages from local JSON
     */
    public function getVueltaStages(int $year = 2025): ?array
    {
        $cacheKey = "cycling_vuelta_stages_{$year}";

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($year) {
            try {
                $filePath = "{$this->localDataPath}/vuelta_stages.json";

                if (!file_exists($filePath)) {
                    Log::warning("Vuelta stages data file not found: {$filePath}");
                    return null;
                }

                $data = json_decode(file_get_contents($filePath), true);

                if (!isset($data[(string)$year])) {
                    Log::info("No Vuelta stages data for year {$year}");
                    return null;
                }

                $yearData = $data[(string)$year];

                return [
                    'year' => $yearData['year'],
                    'competition' => $yearData['competition'],
                    'distance_km' => $yearData['distance_km'] ?? 0,
                    'total_stages' => $yearData['total_stages'] ?? count($yearData['stages']),
                    'stages' => array_map(fn($s) => [
                        'stage' => $s['stage'],
                        'start' => $s['start'] ?? '',
                        'end' => $s['end'] ?? '',
                        'winner' => $s['winner'] ?? '',
                        'red_jersey' => $s['red_jersey'] ?? '',
                        'type' => $s['type'] ?? 'road',
                    ], $yearData['stages']),
                ];
            } catch (\Exception $e) {
                Log::error("Vuelta stages exception: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Get cycling standings for home page display
     */
    public function getStandingsForHome(int $year = 2025): array
    {
        $standings = [];

        // Tour de France GC
        $tdfGC = $this->getTourDeFranceGC($year);
        if ($tdfGC && !empty($tdfGC['standings'])) {
            $standings[] = [
                'competition' => 'Tour de France',
                'year' => $year,
                'type' => 'gc',
                'standings' => $tdfGC['standings'],
            ];
        }

        // Tour de France Stages (last 5)
        $tdfStages = $this->getTourDeFranceStages($year);
        if ($tdfStages && !empty($tdfStages['stages'])) {
            $standings[] = [
                'competition' => 'Tour de France - Stages',
                'year' => $year,
                'type' => 'stages',
                'distance_km' => $tdfStages['distance_km'],
                'stages' => array_slice($tdfStages['stages'], -10),
            ];
        }

        return $standings;
    }

    /**
     * Get available years for Tour de France data
     */
    public function getAvailableYears(): array
    {
        return range(date('Y'), 2010, -1);
    }

    /**
     * Get all classics/monuments results
     */
    public function getClassics(int $year = 2025): ?array
    {
        $cacheKey = "cycling_classics_{$year}";

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($year) {
            try {
                $filePath = "{$this->localDataPath}/classics.json";

                if (!file_exists($filePath)) {
                    Log::warning("Classics data file not found: {$filePath}");
                    return null;
                }

                $data = json_decode(file_get_contents($filePath), true);
                $result = [
                    'year' => $year,
                    'monuments' => [],
                    'other_classics' => [],
                ];

                // Process monuments
                foreach ($data['monuments'] ?? [] as $key => $race) {
                    if (isset($race['results'][(string)$year])) {
                        $result['monuments'][] = [
                            'id' => $key,
                            'name' => $race['name'],
                            'nickname' => $race['nickname'],
                            'country' => $race['country'],
                            'distance_km' => $race['distance_km'],
                            'date' => $race['results'][(string)$year]['date'],
                            'podium' => $race['results'][(string)$year]['podium'],
                        ];
                    }
                }

                // Process other classics
                foreach ($data['other_classics'] ?? [] as $key => $race) {
                    if (isset($race['results'][(string)$year])) {
                        $result['other_classics'][] = [
                            'id' => $key,
                            'name' => $race['name'],
                            'nickname' => $race['nickname'],
                            'country' => $race['country'],
                            'distance_km' => $race['distance_km'],
                            'date' => $race['results'][(string)$year]['date'],
                            'podium' => $race['results'][(string)$year]['podium'],
                        ];
                    }
                }

                return $result;
            } catch (\Exception $e) {
                Log::error("Classics exception: " . $e->getMessage());
                return null;
            }
        });
    }

    /**
     * Get monuments only
     */
    public function getMonuments(int $year = 2025): ?array
    {
        $classics = $this->getClassics($year);
        if (!$classics) return null;

        return [
            'year' => $year,
            'type' => 'Monuments',
            'races' => $classics['monuments'],
        ];
    }

    /**
     * Get a specific classic result
     */
    public function getClassicResult(string $raceId, int $year = 2025): ?array
    {
        try {
            $filePath = "{$this->localDataPath}/classics.json";

            if (!file_exists($filePath)) {
                return null;
            }

            $data = json_decode(file_get_contents($filePath), true);

            // Check monuments
            if (isset($data['monuments'][$raceId]['results'][(string)$year])) {
                $race = $data['monuments'][$raceId];
                return [
                    'name' => $race['name'],
                    'nickname' => $race['nickname'],
                    'country' => $race['country'],
                    'distance_km' => $race['distance_km'],
                    'year' => $year,
                    'date' => $race['results'][(string)$year]['date'],
                    'podium' => $race['results'][(string)$year]['podium'],
                ];
            }

            // Check other classics
            if (isset($data['other_classics'][$raceId]['results'][(string)$year])) {
                $race = $data['other_classics'][$raceId];
                return [
                    'name' => $race['name'],
                    'nickname' => $race['nickname'],
                    'country' => $race['country'],
                    'distance_km' => $race['distance_km'],
                    'year' => $year,
                    'date' => $race['results'][(string)$year]['date'],
                    'podium' => $race['results'][(string)$year]['podium'],
                ];
            }

            return null;
        } catch (\Exception $e) {
            Log::error("Classic result exception: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Clear cache
     */
    public function clearCache(?int $year = null): void
    {
        if ($year) {
            Cache::forget("cycling_worldtour_calendar_{$year}");
            Cache::forget("cycling_tdf_gc_{$year}");
            Cache::forget("cycling_tdf_stages_{$year}");
            Cache::forget("cycling_giro_gc_{$year}");
            Cache::forget("cycling_giro_stages_{$year}");
            Cache::forget("cycling_vuelta_gc_{$year}");
            Cache::forget("cycling_vuelta_stages_{$year}");
            Cache::forget("cycling_classics_{$year}");
        } else {
            foreach ($this->getAvailableYears() as $y) {
                Cache::forget("cycling_worldtour_calendar_{$y}");
                Cache::forget("cycling_tdf_gc_{$y}");
                Cache::forget("cycling_tdf_stages_{$y}");
                Cache::forget("cycling_giro_gc_{$y}");
                Cache::forget("cycling_giro_stages_{$y}");
                Cache::forget("cycling_vuelta_gc_{$y}");
                Cache::forget("cycling_vuelta_stages_{$y}");
                Cache::forget("cycling_classics_{$y}");
            }
        }
    }
}
