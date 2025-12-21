# Sport Standings API - Progress

## Resum del Projecte

API per recopilar resultats i classificacions multiesportives, començant amb futbol. Utilitza openfootball/football.json com a font de dades lliure i oberta.

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
│   │   └── FootballJsonService.php
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

## 10. Vista Pública (Home)

### Descripció:
Vista pública minimalista que mostra les classificacions de totes les lligues, organitzada per esports i temporades.

### Accés:
```
http://localhost:8000/
```

### Característiques:
- Disseny minimalista amb font monospace (estil terminal)
- Tema fosc (GitHub dark)
- Tabs per esports (preparat per futurs esports)
- Acordions per temporades (només mostra temporades amb dades)
- Grid responsive de lligues
- Indicadors de color: verd (top 4), vermell (descens)

### Ordenació de lligues:
1. **Lligues principals** (primer):
   - Premier League (England)
   - Bundesliga (Germany)
   - Serie A (Italy)
   - La Liga (Spain)
2. **Resta de lligues** (després):
   - Ordenades alfabèticament per país i nom de lliga

### Fitxers:
| Fitxer | Descripció |
|--------|------------|
| `app/Http/Controllers/HomeController.php` | Controller amb lògica de dades |
| `resources/views/home.blade.php` | Vista Blade amb tabs i acordions |
| `resources/scss/style.scss` | Estils SCSS amb variables |
| `routes/web.php` | Ruta principal `/` |

### Assets (Vite + SCSS):
```bash
# Desenvolupament
npm run dev

# Producció
npm run build
```

### Variables SCSS principals:
```scss
$color-bg: #0d1117;
$color-primary: #58a6ff;
$color-accent: #f0883e;
$color-success: #3fb950;
$color-danger: #f85149;
```

### Estructura de dades:
```php
$sports = [
    'football' => [
        'name' => 'Football',
        'seasons' => [
            '2025' => [Season, Season, ...],  // 4 lligues principals + resta
            '2024' => [...],
        ],
    ],
    // Futurs esports...
];
```

---

## 11. Pròxims Passos Suggerits

- [ ] Afegir més esports (bàsquet, tennis, etc.)
- [ ] Afegir WebSockets per resultats en temps real
- [ ] Crear dashboard amb estadístiques
- [ ] Afegir tests unitaris i d'integració
- [ ] Implementar rate limiting
- [ ] Afegir suport per múltiples idiomes
- [x] Programar sincronització automàtica (scheduler) - **Completat**
- [x] Documentar API amb OpenAPI/Swagger - **Completat (request-docs)**
- [x] Suport temporada 2025-26 - **Completat**
- [x] Sincronitzar totes les lligues 2025-26 - **Completat (15 lligues, 4.742 partits)**
- [x] Eliminar rutes HEAD duplicades de request-docs - **Completat (controller override)**
- [x] Vista pública amb classificacions - **Completat (tabs + acordions)**

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

*Última actualització: 2025-12-21*
