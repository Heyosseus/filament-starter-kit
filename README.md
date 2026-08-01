# Filament Starter Kit

A Laravel 13 + Filament 5 admin panel with the parts you would otherwise build
on every project already wired together: roles and permissions, an audit trail,
localisation, performance monitoring, and scheduled backups.

## Stack

| | |
|---|---|
| PHP | 8.4+ |
| Laravel | 13 |
| Filament | 5 |
| Database | MySQL (PostgreSQL and SQLite work too) |
| Queue / cache / session | `database` — no Redis required |

## What you get

**Access control** — [Filament Shield](https://github.com/bezhanSalleh/filament-shield)
with generated policies and permissions, a super admin role, and a `panel_user`
role that gates the panel itself. Shield's own roles resource is used as-is
rather than forked into the app.

**Users** — a full resource: list, create, view, edit, role assignment, filters,
queued CSV import and export. The password field is left blank on edit and only
written when something is typed, so saving a user never silently resets it.

**Audit trail** — [spatie/laravel-activitylog](https://github.com/spatie/laravel-activitylog)
records changes to users and roles with a field-by-field diff, attributed to
whoever made them, plus sign-ins, sign-outs and failed login attempts with the
originating IP and user agent. Readable at **System → Activity log**, and
per-record under the History tab on a user. Password hashes are never logged.

Auth events are recorded by `App\Listeners\LogAuthenticationActivity`. Laravel
only scans `app/Listeners` because `bootstrap/app.php` asks it to via
`withEvents(discover: ...)` — without that call a listener in that directory
never runs. If you cache events (`php artisan event:cache`, or `optimize`),
re-run `event:clear` after adding a listener or it will not fire.

**Monitoring** — [Laravel Pulse](https://pulse.laravel.com) at `/pulse` on the
database driver, behind a `view_pulse` permission rather than Pulse's default
"anyone in local" gate.

**Backups** — [spatie/laravel-backup](https://github.com/spatie/laravel-backup),
scheduled nightly with a clean-up pass and a health check.

**Localisation** — English and Georgian, complete and key-for-key identical. The
language switch renders inside the panel and on the login screen.

**Dashboard** — user/role/active-session stats, a 30-day registrations chart,
and a recent-activity table.

## Requirements

- PHP 8.4+ with `pdo_mysql`, `zip`, `intl`, `gd`, `bcmath`
- Composer 2.5+
- Node 20.19+ or 22.12+ (Vite 7)
- MySQL 8+
- `mysqldump` on `PATH` for backups — or set `DB_DUMP_BINARY_PATH`

## Setup

```bash
git clone <your-fork> && cd filament-starter-kit
composer install
cp .env.example .env          # copy .env.example .env  on Windows
php artisan key:generate
# set your DB_* values in .env, then:
npm install && npm run build
php artisan init
```

`php artisan init` runs migrations, generates Shield's policies and
permissions, and prompts you to create a super admin. It is safe to re-run.

Then `composer dev` to start the server, queue worker, log viewer and Vite
together, and open http://localhost:8000/admin.

### Command reference

| Command | What it does |
|---|---|
| `php artisan init` | Migrate, generate permissions, create a super admin |
| `php artisan init --fresh` | The same, but drops every table first |
| `php artisan init --skip-admin` | Skip the super admin prompt |
| `php artisan shield:generate --all --panel=admin` | Regenerate permissions after adding a resource |
| `php artisan shield:super-admin --user=1` | Promote an existing user |
| `composer test` | Run the test suite |
| `composer lint` | Format with Pint |
| `php artisan backup:run` | Take a backup now |

## After adding a resource

Shield derives permissions from your resource classes, so new resources are
invisible until you regenerate:

```bash
php artisan shield:generate --all --panel=admin
```

Then grant the new permissions to a role at **User Management → Roles**.

Generated policies are not Pint-formatted, so run `composer lint` afterwards if
you care about a clean diff.

Note that Shield 4 formats permission keys as `Pascal:Case` — `ViewAny:User`,
not `view_any_user`. Custom permissions declared in `config/filament-shield.php`
are the exception: `format_custom_permission_keys` is off, so they are used
exactly as written (which is why the Pulse permission is plain `view_pulse`).

## Assets

Filament's own CSS, JS and fonts are published into `public/css`, `public/js`
and `public/fonts`. These are **not** committed — `composer install` republishes
them automatically through the `filament:upgrade` step in `post-autoload-dump`,
so they always match the installed version.

If the panel ever renders as unstyled plain HTML, those assets are missing:

```bash
php artisan filament:assets
```

That is also the one thing to check on a deploy that skips composer scripts
(`--no-scripts`), since nothing else will publish them. `PanelAssetsTest`
guards against it by asserting every asset the panel references can actually be
served.

Application CSS and JS go through Vite into `public/build` (`npm run build`).

## Scheduling

The backup and log-pruning schedules in `routes/console.php` need a scheduler.
In production add:

```
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Locally, `php artisan schedule:work`.

## Testing

```bash
php artisan test
```

37 feature tests covering panel access, per-permission authorisation on every
user page, the password-preservation behaviour, the activity log's contents and
attribution, the Pulse gate, and each dashboard widget. They run on SQLite
in-memory by default; the suite also passes against MySQL if you point `DB_*`
at a scratch database.

## Localisation

Translations live in `lang/en` and `lang/ka`, split by area (`navigation`,
`users`, `activity`, `dashboard`). To add a locale, copy `lang/en`, translate,
and add the code to `AppServiceProvider::configureLanguageSwitch()`.

## Optional: Horizon

Horizon is deliberately not included — it needs Redis and the `pcntl`
extension, which rules out Windows, and the kit defaults to the database queue
so it works everywhere. If you are on Redis and Linux:

```bash
composer require laravel/horizon
php artisan horizon:install
```

Set `QUEUE_CONNECTION=redis`, then add a nav item to `AdminPanelProvider`
alongside the Pulse one.

## Project layout

```
app/
├── Console/Commands/InitCommand.php
├── Filament/
│   ├── Exports/            # queued CSV export
│   ├── Imports/            # queued CSV import
│   ├── Pages/Dashboard.php # fixed widget order
│   ├── RelationManagers/   # reusable activity history
│   ├── Resources/
│   │   ├── Activities/     # read-only audit trail
│   │   └── Users/          # Resource + Pages/ Schemas/ Tables/
│   ├── Support/            # NavigationGroup enum
│   └── Widgets/
├── Models/User.php
├── Policies/               # generated by Shield — do not hand-edit
└── Providers/
```

Resources follow Filament 5's split layout: the resource class holds only
navigation and wiring, while the form, table and infolist each live in their own
file under `Schemas/` and `Tables/`.

## Licence

MIT.
