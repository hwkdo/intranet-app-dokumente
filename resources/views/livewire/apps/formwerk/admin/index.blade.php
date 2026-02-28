<?php

use function Livewire\Volt\{state, title};

title('Formwerk - Admin');

state(['activeTab' => 'einstellungen']);

?>

<x-intranet-app-formwerk::formwerk-layout heading="Formwerk App" subheading="Admin">
    <flux:tab.group>
        <flux:tabs wire:model="activeTab">
            <flux:tab name="hintergrundbild" icon="photo">Hintergrundbild</flux:tab>
            <flux:tab name="einstellungen" icon="cog-6-tooth">Einstellungen</flux:tab>
            <flux:tab name="statistiken" icon="chart-bar">Statistiken</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="hintergrundbild">
            <div style="min-height: 400px;">
                @livewire('intranet-app-base::app-background-image', [
                    'appIdentifier' => 'formwerk',
                ])
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="einstellungen">
            <div style="min-height: 400px;">
                @livewire('intranet-app-base::admin-settings', [
                    'appIdentifier' => 'formwerk',
                    'settingsModelClass' => '\Hwkdo\IntranetAppFormwerk\Models\IntranetAppFormwerkSettings',
                    'appSettingsClass' => '\Hwkdo\IntranetAppFormwerk\Data\AppSettings'
                ])
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="statistiken">
            <div style="min-height: 400px;">
                <flux:card class="glass-card">
                    <flux:heading size="lg" class="mb-4">App-Statistiken</flux:heading>
                    <flux:text class="mb-6">
                        Übersicht über die Nutzung der Formwerk App.
                    </flux:text>

                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-xl border border-[#d0e3f9]/80 dark:border-white/10 bg-[#d0e3f9]/40 dark:bg-[#073070]/40 p-4">
                            <flux:heading size="md">Aktive Benutzer</flux:heading>
                            <flux:text size="xl" class="mt-2 font-semibold text-[#073070] dark:text-white">42</flux:text>
                        </div>

                        <div class="rounded-xl border border-[#d0e3f9]/80 dark:border-white/10 bg-[#d0e3f9]/40 dark:bg-[#073070]/40 p-4">
                            <flux:heading size="md">Seitenaufrufe</flux:heading>
                            <flux:text size="xl" class="mt-2 font-semibold text-[#073070] dark:text-white">1.234</flux:text>
                        </div>

                        <div class="rounded-xl border border-[#d0e3f9]/80 dark:border-white/10 bg-[#d0e3f9]/40 dark:bg-[#073070]/40 p-4">
                            <flux:heading size="md">Letzte Aktivität</flux:heading>
                            <flux:text size="xl" class="mt-2 font-semibold text-[#073070] dark:text-white">2 Min</flux:text>
                        </div>
                    </div>
                </flux:card>
            </div>
        </flux:tab.panel>
    </flux:tab.group>
</x-intranet-app-formwerk::formwerk-layout>
