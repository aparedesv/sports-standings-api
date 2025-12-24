<?php

namespace App\Console\Commands;

use App\Services\NbaApiService;
use Illuminate\Console\Command;

class SyncBasketball extends Command
{
    protected $signature = 'sync:basketball
                            {--season= : Season to sync (e.g., 2024-25)}
                            {--clear-cache : Clear cache before syncing}';

    protected $description = 'Sync NBA basketball standings from stats.nba.com';

    public function handle(NbaApiService $nbaService): int
    {
        $season = $this->option('season') ?? $nbaService->getCurrentSeason();

        $this->info("Syncing NBA standings for season {$season}...");

        if ($this->option('clear-cache')) {
            $nbaService->clearCache($season);
            $this->info('Cache cleared.');
        }

        $standings = $nbaService->getStandings($season);

        if (!$standings) {
            $this->error('Failed to fetch NBA standings.');
            return Command::FAILURE;
        }

        $eastCount = count($standings['eastern'] ?? []);
        $westCount = count($standings['western'] ?? []);

        $this->info("Eastern Conference: {$eastCount} teams");
        $this->info("Western Conference: {$westCount} teams");

        if ($eastCount > 0) {
            $this->newLine();
            $this->info('Eastern Conference Top 5:');
            $this->table(
                ['#', 'Team', 'W', 'L', 'PCT'],
                collect($standings['eastern'])->take(5)->map(fn($t) => [
                    $t['rank'],
                    $t['team_city'] . ' ' . $t['team_name'],
                    $t['wins'],
                    $t['losses'],
                    number_format($t['win_pct'], 3),
                ])->toArray()
            );
        }

        if ($westCount > 0) {
            $this->newLine();
            $this->info('Western Conference Top 5:');
            $this->table(
                ['#', 'Team', 'W', 'L', 'PCT'],
                collect($standings['western'])->take(5)->map(fn($t) => [
                    $t['rank'],
                    $t['team_city'] . ' ' . $t['team_name'],
                    $t['wins'],
                    $t['losses'],
                    number_format($t['win_pct'], 3),
                ])->toArray()
            );
        }

        $this->newLine();
        $this->info('NBA standings synced successfully!');

        return Command::SUCCESS;
    }
}
