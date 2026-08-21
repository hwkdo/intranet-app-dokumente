<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Services;

use App\Models\User;
use Hwkdo\IntranetAppDokumente\Data\AppSettings;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Models\IntranetAppDokumenteSettings;
use Hwkdo\IntranetAppDokumente\Notifications\DocumentAcknowledgmentRequiredNotification;
use Hwkdo\IntranetAppDokumente\Notifications\DocumentReviewRequiredNotification;
use Illuminate\Support\Facades\Log;

class DocumentNotificationDispatcher
{
    public function notifyAcknowledgmentRequired(Document $document): void
    {
        $userClass = config('intranet-app-dokumente.user_model', User::class);

        $userClass::permission('see-app-dokumente')
            ->aktiv()
            ->select(['id', 'email', 'socialite_id', 'settings', 'active'])
            ->chunkById(200, function ($users) use ($document): void {
                foreach ($users as $user) {
                    try {
                        $user->notify(new DocumentAcknowledgmentRequiredNotification($document));
                    } catch (\Throwable $e) {
                        Log::error('Dokumente Kenntnisnahme-Notification fehlgeschlagen', [
                            'user_id' => $user->id,
                            'document_id' => $document->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });
    }

    public function notifyReviewRequired(Document $document): void
    {
        $userClass = config('intranet-app-dokumente.user_model', User::class);
        $recipientIds = collect([$document->responsible_id, $document->uploader_id])
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($recipientIds === []) {
            return;
        }

        $userClass::query()
            ->aktiv()
            ->whereIn('id', $recipientIds)
            ->get()
            ->each(function ($user) use ($document): void {
                try {
                    $user->notify(new DocumentReviewRequiredNotification($document));
                } catch (\Throwable $e) {
                    Log::error('Dokumente Review-Notification fehlgeschlagen', [
                        'user_id' => $user->id,
                        'document_id' => $document->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });

        $document->update(['last_review_notified_at' => now()]);
    }

    public function dispatchDueReviewReminders(): int
    {
        $settings = $this->appSettings();
        $warningDays = $settings->validityWarningDays;
        $count = 0;

        Document::query()
            ->where('aktiv', true)
            ->chunkById(100, function ($documents) use ($warningDays, &$count): void {
                foreach ($documents as $document) {
                    if (! $document->isInReviewWindow($warningDays)) {
                        continue;
                    }

                    if ($document->last_review_notified_at !== null
                        && $document->last_review_notified_at->gte($document->reviewWarningStartsAt($warningDays))) {
                        continue;
                    }

                    $this->notifyReviewRequired($document);
                    $count++;
                }
            });

        return $count;
    }

    protected function appSettings(): AppSettings
    {
        $row = IntranetAppDokumenteSettings::query()->first();

        if ($row && $row->settings instanceof AppSettings) {
            return $row->settings;
        }

        return new AppSettings;
    }
}
