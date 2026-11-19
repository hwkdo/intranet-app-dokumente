<div>
    <x-intranet-app-dokumente::dokumente-layout heading="Beispiel-Seite" subheading="Demonstration der Dokumente-Funktionalität.">
        <div class="space-y-4">
            <x-intranet-app-dokumente::hwk-card :title="$exampleData['name']" icon="document-text" :description="$exampleData['description']" padded>
                <div class="flex items-center gap-2">
                    <flux:badge color="green">{{ $exampleData['status'] }}</flux:badge>
                    <p class="text-sm text-slate-600 dark:text-white/70">{{ $exampleData['created_at'] }}</p>
                </div>
            </x-intranet-app-dokumente::hwk-card>

            <x-intranet-app-dokumente::hwk-card title="Weitere Informationen" icon="information-circle" description="Hier könnten weitere Details oder Aktionen für das Beispiel-Item angezeigt werden." padded>
                <div class="flex gap-2">
                    <flux:button variant="primary" size="sm">Bearbeiten</flux:button>
                    <flux:button variant="ghost" size="sm">Löschen</flux:button>
                </div>
            </x-intranet-app-dokumente::hwk-card>
        </div>
    </x-intranet-app-dokumente::dokumente-layout>
</div>
