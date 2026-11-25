<?php

use Hwkdo\IntranetAppDokumente\Support\DokumenteChatSession;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    #[Computed]
    public function ready(): bool
    {
        return DokumenteChatSession::ready();
    }

    #[Computed]
    public function needsToken(): bool
    {
        return DokumenteChatSession::needsToken();
    }

    #[Computed]
    public function notice(): string
    {
        return DokumenteChatSession::notice();
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function prismParameters(): array
    {
        return DokumenteChatSession::prismParameters(embedded: true);
    }
};
?>

<div class="flex h-full min-h-0 flex-col">
    @if ($this->ready)
        <div class="min-h-0 flex-1">
            @livewire('prism-chat', $this->prismParameters, key('dokumente-dashboard-ki-chat'))
        </div>
    @else
        <section class="glass-card flex h-full min-h-0 flex-col overflow-hidden p-0">
            <x-intranet-app-base::dashboard.chat-widget-header title="Dokumente Chat" :href="route('apps.dokumente.chat')" />
            <div class="flex flex-1 items-center justify-center">
                <x-intranet-app-dokumente::hwk-empty bare icon="chat-bubble-left-right" title="Chat nicht bereit">
                    {{ $this->notice }}
                    @if ($this->needsToken)
                        <div class="mt-4">
                            <flux:button variant="primary" href="{{ route('settings.all') }}">
                                Zu den Einstellungen
                            </flux:button>
                        </div>
                    @endif
                </x-intranet-app-dokumente::hwk-empty>
            </div>
        </section>
    @endif
</div>
