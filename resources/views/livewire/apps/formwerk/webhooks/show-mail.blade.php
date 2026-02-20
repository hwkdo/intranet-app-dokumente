<?php

use Hwkdo\IntranetAppFormwerk\Models\TypHasWebhook;
use function Livewire\Volt\{state, title, mount};

state([
    'webhookId' => null,
    'mailSubject' => 'E-Mail',
    'mailFrom' => '',
    'mailTo' => '',
    'mailReceivedDateTime' => '',
    'mailBody' => '',
    'mailBodyType' => 'text',
    'attachments' => [],
    'hasAttachments' => false,
]);

mount(function ($id) {
    $this->webhookId = $id;
    $webhook = TypHasWebhook::findOrFail($id);
    
    try {
        $mail = $webhook->mail();
        
        if ($mail) {
            $bodyContent = $mail->getBody() ? $mail->getBody()->getContent() : 'Kein Inhalt';
            
            // Prüfe ob der Body HTML-Tags enthält, um den Typ zu bestimmen
            $this->mailBodyType = (preg_match('/<[^>]+>/', $bodyContent)) ? 'html' : 'text';
            
            $this->mailSubject = $mail->getSubject() ?? 'Kein Betreff';
            $this->mailFrom = $mail->getFrom() ? $mail->getFrom()->getEmailAddress()->getName() . ' <' . $mail->getFrom()->getEmailAddress()->getAddress() . '>' : 'Unbekannt';
            $this->mailTo = $mail->getToRecipients() ? collect($mail->getToRecipients())->map(fn($r) => $r->getEmailAddress()->getAddress())->implode(', ') : 'Unbekannt';
            $this->mailReceivedDateTime = $mail->getReceivedDateTime() ? $mail->getReceivedDateTime()->format('d.m.Y H:i:s') : 'Unbekannt';
            $this->mailBody = $bodyContent;
            
            // Lade Anhänge
            $this->hasAttachments = $mail->getHasAttachments() ?? false;
            
            if ($this->hasAttachments && $webhook->ms_graph_mail_resource) {
                $resource = $webhook->ms_graph_mail_resource;
                $upn = str($resource)->after('Users/')->before('/Messages/')->value();
                $messageId = str($resource)->after('/Messages/')->value();
                
                $mailService = new \Hwkdo\MsGraphLaravel\Services\MailService();
                $attachmentsResponse = $mailService::listAttachmentsByUpnAndId($upn, $messageId);
                $attachmentsList = $attachmentsResponse?->getValue() ?? [];
                
                $this->attachments = collect($attachmentsList)->map(function ($attachment) {
                    return [
                        'id' => $attachment->getId(),
                        'name' => $attachment->getName(),
                        'contentType' => $attachment->getContentType(),
                        'size' => $attachment->getSize(),
                    ];
                })->toArray();
            }
        } else {
            $this->mailSubject = 'E-Mail nicht gefunden';
            $this->mailFrom = '-';
            $this->mailTo = '-';
            $this->mailReceivedDateTime = '-';
            $this->mailBody = 'Die E-Mail konnte nicht geladen werden.';
            $this->mailBodyType = 'text';
        }
    } catch (\Exception $e) {
        $this->mailSubject = 'Fehler beim Laden';
        $this->mailFrom = '-';
        $this->mailTo = '-';
        $this->mailReceivedDateTime = '-';
        $this->mailBody = 'Fehler: ' . $e->getMessage();
        $this->mailBodyType = 'text';
    }
});

title(fn () => $this->mailSubject);

?>

<x-intranet-app-formwerk::formwerk-layout 
    :heading="$this->mailSubject" 
    subheading="E-Mail Details"
>
    <div class="space-y-6">
        <flux:card class="glass-card">
            <div class="space-y-4">
                <div class="flex items-start gap-2">
                    <span class="text-sm font-semibold text-[#073070]/70 dark:text-white/60 w-32">Von:</span>
                    <span class="text-sm">{{ $mailFrom }}</span>
                </div>
                <flux:separator variant="subtle" />
                <div class="flex items-start gap-2">
                    <span class="text-sm font-semibold text-[#073070]/70 dark:text-white/60 w-32">An:</span>
                    <span class="text-sm">{{ $mailTo }}</span>
                </div>
                <flux:separator variant="subtle" />
                <div class="flex items-start gap-2">
                    <span class="text-sm font-semibold text-[#073070]/70 dark:text-white/60 w-32">Datum:</span>
                    <span class="text-sm">{{ $mailReceivedDateTime }}</span>
                </div>
            </div>
        </flux:card>

        <flux:card class="glass-card">
            <div class="p-4 bg-white/60 dark:bg-[#04214e]/60 rounded-lg overflow-y-auto border border-[#d0e3f9]/60 dark:border-white/10" style="max-height: 70vh;">
                @if($mailBodyType === 'html')
                    <iframe 
                        srcdoc="{{ str_replace('"', '&quot;', $mailBody) }}" 
                        class="w-full border-0 rounded"
                        style="min-height: 60vh;"
                        sandbox="allow-same-origin allow-popups"
                        onload="this.style.height = (this.contentWindow.document.documentElement.scrollHeight + 20) + 'px';"
                    ></iframe>
                @else
                    <pre class="whitespace-pre-wrap text-sm">{{ $mailBody }}</pre>
                @endif
            </div>
        </flux:card>

        @if($hasAttachments && count($attachments) > 0)
            <flux:card class="glass-card">
                <div class="space-y-4">
                    <flux:heading size="lg">Anhänge ({{ count($attachments) }})</flux:heading>
                    <flux:separator variant="subtle" />
                    
                    <div class="space-y-2">
                        @foreach($attachments as $attachment)
                            <div class="flex items-center justify-between p-3 rounded-lg border border-[#d0e3f9]/60 dark:border-white/10 bg-white/50 dark:bg-[#04214e]/40 hover:bg-[#d0e3f9]/60 dark:hover:bg-[#073070]/50 transition">
                                <div class="flex items-center gap-3 min-w-0 flex-1">
                                    <flux:icon.paper-clip class="flex-shrink-0 text-zinc-500" />
                                    <div class="min-w-0 flex-1">
                                        <div class="text-sm font-medium truncate">{{ $attachment['name'] }}</div>
                                        <div class="text-xs text-zinc-500">
                                            {{ number_format($attachment['size'] / 1024, 2) }} KB
                                        </div>
                                    </div>
                                </div>
                                <flux:button 
                                    :href="route('apps.formwerk.webhooks.attachments.download', ['webhookId' => $webhookId, 'id' => $attachment['id']])"
                                    size="sm"
                                    variant="ghost"
                                    icon="arrow-down-tray"
                                >
                                    Herunterladen
                                </flux:button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </flux:card>
        @endif

        <div class="flex justify-start">
            <flux:button 
                :href="route('apps.formwerk.webhooks.index')" 
                variant="ghost"
                icon="arrow-left"
            >
                Zurück zur Übersicht
            </flux:button>
        </div>
    </div>
</x-intranet-app-formwerk::formwerk-layout>

