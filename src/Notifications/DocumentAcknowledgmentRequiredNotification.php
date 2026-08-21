<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Notifications;

use Hwkdo\IntranetAppBase\Notifications\IntranetNotification;
use Hwkdo\IntranetAppDokumente\IntranetAppDokumente;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Illuminate\Notifications\Messages\MailMessage;
use NotificationChannels\WebPush\WebPushMessage;

class DocumentAcknowledgmentRequiredNotification extends IntranetNotification
{
    public function __construct(
        public readonly Document $document,
    ) {
        parent::__construct();
    }

    public function typeKey(): string
    {
        return 'dokumente.acknowledgment_required';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kenntnisnahme erforderlich: '.$this->document->title)
            ->line('Bitte nehmen Sie das Dokument „'.$this->document->title.'“ zur Kenntnis.')
            ->action('Dokument öffnen', route('apps.dokumente.index', ['document' => $this->document->id]));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return $this->inboxPayload(
            title: 'Kenntnisnahme erforderlich',
            body: $this->document->title,
            url: route('apps.dokumente.index', ['document' => $this->document->id]),
            appIdentifier: IntranetAppDokumente::identifier(),
        );
    }

    public function toWebPush(object $notifiable, mixed $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Kenntnisnahme erforderlich')
            ->body($this->document->title)
            ->data(['url' => route('apps.dokumente.index', ['document' => $this->document->id])]);
    }

    public function toTeams(object $notifiable): array
    {
        return [
            'preview' => 'Kenntnisnahme: '.$this->document->title,
            'topic' => 'Dokumente',
            'url' => route('apps.dokumente.index', ['document' => $this->document->id]),
        ];
    }
}
