# Filament Starter Kit v2 — Design

**Date:** 2026-08-01
**Branch:** `feat/filament-5`
**Status:** Approved

## Problem

The starter kit runs Laravel 11 / Filament 3.2. Both are two major versions behind. Beyond the version gap, the kit carries three dependencies that no application code references, ships a single placeholder dashboard widget, has no observability story, and leaves most of its UI untranslated despite shipping `en` and `ka` locale directories. It is a thin scaffold rather than a foundation worth starting a project from.

## Goals

1. Land on Laravel 13 / Filament 5 with the resource layout, navigation and API conventions those versions expect.
2. Remove dead weight so what remains is all load-bearing.
3. Add an operations story: know what the app is doing, who changed what, and be able to restore it.
4. Make the admin panel feel finished — real dashboard, complete translations, working search and profile.

## Non-goals

Horizon (documented opt-in only — see Constraints), Fortify / Socialite / 2FA, Telescope, Pennant, Scramble, translatable models, GitHub Actions CI, Pest / Larastan / Rector.

## Constraints

Verified on the development machine on 2026-08-01:

| Fact | Consequence |
|---|---|
| PHP 8.5.9 (`C:\php\8.5.9`) | Satisfies Laravel 13's `^8.3`. Note `php` resolves to 7.3 under Git Bash but 8.5.9 under PowerShell — **use PowerShell for all `composer` and `artisan` commands**. |
| Composer at `C:\composer\composer.bat` | Not on the Git Bash PATH. |
| MySQL 9.1 running on 127.0.0.1:3306 | Migrations and panel walkthroughs are verifiable. |
| Redis not running; no `redis` PHP extension | Rules out Redis-backed queue/cache. |
| No `pcntl` extension (absent on Windows) | **Rules out Horizon**, which requires it. |
| `mysqldump` present | `spatie/laravel-backup` DB dumps are verifiable. |
| Extensions present: `pdo_mysql`, `sqlite3`, `pdo_sqlite`, `zip`, `gd`, `intl`, `bcmath` | `zip` covers backup archives; `intl` covers localisation. |

Horizon is therefore out. The README will document it as a one-package opt-in for teams on Redis and Linux, but no Horizon code, config or nav item ships in this repo — shipping unrunnable, unverified code is worse than shipping none.

## Target stack

All versions confirmed against Packagist on 2026-08-01.

| Package | From | To |
|---|---|---|
| `laravel/framework` | ^11.31 | ^13.23 |
| `filament/filament` | ^3.2 | ^5.7 |
| `php` | ^8.2 | ^8.3 |
| `bezhansalleh/filament-shield` | ^3.3 | ^4.3 |
| `bezhansalleh/filament-language-switch` | ^3.1 | ^5.0 |
| `solution-forest/filament-tree` | ^2.1 | ^4.0 |
| `laravel/pulse` | — | ^1.7 |
| `spatie/laravel-activitylog` | — | ^4.12 |
| `spatie/laravel-backup` | — | ^9.3 |

Shield 4.3, language-switch 5.0 and filament-tree 4.0 all declare `filament/support: ^5.0`, so the plugin set is compatible with Filament 5 with no version pinning gymnastics.

**Removed:** `maatwebsite/excel` (referenced only by `config/excel.php`; the existing `UserExporter`/`UserImporter` already use Filament's native `Exporter`/`Importer`), `amidesfahani/filament-tinyeditor` (zero references; Filament 5's native `RichEditor` replaces it), `barryvdh/laravel-debugbar` (Pulse supersedes it), `laravel/sail` (unused).

Runtime config stays on MySQL with `database` drivers for queue, cache and session — every default is runnable on the target machine.

## Approach: fresh skeleton, port code

Laravel's skeleton changed substantially across 11 → 12 → 13 (`bootstrap/app.php` middleware registration, config file contents, default `phpunit.xml`). An incremental constraint bump leaves those files stale in ways that surface as confusing runtime behaviour later.

Instead: fetch a clean `laravel/laravel` v13 skeleton into the scratchpad, copy its infrastructure files over the working tree, then hand-port application code onto it.

**Taken from the skeleton:** `bootstrap/`, `config/`, `public/`, `.env.example`, `phpunit.xml`, `vite.config.js`, `package.json`, `composer.json`, `artisan`, `.gitignore`.

**Hand-ported:** everything under `app/`, `lang/`, `database/`, `routes/`, `tests/`.

This is a working-tree replacement on `feat/filament-5`. Git history is preserved; `main` is untouched throughout.

## Structure

### Resource layout

Filament 5 splits a resource across a directory. `UserResource.php` plus `UserResource/Pages/` becomes:

```
app/Filament/Resources/Users/
├── UserResource.php            # navigation, permissions, wiring only
├── Pages/{List,Create,Edit,View}User.php
├── Schemas/UserForm.php        # form(Schema $schema): Schema
└── Tables/UsersTable.php
```

Applied identically to `Roles/` and the new `Activities/`. The split is the point: `UserResource.php` stops being the file that knows everything, and the form and table become independently readable units. Each should be understandable without reading the others.

### API migrations

| Filament 3 | Filament 5 |
|---|---|
| `Filament\Forms\Form` | `Filament\Schemas\Schema` |
| `Tables\Actions\*`, `Pages\Actions\*` | `Filament\Actions\*` (unified) |
| `protected static ?string $navigationIcon = 'heroicon-o-users'` | `protected static string\|BackedEnum\|null $navigationIcon = Heroicon::OutlinedUsers` |
| `VerifyCsrfToken::class` | `PreventRequestForgery::class` |
| `Infolists\Infolist` | `Filament\Schemas\Schema` |

### Navigation

Replace the stringly-typed groups in `AdminPanelProvider`:

```php
NavigationGroup::make(__('SectionList.user_management')),
NavigationGroup::make(__('SectionList.delivery')),
```

with a `App\Filament\Support\NavigationGroup` backed enum implementing `HasLabel` and `HasIcon` (crocoshop-portal's pattern), registered via `->navigationGroups(NavigationGroup::class)`. Case declaration order becomes display order; labels and icons live in one file. Cases: `UserManagement`, `System`.

The current `delivery` and `operator` groups are dropped — they are domain-specific leftovers with no resources behind them and no place in a generic starter kit.

## Features

### Observability

**Pulse** — mounted at `/pulse` on the `database` ingest and storage driver. Registered as a panel `NavigationItem` under `System`, opening in a new tab. Access gated by a Shield permission via a `PulseServiceProvider` gate so non-admins get a 403 rather than a dashboard of production internals.

**Activity log** — `spatie/laravel-activitylog`. `User` and `Role` use `LogsActivity` with explicit `logOnly` attribute lists (never log the password hash). A read-only `Activities` resource provides a list page and a view page whose infolist renders the before/after property diff. An `ActivitiesRelationManager` on `UserResource` shows a single user's trail in place.

**Backup** — `spatie/laravel-backup` with a daily scheduled run defined in `routes/console.php`. Triggered manually via the package's own `php artisan backup:run`; no Filament UI is built for it, since a settings page exists nowhere else in this design and inventing one to host a single button is not worth the surface area. `mysqldump` is confirmed present, so this is verifiable rather than aspirational.

### Admin UX

**Dashboard** — an explicit `App\Filament\Pages\Dashboard` with a fixed `getWidgets()` order rather than relying on discovery order, so the page reads consistently top-down. Widgets:

1. `UserStatsWidget` — total users, total roles, active sessions (stat overview).
2. `UserRegistrationsChartWidget` — registrations per day over the trailing 30 days.
3. `RecentActivityWidget` — table of the latest activity-log entries.

This replaces the current single placeholder `DashboardWidget`.

**Panel** — global search with `['command+k', 'ctrl+k']` bindings, `->profile()`, `->databaseNotifications()`, dark mode, `->sidebarCollapsibleOnDesktop()`, `->maxContentWidth('full')`.

**Translations** — `lang/en` and `lang/ka` gain complete coverage for every navigation group, resource label, field label, widget heading and action. The existing three files (`BaseForm`, `SectionList`, `Widgets`) are restructured to match the new navigation and resource naming. No user-visible English string ships untranslated.

**Cleanup** — delete `resources/views/vendor/filament-tree/` (~40 Blade views published from filament-tree v2; they will not render under v4 and stale published views silently override the package's own).

### filament-tree

Kept per explicit instruction and upgraded to v4, but recorded plainly: **no application code references it**. It ships as an available dependency for hierarchical resources, not as a used feature.

## Testing

The code-quality tooling package (Pest, Larastan, Rector, CI) is out of scope, so the existing PHPUnit 12 setup stays. A feature test file is added covering the claims this upgrade makes:

- The admin panel boots and `/admin/login` renders.
- For a `super_admin`, each resource's index, create and edit pages return 200.
- For a user without the relevant permission, those pages return 403.
- `/pulse` returns 200 for a permitted user and 403 otherwise.
- The activity log records a change when a User is updated.

Beyond automated tests: run migrations against the live MySQL database, run `php artisan init`, and walk `/admin` and `/pulse` in a browser. Results reported as actual command output, including failures.

## Risks

| Risk | Mitigation |
|---|---|
| Shield 4.x changed its permission-generation commands and config shape versus 3.x | Re-run `shield:install` / `shield:generate` fresh rather than porting `config/filament-shield.php`; verify the generated permission names against the new resource namespaces. |
| `InitCommand` calls `shield:generate --all --panel=admin`; the flag set may have changed | Verify against Shield 4.3's actual command signature before relying on it; adjust the command rather than assuming. |
| PHP 8.5 is newer than anything these packages advertise testing against | MySQL and the panel walkthrough are the check. Fall back to `C:\php-8.3.14` if a package breaks on 8.5. |
| Filament 5 resource-directory move loses Shield permission mappings | Shield derives permissions from resource class names; regenerate and diff the `permissions` table before and after. |

## Definition of done

- `composer install` and `npm run build` succeed.
- `php artisan migrate:fresh` and `php artisan init` complete against MySQL.
- The added feature tests pass, with output shown.
- `/admin` and `/pulse` load and are navigable in a browser.
- No PHP file references a removed package.
- `lang/en` and `lang/ka` have matching key sets.
- README rewritten for the new stack, including the Horizon opt-in note.
