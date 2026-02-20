<?php

use Hwkdo\IntranetAppFormwerk\Models\TypHasWebhook;
use function Livewire\Volt\{state, title, mount};

state([
    'webhookId' => null,
    'payloadData' => null,
    'payloadHeading' => 'Webhook Payload',
]);

mount(function ($id) {
    $this->webhookId = $id;
    $webhook = TypHasWebhook::with('webhook')->findOrFail($id);
    
    if ($webhook->webhook) {
        $this->payloadHeading = 'Webhook Payload #' . $webhook->webhook->id;
        $this->payloadData = [
            'id' => $webhook->webhook->id,
            'name' => $webhook->webhook->name,
            'url' => $webhook->webhook->url,
            'headers' => $webhook->webhook->headers,
            'payload' => $webhook->webhook->payload,
            'created_at' => $webhook->webhook->created_at?->format('d.m.Y H:i:s'),
        ];
    } else {
        $this->payloadData = null;
    }
});

title(fn () => $this->payloadHeading);

?>

<x-intranet-app-formwerk::formwerk-layout 
    :heading="$this->payloadHeading" 
    subheading="Webhook Details und Payload"
>
    @if($payloadData)
        <div class="space-y-6">
            <flux:card class="glass-card">
                <div class="space-y-4">
                    <div class="flex items-start gap-2">
                        <span class="text-sm font-semibold text-[#073070]/70 dark:text-white/60 w-32">Name:</span>
                        <span class="text-sm">{{ $payloadData['name'] }}</span>
                    </div>
                    <flux:separator variant="subtle" />
                    <div class="flex items-start gap-2">
                        <span class="text-sm font-semibold text-[#073070]/70 dark:text-white/60 w-32">URL:</span>
                        <span class="text-sm break-all">{{ $payloadData['url'] }}</span>
                    </div>
                    <flux:separator variant="subtle" />
                    <div class="flex items-start gap-2">
                        <span class="text-sm font-semibold text-[#073070]/70 dark:text-white/60 w-32">Erstellt am:</span>
                        <span class="text-sm">{{ $payloadData['created_at'] }}</span>
                    </div>
                </div>
            </flux:card>
            
            @if($payloadData['headers'])
                <flux:card class="glass-card">
                    <flux:heading size="lg" class="mb-4">Headers</flux:heading>
                    <div class="p-4 bg-white/60 dark:bg-[#04214e]/60 rounded-lg overflow-y-auto border border-[#d0e3f9]/60 dark:border-white/10" style="max-height: 40vh;">
                        <pre class="text-xs">{{ json_encode($payloadData['headers'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </flux:card>
            @endif
            
            @if($payloadData['payload'])
                <flux:card class="glass-card">
                    <flux:heading size="lg" class="mb-4">Payload</flux:heading>
                    <div class="p-4 bg-white/60 dark:bg-[#04214e]/60 rounded-lg overflow-y-auto border border-[#d0e3f9]/60 dark:border-white/10" style="max-height: 60vh;">
                        <pre class="text-xs">{{ json_encode($payloadData['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                </flux:card>
            @endif

            <div class="flex justify-start">
                <flux:button 
                    :href="route('apps.formwerk.webhooks.index')" 
                    variant="ghost"
                    icon="arrow-left"
                >
                    Zurück zur Übersicht
                </flux:button>
            </div>
        </div>
    @else
        <flux:card class="glass-card">
            <p class="text-zinc-600 dark:text-zinc-400">Keine Webhook-Daten gefunden.</p>
        </flux:card>
    @endif
</x-intranet-app-formwerk::formwerk-layout>

