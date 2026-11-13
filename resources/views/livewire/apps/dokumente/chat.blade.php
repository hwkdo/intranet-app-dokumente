<div>
    <x-intranet-app-dokumente::dokumente-layout heading="KI-Chat" subheading="LightRAG liefert den Kontext der Dokumente. Die Modellliste kommt von OpenWebUI.">
        @if ($this->ready)
            <div class="mx-auto flex w-full max-w-4xl flex-col gap-4">
                <flux:select wire:model.live="selectedModel" label="Modell" variant="listbox" searchable>
                    @foreach ($models as $model)
                        <flux:select.option value="{{ $model['id'] }}">{{ $model['name'] }}</flux:select.option>
                    @endforeach
                </flux:select>

                @livewire('prism-chat', [
                    'appIdentifier' => 'dokumente',
                    'appName' => 'Dokumente',
                    'model' => $selectedModel,
                    'apiKey' => $this->apiKey,
                    'baseUrl' => config('openwebui-api-laravel.base_api_url'),
                    'useMcpTools' => false,
                    'lightragInstance' => 'dokumente',
                    'suggestions' => [
                        'Welche Dokumente gibt es zur Reisekostenabrechnung?',
                        'Fasse die Regelungen zur Arbeitszeit zusammen.',
                        'Wo steht, wer eine Bestellung freigeben darf?',
                    ],
                ], key('dokumente-ki-chat-'.$selectedModel))
            </div>
        @else
            <flux:card class="glass-card">
                <flux:callout variant="warning">
                    <flux:heading size="sm">Chat nicht bereit</flux:heading>
                    <flux:text>{{ $this->notice }}</flux:text>
                </flux:callout>
                @if ($this->needsToken)
                    <flux:button class="mt-4" variant="primary" href="{{ route('settings.all') }}">
                        Zu den Einstellungen
                    </flux:button>
                @endif
            </flux:card>
        @endif
    </x-intranet-app-dokumente::dokumente-layout>
</div>
