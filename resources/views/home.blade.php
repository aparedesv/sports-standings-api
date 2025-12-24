<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sport Standings</title>
    @vite('resources/scss/style.scss')
</head>
<body>
    <h1>// sport-standings</h1>

    <div class="tabs">
        <div class="tabs-nav">
            @php $first = true; @endphp
            @foreach($sports as $key => $sport)
                <label class="{{ $first ? 'tab-active' : '' }}" data-tab="{{ $key }}">{{ $sport['name'] }}</label>
                @php $first = false; @endphp
            @endforeach
        </div>

        @php $first = true; @endphp
        @foreach($sports as $key => $sport)
            <div class="tab-content" data-tab-content="{{ $key }}" style="{{ $first ? 'display: block;' : '' }}">
                @php $firstSeason = true; @endphp
                @forelse($sport['seasons'] as $year => $seasonList)
                    <div class="accordion">
                        <input type="checkbox" id="accordion-{{ $key }}-{{ $year }}" {{ $firstSeason ? 'checked' : '' }}>
                        <label class="accordion-header" for="accordion-{{ $key }}-{{ $year }}">
                            {{ $year }}-{{ substr($year + 1, -2) }}
                        </label>
                        <div class="accordion-content">
                            <div class="accordion-inner">
                                <div class="leagues-grid">
                                    @foreach($seasonList as $season)
                                        <div class="league">
                                            <div class="league-name">
                                                {{ $season->league->name ?? 'Unknown' }}
                                                <span>{{ $season->league->country->name ?? '' }}</span>
                                            </div>

                                            @if($season->standings->count() > 0)
                                                @php
                                                    $type = $season->type ?? $key;
                                                    $isBasketball = in_array($key, ['basketball', 'nfl']);
                                                    $isF1 = in_array($type, ['f1_drivers', 'f1_constructors']);
                                                    $isTennis = $type === 'tennis';
                                                    $isCyclingGC = $type === 'cycling_gc';
                                                    $isCyclingStages = $type === 'cycling_stages';
                                                    $isCyclingClassics = $type === 'cycling_classics';
                                                    $isCycling = $isCyclingGC || $isCyclingStages || $isCyclingClassics;
                                                    $isRanking = $isF1 || $isTennis;
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
                                                                <th>Yellow</th>
                                                            @elseif($isRanking)
                                                                <th>{{ $isRanking ? 'Name' : 'Team' }}</th>
                                                                @if($type === 'f1_drivers' || $isTennis)
                                                                    <th>Country</th>
                                                                @endif
                                                                <th class="num">Pts</th>
                                                            @else
                                                                <th>Team</th>
                                                                <th class="num">{{ $isBasketball ? 'G' : 'P' }}</th>
                                                                <th class="num">W</th>
                                                                @if(!$isBasketball)<th class="num">D</th>@endif
                                                                <th class="num">L</th>
                                                                <th class="num">{{ $isBasketball ? 'PCT' : '+/-' }}</th>
                                                                @if(!$isBasketball)<th class="num">Pts</th>@endif
                                                            @endif
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($season->standings->sortBy('rank') as $standing)
                                                            @php
                                                                $totalTeams = $season->standings->count();
                                                                $rowClass = '';
                                                                if ($isCycling) {
                                                                    if ($standing->rank <= 3) $rowClass = 'top-4';
                                                                } elseif ($isBasketball) {
                                                                    if ($standing->rank <= 6) $rowClass = 'top-4';
                                                                    elseif ($standing->rank <= 10) $rowClass = 'play-in';
                                                                } elseif ($isF1) {
                                                                    if ($standing->rank <= 3) $rowClass = 'top-4';
                                                                } elseif ($isTennis) {
                                                                    if ($standing->rank <= 10) $rowClass = 'top-4';
                                                                } else {
                                                                    if ($standing->rank <= 4) $rowClass = 'top-4';
                                                                    elseif ($standing->rank > $totalTeams - 3) $rowClass = 'relegation';
                                                                }
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
                                                                @elseif($isRanking)
                                                                    <td class="team">{{ $standing->team->name ?? '-' }}</td>
                                                                    @if($type === 'f1_drivers' || $isTennis)
                                                                        <td>{{ $standing->country ?? '' }}</td>
                                                                    @endif
                                                                    <td class="num pts">{{ $standing->points }}</td>
                                                                @else
                                                                    <td class="team">{{ $standing->team->name ?? '-' }}</td>
                                                                    <td class="num">{{ $standing->played }}</td>
                                                                    <td class="num">{{ $standing->won }}</td>
                                                                    @if(!$isBasketball)<td class="num">{{ $standing->drawn }}</td>@endif
                                                                    <td class="num">{{ $standing->lost }}</td>
                                                                    @if($isBasketball)
                                                                        <td class="num pts">{{ number_format($standing->win_pct ?? 0, 3) }}</td>
                                                                    @else
                                                                        <td class="num">{{ $standing->goal_diff >= 0 ? '+' : '' }}{{ $standing->goal_diff }}</td>
                                                                        <td class="num pts">{{ $standing->points }}</td>
                                                                    @endif
                                                                @endif
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            @else
                                                <p class="no-data">no standings data</p>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    @php $firstSeason = false; @endphp
                @empty
                    <p class="no-data">no seasons found</p>
                @endforelse
            </div>
            @php $first = false; @endphp
        @endforeach
    </div>

    <script>
        document.querySelectorAll('.tabs-nav label').forEach(tab => {
            tab.addEventListener('click', () => {
                const tabId = tab.dataset.tab;
                document.querySelectorAll('.tabs-nav label').forEach(t => t.classList.remove('tab-active'));
                document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
                tab.classList.add('tab-active');
                document.querySelector(`[data-tab-content="${tabId}"]`).style.display = 'block';
            });
        });
    </script>
</body>
</html>
