<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\League;
use App\Models\Season;
use App\Services\FootballJsonService;
use Illuminate\Database\Seeder;

class LeaguesSeeder extends Seeder
{
    /**
     * Seed all available leagues from openfootball/football.json.
     */
    public function run(): void
    {
        $service = new FootballJsonService();
        $leagues = $service->getAvailableLeagues();
        $seasons = $service->getAvailableSeasons();

        foreach ($leagues as $code => $info) {
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

            $this->command->info("  - {$info['name']} ({$info['country']})");
        }

        $this->command->info("Seeded " . count($leagues) . " leagues with " . count($seasons) . " seasons each.");
    }
}
