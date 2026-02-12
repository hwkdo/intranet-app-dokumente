<x-intranet-app-dokumente::dokumente-layout heading="Beispiel-Seite" subheading="Demonstration der Dokumente-Funktionalität">
<div>
    <flux:card>
        <flux:heading size="lg" class="mb-4">Beispiel-Content</flux:heading>
        <flux:text class="mb-6">
            Diese Seite zeigt, wie eine typische App-Seite aussehen könnte.
        </flux:text>

        <div class="space-y-4">
            <div class="rounded-lg border p-4">
                <flux:heading size="md">{{ $exampleData['name'] }}</flux:heading>
                <flux:text class="mt-2">{{ $exampleData['description'] }}</flux:text>
                <div class="mt-2 flex items-center gap-2">
                    <flux:badge variant="success">{{ $exampleData['status'] }}</flux:badge>
                    <flux:text class="text-sm text-zinc-500">{{ $exampleData['created_at'] }}</flux:text>
                </div>
            </div>

            <div class="rounded-lg border p-4">
                <flux:heading size="md">Weitere Informationen</flux:heading>
                <flux:text class="mt-2">
                    Hier könnten weitere Details oder Aktionen für das Beispiel-Item angezeigt werden.
                </flux:text>
                <div class="mt-4 flex gap-2">
                    <flux:button variant="primary" size="sm">Bearbeiten</flux:button>
                    <flux:button variant="outline" size="sm">Löschen</flux:button>
                </div>
            </div>
        </div>
    </flux:card>
</div>
</x-intranet-app-dokumente::dokumente-layout>
