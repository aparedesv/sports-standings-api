# Cycling Standings - Progress

## Resum del Projecte

Aplicació dedicada exclusivament al ciclisme professional amb **wallpaper dinàmic 1920x1080**.

### Contingut:
- **Road Cycling**: 3 Grans Voltes (Giro, Tour, Vuelta) + 8 Clàssiques (5 Monuments + 3 altres)
- **Ciclocross (CX)**: World Championships + UCI World Cup
- **Mountain Bike (MTB/BTT)**: XCO World Cup + Downhill World Cup

### Característiques:
- Layout 3 columnes (Classics | Grand Tours | CX+MTB)
- Dual-column men/women per totes les seccions
- Auto-refresh cada hora (meta refresh)
- Dades 2025 actualitzades
- Disseny compacte per wallpaper (sense scroll)

Utilitza fitxers JSON locals per emmagatzemar les dades de ciclisme.

---

## 1. Configuració Inicial

### Laravel Sanctum (Autenticació API)
- [x] Instal·lat `laravel/sanctum`
- [x] Publicada configuració i migracions
- [x] Configurat trait `HasApiTokens` al model `User`
- [x] Creat `AuthController` amb endpoints:
  - `POST /api/register` - Registrar usuari
  - `POST /api/login` - Iniciar sessió
  - `POST /api/logout` - Tancar sessió
  - `GET /api/user` - Obtenir usuari actual

---

## 2. Models i Base de Dades

### Models creats:
| Model | Taula | Descripció |
|-------|-------|------------|
| Country | countries | Països |
| League | leagues | Lligues (La Liga, Premier, etc.) |
| Season | seasons | Temporades per lliga |
| Team | teams | Equips |
| Fixture | fixtures | Partits |
| Standing | standings | Classificacions |

### Migracions:
- `countries` - name, code, flag
- `leagues` - external_id, name, type, logo, country_id
- `seasons` - league_id, year, start, end, current
- `teams` - external_id, name, code, country_id, founded, logo, venue_name, venue_city
- `fixtures` - external_id, league_id, season_id, home_team_id, away_team_id, date, status, scores, venue, referee, round
- `standings` - league_id, season_id, team_id, rank, points, played, won, drawn, lost, goals_for, goals_against, goal_diff, form, description

### Relacions:
- Country hasMany Leagues, Teams
- League belongsTo Country, hasMany Seasons, Fixtures, Standings
- Season belongsTo League, hasMany Fixtures, Standings
- Team belongsTo Country, hasMany Fixtures (home/away), Standings
- Fixture belongsTo League, Season, HomeTeam, AwayTeam
- Standing belongsTo League, Season, Team

---

## 3. Font de Dades: openfootball/football.json

### Característiques:
- Dades obertes i gratuïtes de GitHub
- No requereix API key
- Inclou temporada actual (2025-26)
- Actualitzacions regulars de la comunitat
- Cache local d'1 hora per reduir peticions
- URL base: `https://raw.githubusercontent.com/openfootball/football.json/master/{season}/{league}.json`

### Lligues disponibles:
| Codi | Lliga | País |
|------|-------|------|
| es.1 | La Liga | Spain |
| es.2 | Segunda División | Spain |
| en.1 | Premier League | England |
| en.2 | Championship | England |
| de.1 | Bundesliga | Germany |
| de.2 | 2. Bundesliga | Germany |
| it.1 | Serie A | Italy |
| it.2 | Serie B | Italy |
| fr.1 | Ligue 1 | France |
| fr.2 | Ligue 2 | France |
| pt.1 | Primeira Liga | Portugal |
| nl.1 | Eredivisie | Netherlands |
| be.1 | First Division A | Belgium |
| at.1 | Bundesliga | Austria |
| ch.1 | Super League | Switzerland |

### Temporades disponibles:
- 2025-26 (actual)
- 2024-25
- 2023-24
- 2022-23
- 2021-22
- 2020-21

### Servei:
- `App\Services\FootballJsonService` amb mètodes:
  - `getLeagueData(leagueCode, season)` - Obtenir dades JSON
  - `getTeams(leagueCode, season)` - Llistar equips
  - `getMatches(leagueCode, season)` - Llistar partits
  - `calculateStandings(leagueCode, season)` - Calcular classificació
  - `getAvailableLeagues()` - Lligues disponibles
  - `getAvailableSeasons()` - Temporades disponibles
  - `clearCache(leagueCode, season)` - Netejar cache

### Comandes de Sincronització:
```bash
# Sincronització completa (totes les lligues)
php artisan sync:all                          # Sincronitza tot (temporada actual 2025-26)
php artisan sync:all --season=2025-26         # Sincronitza temporada específica
php artisan sync:all --league=es.1            # Sincronitza només una lliga
php artisan sync:all --league=es.1 --season=2025-26  # Lliga i temporada específiques

# Comandes individuals
php artisan sync:leagues                      # Sincronitza totes les lligues
php artisan sync:leagues --country=Spain      # Sincronitza lligues d'un país
php artisan sync:teams es.1 --season=2024-25  # Sincronitza equips La Liga
php artisan sync:fixtures es.1 --season=2024-25  # Sincronitza partits
php artisan sync:standings es.1 --season=2024-25 # Sincronitza classificació
```

### Scheduler (Sincronització Automàtica):
La sincronització s'executa automàticament cada dia a les 6:00 AM.

**Configuració a `routes/console.php`:**
```php
Schedule::command('sync:all')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/sync-all.log'));
```

**Activar scheduler al servidor (crontab):**
```bash
* * * * * cd /var/www/html/sport-standings-api && php artisan schedule:run >> /dev/null 2>&1
```

**Comandes scheduler:**
```bash
php artisan schedule:list    # Veure tasques programades
php artisan schedule:run     # Executar manualment
```

---

## 4. Endpoints de l'API

### Auth (públics):
| Mètode | Endpoint | Descripció |
|--------|----------|------------|
| POST | /api/register | Registrar usuari |
| POST | /api/login | Iniciar sessió |

### Protegits (requereixen token + permisos):
| Mètode | Endpoint | Permís | Descripció |
|--------|----------|--------|------------|
| GET | /api/user | - | Usuari actual |
| POST | /api/logout | - | Tancar sessió |
| GET | /api/countries | read_countries | Llistar països |
| GET | /api/countries/{id} | read_countries | Detall país |
| GET | /api/leagues | read_leagues | Llistar lligues |
| GET | /api/leagues/{id} | read_leagues | Detall lliga |
| GET | /api/teams | read_teams | Llistar equips |
| GET | /api/teams/{id} | read_teams | Detall equip |
| GET | /api/fixtures | read_fixtures | Llistar partits |
| GET | /api/fixtures/live | read_fixtures | Partits en directe |
| GET | /api/fixtures/today | read_fixtures | Partits d'avui |
| GET | /api/fixtures/{id} | read_fixtures | Detall partit |
| GET | /api/standings | read_standings | Llistar classificacions |
| GET | /api/standings/league/{id} | read_standings | Classificació per lliga |

### Paràmetres de filtre:
- **leagues**: `country_id`, `type`
- **teams**: `country_id`, `search`
- **fixtures**: `league_id`, `season_id`, `team_id`, `date`, `status`, `from`, `to`
- **standings**: `league_id`, `season_id`

---

## 5. Sistema de Permisos (Spatie Laravel Permission)

### Instal·lació:
- [x] Instal·lat `spatie/laravel-permission`
- [x] Publicada configuració i migracions
- [x] Configurat trait `HasRoles` al model `User`

### Fitxers creats:
- `app/constants.php` - Definició d'entitats i accions
- `app/Http/Middleware/PermissionMiddleware.php` - Verificació de permisos
- `app/Http/Middleware/UserStatusMiddleware.php` - Verificació usuari actiu
- `config/roles.php` - Configuració de rols i permisos
- `database/seeders/RolesAndPermissionsSeeder.php` - Seeder de permisos

### Entitats amb permisos (9):
- users, role_permissions
- countries, leagues, seasons, teams, fixtures, standings
- settings

### Accions (4):
- read, create, update, delete

### Total: 36 permisos (9 entitats × 4 accions)

### Rols configurats:
| Rol | Permisos | Descripció |
|-----|----------|------------|
| super_admin | Tots (Gate::before) | Accés total sense restriccions |
| admin | 36 | Gestió completa del sistema |
| editor | 20 | Edició sense eliminar ni gestionar usuaris |
| user | 6 | Només lectura de dades esportives |
| guest | 4 | Lectura mínima (lligues, equips, partits, classificacions) |

### Middleware registrats a `bootstrap/app.php`:
```php
$middleware->alias([
    'permission' => \App\Http\Middleware\PermissionMiddleware::class,
    'userStatus' => \App\Http\Middleware\UserStatusMiddleware::class,
]);
```

### Gate super_admin a `AppServiceProvider`:
```php
Gate::before(function ($user, $ability) {
    return $user->hasRole('super_admin') ? true : null;
});
```

---

## 6. Usuari Super Admin

- **Email:** info@osonaweb.cat
- **Password:** admin1234
- **Rol:** super_admin (accés total)

### Seeder:
```bash
php artisan db:seed --class=SuperAdminSeeder
```

---

## 7. Estructura de Fitxers

```
sport-standings-api/
├── app/
│   ├── Console/Commands/
│   │   ├── SyncAll.php
│   │   ├── SyncBasketball.php            # Sync/verify NBA data
│   │   ├── SyncEuroleague.php            # Sync/verify Euroleague/Eurocup data
│   │   ├── SyncCycling.php               # Sync/verify Cycling data
│   │   ├── SyncLeagues.php
│   │   ├── SyncTeams.php
│   │   ├── SyncFixtures.php
│   │   └── SyncStandings.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php
│   │   │   │   ├── CountryController.php
│   │   │   │   ├── LeagueController.php
│   │   │   │   ├── TeamController.php
│   │   │   │   ├── FixtureController.php
│   │   │   │   └── StandingController.php
│   │   │   ├── HomeController.php          # Vista pública
│   │   │   └── RequestDocsController.php   # Override per exclude_http_methods
│   │   └── Middleware/
│   │       ├── PermissionMiddleware.php
│   │       └── UserStatusMiddleware.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Country.php
│   │   ├── League.php
│   │   ├── Season.php
│   │   ├── Team.php
│   │   ├── Fixture.php
│   │   └── Standing.php
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   ├── Services/
│   │   ├── EspnFootballService.php       # ESPN API futbol
│   │   ├── EspnNflService.php            # ESPN API NFL
│   │   ├── EspnF1Service.php             # ESPN API Formula 1
│   │   ├── EspnTennisService.php         # ESPN API Tennis
│   │   ├── EuroleagueApiService.php      # Euroleague API bàsquet europeu
│   │   ├── CyclingApiService.php         # TheSportsDB + LeTourDataSet
│   │   ├── FootballJsonService.php       # openfootball (API interna)
│   │   └── NbaApiService.php             # ESPN API bàsquet
│   └── constants.php
├── config/
│   ├── permission.php
│   ├── request-docs.php
│   ├── roles.php
│   └── services.php
├── database/
│   ├── migrations/
│   │   ├── *_create_countries_table.php
│   │   ├── *_create_leagues_table.php
│   │   ├── *_create_seasons_table.php
│   │   ├── *_create_teams_table.php
│   │   ├── *_create_fixtures_table.php
│   │   ├── *_create_standings_table.php
│   │   └── *_create_permission_tables.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── SuperAdminSeeder.php
│       ├── RolesAndPermissionsSeeder.php
│       └── LeaguesSeeder.php
├── docs/
│   ├── insomnia.json
│   └── progress.md
├── resources/
│   ├── scss/
│   │   └── style.scss                      # Estils SCSS
│   └── views/
│       └── home.blade.php                  # Vista pública
├── routes/
│   ├── api.php
│   ├── web.php               # Ruta home
│   └── console.php           # Scheduler configuration
└── storage/
    ├── app/
    │   └── cycling/
    │       ├── giro_gc.json          # Giro GC (2024, 2025)
    │       ├── giro_stages.json      # Giro Stages (2024, 2025)
    │       ├── vuelta_gc.json        # Vuelta GC (2024, 2025)
    │       ├── vuelta_stages.json    # Vuelta Stages (2024, 2025)
    │       ├── classics.json         # Monuments i clàssiques (2022-2025)
    │       ├── cyclocross.json       # CX World Cup + World Championships
    │       └── mtb.json              # MTB XCO + Downhill + Olympics
    └── logs/
        └── sync-all.log      # Sync logs
```

---

## 8. Documentació API (Laravel Request Docs)

### Instal·lació:
- [x] Instal·lat `rakutentech/laravel-request-docs`
- [x] Publicada configuració
- [x] Registrades rutes explícites a `routes/web.php`

### Accés:
```
http://localhost:8000/request-docs
```

### Endpoints disponibles:
| URL | Descripció |
|-----|------------|
| `/request-docs` | Interfície interactiva de documentació |
| `/request-docs/api` | Llistat JSON de tots els endpoints |
| `/request-docs/config` | Configuració actual |
| `/request-docs/api?openapi=true` | Exportar OpenAPI 3.0 JSON |

### Característiques:
- Documentació automàtica de totes les rutes API
- Interfície interactiva per testejar endpoints
- Compatible amb OpenAPI 3.0
- Suport per Bearer token authentication

### Exportar OpenAPI:
```bash
php artisan lrd:export    # Genera api.json
```

### Configuració (`config/request-docs.php`):
- Títol: "Sport Standings API - Documentation"
- Només rutes `/api/*`
- Autenticació: Bearer token

### Controller personalitzat:
El package original no aplica `exclude_http_methods` a la vista principal (només a l'export OpenAPI). S'ha creat un controller override per solucionar-ho.

**`app/Http/Controllers/RequestDocsController.php`:**
- Estén `LaravelRequestDocsController`
- Llegeix `exclude_http_methods` de la config i l'aplica a totes les vistes
- Elimina rutes HEAD duplicades (Laravel auto-registra HEAD per cada GET)

### Rutes (`routes/web.php`):
```php
use App\Http\Controllers\RequestDocsController;

Route::get('request-docs', [RequestDocsController::class, 'index'])->name('request-docs.index');
Route::get('request-docs/api', [RequestDocsController::class, 'api'])->name('request-docs.api');
Route::get('request-docs/config', [RequestDocsController::class, 'config'])->name('request-docs.config');
Route::get('request-docs/_astro/{slug}', [RequestDocsController::class, 'assets'])
    ->where('slug', '.*js|.*css|.*png|.*jpg|.*jpeg|.*gif|.*svg|.*ico|.*woff|.*woff2|.*ttf|.*eot|.*otf|.*map')
    ->name('request-docs.assets');
```

### Configuració `exclude_http_methods`:
```php
// config/request-docs.php
'open_api' => [
    'exclude_http_methods' => ['HEAD'],  // Ara s'aplica globalment
]
```

### Troubleshooting - Error JSON.parse:
Si apareix l'error `JSON.parse: unexpected character at line 1 column 1`:

**Causa:** Els assets publicats a `public/request-docs/` interfereixen amb les rutes de Laravel.

**Solució:**
```bash
# Eliminar assets estàtics publicats
rm -rf public/request-docs/

# Netejar caches
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

**Important:** NO executar `php artisan vendor:publish --tag=request-docs-assets` ja que publica fitxers estàtics que conflicten amb les rutes.

---

## 9. Dades Sincronitzades (2025-26)

### Estadístiques actuals:
| Mètrica | Valor |
|---------|-------|
| Lligues | 15 |
| Temporades | 15 (2025-26) |
| Partits | 4.742 |
| Equips | ~280 |

### Partits per lliga (2025-26):
| Lliga | País | Partits |
|-------|------|---------|
| La Liga | Spain | 380 |
| Segunda División | Spain | 462 |
| Premier League | England | 380 |
| Championship | England | 552 |
| Bundesliga | Germany | 306 |
| 2. Bundesliga | Germany | 306 |
| Serie A | Italy | 380 |
| Serie B | Italy | 380 |
| Ligue 1 | France | 306 |
| Ligue 2 | France | 306 |
| Primeira Liga | Portugal | 306 |
| Eredivisie | Netherlands | 306 |
| First Division A | Belgium | 240 |
| Bundesliga | Austria | 132 |

### Season IDs (2025-26):
| Lliga | Season ID |
|-------|-----------|
| La Liga | 317 |
| Premier League | 319 |
| Bundesliga | 321 |
| Serie A | 323 |
| Ligue 1 | 325 |

### Exemple consulta API:
```bash
# Classificació La Liga 2025-26
GET /api/standings?season_id=317

# Classificació Premier League 2025-26
GET /api/standings?season_id=319

# Partits La Liga 2025-26
GET /api/fixtures?season_id=317
```

---

## 10. ESPN API Integration

### Descripció:
La vista pública utilitza l'API d'ESPN per obtenir dades en temps real de múltiples esports (futbol, bàsquet, NFL, F1, tennis). Això elimina la necessitat de sincronització manual i proporciona classificacions sempre actualitzades.

### API Base URLs:
```
Football:   https://site.web.api.espn.com/apis/v2/sports/soccer/{league}/standings
Basketball: https://site.api.espn.com/apis/v2/sports/basketball/nba/standings
NFL:        https://site.api.espn.com/apis/v2/sports/football/nfl/standings
F1:         https://site.web.api.espn.com/apis/v2/sports/racing/f1/standings
Tennis:     https://site.api.espn.com/apis/site/v2/sports/tennis/atp/rankings
```

### Avantatges vs openfootball:
- Dades en **temps real** (cache 30 min)
- Sense sincronització manual
- Inclou competicions europees (Champions, Europa League)
- Sense API key requerida

### Serveis creats:

#### EspnFootballService
| Mètode | Descripció |
|--------|------------|
| `getStandings($leagueCode)` | Obtenir classificació d'una lliga |
| `getAllStandings()` | Obtenir totes les classificacions |
| `clearCache($leagueCode)` | Netejar cache |

#### NbaApiService
| Mètode | Descripció |
|--------|------------|
| `getStandings()` | Obtenir classificacions NBA |
| `getCurrentSeason()` | Temporada actual |
| `clearCache()` | Netejar cache |

#### EspnNflService
| Mètode | Descripció |
|--------|------------|
| `getStandings()` | Obtenir classificacions NFL (AFC/NFC) |
| `getCurrentSeason()` | Temporada actual |
| `clearCache()` | Netejar cache |

#### EspnF1Service
| Mètode | Descripció |
|--------|------------|
| `getStandings()` | Obtenir classificacions F1 (pilots i constructors) |
| `getCurrentSeason()` | Temporada actual |
| `clearCache()` | Netejar cache |

#### EspnTennisService
| Mètode | Descripció |
|--------|------------|
| `getRankings($tour)` | Obtenir rànquing ATP o WTA |
| `getAtpRankings()` | Rànquing ATP (top 50) |
| `getWtaRankings()` | Rànquing WTA |
| `clearCache($tour)` | Netejar cache |

#### EuroleagueApiService
| Mètode | Descripció |
|--------|------------|
| `getStandings($competition, $seasonCode)` | Classificació Euroleague/Eurocup |
| `getTeams($competition, $seasonCode)` | Equips amb informació |
| `getAllStandings()` | Totes les classificacions |
| `getCurrentSeasonCode($competition)` | Codi temporada actual (E2024, U2024) |
| `getAvailableCompetitions()` | Competicions disponibles |
| `getAvailableSeasons($competition)` | Temporades disponibles |
| `clearCache($competition, $seasonCode)` | Netejar cache |

#### CyclingApiService
| Mètode | Descripció |
|--------|------------|
| **Road - Grand Tours** | |
| `getTourDeFranceGC($year)` | Classificació general Tour de France |
| `getTourDeFranceStages($year)` | Resultats etapes Tour de France |
| `getGiroGC($year)` | Classificació general Giro d'Italia |
| `getGiroStages($year)` | Resultats etapes Giro d'Italia |
| `getVueltaGC($year)` | Classificació general Vuelta a España |
| `getVueltaStages($year)` | Resultats etapes Vuelta a España |
| `getAllGrandToursGC($year)` | GC de les 3 grans voltes |
| **Road - Classics** | |
| `getClassics($year)` | Totes les clàssiques (Monuments + altres) |
| `getMonuments($year)` | Només els 5 Monuments |
| `getClassicResult($raceId, $year)` | Resultat d'una clàssica específica |
| **Ciclocross (CX)** | |
| `getCyclocross($season)` | Totes les dades CX d'una temporada |
| `getCyclocrossWorldCup($season)` | UCI CX World Cup (standings + race winners) |
| `getCyclocrossWorldChampionships($year)` | CX World Championships (podiums) |
| **Mountain Bike (MTB)** | |
| `getMtbXcoWorldCup($year)` | MTB XCO World Cup standings |
| `getMtbDownhillWorldCup($year)` | MTB Downhill World Cup standings |
| `getMtbWorldChampionships($year)` | MTB World Championships |
| `getMtbOlympics()` | MTB Olympic Games results |
| **Utils** | |
| `getAvailableYears()` | Anys disponibles (2010-actual) |
| `clearCache($year)` | Netejar cache |

### Competicions de Futbol (10):
| Codi ESPN | Lliga | País |
|-----------|-------|------|
| `uefa.champions` | Champions League | Europe |
| `uefa.europa` | Europa League | Europe |
| `eng.1` | Premier League | England |
| `ger.1` | Bundesliga | Germany |
| `ita.1` | Serie A | Italy |
| `esp.1` | La Liga | Spain |
| `fra.1` | Ligue 1 | France |
| `ned.1` | Eredivisie | Netherlands |
| `por.1` | Primeira Liga | Portugal |
| `bel.1` | Pro League | Belgium |

### Bàsquet:
**NBA:**
- Eastern Conference (15 equips)
- Western Conference (15 equips)
- Ordenat per WIN%

**Euroleague/Eurocup:**
- Euroleague (18 equips)
- Eurocup Group A (10 equips)
- Eurocup Group B (10 equips)
- Ordenat per victòries

### NFL:
- AFC Conference (16 equips)
- NFC Conference (16 equips)
- Estadístiques: W, L, T, PCT
- Ordenat per WIN%

### Formula 1:
- Drivers Championship (top 20 pilots)
- Constructors Championship (10 equips)
- Estadístiques: Posició, Nom, País, Punts

### Tennis:
- **ATP Rankings** - Top 50 masculí
- **WTA Rankings** - Top 50 femení
- Estadístiques: Posició, Nom, País, Punts

### Ciclisme (3 Grans Voltes):
**Fonts de dades:**
- **TheSportsDB** - Calendari UCI World Tour
- **LeTourDataSet** (GitHub) - Resultats Tour de France
- **Fitxers JSON locals** - Giro d'Italia i Vuelta a España

**Giro d'Italia 2025:**
- Classificació General (top 10)
- Resultats per etapes (21 etapes)
- Guanyador: Simon Yates (Visma-Lease a Bike)
- Distància: 3.445 km

**Tour de France 2025:**
- Classificació General (top 10)
- Resultats per etapes (21 etapes)
- Guanyador: Tadej Pogacar (UAE Team Emirates)
- Distància: 3.323 km

**Vuelta a España 2025:**
- Classificació General (top 10)
- Resultats per etapes (21 etapes)
- Guanyador: Jonas Vingegaard (Visma-Lease a Bike)
- Distància: 3.300 km

**Dades disponibles:**
- Rank, Rider, Team, Time, Gap (GC)
- Stage, Winner, Route, Leader Jersey (Stages)

**Fitxers JSON:**
- `storage/app/cycling/giro_gc.json` - GC Giro (2024, 2025)
- `storage/app/cycling/giro_stages.json` - Etapes Giro (2024, 2025)
- `storage/app/cycling/vuelta_gc.json` - GC Vuelta (2024, 2025)
- `storage/app/cycling/vuelta_stages.json` - Etapes Vuelta (2024, 2025)
- `storage/app/cycling/classics.json` - Monuments i clàssiques (2022-2025)

### Monuments (5 Grans Clàssiques):
| Cursa | Sobrenom | País | Distància |
|-------|----------|------|-----------|
| Milan-San Remo | La Primavera | Italy | 289 km |
| Tour of Flanders | De Ronde | Belgium | 269 km |
| Paris-Roubaix | L'Enfer du Nord | France | 259 km |
| Liège-Bastogne-Liège | La Doyenne | Belgium | 252 km |
| Il Lombardia | La Classica delle Foglie Morte | Italy | 252 km |

### Altres Clàssiques:
| Cursa | Sobrenom | País | Distància |
|-------|----------|------|-----------|
| Strade Bianche | La Classica del Sterrato | Italy | 184 km |
| Gent-Wevelgem | De Witte Molenrace | Belgium | 261 km |
| Amstel Gold Race | Dutch Monument | Netherlands | 254 km |
| La Flèche Wallonne | Mur de Huy | Belgium | 194 km |

**Guanyadors 2025 (8 curses):**
| Cursa | Guanyador | 2n | 3r |
|-------|-----------|----|----|
| Strade Bianche | Tadej Pogacar | Tom Pidcock | Tim Wellens |
| Milan-San Remo | Mathieu van der Poel | Filippo Ganna | Tadej Pogacar |
| Gent-Wevelgem | Mads Pedersen | Tim Merlier | Jonathan Milan |
| Tour of Flanders | Tadej Pogacar | Mads Pedersen | M. van der Poel |
| Paris-Roubaix | Mathieu van der Poel | Tadej Pogacar | Mads Pedersen |
| Amstel Gold Race | Mattias Skjelmose | Tadej Pogacar | Remco Evenepoel |
| La Flèche Wallonne | Tadej Pogacar | Kevin Vauquelin | Tom Pidcock |
| Liège-Bastogne-Liège | Tadej Pogacar | Giulio Ciccone | Ben Healy |

### Ciclocross (CX):

**Fitxer JSON:** `storage/app/cycling/cyclocross.json`

**World Championships:**
| Any | Localització | Men Elite | Women Elite |
|-----|--------------|-----------|-------------|
| 2025 | Liévin, France | Mathieu van der Poel | Fem van Empel |
| 2024 | Tábor, Czech Republic | Mathieu van der Poel | Fem van Empel |
| 2023 | Hoogerheide, Netherlands | Mathieu van der Poel | Fem van Empel |

**UCI World Cup 2024-25:**
| Categoria | Líder | País | Punts |
|-----------|-------|------|-------|
| Men Elite | Michael Vanthourenhout | BEL | 284 |
| Women Elite | Lucinda Brand | NED | 310 |

**Top 5 Men World Cup 2024-25:**
| Rank | Rider | Country | Points |
|------|-------|---------|--------|
| 1 | Michael Vanthourenhout | BEL | 284 |
| 2 | Eli Iserbyt | BEL | 257 |
| 3 | Niels Vandeputte | BEL | 226 |
| 4 | Thibau Nys | BEL | 210 |
| 5 | Laurens Sweeck | BEL | 186 |

**Top 5 Women World Cup 2024-25:**
| Rank | Rider | Country | Points |
|------|-------|---------|--------|
| 1 | Lucinda Brand | NED | 310 |
| 2 | Fem van Empel | NED | 285 |
| 3 | Ceylin del Carmen Alvarado | NED | 248 |
| 4 | Puck Pieterse | NED | 230 |
| 5 | Marie Schreiber | LUX | 195 |

### Mountain Bike (MTB/BTT):

**Fitxer JSON:** `storage/app/cycling/mtb.json`

**XCO World Cup 2025:**
| Categoria | Campió | País | Punts |
|-----------|--------|------|-------|
| Men Elite | Christopher Blevins | USA | 1680 |
| Women Elite | Samara Maxwell | AUS | 2103 |

**Top 10 Men XCO World Cup 2025:**
| Rank | Rider | Country | Points |
|------|-------|---------|--------|
| 1 | Christopher Blevins | USA | 1680 |
| 2 | Martin Vidaurre | CHI | 1575 |
| 3 | Luca Martin | FRA | 1285 |
| 4 | Fabio Püntener | SUI | 1240 |
| 5 | Luca Braidot | ITA | 1041 |
| 6 | Mathis Azzaro | FRA | 1021 |
| 7 | Simone Avondetto | ITA | 1020 |
| 8 | Victor Koretzky | FRA | 1010 |
| 9 | Luca Schwarzbauer | GER | 937 |
| 10 | Charlie Aldridge | GBR | 916 |

**Top 10 Women XCO World Cup 2025:**
| Rank | Rider | Country | Points |
|------|-------|---------|--------|
| 1 | Samara Maxwell | AUS | 2103 |
| 2 | Jenny Rissveds | SWE | 1876 |
| 3 | Alessandra Keller | SUI | 1654 |
| 4 | Evie Richards | GBR | 1457 |
| 5 | Puck Pieterse | NED | 1398 |
| 6 | Laura Stigger | AUT | 1287 |
| 7 | Haley Batten | USA | 1198 |
| 8 | Candice Lill | RSA | 1076 |
| 9 | Anne Terpstra | NED | 987 |
| 10 | Loana Lecomte | FRA | 923 |

**Downhill World Cup 2025:**
| Categoria | Campió | País | Punts |
|-----------|--------|------|-------|
| Men Elite | Jackson Goldstone | CAN | 1946 |
| Women Elite | Tahnée Seagrave | GBR | 1812 |

**Top 10 Men DH World Cup 2025:**
| Rank | Rider | Country | Points |
|------|-------|---------|--------|
| 1 | Jackson Goldstone | CAN | 1946 |
| 2 | Loïc Bruni | FRA | 1768 |
| 3 | Loris Vergier | FRA | 1425 |
| 4 | Luca Shaw | USA | 1366 |
| 5 | Dakotah Norton | USA | 1245 |
| 6 | Amaury Pierron | FRA | 1123 |
| 7 | Troy Brosnan | AUS | 1087 |
| 8 | Finn Iles | CAN | 1023 |
| 9 | Benoit Coulanges | FRA | 956 |
| 10 | Andreas Kolb | AUT | 912 |

**Top 10 Women DH World Cup 2025:**
| Rank | Rider | Country | Points |
|------|-------|---------|--------|
| 1 | Tahnée Seagrave | GBR | 1812 |
| 2 | Nina Hoffmann | GER | 1756 |
| 3 | Vali Höll | AUT | 1654 |
| 4 | Marine Cabirou | FRA | 1387 |
| 5 | Gracey Hemstreet | CAN | 1198 |
| 6 | Myriam Nicole | FRA | 1054 |
| 7 | Monika Hrastnik | SLO | 978 |
| 8 | Eleonora Farina | ITA | 923 |
| 9 | Anna Newkirk | USA | 867 |
| 10 | Pomme Cidre | FRA | 756 |

### Bàsquet Europeu (Euroleague API):

**API Base URL:** `https://api-live.euroleague.net/v1`

| Competició | Season Code | Equips |
|------------|-------------|--------|
| Euroleague | E2024 (2024-25) | 18 |
| Eurocup | U2024 (2024-25) | 20 |

**Endpoints disponibles:**
- `/v1/standings?seasonCode=E2024` - Classificació
- `/v1/teams?seasonCode=E2024` - Equips amb plantilles
- `/v1/games?seasonCode=E2024&gameCode=X` - Box score
- `/v1/results?seasonCode=E2024` - Resultats
- `/v1/schedules?seasonCode=E2024` - Calendari

**Equips Euroleague 2024-25:**
Real Madrid, FC Barcelona, Baskonia, Olympiacos, Panathinaikos, Fenerbahçe, Anadolu Efes, AS Monaco, EA7 Milan, Virtus Bologna, Bayern Munich, ALBA Berlin, Maccabi Tel Aviv, Paris Basketball, Partizan, Crvena Zvezda, LDLC ASVEL, Zalgiris

### Comandes:
```bash
# Verificar dades NBA
php artisan sync:basketball
php artisan sync:basketball --clear-cache

# Verificar dades Euroleague/Eurocup
php artisan sync:euroleague                              # Totes les competicions
php artisan sync:euroleague --competition=euroleague     # Només Euroleague
php artisan sync:euroleague --competition=eurocup        # Només Eurocup
php artisan sync:euroleague --season=E2024               # Temporada específica
php artisan sync:euroleague --teams                      # Mostrar equips
php artisan sync:euroleague --clear-cache                # Netejar cache

# Verificar dades Ciclisme (3 Grans Voltes)
php artisan sync:cycling                                 # Tot (GC + stages de les 3 voltes)
php artisan sync:cycling --race=giro                     # Només Giro d'Italia
php artisan sync:cycling --race=tour                     # Només Tour de France
php artisan sync:cycling --race=vuelta                   # Només Vuelta a España
php artisan sync:cycling --year=2024                     # Any específic
php artisan sync:cycling --clear-cache                   # Netejar cache

# Netejar cache ESPN
php artisan cache:clear
```

---

## 11. Wallpaper Dinàmic (Home)

### Descripció:
Wallpaper dinàmic 1920x1080 que mostra totes les classificacions de ciclisme (Road + CX + MTB) en una sola pantalla sense scroll. Auto-refresh cada hora.

### Accés:
```
http://localhost:8000/
```

### Layout (3 columnes):
```
┌─────────────────┬─────────────────┬─────────────────┐
│   MONUMENTS &   │   GRAND TOURS   │   CX + MTB      │
│    CLASSICS     │                 │                 │
├─────────────────┼─────────────────┼─────────────────┤
│ Strade Bianche  │ GIRO D'ITALIA   │ CX WORLD CHAMPS │
│ Men    | Women  │ Men    | Women  │ Men    | Women  │
│                 │                 │                 │
│ Milan-San Remo  │ TOUR DE FRANCE  │ CX WORLD CUP    │
│ Men    | Women  │ Men    | Women  │ Men    | Women  │
│                 │                 │                 │
│ Gent-Wevelgem   │ VUELTA A ESPAÑA │ XCO WORLD CUP   │
│ Men    | Women  │ Men    | Women  │ Men    | Women  │
│        ...      │                 │                 │
│ (8 curses)      │                 │ DH WORLD CUP    │
│                 │                 │ Men    | Women  │
└─────────────────┴─────────────────┴─────────────────┘
```

### Característiques:
- **Resolució**: 1920x1080 (Full HD)
- **Layout**: CSS Grid 3 columnes
- **Dual-column**: Men/Women per cada secció
- **Auto-refresh**: Meta refresh cada hora
- **Tema**: GitHub dark (fons #0d1117)
- **Font**: Monospace 11px (estil terminal)
- **Sense scroll**: Tot visible en una pantalla

### Dades mostrades:
| Secció | Contingut |
|--------|-----------|
| Classics | 8 curses × podium (top 3) men + women |
| Giro GC | Top 10 men + Top 10 women |
| Tour GC | Top 10 men + Top 10 women |
| Vuelta GC | Top 10 men + Top 10 women |
| CX Worlds | Top 10 men + Top 10 women |
| CX World Cup | Top 10 men + Top 10 women |
| MTB XCO | Top 10 men + Top 10 women |
| MTB DH | Top 10 men + Top 10 women |

### Fitxers:
| Fitxer | Descripció |
|--------|------------|
| `app/Http/Controllers/HomeController.php` | Controller amb lògica de dades |
| `app/Services/CyclingApiService.php` | Servei dades ciclisme |
| `resources/views/home.blade.php` | Vista Blade wallpaper |
| `resources/scss/style.scss` | Estils SCSS wallpaper |

### Assets (Vite + SCSS):
```bash
npm run dev    # Desenvolupament
npm run build  # Producció
```

### Variables SCSS principals:
```scss
$color-bg: #0d1117;
$color-bg-secondary: #161b22;
$color-primary: #58a6ff;
$color-accent: #f0883e;
$color-success: #3fb950;
$color-gold: #ffd700;
```

---

## 12. Pròxims Passos Suggerits

- [ ] Afegir WebSockets per resultats en temps real
- [ ] Crear dashboard amb estadístiques
- [ ] Afegir tests unitaris i d'integració
- [ ] Implementar rate limiting
- [ ] Afegir suport per múltiples idiomes
- [x] Afegir WTA (tennis femení) - **Completat**
- [x] Integració Euroleague API (bàsquet europeu) - **Completat (Euroleague + Eurocup)**
- [x] Programar sincronització automàtica (scheduler) - **Completat**
- [x] Documentar API amb OpenAPI/Swagger - **Completat (request-docs)**
- [x] Suport temporada 2025-26 - **Completat**
- [x] Sincronitzar totes les lligues 2025-26 - **Completat (15 lligues, 4.742 partits)**
- [x] Eliminar rutes HEAD duplicades de request-docs - **Completat (controller override)**
- [x] Vista pública amb classificacions - **Completat (tabs + acordions)**
- [x] Integració ESPN API (futbol) - **Completat (10 competicions)**
- [x] Integració ESPN API (bàsquet/NBA) - **Completat (30 equips)**
- [x] Integració ESPN API (NFL) - **Completat (AFC/NFC, 32 equips)**
- [x] Integració ESPN API (Formula 1) - **Completat (pilots i constructors)**
- [x] Integració ESPN API (Tennis ATP) - **Completat (top 50)**
- [x] Integració Ciclisme (3 Grans Voltes) - **Completat (Giro, Tour, Vuelta - GC + Stages)**
- [x] Integració Ciclisme (Monuments i Clàssiques) - **Completat (5 Monuments + 3 altres)**
- [x] Integració Ciclocross (CX) - **Completat (World Championships + World Cup Men/Women)**
- [x] Integració Mountain Bike (MTB/BTT) - **Completat (XCO + Downhill World Cup + Olympics)**

---

## Comandes Útils

```bash
# Servidor de desenvolupament
php artisan serve

# Sincronització completa (recomanat)
php artisan sync:all                          # Tot: lligues, equips, fixtures, standings (2025-26)
php artisan sync:all --league=es.1            # Només La Liga
php artisan sync:all --season=2024-25         # Temporada anterior

# Sincronització individual
php artisan sync:leagues
php artisan sync:teams es.1 --season=2025-26
php artisan sync:fixtures es.1 --season=2025-26
php artisan sync:standings es.1 --season=2025-26

# Scheduler
php artisan schedule:list                     # Veure tasques programades
php artisan schedule:run                      # Executar tasques pendents

# Seeders
php artisan db:seed                           # Tots els seeders
php artisan db:seed --class=LeaguesSeeder     # Només lligues
php artisan db:seed --class=SuperAdminSeeder
php artisan db:seed --class=RolesAndPermissionsSeeder

# Netejar cache de permisos
php artisan permission:cache-reset

# Veure rutes
php artisan route:list --path=api

# Documentació API
# Accedir a http://localhost:8000/request-docs
php artisan lrd:export                        # Exportar OpenAPI JSON
```

---

*Última actualització: 2025-12-24 (Wallpaper 3 columnes amb dades 2025 - Road + CX + MTB)*
