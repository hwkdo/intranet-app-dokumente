<?php

declare(strict_types=1);

use Hwkdo\IntranetAppDokumente\Commands\SendDocumentReviewRemindersCommand;
use Illuminate\Console\Scheduling\Schedule;

app(Schedule::class)->command(SendDocumentReviewRemindersCommand::class)->dailyAt('07:30');
app(Schedule::class)->command('dokumente:sync-lightrag-status')->everyMinute()->withoutOverlapping();
