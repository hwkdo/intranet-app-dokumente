<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Commands;

use Hwkdo\IntranetAppDokumente\Services\DocumentNotificationDispatcher;
use Illuminate\Console\Command;

class SendDocumentReviewRemindersCommand extends Command
{
    protected $signature = 'dokumente:send-review-reminders';

    protected $description = 'Sendet Pflicht-Notifications für Dokumente im Gültigkeits-/Prüffenster';

    public function handle(DocumentNotificationDispatcher $dispatcher): int
    {
        $count = $dispatcher->dispatchDueReviewReminders();
        $this->info("Review-Reminders gesendet: {$count}");

        return self::SUCCESS;
    }
}
