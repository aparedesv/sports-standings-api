<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\League;
use App\Models\Team;
use App\Services\FootballJsonService;
use Illuminate\Console\Command;

class SyncTeams extends Command
{
    protected $signature = 'sync:teams {league : League ID or code (e.g., es.1)} {--season=2024-25 : Season code}';
    protected $description = 'Sync teams from openfootball/football.json';

    public function handle(FootballJsonService $service): int
    {
        $leagueArg = $this->argument('league');
        $season = $this->option('season');

        // Determinar si és un ID o un codi
        $leagueCode = null;
        $league = null;

        if (is_numeric($leagueArg)) {
            $league = League::find($leagueArg);
            if (!$league) {
                $this->error("League with ID {$leagueArg} not found.");
                return Command::FAILURE;
            }
            // Buscar el codi de la lliga
            foreach ($service->getAvailableLeagues() as $code => $info) {
                if (crc32($code) == $league->external_id) {
                    $leagueCode = $code;
                    break;
                }
            }
        } else {
            $leagueCode = $leagueArg;
            $leagues = $service->getAvailableLeagues();
            if (!isset($leagues[$leagueCode])) {
                $this->error("League code '{$leagueCode}' not found.");
                $this->line("Available codes: " . implode(', ', array_keys($leagues)));
                return Command::FAILURE;
            }
            $league = League::where('external_id', crc32($leagueCode))->first();
        }

        if (!$leagueCode) {
            $this->error("Could not determine league code.");
            return Command::FAILURE;
        }

        $this->info("Syncing teams for {$leagueCode} ({$season})...");

        $teams = $service->getTeams($leagueCode, $season);

        if (empty($teams)) {
            $this->warn("No teams found for this league/season.");
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar(count($teams));
        $bar->start();

        $country = $league ? $league->country : null;

        foreach ($teams as $teamName) {
            Team::updateOrCreate(
                ['external_id' => crc32($teamName)],
                [
                    'name' => $teamName,
                    'code' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $teamName), 0, 3)),
                    'country_id' => $country?->id,
                ]
            );
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Synced " . count($teams) . " teams.");

        return Command::SUCCESS;
    }
}
