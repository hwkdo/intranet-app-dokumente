<div>
    <x-intranet-app-dokumente::dokumente-layout heading="Dokument prüfen" :subheading="$document->title">
        <x-intranet-app-dokumente::hwk-card
            title="Prüfung"
            icon="clipboard-document-check"
            description="Bitte entscheiden Sie, wie mit diesem Dokument verfahren werden soll."
            padded
        >
            <dl class="hwk-rows divide-y divide-[#d0e3f9] dark:divide-white/10">
                <div>
                    <dt>Gültig bis</dt>
                    <dd>{{ $document->gueltig_bis?->format('d.m.Y') ?? 'ohne Frist (Jahresprüfung)' }}</dd>
                </div>
            </dl>
            <div class="mt-4 flex flex-wrap gap-2">
                <flux:button variant="primary" wire:click="chooseExtend">Gültigkeit verlängern</flux:button>
                <flux:button variant="filled" wire:click="chooseUpdate">Dokument aktualisieren</flux:button>
                <flux:button variant="danger" wire:click="chooseDelete" wire:confirm="Dokument wirklich löschen?">Dokument löschen</flux:button>
                <flux:button variant="ghost" :href="route('apps.dokumente.index', ['document' => $document->id])">Details</flux:button>
            </div>
        </x-intranet-app-dokumente::hwk-card>

        @include('intranet-app-dokumente::livewire.apps.dokumente.partials.document-modals')
    </x-intranet-app-dokumente::dokumente-layout>
</div>
