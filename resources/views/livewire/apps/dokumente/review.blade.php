<div>
    <x-intranet-app-dokumente::dokumente-layout heading="Dokument prüfen" :subheading="$document->title">
        <flux:card class="glass-card space-y-4">
            <flux:callout>
                Bitte entscheiden Sie, wie mit diesem Dokument verfahren werden soll.
            </flux:callout>
            <flux:text>Gültig bis: {{ $document->gueltig_bis?->format('d.m.Y') ?? 'ohne Frist (Jahresprüfung)' }}</flux:text>
            <div class="flex flex-wrap gap-2">
                <flux:button variant="primary" wire:click="chooseExtend">Gültigkeit verlängern</flux:button>
                <flux:button variant="filled" wire:click="chooseUpdate">Dokument aktualisieren</flux:button>
                <flux:button variant="danger" wire:click="chooseDelete" wire:confirm="Dokument wirklich löschen?">Dokument löschen</flux:button>
                <flux:button variant="ghost" :href="route('apps.dokumente.index', ['document' => $document->id])">Details</flux:button>
            </div>
        </flux:card>

        @include('intranet-app-dokumente::livewire.apps.dokumente.partials.document-modals')
    </x-intranet-app-dokumente::dokumente-layout>
</div>
