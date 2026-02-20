<?php

use Hwkdo\IntranetAppFormwerk\Models\TypHasWebhook;
use Hwkdo\MsGraphLaravel\Jobs\ProcessFormwerkMail;
use function Livewire\Volt\{state, title, usesPagination, computed};

usesPagination();

state([
    'filter' => '',
]);

$webhooks = computed(function () {
    return TypHasWebhook::query()
        ->with(['typ', 'webhook'])
        ->when($this->filter, function($q) {
            $q->whereHas('typ', fn($query) => $query->where('name', 'like', '%' . $this->filter . '%'))
              ->orWhere('identifier', 'like', '%' . $this->filter . '%');
        })
        ->orderBy('created_at', 'desc')
        ->paginate(15);
});

$reprocessMail = function (TypHasWebhook $webhook) {
    if (! $webhook->ms_graph_mail_resource) {
        return;
    }

    ProcessFormwerkMail::dispatch([
        'resource' => $webhook->ms_graph_mail_resource,
    ]);
};

title('Formwerk - Webhooks');

?>
<div>
<x-intranet-app-formwerk::formwerk-layout heading="Webhooks" subheading="Übersicht aller Webhook-Aufrufe">
    <flux:card class="glass-card">
        <div class="space-y-6">
        <flux:input
            wire:model.live.debounce.300ms="filter"
            placeholder="Nach Typ oder Identifier suchen..."
            icon="magnifying-glass"
            class="w-64"
        />

        <flux:table :paginate="$this->webhooks">
            <flux:table.columns>
                <flux:table.column>Typ</flux:table.column>
                <flux:table.column>Identifier</flux:table.column>
                <flux:table.column>Webhook ID</flux:table.column>
                <flux:table.column>Formwerk UUID</flux:table.column>
                <flux:table.column>Erstellt am</flux:table.column>
                <flux:table.column>Aktionen</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->webhooks as $webhook)
                    <flux:table.row :key="'webhook-'.$webhook->id">
                        <flux:table.cell>
                            <span class="font-medium">{{ $webhook->typ?->name ?? 'Unbekannt' }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="text-sm font-mono">{{ $webhook->identifier ?? '-' }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="text-xs text-zinc-500">{{ $webhook->webhook_call_id ?? '-' }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            <span class="text-xs text-zinc-500">{{ $webhook->formwerk_uuid ?? '-' }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            {{ $webhook->created_at?->format('d.m.Y H:i') ?? '-' }}
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex gap-2">
                                <flux:button 
                                    :href="route('apps.formwerk.webhooks.show-payload', $webhook->id)" 
                                    size="xs" 
                                    icon="code-bracket"
                                    variant="ghost"
                                >
                                    Payload
                                </flux:button>
                                
                                @if($webhook->ms_graph_mail_resource)
                                    <flux:button 
                                        :href="route('apps.formwerk.webhooks.show-mail', $webhook->id)" 
                                        size="xs" 
                                        icon="envelope"
                                        variant="ghost"
                                    >
                                        E-Mail
                                    </flux:button>
                                    <flux:button 
                                        wire:confirm="E-Mail erneut verarbeiten?"
                                        wire:click="reprocessMail({{ $webhook->id }})"
                                        wire:loading.attr="disabled"
                                        size="xs" 
                                        icon="arrow-path"
                                        variant="ghost"
                                    >
                                        E-Mail
                                    </flux:button>
                                @endif
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
        </div>
    </flux:card>
</x-intranet-app-formwerk::formwerk-layout>
</div>
