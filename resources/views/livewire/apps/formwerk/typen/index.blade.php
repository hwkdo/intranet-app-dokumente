<?php

use Hwkdo\IntranetAppFormwerk\Models\Typ;
use function Livewire\Volt\{state, title, usesPagination, computed};

usesPagination();

state([
    'filter' => '',
    'showTokenModal' => false,
    'selectedTyp' => null,
]);

$showToken = function (Typ $typ) {
    $this->selectedTyp = $typ;
    $this->showTokenModal = true;
};

$typen = computed(function () {
    return Typ::query()
        ->when($this->filter, fn($q) => $q->where('name', 'like', '%' . $this->filter . '%'))
        ->orderBy('name')
        ->paginate(10);
});

title('Formwerk - Typen');

?>
<div>
<x-intranet-app-formwerk::formwerk-layout heading="Typen" subheading="Formwerk-Typen verwalten">
    <flux:card class="glass-card">
        <div class="space-y-6">
        <div class="flex items-center justify-between">
            <flux:input
                wire:model.live.debounce.300ms="filter"
                placeholder="Nach Name suchen..."
                icon="magnifying-glass"
                class="w-64"
            />
            @can('manage-app-formwerk')
                <flux:button :href="route('apps.formwerk.typen.create')" icon="plus" variant="primary">
                    Neuer Typ
                </flux:button>
            @endcan
        </div>

        <flux:table :paginate="$this->typen">
            <flux:table.columns>
                <flux:table.column>Name</flux:table.column>
                <flux:table.column>Form Identifier</flux:table.column>
                <flux:table.column>Subject</flux:table.column>
                <flux:table.column>Job Class</flux:table.column>
                <flux:table.column>Status</flux:table.column>
                <flux:table.column>Aktionen</flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($this->typen as $typ)
                    <flux:table.row :key="'typ-'.$typ->id">
                        <flux:table.cell>{{ $typ->name }}</flux:table.cell>
                        <flux:table.cell>{{ $typ->form_identifier }}</flux:table.cell>
                        <flux:table.cell>{{ $typ->subject ?? '-' }}</flux:table.cell>
                        <flux:table.cell>
                            <span class="text-xs font-mono">{{ $typ->jobClass ? class_basename($typ->jobClass) : '-' }}</span>
                        </flux:table.cell>
                        <flux:table.cell>
                            @if($typ->is_active)
                                <flux:badge color="green" size="sm">Aktiv</flux:badge>
                            @else
                                <flux:badge color="zinc" size="sm">Inaktiv</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>
                            <div class="flex items-center gap-2">
                                <flux:button 
                                    wire:click="showToken({{ $typ->id }})"
                                    size="xs" 
                                    icon="eye"
                                    variant="ghost"
                                >
                                    Token
                                </flux:button>
                                <flux:button 
                                    :href="route('apps.formwerk.typen.edit', $typ)" 
                                    size="xs" 
                                    icon="pencil-square"
                                    variant="ghost"
                                >
                                    Bearbeiten
                                </flux:button>
                            </div>
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
        </div>
    </flux:card>

</x-intranet-app-formwerk::formwerk-layout>
{{-- Token Modal --}}
    <flux:modal wire:model="showTokenModal" name="token-modal" class="max-w-md">
        <div class="space-y-6">
            <flux:heading size="lg">Token für {{ $selectedTyp?->name }}</flux:heading>
            
            @if($selectedTyp)
                <div class="space-y-4">
                    <flux:field>
                        <flux:label>Token</flux:label>
                        <flux:input 
                            readonly
                            value="{{ $selectedTyp->token }}"
                            class="font-mono text-sm"
                        />
                    </flux:field>
                    
                    <flux:callout icon="information-circle" variant="info">
                        Dieser Token wird für die Webhook-Authentifizierung verwendet.
                    </flux:callout>
                </div>
            @endif

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">Schließen</flux:button>
                </flux:modal.close>
            </div>
        </div>
    </flux:modal>
</div>
