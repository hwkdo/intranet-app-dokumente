<div>
    <x-intranet-app-dokumente::dokumente-layout heading="App-Info" subheading="Installierte Version und Release-Historie.">
        <x-intranet-app-dokumente::hwk-card title="Version" icon="information-circle" description="Installierte Version und Release-Historie dieser App." padded>
            @livewire('intranet-app-base::app-info', ['appIdentifier' => 'dokumente'])
        </x-intranet-app-dokumente::hwk-card>
    </x-intranet-app-dokumente::dokumente-layout>
</div>
