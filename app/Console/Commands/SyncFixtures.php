<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Models\League;
use App\Models\Team;
use App\Services\FootballJsonService;
use Illuminate\Console\Command;

class SyncFixtures extends Command
{
    protected $signature = 'sync:fixtures {league : League ID or code (e.g., es.1)} {--season=2024-25 : Season code}';
    protected $description = 'Sync fixtures from openfootball/football.json';

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

        $this->info("Syncing fixtures for {$league->name} ({$seasonCode})...");

        $matches = $service->getMatches($leagueCode, $seasonCode);

        if (empty($matches)) {
            $this->warn("No matches found.");
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar(count($matches));
        $bar->start();

        $synced = 0;
        $skipped = 0;

        foreach ($matches as $match) {
            $homeTeam = Team::where('external_id', crc32($match['team1']))->first();
            $awayTeam = Team::where('external_id', crc32($match['team2']))->first();

            if (!$homeTeam || !$awayTeam) {
                $skipped++;
                $bar->advance();
                continue;
            }

            $hasScore = !empty($match['score']) && isset($match['score']['ft']);
            $homeScore = $hasScore ? $match['score']['ft'][0] : null;
            $awayScore = $hasScore ? $match['score']['ft'][1] : null;

            // Determinar estat
            $status = 'NS'; // Not Started
            if ($hasScore) {
                $status = 'FT'; // Full Time
            }

            // Extreure jornada
            $round = null;
            if (isset($match['round']) && preg_match('/\d+/', $match['round'], $m)) {
                $round = (int) $m[0];
            }

            // Crear ID únic per al partit
            $externalId = crc32("{$match['date']}_{$match['team1']}_{$match['team2']}");

            $dateTime = $match['date'];
            if (isset($match['time'])) {
                $dateTime .= ' ' . $match['time'];
            } else {
                $dateTime .= ' 00:00';
            }

            Fixture::updateOrCreate(
                ['external_id' => $externalId],
                [
                    'league_id' => $league->id,
                    'season_id' => $season->id,
                    'home_team_id' => $homeTeam->id,
                    'away_team_id' => $awayTeam->id,
                    'date' => $dateTime,
                    'status' => $status,
                    'home_score' => $homeScore,
                    'away_score' => $awayScore,
                    'round' => $round,
                ]
            );

            $synced++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Synced {$synced} fixtures. Skipped {$skipped} (missing teams).");

        return Command::SUCCESS;
    }
}
