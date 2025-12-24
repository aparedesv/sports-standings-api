<?php

namespace App\Console\Commands;

use App\Services\CyclingApiService;
use Illuminate\Console\Command;

class SyncCycling extends Command
{
    protected $signature = 'sync:cycling
                            {--year= : Year to sync (default: current)}
                            {--calendar : Show UCI World Tour calendar}
                            {--tdf : Show Tour de France GC}
                            {--giro : Show Giro d\'Italia GC}
                            {--vuelta : Show Vuelta a Espana GC}
                            {--tdf-stages : Show Tour de France stages}
                            {--giro-stages : Show Giro d\'Italia stages}
                            {--vuelta-stages : Show Vuelta a Espana stages}
                            {--all-gc : Show all Grand Tours GC}
                            {--all-stages : Show all Grand Tours stages}
                            {--clear-cache : Clear cache before syncing}';

    protected $description = 'Sync cycling data from TheSportsDB, LeTourDataSet, and local JSON files';

    public function handle(CyclingApiService $cyclingService): int
    {
        $year = (int)($this->option('year') ?: date('Y'));

        if ($this->option('clear-cache')) {
            $cyclingService->clearCache($year);
            $this->info('Cache cleared.');
        }

        $hasSpecificOption = $this->option('calendar') || $this->option('tdf') ||
                            $this->option('giro') || $this->option('vuelta') ||
                            $this->option('tdf-stages') || $this->option('giro-stages') ||
                            $this->option('vuelta-stages') || $this->option('all-gc') ||
                            $this->option('all-stages');

        $showAll = !$hasSpecificOption;

        // UCI World Tour Calendar
        if ($showAll || $this->option('calendar')) {
            $this->showCalendar($cyclingService, $year);
        }

        // All Grand Tours GC
        if ($this->option('all-gc')) {
            $this->showGiroGC($cyclingService, $year);
            $this->showTdfGC($cyclingService, $year);
            $this->showVueltaGC($cyclingService, $year);
        } else {
            // Individual Grand Tour GC options
            if ($showAll || $this->option('giro')) {
                $this->showGiroGC($cyclingService, $year);
            }

            if ($showAll || $this->option('tdf')) {
                $this->showTdfGC($cyclingService, $year);
            }

            if ($showAll || $this->option('vuelta')) {
                $this->showVueltaGC($cyclingService, $year);
            }
        }

        // All Grand Tours Stages
        if ($this->option('all-stages')) {
            $this->showGiroStages($cyclingService, $year);
            $this->showTdfStages($cyclingService, $year);
            $this->showVueltaStages($cyclingService, $year);
        } else {
            // Individual Grand Tour stages options
            if ($this->option('giro-stages')) {
                $this->showGiroStages($cyclingService, $year);
            }

            if ($showAll || $this->option('tdf-stages')) {
                $this->showTdfStages($cyclingService, $year);
            }

            if ($this->option('vuelta-stages')) {
                $this->showVueltaStages($cyclingService, $year);
            }
        }

        $this->newLine();
        $this->info('Cycling data synced successfully!');

        return Command::SUCCESS;
    }

    private function showCalendar(CyclingApiService $service, int $year): void
    {
        $this->info("Fetching UCI World Tour calendar {$year}...");

        $data = $service->getWorldTourCalendar($year);

        if (!$data || empty($data['races'])) {
            $this->error('No calendar data found.');
            return;
        }

        $this->newLine();
        $this->info("=== UCI World Tour {$year} ===");
        $this->info("Total events: {$data['total_events']}");
        $this->newLine();

        $races = collect($data['races'])->take(15)->map(fn($r) => [
            $r['name'],
            $r['country'],
            $r['start_date'],
            $r['end_date'],
            count($r['stages']) . ' stages',
        ])->toArray();

        $this->table(
            ['Race', 'Country', 'Start', 'End', 'Stages'],
            $races
        );

        $total = count($data['races']);
        if ($total > 15) {
            $this->info("... and " . ($total - 15) . " more races");
        }
    }

    private function showGiroGC(CyclingApiService $service, int $year): void
    {
        $this->info("Fetching Giro d'Italia GC {$year}...");

        $data = $service->getGiroGC($year);

        if (!$data || empty($data['standings'])) {
            $this->warn("No Giro d'Italia GC data found for {$year}");
            return;
        }

        $this->newLine();
        $this->info("=== Giro d'Italia {$year} - General Classification ===");

        $riders = collect($data['standings'])->take(10)->map(fn($r) => [
            $r['rank'],
            $r['rider'],
            $r['team'],
            $r['time'] ?: '-',
            $r['gap'] ?: '-',
        ])->toArray();

        $this->table(
            ['#', 'Rider', 'Team', 'Time', 'Gap'],
            $riders
        );
    }

    private function showTdfGC(CyclingApiService $service, int $year): void
    {
        $this->info("Fetching Tour de France GC {$year}...");

        $data = $service->getTourDeFranceGC($year);

        if (!$data || empty($data['standings'])) {
            $this->warn("No Tour de France GC data found for {$year}");
            return;
        }

        $this->newLine();
        $this->info("=== Tour de France {$year} - General Classification ===");

        $riders = collect($data['standings'])->take(10)->map(fn($r) => [
            $r['rank'],
            $r['rider'],
            $r['team'],
            $r['time'] ?: '-',
            $r['gap'] ?: '-',
        ])->toArray();

        $this->table(
            ['#', 'Rider', 'Team', 'Time', 'Gap'],
            $riders
        );
    }

    private function showVueltaGC(CyclingApiService $service, int $year): void
    {
        $this->info("Fetching Vuelta a Espana GC {$year}...");

        $data = $service->getVueltaGC($year);

        if (!$data || empty($data['standings'])) {
            $this->warn("No Vuelta a Espana GC data found for {$year}");
            return;
        }

        $this->newLine();
        $this->info("=== Vuelta a Espana {$year} - General Classification ===");

        $riders = collect($data['standings'])->take(10)->map(fn($r) => [
            $r['rank'],
            $r['rider'],
            $r['team'],
            $r['time'] ?: '-',
            $r['gap'] ?: '-',
        ])->toArray();

        $this->table(
            ['#', 'Rider', 'Team', 'Time', 'Gap'],
            $riders
        );
    }

    private function showGiroStages(CyclingApiService $service, int $year): void
    {
        $this->info("Fetching Giro d'Italia stages {$year}...");

        $data = $service->getGiroStages($year);

        if (!$data || empty($data['stages'])) {
            $this->warn("No Giro d'Italia stages data found for {$year}");
            return;
        }

        $this->newLine();
        $this->info("=== Giro d'Italia {$year} - Stages ===");
        $this->info("Distance: {$data['distance_km']} km | Stages: {$data['total_stages']}");
        $this->newLine();

        $stages = collect($data['stages'])->map(fn($s) => [
            $s['stage'],
            $s['start'] . ' → ' . $s['end'],
            $s['winner'],
            $s['pink_jersey'],
        ])->toArray();

        $this->table(
            ['Stage', 'Route', 'Winner', 'Maglia Rosa'],
            $stages
        );
    }

    private function showTdfStages(CyclingApiService $service, int $year): void
    {
        $this->info("Fetching Tour de France stages {$year}...");

        $data = $service->getTourDeFranceStages($year);

        if (!$data || empty($data['stages'])) {
            $this->warn("No Tour de France stages data found for {$year}");
            return;
        }

        $this->newLine();
        $this->info("=== Tour de France {$year} - Stages ===");
        $this->info("Distance: {$data['distance_km']} km | Stages: {$data['total_stages']}");
        $this->newLine();

        $stages = collect($data['stages'])->map(fn($s) => [
            $s['stage'],
            $s['start'] . ' → ' . $s['end'],
            $s['winner'],
            $s['yellow_jersey'],
        ])->toArray();

        $this->table(
            ['Stage', 'Route', 'Winner', 'Maillot Jaune'],
            $stages
        );
    }

    private function showVueltaStages(CyclingApiService $service, int $year): void
    {
        $this->info("Fetching Vuelta a Espana stages {$year}...");

        $data = $service->getVueltaStages($year);

        if (!$data || empty($data['stages'])) {
            $this->warn("No Vuelta a Espana stages data found for {$year}");
            return;
        }

        $this->newLine();
        $this->info("=== Vuelta a Espana {$year} - Stages ===");
        $this->info("Distance: {$data['distance_km']} km | Stages: {$data['total_stages']}");
        $this->newLine();

        $stages = collect($data['stages'])->map(fn($s) => [
            $s['stage'],
            $s['start'] . ' → ' . $s['end'],
            $s['winner'],
            $s['red_jersey'],
        ])->toArray();

        $this->table(
            ['Stage', 'Route', 'Winner', 'Maillot Rojo'],
            $stages
        );
    }
}
