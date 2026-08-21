<div>
    <x-intranet-app-dokumente::dokumente-layout heading="Dokument" :subheading="$document->title">
        <div class="mb-4">
            <flux:button :href="route('apps.dokumente.index')" variant="ghost" icon="arrow-left">Zur Matrix</flux:button>
        </div>
        @include('intranet-app-dokumente::livewire.apps.dokumente.partials.document-modals')
    </x-intranet-app-dokumente::dokumente-layout>
</div>
