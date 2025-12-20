<?php

namespace App\Console\Commands;

use App\Services\FootballJsonService;
use Illuminate\Console\Command;

class SyncAll extends Command
{
    protected $signature = 'sync:all {--season=2024-25 : Season code} {--league= : Specific league code (e.g., es.1)}';
    protected $description = 'Sync all data (leagues, teams, fixtures, standings) from openfootball/football.json';

    public function handle(FootballJsonService $service): int
    {
        $season = $this->option('season');
        $leagueFilter = $this->option('league');

        $startTime = microtime(true);

        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════╗');
        $this->info('║          SPORT STANDINGS API - FULL SYNC                 ║');
        $this->info('╚══════════════════════════════════════════════════════════╝');
        $this->info('');

        // 1. Sync Leagues
        $this->info('📋 [1/4] Syncing leagues...');
        $this->call('sync:leagues');
        $this->newLine();

        // Get leagues to sync
        $leagues = $service->getAvailableLeagues();

        if ($leagueFilter) {
            if (!isset($leagues[$leagueFilter])) {
                $this->error("League '{$leagueFilter}' not found.");
                $this->line("Available leagues: " . implode(', ', array_keys($leagues)));
                return Command::FAILURE;
            }
            $leagues = [$leagueFilter => $leagues[$leagueFilter]];
        }

        $totalLeagues = count($leagues);
        $currentLeague = 0;

        // 2. Sync Teams
        $this->info("🏟️  [2/4] Syncing teams for {$totalLeagues} leagues...");
        $teamsCount = 0;

        foreach ($leagues as $code => $info) {
            $currentLeague++;
            $this->line("  [{$currentLeague}/{$totalLeagues}] {$info['name']}...");

            $result = $this->callSilently('sync:teams', [
                'league' => $code,
                '--season' => $season,
            ]);

            if ($result === Command::SUCCESS) {
                $teamsCount++;
            }
        }
        $this->info("  ✓ Teams synced for {$teamsCount} leagues");
        $this->newLine();

        // 3. Sync Fixtures
        $this->info("⚽ [3/4] Syncing fixtures for {$totalLeagues} leagues...");
        $fixturesCount = 0;
        $currentLeague = 0;

        foreach ($leagues as $code => $info) {
            $currentLeague++;
            $this->line("  [{$currentLeague}/{$totalLeagues}] {$info['name']}...");

            $result = $this->callSilently('sync:fixtures', [
                'league' => $code,
                '--season' => $season,
            ]);

            if ($result === Command::SUCCESS) {
                $fixturesCount++;
            }
        }
        $this->info("  ✓ Fixtures synced for {$fixturesCount} leagues");
        $this->newLine();

        // 4. Sync Standings
        $this->info("🏆 [4/4] Syncing standings for {$totalLeagues} leagues...");
        $standingsCount = 0;
        $currentLeague = 0;

        foreach ($leagues as $code => $info) {
            $currentLeague++;
            $this->line("  [{$currentLeague}/{$totalLeagues}] {$info['name']}...");

            $result = $this->callSilently('sync:standings', [
                'league' => $code,
                '--season' => $season,
            ]);

            if ($result === Command::SUCCESS) {
                $standingsCount++;
            }
        }
        $this->info("  ✓ Standings synced for {$standingsCount} leagues");
        $this->newLine();

        // Summary
        $elapsed = round(microtime(true) - $startTime, 2);

        $this->info('╔══════════════════════════════════════════════════════════╗');
        $this->info('║                    SYNC COMPLETE                         ║');
        $this->info('╚══════════════════════════════════════════════════════════╝');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Season', $season],
                ['Leagues', $totalLeagues],
                ['Teams synced', $teamsCount . ' leagues'],
                ['Fixtures synced', $fixturesCount . ' leagues'],
                ['Standings synced', $standingsCount . ' leagues'],
                ['Time elapsed', $elapsed . 's'],
            ]
        );

        return Command::SUCCESS;
    }
}
