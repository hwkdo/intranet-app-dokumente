<div>
    <x-intranet-app-dokumente::dokumente-layout heading="Benachrichtigungen" subheading="Legen Sie fest, worüber Sie in dieser App informiert werden.">
        <section class="glass-card overflow-hidden p-5">
            @livewire('intranet-app-base::notification-settings', ['appIdentifier' => 'dokumente'])
        </section>
    </x-intranet-app-dokumente::dokumente-layout>
</div>
