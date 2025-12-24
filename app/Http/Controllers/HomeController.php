<?php

namespace App\Http\Controllers;

use App\Services\EspnFootballService;
use App\Services\NbaApiService;
use App\Services\EspnNflService;
use App\Services\EspnF1Service;
use App\Services\EspnTennisService;
use App\Services\EuroleagueApiService;
use App\Services\CyclingApiService;

class HomeController extends Controller
{
    public function __construct(
        private NbaApiService $nbaService,
        private EspnFootballService $footballService,
        private EspnNflService $nflService,
        private EspnF1Service $f1Service,
        private EspnTennisService $tennisService,
        private EuroleagueApiService $euroleagueService,
        private CyclingApiService $cyclingService
    ) {}

    public function index()
    {
        $sports = [
            'football' => [
                'name' => 'Football',
                'seasons' => $this->getFootballSeasons(),
            ],
            'basketball' => [
                'name' => 'Basketball',
                'seasons' => $this->getBasketballSeasons(),
            ],
            'nfl' => [
                'name' => 'NFL',
                'seasons' => $this->getNflSeasons(),
            ],
            'f1' => [
                'name' => 'Formula 1',
                'seasons' => $this->getF1Seasons(),
            ],
            'tennis' => [
                'name' => 'Tennis',
                'seasons' => $this->getTennisSeasons(),
            ],
            'cycling' => [
                'name' => 'Cycling',
                'seasons' => $this->getCyclingSeasons(),
            ],
        ];

        return view('home', compact('sports'));
    }

    private function getFootballSeasons(): array
    {
        $allStandings = $this->footballService->getAllStandings();

        if (empty($allStandings)) {
            return [];
        }

        // ESPN returns current season only
        $year = now()->month >= 8 ? now()->year : now()->year - 1;

        $leagues = [];
        foreach ($allStandings as $leagueData) {
            $leagues[] = (object) [
                'league' => (object) [
                    'name' => $leagueData['league'],
                    'country' => (object) ['name' => $leagueData['country']],
                ],
                'standings' => collect($leagueData['standings'])->map(fn($t) => (object) [
                    'rank' => $t['rank'],
                    'team' => (object) ['name' => $t['team_name']],
                    'played' => $t['played'],
                    'won' => $t['won'],
                    'drawn' => $t['drawn'],
                    'lost' => $t['lost'],
                    'goal_diff' => $t['goal_diff'],
                    'points' => $t['points'],
                ]),
            ];
        }

        return [$year => $leagues];
    }

    private function getBasketballSeasons(): array
    {
        $leagues = [];

        // NBA standings
        $nbaStandings = $this->nbaService->getStandings();
        if ($nbaStandings && (count($nbaStandings['eastern'] ?? []) > 0 || count($nbaStandings['western'] ?? []) > 0)) {
            $leagues[] = (object) [
                'league' => (object) [
                    'name' => 'NBA Eastern',
                    'country' => (object) ['name' => 'USA'],
                ],
                'standings' => collect($nbaStandings['eastern'] ?? [])->values()->map(fn($t, $i) => (object) [
                    'rank' => $t['rank'] ?? $i + 1,
                    'team' => (object) ['name' => ($t['team_city'] ?? '') . ' ' . ($t['team_name'] ?? '')],
                    'played' => ($t['wins'] ?? 0) + ($t['losses'] ?? 0),
                    'won' => $t['wins'] ?? 0,
                    'drawn' => 0,
                    'lost' => $t['losses'] ?? 0,
                    'goal_diff' => 0,
                    'points' => $t['wins'] ?? 0,
                    'win_pct' => $t['win_pct'] ?? 0,
                ]),
            ];
            $leagues[] = (object) [
                'league' => (object) [
                    'name' => 'NBA Western',
                    'country' => (object) ['name' => 'USA'],
                ],
                'standings' => collect($nbaStandings['western'] ?? [])->values()->map(fn($t, $i) => (object) [
                    'rank' => $t['rank'] ?? $i + 1,
                    'team' => (object) ['name' => ($t['team_city'] ?? '') . ' ' . ($t['team_name'] ?? '')],
                    'played' => ($t['wins'] ?? 0) + ($t['losses'] ?? 0),
                    'won' => $t['wins'] ?? 0,
                    'drawn' => 0,
                    'lost' => $t['losses'] ?? 0,
                    'goal_diff' => 0,
                    'points' => $t['wins'] ?? 0,
                    'win_pct' => $t['win_pct'] ?? 0,
                ]),
            ];
        }

        // Euroleague standings
        $euroleagueStandings = $this->euroleagueService->getStandings('euroleague', 'E2024');
        if ($euroleagueStandings && !empty($euroleagueStandings['standings'])) {
            $leagues[] = (object) [
                'league' => (object) [
                    'name' => 'Euroleague',
                    'country' => (object) ['name' => 'Europe'],
                ],
                'standings' => collect($euroleagueStandings['standings'])->map(fn($t) => (object) [
                    'rank' => $t['rank'],
                    'team' => (object) ['name' => $t['team_name']],
                    'played' => $t['played'],
                    'won' => $t['won'],
                    'drawn' => 0,
                    'lost' => $t['lost'],
                    'goal_diff' => $t['point_diff'],
                    'points' => $t['won'],
                    'win_pct' => $t['played'] > 0 ? $t['won'] / $t['played'] : 0,
                ]),
            ];
        }

        // Eurocup standings (has groups)
        $eurocupStandings = $this->euroleagueService->getStandings('eurocup', 'U2024');
        if ($eurocupStandings) {
            if (!empty($eurocupStandings['groups'])) {
                // Multiple groups
                foreach ($eurocupStandings['groups'] as $index => $group) {
                    $groupName = count($eurocupStandings['groups']) > 1 ? ' Group ' . chr(65 + $index) : '';
                    $leagues[] = (object) [
                        'league' => (object) [
                            'name' => 'Eurocup' . $groupName,
                            'country' => (object) ['name' => 'Europe'],
                        ],
                        'standings' => collect($group['teams'])->map(fn($t) => (object) [
                            'rank' => $t['rank'],
                            'team' => (object) ['name' => $t['team_name']],
                            'played' => $t['played'],
                            'won' => $t['won'],
                            'drawn' => 0,
                            'lost' => $t['lost'],
                            'goal_diff' => $t['point_diff'],
                            'points' => $t['won'],
                            'win_pct' => $t['played'] > 0 ? $t['won'] / $t['played'] : 0,
                        ]),
                    ];
                }
            } elseif (!empty($eurocupStandings['standings'])) {
                // Single group
                $leagues[] = (object) [
                    'league' => (object) [
                        'name' => 'Eurocup',
                        'country' => (object) ['name' => 'Europe'],
                    ],
                    'standings' => collect($eurocupStandings['standings'])->map(fn($t) => (object) [
                        'rank' => $t['rank'],
                        'team' => (object) ['name' => $t['team_name']],
                        'played' => $t['played'],
                        'won' => $t['won'],
                        'drawn' => 0,
                        'lost' => $t['lost'],
                        'goal_diff' => $t['point_diff'],
                        'points' => $t['won'],
                        'win_pct' => $t['played'] > 0 ? $t['won'] / $t['played'] : 0,
                    ]),
                ];
            }
        }

        if (empty($leagues)) {
            return [];
        }

        // Use current season year
        $year = (int) substr($this->nbaService->getCurrentSeason(), 0, 4);

        return [$year => $leagues];
    }

    private function getNflSeasons(): array
    {
        $standings = $this->nflService->getStandings();

        if (!$standings || (count($standings['afc'] ?? []) === 0 && count($standings['nfc'] ?? []) === 0)) {
            return [];
        }

        $year = (int) $this->nflService->getCurrentSeason();

        return [
            $year => [
                (object) [
                    'league' => (object) [
                        'name' => 'AFC',
                        'country' => (object) ['name' => 'USA'],
                    ],
                    'standings' => collect($standings['afc'] ?? [])->values()->map(fn($t, $i) => (object) [
                        'rank' => $t['rank'] ?? $i + 1,
                        'team' => (object) ['name' => ($t['team_city'] ?? '') . ' ' . ($t['team_name'] ?? '')],
                        'played' => ($t['wins'] ?? 0) + ($t['losses'] ?? 0) + ($t['ties'] ?? 0),
                        'won' => $t['wins'] ?? 0,
                        'drawn' => $t['ties'] ?? 0,
                        'lost' => $t['losses'] ?? 0,
                        'goal_diff' => $t['point_diff'] ?? 0,
                        'points' => $t['wins'] ?? 0,
                        'win_pct' => $t['win_pct'] ?? 0,
                    ]),
                ],
                (object) [
                    'league' => (object) [
                        'name' => 'NFC',
                        'country' => (object) ['name' => 'USA'],
                    ],
                    'standings' => collect($standings['nfc'] ?? [])->values()->map(fn($t, $i) => (object) [
                        'rank' => $t['rank'] ?? $i + 1,
                        'team' => (object) ['name' => ($t['team_city'] ?? '') . ' ' . ($t['team_name'] ?? '')],
                        'played' => ($t['wins'] ?? 0) + ($t['losses'] ?? 0) + ($t['ties'] ?? 0),
                        'won' => $t['wins'] ?? 0,
                        'drawn' => $t['ties'] ?? 0,
                        'lost' => $t['losses'] ?? 0,
                        'goal_diff' => $t['point_diff'] ?? 0,
                        'points' => $t['wins'] ?? 0,
                        'win_pct' => $t['win_pct'] ?? 0,
                    ]),
                ],
            ],
        ];
    }

    private function getF1Seasons(): array
    {
        $standings = $this->f1Service->getStandings();

        if (!$standings || (count($standings['drivers'] ?? []) === 0)) {
            return [];
        }

        $year = (int) $this->f1Service->getCurrentSeason();

        return [
            $year => [
                (object) [
                    'league' => (object) [
                        'name' => 'Drivers Championship',
                        'country' => (object) ['name' => 'World'],
                    ],
                    'standings' => collect($standings['drivers'] ?? [])->map(fn($d) => (object) [
                        'rank' => $d['rank'],
                        'team' => (object) ['name' => $d['name']],
                        'country' => $d['country'] ?? '',
                        'points' => $d['points'],
                    ]),
                    'type' => 'f1_drivers',
                ],
                (object) [
                    'league' => (object) [
                        'name' => 'Constructors Championship',
                        'country' => (object) ['name' => 'World'],
                    ],
                    'standings' => collect($standings['constructors'] ?? [])->map(fn($c) => (object) [
                        'rank' => $c['rank'],
                        'team' => (object) ['name' => $c['name']],
                        'points' => $c['points'],
                    ]),
                    'type' => 'f1_constructors',
                ],
            ],
        ];
    }

    private function getTennisSeasons(): array
    {
        $rankings = [];

        // ATP Rankings
        $atpRankings = $this->tennisService->getAtpRankings();
        if ($atpRankings && count($atpRankings['rankings'] ?? []) > 0) {
            $rankings[] = (object) [
                'league' => (object) [
                    'name' => 'ATP Rankings',
                    'country' => (object) ['name' => 'Men'],
                ],
                'standings' => collect($atpRankings['rankings'] ?? [])->take(50)->map(fn($p) => (object) [
                    'rank' => $p['rank'],
                    'team' => (object) ['name' => $p['name']],
                    'country' => $p['country'] ?? '',
                    'points' => $p['points'],
                    'previous_rank' => $p['previous_rank'] ?? $p['rank'],
                ]),
                'type' => 'tennis',
            ];
        }

        // WTA Rankings
        $wtaRankings = $this->tennisService->getWtaRankings();
        if ($wtaRankings && count($wtaRankings['rankings'] ?? []) > 0) {
            $rankings[] = (object) [
                'league' => (object) [
                    'name' => 'WTA Rankings',
                    'country' => (object) ['name' => 'Women'],
                ],
                'standings' => collect($wtaRankings['rankings'] ?? [])->take(50)->map(fn($p) => (object) [
                    'rank' => $p['rank'],
                    'team' => (object) ['name' => $p['name']],
                    'country' => $p['country'] ?? '',
                    'points' => $p['points'],
                    'previous_rank' => $p['previous_rank'] ?? $p['rank'],
                ]),
                'type' => 'tennis',
            ];
        }

        if (empty($rankings)) {
            return [];
        }

        $year = now()->year;

        return [$year => $rankings];
    }

    private function getCyclingSeasons(): array
    {
        $competitions = [];
        $year = now()->year;

        // Monuments (5 big classics) - First!
        $classics = $this->cyclingService->getClassics($year);
        if ($classics && !empty($classics['monuments'])) {
            $allMonuments = array_merge($classics['monuments'], $classics['other_classics'] ?? []);
            // Sort by date
            usort($allMonuments, fn($a, $b) => $a['date'] <=> $b['date']);

            $competitions[] = (object) [
                'league' => (object) [
                    'name' => 'Monuments & Classics',
                    'country' => (object) ['name' => count($allMonuments) . ' races'],
                ],
                'standings' => collect($allMonuments)->map(fn($race, $i) => (object) [
                    'rank' => $i + 1,
                    'race_name' => $race['name'],
                    'nickname' => $race['nickname'],
                    'date' => $race['date'],
                    'team' => (object) ['name' => $race['podium'][0]['rider'] ?? ''],
                    'country' => $race['podium'][0]['country'] ?? '',
                    'second' => $race['podium'][1]['rider'] ?? '',
                    'third' => $race['podium'][2]['rider'] ?? '',
                ]),
                'type' => 'cycling_classics',
            ];
        }

        // Giro d'Italia GC
        $giroGC = $this->cyclingService->getGiroGC($year);
        if ($giroGC && !empty($giroGC['standings'])) {
            $competitions[] = (object) [
                'league' => (object) [
                    'name' => 'Giro d\'Italia',
                    'country' => (object) ['name' => 'GC'],
                ],
                'standings' => collect($giroGC['standings'])->take(10)->map(fn($r) => (object) [
                    'rank' => $r['rank'],
                    'team' => (object) ['name' => $r['rider']],
                    'country' => $r['team'],
                    'points' => $r['time'],
                    'gap' => $r['gap'],
                ]),
                'type' => 'cycling_gc',
            ];
        }

        // Tour de France GC
        $tdfGC = $this->cyclingService->getTourDeFranceGC($year);
        if ($tdfGC && !empty($tdfGC['standings'])) {
            $competitions[] = (object) [
                'league' => (object) [
                    'name' => 'Tour de France',
                    'country' => (object) ['name' => 'GC'],
                ],
                'standings' => collect($tdfGC['standings'])->take(10)->map(fn($r) => (object) [
                    'rank' => $r['rank'],
                    'team' => (object) ['name' => $r['rider']],
                    'country' => $r['team'],
                    'points' => $r['time'],
                    'gap' => $r['gap'],
                ]),
                'type' => 'cycling_gc',
            ];
        }

        // Vuelta a España GC
        $vueltaGC = $this->cyclingService->getVueltaGC($year);
        if ($vueltaGC && !empty($vueltaGC['standings'])) {
            $competitions[] = (object) [
                'league' => (object) [
                    'name' => 'Vuelta a España',
                    'country' => (object) ['name' => 'GC'],
                ],
                'standings' => collect($vueltaGC['standings'])->take(10)->map(fn($r) => (object) [
                    'rank' => $r['rank'],
                    'team' => (object) ['name' => $r['rider']],
                    'country' => $r['team'],
                    'points' => $r['time'],
                    'gap' => $r['gap'],
                ]),
                'type' => 'cycling_gc',
            ];
        }

        // Giro d'Italia Stages
        $giroStages = $this->cyclingService->getGiroStages($year);
        if ($giroStages && !empty($giroStages['stages'])) {
            $competitions[] = (object) [
                'league' => (object) [
                    'name' => 'Giro d\'Italia - Stages',
                    'country' => (object) ['name' => $giroStages['distance_km'] . ' km'],
                ],
                'standings' => collect($giroStages['stages'])->map(fn($s) => (object) [
                    'rank' => $s['stage'],
                    'team' => (object) ['name' => $s['winner']],
                    'route' => $s['start'] . ' → ' . $s['end'],
                    'yellow' => $s['pink_jersey'],
                ]),
                'type' => 'cycling_stages',
            ];
        }

        // Tour de France Stages
        $tdfStages = $this->cyclingService->getTourDeFranceStages($year);
        if ($tdfStages && !empty($tdfStages['stages'])) {
            $competitions[] = (object) [
                'league' => (object) [
                    'name' => 'Tour de France - Stages',
                    'country' => (object) ['name' => $tdfStages['distance_km'] . ' km'],
                ],
                'standings' => collect($tdfStages['stages'])->map(fn($s) => (object) [
                    'rank' => $s['stage'],
                    'team' => (object) ['name' => $s['winner']],
                    'route' => $s['start'] . ' → ' . $s['end'],
                    'yellow' => $s['yellow_jersey'],
                ]),
                'type' => 'cycling_stages',
            ];
        }

        // Vuelta a España Stages
        $vueltaStages = $this->cyclingService->getVueltaStages($year);
        if ($vueltaStages && !empty($vueltaStages['stages'])) {
            $competitions[] = (object) [
                'league' => (object) [
                    'name' => 'Vuelta a España - Stages',
                    'country' => (object) ['name' => $vueltaStages['distance_km'] . ' km'],
                ],
                'standings' => collect($vueltaStages['stages'])->map(fn($s) => (object) [
                    'rank' => $s['stage'],
                    'team' => (object) ['name' => $s['winner']],
                    'route' => $s['start'] . ' → ' . $s['end'],
                    'yellow' => $s['red_jersey'],
                ]),
                'type' => 'cycling_stages',
            ];
        }

        if (empty($competitions)) {
            return [];
        }

        return [$year => $competitions];
    }
}
