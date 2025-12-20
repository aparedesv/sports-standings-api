<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fixture extends Model
{
    protected $fillable = [
        'external_id',
        'league_id',
        'season_id',
        'home_team_id',
        'away_team_id',
        'date',
        'status',
        'home_score',
        'away_score',
        'venue',
        'referee',
        'round',
    ];

    protected $casts = [
        'date' => 'datetime',
    ];

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function homeTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function isFinished(): bool
    {
        return $this->status === 'FT';
    }

    public function isLive(): bool
    {
        return in_array($this->status, ['1H', 'HT', '2H', 'ET', 'P']);
    }
}
