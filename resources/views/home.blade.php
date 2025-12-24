<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cycling Standings</title>
    @vite('resources/scss/style.scss')
</head>
<body>
    <h1>// cycling-standings</h1>

    <div class="season-header">
        <span class="year">{{ $year }}</span>
    </div>

    <div class="leagues-grid">
        @forelse($competitions as $competition)
            <div class="league">
                <div class="league-name">
                    {{ $competition->league->name ?? 'Unknown' }}
                    <span>{{ $competition->league->country->name ?? '' }}</span>
                </div>

                @if($competition->standings->count() > 0)
                    @php
                        $type = $competition->type ?? 'cycling';
                        $isCyclingGC = $type === 'cycling_gc';
                        $isCyclingStages = $type === 'cycling_stages';
                        $isCyclingClassics = $type === 'cycling_classics';
                        $isCxWorlds = $type === 'cx_worlds';
                        $isCxStandings = $type === 'cx_standings';
                        $isMtbStandings = $type === 'mtb_standings';
                        $isRankingType = $isCxStandings || $isMtbStandings;
                    @endphp
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                @if($isCyclingClassics)
                                    <th>Race</th>
                                    <th>Winner</th>
                                    <th>2nd</th>
                                    <th>3rd</th>
                                @elseif($isCyclingGC)
                                    <th>Rider</th>
                                    <th>Team</th>
                                    <th>Time</th>
                                    <th>Gap</th>
                                @elseif($isCyclingStages)
                                    <th>Winner</th>
                                    <th>Route</th>
                                    <th>Leader</th>
                                @elseif($isCxWorlds)
                                    <th>Rider</th>
                                    <th>Country</th>
                                    <th>Category</th>
                                @elseif($isRankingType)
                                    <th>Rider</th>
                                    <th>Country</th>
                                    <th>Pts</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($competition->standings->sortBy('rank') as $standing)
                                @php
                                    $rowClass = $standing->rank <= 3 ? 'top-4' : '';
                                @endphp
                                <tr class="{{ $rowClass }}">
                                    <td class="rank">{{ $standing->rank }}</td>
                                    @if($isCyclingClassics)
                                        <td class="team" title="{{ $standing->nickname ?? '' }}">{{ $standing->race_name ?? '-' }}</td>
                                        <td>{{ $standing->team->name ?? '-' }}</td>
                                        <td>{{ $standing->second ?? '-' }}</td>
                                        <td>{{ $standing->third ?? '-' }}</td>
                                    @elseif($isCyclingGC)
                                        <td class="team">{{ $standing->team->name ?? '-' }}</td>
                                        <td>{{ $standing->country ?? '' }}</td>
                                        <td class="num">{{ $standing->points ?? '' }}</td>
                                        <td class="num">{{ $standing->gap ?? '-' }}</td>
                                    @elseif($isCyclingStages)
                                        <td class="team">{{ $standing->team->name ?? '-' }}</td>
                                        <td>{{ $standing->route ?? '' }}</td>
                                        <td>{{ $standing->yellow ?? '' }}</td>
                                    @elseif($isCxWorlds)
                                        <td class="team">{{ $standing->team->name ?? '-' }}</td>
                                        <td>{{ $standing->country ?? '' }}</td>
                                        <td>{{ $standing->category ?? '' }}</td>
                                    @elseif($isRankingType)
                                        <td class="team">{{ $standing->team->name ?? '-' }}</td>
                                        <td>{{ $standing->country ?? '' }}</td>
                                        <td class="num">{{ $standing->points ?? 0 }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p class="no-data">no data</p>
                @endif
            </div>
        @empty
            <p class="no-data">no competitions found</p>
        @endforelse
    </div>
</body>
</html>
