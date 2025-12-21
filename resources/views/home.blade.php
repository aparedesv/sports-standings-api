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
                                                <table>
                                                    <thead>
                                                        <tr>
                                                            <th>#</th>
                                                            <th>Team</th>
                                                            <th class="num">P</th>
                                                            <th class="num">W</th>
                                                            <th class="num">D</th>
                                                            <th class="num">L</th>
                                                            <th class="num">+/-</th>
                                                            <th class="num">Pts</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($season->standings->sortBy('rank') as $standing)
                                                            @php
                                                                $totalTeams = $season->standings->count();
                                                                $rowClass = '';
                                                                if ($standing->rank <= 4) $rowClass = 'top-4';
                                                                elseif ($standing->rank > $totalTeams - 3) $rowClass = 'relegation';
                                                            @endphp
                                                            <tr class="{{ $rowClass }}">
                                                                <td class="rank">{{ $standing->rank }}</td>
                                                                <td class="team">{{ $standing->team->name ?? '-' }}</td>
                                                                <td class="num">{{ $standing->played }}</td>
                                                                <td class="num">{{ $standing->won }}</td>
                                                                <td class="num">{{ $standing->drawn }}</td>
                                                                <td class="num">{{ $standing->lost }}</td>
                                                                <td class="num">{{ $standing->goal_diff >= 0 ? '+' : '' }}{{ $standing->goal_diff }}</td>
                                                                <td class="num pts">{{ $standing->points }}</td>
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
