<?php

namespace App\Http\Controllers;

use App\Services\CyclingApiService;

class HomeController extends Controller
{
    public function __construct(
        private CyclingApiService $cyclingService
    ) {}

    public function index()
    {
        $competitions = [];
        $year = now()->year;

        // Monuments & Classics
        $classics = $this->cyclingService->getClassics($year);
        if ($classics && !empty($classics['monuments'])) {
            $allMonuments = array_merge($classics['monuments'], $classics['other_classics'] ?? []);
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

        // Giro Stages
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

        // Tour Stages
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

        // Vuelta Stages
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

        // Cyclocross World Championships
        $cxWorlds = $this->cyclingService->getCyclocrossWorldChampionships($year);
        if ($cxWorlds) {
            $competitions[] = (object) [
                'league' => (object) [
                    'name' => 'CX World Championships',
                    'country' => (object) ['name' => $cxWorlds['location']],
                ],
                'standings' => collect([
                    ['cat' => 'Men Elite', 'data' => $cxWorlds['men_elite']['podium'] ?? []],
                    ['cat' => 'Women Elite', 'data' => $cxWorlds['women_elite']['podium'] ?? []],
                ])->flatMap(fn($cat) => collect($cat['data'])->map(fn($r) => (object) [
                    'rank' => $r['rank'],
                    'team' => (object) ['name' => $r['rider']],
                    'country' => $r['country'],
                    'category' => $cat['cat'],
                ])),
                'type' => 'cx_worlds',
            ];
        }

        // Cyclocross World Cup
        $cxWorldCup = $this->cyclingService->getCyclocrossWorldCup('2024-2025');
        if ($cxWorldCup && !empty($cxWorldCup['men_elite']['standings'])) {
            $competitions[] = (object) [
                'league' => (object) [
                    'name' => 'CX World Cup - Men',
                    'country' => (object) ['name' => '2024-25'],
                ],
                'standings' => collect($cxWorldCup['men_elite']['standings'])->take(10)->map(fn($r) => (object) [
                    'rank' => $r['rank'],
                    'team' => (object) ['name' => $r['rider']],
                    'country' => $r['country'],
                    'points' => $r['points'],
                ]),
                'type' => 'cx_standings',
            ];
        }

        if ($cxWorldCup && !empty($cxWorldCup['women_elite']['standings'])) {
            $competitions[] = (object) [
                'league' => (object) [
                    'name' => 'CX World Cup - Women',
                    'country' => (object) ['name' => '2024-25'],
                ],
                'standings' => collect($cxWorldCup['women_elite']['standings'])->take(10)->map(fn($r) => (object) [
                    'rank' => $r['rank'],
                    'team' => (object) ['name' => $r['rider']],
                    'country' => $r['country'],
                    'points' => $r['points'],
                ]),
                'type' => 'cx_standings',
            ];
        }

        // MTB XCO World Cup
        $mtbXco = $this->cyclingService->getMtbXcoWorldCup(2024);
        if ($mtbXco && !empty($mtbXco['men_elite']['standings'])) {
            $competitions[] = (object) [
                'league' => (object) [
                    'name' => 'MTB XCO World Cup - Men',
                    'country' => (object) ['name' => '2024'],
                ],
                'standings' => collect($mtbXco['men_elite']['standings'])->take(10)->map(fn($r) => (object) [
                    'rank' => $r['rank'],
                    'team' => (object) ['name' => $r['rider']],
                    'country' => $r['country'],
                    'points' => $r['points'],
                ]),
                'type' => 'mtb_standings',
            ];
        }

        if ($mtbXco && !empty($mtbXco['women_elite']['standings'])) {
            $competitions[] = (object) [
                'league' => (object) [
                    'name' => 'MTB XCO World Cup - Women',
                    'country' => (object) ['name' => '2024'],
                ],
                'standings' => collect($mtbXco['women_elite']['standings'])->take(10)->map(fn($r) => (object) [
                    'rank' => $r['rank'],
                    'team' => (object) ['name' => $r['rider']],
                    'country' => $r['country'],
                    'points' => $r['points'],
                ]),
                'type' => 'mtb_standings',
            ];
        }

        // MTB Downhill World Cup
        $mtbDh = $this->cyclingService->getMtbDownhillWorldCup(2024);
        if ($mtbDh && !empty($mtbDh['men_elite']['standings'])) {
            $competitions[] = (object) [
                'league' => (object) [
                    'name' => 'MTB Downhill World Cup - Men',
                    'country' => (object) ['name' => '2024'],
                ],
                'standings' => collect($mtbDh['men_elite']['standings'])->take(10)->map(fn($r) => (object) [
                    'rank' => $r['rank'],
                    'team' => (object) ['name' => $r['rider']],
                    'country' => $r['country'],
                    'points' => $r['points'],
                ]),
                'type' => 'mtb_standings',
            ];
        }

        if ($mtbDh && !empty($mtbDh['women_elite']['standings'])) {
            $competitions[] = (object) [
                'league' => (object) [
                    'name' => 'MTB Downhill World Cup - Women',
                    'country' => (object) ['name' => '2024'],
                ],
                'standings' => collect($mtbDh['women_elite']['standings'])->take(10)->map(fn($r) => (object) [
                    'rank' => $r['rank'],
                    'team' => (object) ['name' => $r['rider']],
                    'country' => $r['country'],
                    'points' => $r['points'],
                ]),
                'type' => 'mtb_standings',
            ];
        }

        return view('home', [
            'competitions' => $competitions,
            'year' => $year,
        ]);
    }
}
