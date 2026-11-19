<div>
    <x-intranet-app-dokumente::dokumente-layout heading="KI-Chat" subheading="LightRAG liefert den Kontext der Dokumente. Die Modellliste kommt von OpenWebUI.">
        @if ($this->ready)
            <div class="mx-auto flex w-full max-w-4xl flex-col gap-4">
                <section class="glass-card overflow-hidden">
                    <div class="flex items-center gap-3 border-b border-[#d0e3f9] px-5 py-4 dark:border-white/10">
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-[#073070]/10 text-[#073070] dark:bg-white/10 dark:text-white">
                            <flux:icon icon="sparkles" variant="micro" class="size-5" />
                        </span>
                        <div>
                            <h2 class="text-sm font-semibold text-[#073070] dark:text-white">Modell</h2>
                            <p class="text-xs text-slate-600 dark:text-white/70">Antworten beziehen sich auf die indexierten Dokumente.</p>
                        </div>
                    </div>
                    <div class="p-5">
                        <flux:select wire:model.live="selectedModel" label="Modell" variant="listbox" searchable>
                            @foreach ($models as $model)
                                <flux:select.option value="{{ $model['id'] }}">{{ $model['name'] }}</flux:select.option>
                            @endforeach
                        </flux:select>
                    </div>
                </section>

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
