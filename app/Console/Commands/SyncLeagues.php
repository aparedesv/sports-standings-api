<?php

namespace App\Console\Commands;

use App\Models\Country;
use App\Models\League;
use App\Models\Season;
use App\Services\FootballJsonService;
use Illuminate\Console\Command;

class SyncLeagues extends Command
{
    protected $signature = 'sync:leagues {--country= : Country name to filter}';
    protected $description = 'Sync leagues from openfootball/football.json';

    public function handle(FootballJsonService $service): int
    {
        $countryFilter = $this->option('country');
        $leagues = $service->getAvailableLeagues();
        $seasons = $service->getAvailableSeasons();

        $this->info('Syncing leagues from football.json...');

        $count = 0;
        foreach ($leagues as $code => $info) {
            if ($countryFilter && strtolower($info['country']) !== strtolower($countryFilter)) {
                continue;
            }

            // Crear o obtenir país
            $country = Country::firstOrCreate(
                ['name' => $info['country']],
                ['code' => strtoupper(substr($info['country'], 0, 2))]
            );

            // Crear o actualitzar lliga
            $league = League::updateOrCreate(
                ['external_id' => crc32($code)],
                [
                    'name' => $info['name'],
                    'type' => 'league',
                    'country_id' => $country->id,
                ]
            );

            // Crear temporades
            foreach ($seasons as $seasonCode) {
                $year = $service->seasonToYear($seasonCode);
                $isCurrent = ($seasonCode === '2024-25');

                Season::updateOrCreate(
                    [
                        'league_id' => $league->id,
                        'year' => $year,
                    ],
                    [
                        'start' => "{$year}-08-01",
                        'end' => ($year + 1) . "-06-30",
                        'current' => $isCurrent,
                    ]
                );
            }

            $this->line("  - {$info['name']} ({$info['country']})");
            $count++;
        }

        $this->info("Synced {$count} leagues.");

        return Command::SUCCESS;
    }
}
