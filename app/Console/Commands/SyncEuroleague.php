<?php

namespace App\Console\Commands;

use App\Services\EuroleagueApiService;
use Illuminate\Console\Command;

class SyncEuroleague extends Command
{
    protected $signature = 'sync:euroleague
                            {--competition= : Competition to sync (euroleague, eurocup)}
                            {--season= : Season code (e.g., E2024, U2024)}
                            {--clear-cache : Clear cache before syncing}
                            {--teams : Show teams instead of standings}';

    protected $description = 'Sync European basketball standings from Euroleague API';

    public function handle(EuroleagueApiService $euroleagueService): int
    {
        $competition = $this->option('competition');
        $seasonCode = $this->option('season');

        if ($this->option('clear-cache')) {
            $euroleagueService->clearCache($competition, $seasonCode);
            $this->info('Cache cleared.');
        }

        // Show teams if requested
        if ($this->option('teams')) {
            return $this->showTeams($euroleagueService, $competition, $seasonCode);
        }

        // Show standings
        return $this->showStandings($euroleagueService, $competition, $seasonCode);
    }

    private function showStandings(EuroleagueApiService $service, ?string $competition, ?string $seasonCode): int
    {
        $competitions = $competition ? [$competition] : array_keys($service->getAvailableCompetitions());

        foreach ($competitions as $comp) {
            // If season code provided, convert prefix if needed (E2024 -> U2024 for eurocup)
            if ($seasonCode) {
                $prefix = $service->getAvailableCompetitions()[$comp]['code'] ?? 'E';
                $year = substr($seasonCode, 1);
                $code = $prefix . $year;
            } else {
                $code = $service->getCurrentSeasonCode($comp);
            }

            $this->info("Fetching {$comp} standings ({$code})...");

            $data = $service->getStandings($comp, $code);

            if (!$data) {
                $this->error("Failed to fetch {$comp} standings.");
                continue;
            }

            $this->newLine();
            $this->info("=== {$data['competition']} {$data['season']} ===");

            if ($data['has_groups'] ?? false) {
                // Multiple groups (Eurocup)
                foreach ($data['groups'] as $group) {
                    $this->newLine();
                    $this->info($group['name'] . ':');
                    $this->displayStandingsTable($group['teams']);
                }
            } else {
                // Single group (Euroleague)
                $this->displayStandingsTable($data['standings'] ?? []);
            }
        }

        $this->newLine();
        $this->info('Euroleague standings synced successfully!');

        return Command::SUCCESS;
    }

    private function displayStandingsTable(array $teams): void
    {
        if (empty($teams)) {
            $this->warn('No teams found.');
            return;
        }

        $this->table(
            ['#', 'Team', 'P', 'W', 'L', 'PF', 'PA', '+/-'],
            collect($teams)->take(10)->map(fn($t) => [
                $t['rank'],
                $t['team_name'],
                $t['played'],
                $t['won'],
                $t['lost'],
                $t['points_for'],
                $t['points_against'],
                ($t['point_diff'] >= 0 ? '+' : '') . $t['point_diff'],
            ])->toArray()
        );

        $total = count($teams);
        if ($total > 10) {
            $this->info("... and " . ($total - 10) . " more teams");
        }
    }

    private function showTeams(EuroleagueApiService $service, ?string $competition, ?string $seasonCode): int
    {
        $competitions = $competition ? [$competition] : array_keys($service->getAvailableCompetitions());

        foreach ($competitions as $comp) {
            // If season code provided, convert prefix if needed
            if ($seasonCode) {
                $prefix = $service->getAvailableCompetitions()[$comp]['code'] ?? 'E';
                $year = substr($seasonCode, 1);
                $code = $prefix . $year;
            } else {
                $code = $service->getCurrentSeasonCode($comp);
            }

            $this->info("Fetching {$comp} teams ({$code})...");

            $data = $service->getTeams($comp, $code);

            if (!$data || empty($data['teams'])) {
                $this->error("Failed to fetch {$comp} teams.");
                continue;
            }

            $this->newLine();
            $this->info("=== {$data['competition']} {$data['season']} - Teams ===");

            $this->table(
                ['Code', 'Team', 'City', 'Country'],
                collect($data['teams'])->map(fn($t) => [
                    $t['code'],
                    $t['name'],
                    $t['city'],
                    $t['country'],
                ])->toArray()
            );
        }

        return Command::SUCCESS;
    }
}
