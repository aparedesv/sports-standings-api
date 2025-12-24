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
        $year = now()->year;
        $data = [
            'year' => $year,
            'classics' => [],
            'grandTours' => [],
            'cyclocross' => [],
            'mtb' => [],
        ];

        // === CLASSICS (8 races with full podium, men + women) ===
        $classics = $this->cyclingService->getClassics($year);
        if ($classics && !empty($classics['monuments'])) {
            $allRaces = array_merge($classics['monuments'], $classics['other_classics'] ?? []);
            usort($allRaces, fn($a, $b) => $a['date'] <=> $b['date']);

            $data['classics'] = collect($allRaces)->map(fn($race) => [
                'name' => $race['name'],
                'men' => collect($race['men'] ?? [])->take(3)->map(fn($r) => [
                    'rider' => $r['rider'] ?? '-',
                    'country' => $r['country'] ?? '',
                ])->toArray(),
                'women' => $race['women'] ? collect($race['women'])->take(3)->map(fn($r) => [
                    'rider' => $r['rider'] ?? '-',
                    'country' => $r['country'] ?? '',
                ])->toArray() : null,
            ])->toArray();
        }

        // === GRAND TOURS (top 10 with gaps, men + women) ===
        $giroGC = $this->cyclingService->getGiroGC($year);
        if ($giroGC && isset($giroGC['men'])) {
            $data['grandTours']['giro'] = [
                'name' => "Giro d'Italia",
                'men' => collect($giroGC['men']['standings'] ?? [])->take(10)->map(fn($r) => [
                    'rank' => $r['rank'],
                    'rider' => $r['rider'],
                    'gap' => $r['gap'] ?? '',
                ])->toArray(),
                'women' => isset($giroGC['women']) ? collect($giroGC['women']['standings'] ?? [])->take(10)->map(fn($r) => [
                    'rank' => $r['rank'],
                    'rider' => $r['rider'],
                    'gap' => $r['gap'] ?? '',
                ])->toArray() : null,
            ];
        }

        $tdfGC = $this->cyclingService->getTourDeFranceGC($year);
        if ($tdfGC && isset($tdfGC['men'])) {
            $data['grandTours']['tour'] = [
                'name' => 'Tour de France',
                'men' => collect($tdfGC['men']['standings'] ?? [])->take(10)->map(fn($r) => [
                    'rank' => $r['rank'],
                    'rider' => $r['rider'],
                    'gap' => $r['gap'] ?? '',
                ])->toArray(),
                'women' => isset($tdfGC['women']) ? collect($tdfGC['women']['standings'] ?? [])->take(10)->map(fn($r) => [
                    'rank' => $r['rank'],
                    'rider' => $r['rider'],
                    'gap' => $r['gap'] ?? '',
                ])->toArray() : null,
            ];
        }

        $vueltaGC = $this->cyclingService->getVueltaGC($year);
        if ($vueltaGC && isset($vueltaGC['men'])) {
            $data['grandTours']['vuelta'] = [
                'name' => 'Vuelta a España',
                'men' => collect($vueltaGC['men']['standings'] ?? [])->take(10)->map(fn($r) => [
                    'rank' => $r['rank'],
                    'rider' => $r['rider'],
                    'gap' => $r['gap'] ?? '',
                ])->toArray(),
                'women' => isset($vueltaGC['women']) ? collect($vueltaGC['women']['standings'] ?? [])->take(10)->map(fn($r) => [
                    'rank' => $r['rank'],
                    'rider' => $r['rider'],
                    'gap' => $r['gap'] ?? '',
                ])->toArray() : null,
            ];
        }

        // === CYCLOCROSS (expanded) ===
        $cxWorlds = $this->cyclingService->getCyclocrossWorldChampionships($year);
        if ($cxWorlds) {
            $data['cyclocross']['worlds'] = [
                'name' => 'World Championships',
                'location' => $cxWorlds['location'],
                'men' => collect($cxWorlds['men_elite']['podium'] ?? [])->take(10)->map(fn($r) => [
                    'rank' => $r['rank'],
                    'rider' => $r['rider'],
                    'country' => $r['country'],
                ])->toArray(),
                'women' => collect($cxWorlds['women_elite']['podium'] ?? [])->take(10)->map(fn($r) => [
                    'rank' => $r['rank'],
                    'rider' => $r['rider'],
                    'country' => $r['country'],
                ])->toArray(),
            ];
        }

        $cxWorldCup = $this->cyclingService->getCyclocrossWorldCup('2024-2025');
        if ($cxWorldCup) {
            $data['cyclocross']['worldcup'] = [
                'name' => 'World Cup 24-25',
                'men' => collect($cxWorldCup['men_elite']['standings'] ?? [])->take(10)->map(fn($r) => [
                    'rank' => $r['rank'],
                    'rider' => $r['rider'],
                    'country' => $r['country'],
                    'points' => $r['points'] ?? 0,
                ])->toArray(),
                'women' => collect($cxWorldCup['women_elite']['standings'] ?? [])->take(10)->map(fn($r) => [
                    'rank' => $r['rank'],
                    'rider' => $r['rider'],
                    'country' => $r['country'],
                    'points' => $r['points'] ?? 0,
                ])->toArray(),
            ];
        }

        // === MTB (2025 data) ===
        $mtbXco = $this->cyclingService->getMtbXcoWorldCup($year);
        if ($mtbXco) {
            $data['mtb']['xco'] = [
                'name' => "XCO World Cup {$year}",
                'men' => collect($mtbXco['men_elite']['standings'] ?? [])->take(10)->map(fn($r) => [
                    'rank' => $r['rank'],
                    'rider' => $r['rider'],
                    'country' => $r['country'],
                    'points' => $r['points'] ?? 0,
                ])->toArray(),
                'women' => collect($mtbXco['women_elite']['standings'] ?? [])->take(10)->map(fn($r) => [
                    'rank' => $r['rank'],
                    'rider' => $r['rider'],
                    'country' => $r['country'],
                    'points' => $r['points'] ?? 0,
                ])->toArray(),
            ];
        }

        $mtbDh = $this->cyclingService->getMtbDownhillWorldCup($year);
        if ($mtbDh) {
            $data['mtb']['dh'] = [
                'name' => "DH World Cup {$year}",
                'men' => collect($mtbDh['men_elite']['standings'] ?? [])->take(10)->map(fn($r) => [
                    'rank' => $r['rank'],
                    'rider' => $r['rider'],
                    'country' => $r['country'],
                    'points' => $r['points'] ?? 0,
                ])->toArray(),
                'women' => collect($mtbDh['women_elite']['standings'] ?? [])->take(10)->map(fn($r) => [
                    'rank' => $r['rank'],
                    'rider' => $r['rider'],
                    'country' => $r['country'],
                    'points' => $r['points'] ?? 0,
                ])->toArray(),
            ];
        }

        return view('home', $data);
    }
}
