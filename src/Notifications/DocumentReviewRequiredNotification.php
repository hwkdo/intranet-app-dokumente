<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Notifications;

use Hwkdo\IntranetAppBase\Notifications\IntranetNotification;
use Hwkdo\IntranetAppDokumente\IntranetAppDokumente;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\WebPush\WebPushMessage;

class DocumentReviewRequiredNotification extends IntranetNotification
{
    public function __construct(
        public readonly Document $document,
    ) {
        parent::__construct();
    }

    public function typeKey(): string
    {
        return 'dokumente.review_required';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Dokument prüfen: '.$this->document->title)
            ->line('Bitte prüfen Sie das Dokument „'.$this->document->title.'“ (verlängern, aktualisieren oder löschen).')
            ->action('Dokument prüfen', route('apps.dokumente.review', $this->document));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->inboxPayload(
            title: 'Dokument prüfen',
            body: $this->document->title,
            url: route('apps.dokumente.review', $this->document),
            appIdentifier: IntranetAppDokumente::identifier(),
        );
    }

    public function toWebPush(object $notifiable, mixed $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Dokument prüfen')
            ->body($this->document->title)
            ->data(['url' => route('apps.dokumente.review', $this->document)]);
    }

    public function toTeams(object $notifiable): array
    {
        return [
            'preview' => 'Dokument prüfen: '.$this->document->title,
            'topic' => 'Dokumente',
            'url' => route('apps.dokumente.review', $this->document),
        ];
    }
}
