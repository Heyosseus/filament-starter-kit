<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

/*
 * A nightly backup, preceded by a sweep of ones that have aged out, and a
 * health check afterwards so a silently failing backup gets noticed. All three
 * need a running scheduler; see the README for the cron entry.
 */
Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('01:30');
Schedule::command('backup:monitor')->daily()->at('02:00');

/*
 * Activity older than activitylog.clean_after_days is pruned so the audit table
 * does not grow without bound.
 *
 * Pulse is deliberately absent here: it trims its own tables on ingest, and the
 * only command that touches them (pulse:clear) deletes everything rather than
 * just the old rows.
 */
Schedule::command('activitylog:clean')->weekly();
