<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class EspnTennisService
{
    private string $baseUrl = 'https://site.api.espn.com/apis/site/v2/sports/tennis';
    private int $cacheMinutes = 60;

    public function getRankings(string $tour = 'atp'): ?array
    {
        $cacheKey = "tennis_rankings_{$tour}_espn";

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($tour) {
            try {
                $response = Http::timeout(30)
                    ->get("{$this->baseUrl}/{$tour}/rankings");

                if ($response->successful()) {
                    return $this->parseRankings($response->json(), $tour);
                }

                Log::error("ESPN Tennis API error for {$tour}: " . $response->status());
                return null;
            } catch (\Exception $e) {
                Log::error("ESPN Tennis API exception for {$tour}: " . $e->getMessage());
                return null;
            }
        });
    }

    private function parseRankings(array $data, string $tour): array
    {
        $rankings = [];

        $rankingsData = $data['rankings'][0]['ranks'] ?? [];

        foreach ($rankingsData as $entry) {
            $athlete = $entry['athlete'] ?? [];

            $rankings[] = [
                'rank' => (int) ($entry['current'] ?? 0),
                'previous_rank' => (int) ($entry['previous'] ?? 0),
                'name' => $athlete['displayName'] ?? '',
                'country' => $athlete['flagAltText'] ?? $athlete['citizenshipCountry'] ?? '',
                'points' => (int) ($entry['points'] ?? 0),
                'trend' => $entry['trend'] ?? '-',
            ];
        }

        return [
            'tour' => strtoupper($tour),
            'rankings' => $rankings,
        ];
    }

    public function getAtpRankings(): ?array
    {
        return $this->getRankings('atp');
    }

    public function getWtaRankings(): ?array
    {
        return $this->getRankings('wta');
    }

    public function clearCache(?string $tour = null): void
    {
        if ($tour) {
            Cache::forget("tennis_rankings_{$tour}_espn");
        } else {
            Cache::forget("tennis_rankings_atp_espn");
            Cache::forget("tennis_rankings_wta_espn");
        }
    }
}
