<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=1920">
    <meta http-equiv="refresh" content="3600">
    <title>Cycling Wallpaper {{ $year }}</title>
    @vite('resources/scss/style.scss')
</head>
<body>
    <header class="header">
        <span class="title">// cycling-standings</span>
        <span class="year">{{ $year }}</span>
    </header>

    <div class="wallpaper-grid">
        {{-- COLUMN 1: CLASSICS WITH PODIUM (MEN + WOMEN) --}}
        <section class="section">
            <h2 class="section-title">Monuments & Classics</h2>
            @foreach($classics as $race)
                <div class="subsection">
                    <h3 class="subsection-title">{{ $race['name'] }}</h3>
                    <div class="dual-column">
                        <div class="col">
                            <span class="col-header">Men</span>
                            @foreach($race['men'] as $i => $rider)
                                <div class="rider-row {{ $i === 0 ? 'leader' : 'podium' }}">
                                    <span class="rank">{{ $i + 1 }}.</span>
                                    <span class="name">{{ $rider['rider'] }}</span>
                                    <span class="country">{{ $rider['country'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        @if($race['women'])
                            <div class="col">
                                <span class="col-header">Women</span>
                                @foreach($race['women'] as $i => $rider)
                                    <div class="rider-row {{ $i === 0 ? 'leader' : 'podium' }}">
                                        <span class="rank">{{ $i + 1 }}.</span>
                                        <span class="name">{{ $rider['rider'] }}</span>
                                        <span class="country">{{ $rider['country'] }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </section>

        {{-- COLUMN 2: GRAND TOURS (MEN + WOMEN) --}}
        <section class="section">
            <h2 class="section-title">Grand Tours</h2>

            @foreach($grandTours as $key => $tour)
                <div class="subsection">
                    <h3 class="subsection-title">{{ $tour['name'] }}</h3>
                    <div class="dual-column">
                        <div class="col">
                            <span class="col-header">Men</span>
                            @foreach($tour['men'] as $rider)
                                <div class="rider-row {{ $rider['rank'] == 1 ? 'leader' : ($rider['rank'] <= 3 ? 'podium' : '') }}">
                                    <span class="rank">{{ $rider['rank'] }}.</span>
                                    <span class="name">{{ $rider['rider'] }}</span>
                                    <span class="gap">{{ $rider['gap'] ?: '-' }}</span>
                                </div>
                            @endforeach
                        </div>
                        @if($tour['women'])
                            <div class="col">
                                <span class="col-header">Women</span>
                                @foreach($tour['women'] as $rider)
                                    <div class="rider-row {{ $rider['rank'] == 1 ? 'leader' : ($rider['rank'] <= 3 ? 'podium' : '') }}">
                                        <span class="rank">{{ $rider['rank'] }}.</span>
                                        <span class="name">{{ $rider['rider'] }}</span>
                                        <span class="gap">{{ $rider['gap'] ?: '-' }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </section>

        {{-- COLUMN 3: CYCLOCROSS + MTB --}}
        <section class="section">
            <h2 class="section-title">Cyclocross</h2>

            @if(isset($cyclocross['worlds']))
                <div class="subsection">
                    <h3 class="subsection-title">World Champs - {{ $cyclocross['worlds']['location'] }}</h3>
                    <div class="dual-column">
                        <div class="col">
                            <span class="col-header">Men</span>
                            @foreach($cyclocross['worlds']['men'] as $rider)
                                <div class="rider-row {{ $rider['rank'] == 1 ? 'leader' : ($rider['rank'] <= 3 ? 'podium' : '') }}">
                                    <span class="rank">{{ $rider['rank'] }}.</span>
                                    <span class="name">{{ $rider['rider'] }}</span>
                                    <span class="country">{{ $rider['country'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="col">
                            <span class="col-header">Women</span>
                            @foreach($cyclocross['worlds']['women'] as $rider)
                                <div class="rider-row {{ $rider['rank'] == 1 ? 'leader' : ($rider['rank'] <= 3 ? 'podium' : '') }}">
                                    <span class="rank">{{ $rider['rank'] }}.</span>
                                    <span class="name">{{ $rider['rider'] }}</span>
                                    <span class="country">{{ $rider['country'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            @if(isset($cyclocross['worldcup']))
                <div class="subsection">
                    <h3 class="subsection-title">{{ $cyclocross['worldcup']['name'] }}</h3>
                    <div class="dual-column">
                        <div class="col">
                            <span class="col-header">Men</span>
                            @foreach($cyclocross['worldcup']['men'] as $rider)
                                <div class="rider-row {{ $rider['rank'] == 1 ? 'leader' : ($rider['rank'] <= 3 ? 'podium' : '') }}">
                                    <span class="rank">{{ $rider['rank'] }}.</span>
                                    <span class="name">{{ $rider['rider'] }}</span>
                                    <span class="points">{{ $rider['points'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="col">
                            <span class="col-header">Women</span>
                            @foreach($cyclocross['worldcup']['women'] as $rider)
                                <div class="rider-row {{ $rider['rank'] == 1 ? 'leader' : ($rider['rank'] <= 3 ? 'podium' : '') }}">
                                    <span class="rank">{{ $rider['rank'] }}.</span>
                                    <span class="name">{{ $rider['rider'] }}</span>
                                    <span class="points">{{ $rider['points'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <h2 class="section-title" style="margin-top: 12px;">Mountain Bike</h2>

            @if(isset($mtb['xco']))
                <div class="subsection">
                    <h3 class="subsection-title">{{ $mtb['xco']['name'] }}</h3>
                    <div class="dual-column">
                        <div class="col">
                            <span class="col-header">Men</span>
                            @foreach($mtb['xco']['men'] as $rider)
                                <div class="rider-row {{ $rider['rank'] == 1 ? 'leader' : ($rider['rank'] <= 3 ? 'podium' : '') }}">
                                    <span class="rank">{{ $rider['rank'] }}.</span>
                                    <span class="name">{{ $rider['rider'] }}</span>
                                    <span class="points">{{ $rider['points'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="col">
                            <span class="col-header">Women</span>
                            @foreach($mtb['xco']['women'] as $rider)
                                <div class="rider-row {{ $rider['rank'] == 1 ? 'leader' : ($rider['rank'] <= 3 ? 'podium' : '') }}">
                                    <span class="rank">{{ $rider['rank'] }}.</span>
                                    <span class="name">{{ $rider['rider'] }}</span>
                                    <span class="points">{{ $rider['points'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            @if(isset($mtb['dh']))
                <div class="subsection">
                    <h3 class="subsection-title">{{ $mtb['dh']['name'] }}</h3>
                    <div class="dual-column">
                        <div class="col">
                            <span class="col-header">Men</span>
                            @foreach($mtb['dh']['men'] as $rider)
                                <div class="rider-row {{ $rider['rank'] == 1 ? 'leader' : ($rider['rank'] <= 3 ? 'podium' : '') }}">
                                    <span class="rank">{{ $rider['rank'] }}.</span>
                                    <span class="name">{{ $rider['rider'] }}</span>
                                    <span class="points">{{ $rider['points'] }}</span>
                                </div>
                            @endforeach
                        </div>
                        <div class="col">
                            <span class="col-header">Women</span>
                            @foreach($mtb['dh']['women'] as $rider)
                                <div class="rider-row {{ $rider['rank'] == 1 ? 'leader' : ($rider['rank'] <= 3 ? 'podium' : '') }}">
                                    <span class="rank">{{ $rider['rank'] }}.</span>
                                    <span class="name">{{ $rider['rider'] }}</span>
                                    <span class="points">{{ $rider['points'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </section>
    </div>
</body>
</html>
