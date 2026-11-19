<div>
    <x-intranet-app-dokumente::dokumente-layout heading="Dokument" :subheading="$document->title">
        <x-intranet-app-dokumente::hwk-card title="Dokument" icon="document-text" :description="$document->title" padded>
            <flux:button :href="route('apps.dokumente.index')" variant="ghost" icon="arrow-left">Zur Matrix</flux:button>
        </x-intranet-app-dokumente::hwk-card>
        @include('intranet-app-dokumente::livewire.apps.dokumente.partials.document-modals')
    </x-intranet-app-dokumente::dokumente-layout>
</div>
