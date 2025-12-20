<?php

namespace App\Console\Commands;

use App\Models\League;
use App\Models\Standing;
use App\Models\Team;
use App\Services\FootballJsonService;
use Illuminate\Console\Command;

class SyncStandings extends Command
{
    protected $signature = 'sync:standings {league : League ID or code (e.g., es.1)} {--season=2024-25 : Season code}';
    protected $description = 'Calculate and sync standings from openfootball/football.json';

    public function handle(FootballJsonService $service): int
    {
        $leagueArg = $this->argument('league');
        $seasonCode = $this->option('season');

        // Determinar si és un ID o un codi
        $leagueCode = null;
        $league = null;

        if (is_numeric($leagueArg)) {
            $league = League::find($leagueArg);
            if (!$league) {
                $this->error("League with ID {$leagueArg} not found.");
                return Command::FAILURE;
            }
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
                return Command::FAILURE;
            }
            $league = League::where('external_id', crc32($leagueCode))->first();
        }

        if (!$leagueCode || !$league) {
            $this->error("Could not find league. Run sync:leagues first.");
            return Command::FAILURE;
        }

        $year = $service->seasonToYear($seasonCode);
        $season = $league->seasons()->where('year', $year)->first();

        if (!$season) {
            $this->error("Season {$year} not found for this league.");
            return Command::FAILURE;
        }

        $this->info("Calculating standings for {$league->name} ({$seasonCode})...");

        $standings = $service->calculateStandings($leagueCode, $seasonCode);

        if (empty($standings)) {
            $this->warn("No standings data available.");
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar(count($standings));
        $bar->start();

        $synced = 0;

        foreach ($standings as $standingData) {
            $team = Team::where('external_id', crc32($standingData['team']))->first();

            if (!$team) {
                $bar->advance();
                continue;
            }

            // Determinar descripció (Champions League, Europa League, Descens, etc.)
            $description = null;
            if ($standingData['rank'] <= 4) {
                $description = 'Champions League';
            } elseif ($standingData['rank'] <= 6) {
                $description = 'Europa League';
            } elseif ($standingData['rank'] >= 18) {
                $description = 'Relegation';
            }

            Standing::updateOrCreate(
                [
                    'league_id' => $league->id,
                    'season_id' => $season->id,
                    'team_id' => $team->id,
                ],
                [
                    'rank' => $standingData['rank'],
                    'points' => $standingData['points'],
                    'played' => $standingData['played'],
                    'won' => $standingData['won'],
                    'drawn' => $standingData['drawn'],
                    'lost' => $standingData['lost'],
                    'goals_for' => $standingData['goals_for'],
                    'goals_against' => $standingData['goals_against'],
                    'goal_diff' => $standingData['goal_diff'],
                    'description' => $description,
                ]
            );

            $synced++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Synced {$synced} standings.");

        return Command::SUCCESS;
    }
}
