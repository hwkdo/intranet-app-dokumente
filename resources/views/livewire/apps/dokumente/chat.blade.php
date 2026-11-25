<div>
    <x-intranet-app-dokumente::dokumente-layout heading="KI-Chat" subheading="Antworten beziehen sich auf die indexierten Dokumente.">
        @if ($this->ready)
            <div class="mx-auto flex w-full max-w-4xl flex-col">
                @livewire('prism-chat', $this->prismParameters, key('dokumente-ki-chat'))
            </div>
        @else
            <x-intranet-app-dokumente::hwk-empty icon="chat-bubble-left-right" title="Chat nicht bereit">
                {{ $this->notice }}
                @if ($this->needsToken)
                    <div class="mt-4">
                        <flux:button variant="primary" href="{{ route('settings.all') }}">
                            Zu den Einstellungen
                        </flux:button>
                    </div>
                @endif
            </x-intranet-app-dokumente::hwk-empty>
        @endif
    </x-intranet-app-dokumente::dokumente-layout>
</div>
