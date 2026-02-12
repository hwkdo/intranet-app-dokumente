<x-intranet-app-dokumente::dokumente-layout heading="Dokumente App" subheading="Admin">
<div>
    <flux:tab.group>
        <flux:tabs wire:model="activeTab">
            <flux:tab name="einstellungen" icon="cog-6-tooth">Einstellungen</flux:tab>
            <flux:tab name="kategorien" icon="folder">Kategorien</flux:tab>
            <flux:tab name="statistiken" icon="chart-bar">Statistiken</flux:tab>
        </flux:tabs>

        <flux:tab.panel name="einstellungen">
            <div class="min-h-[400px]">
                @livewire('intranet-app-base::admin-settings', [
                    'appIdentifier' => 'dokumente',
                    'settingsModelClass' => \Hwkdo\IntranetAppDokumente\Models\IntranetAppDokumenteSettings::class,
                    'appSettingsClass' => \Hwkdo\IntranetAppDokumente\Data\AppSettings::class,
                ])
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="kategorien">
            <div class="min-h-[400px] space-y-6">
                @if($showCategoryForm)
                    <flux:card>
                        <flux:heading size="md" class="mb-4">
                            {{ $editingCategoryId ? 'Kategorie bearbeiten' : 'Neue Kategorie' }}
                        </flux:heading>
                        <form wire:submit="saveCategory" class="space-y-4">
                            <flux:input wire:model="editingName" label="Name" required />
                            <flux:input wire:model="editingSortOrder" type="number" min="0" label="Sortierung" required />
                            <div class="flex gap-2">
                                <flux:button type="submit" variant="primary">Speichern</flux:button>
                                <flux:button type="button" wire:click="cancelEditCategory" variant="ghost">Abbrechen</flux:button>
                            </div>
                        </form>
                    </flux:card>
                @endif

                <flux:card>
                    <div class="mb-4 flex justify-between">
                        <flux:heading size="lg">Kategorien</flux:heading>
                        <flux:button wire:click="startNewCategory" variant="primary" size="sm">Neue Kategorie</flux:button>
                    </div>
                    <flux:table>
                        <flux:table.columns>
                            <flux:table.column>Name</flux:table.column>
                            <flux:table.column>Sortierung</flux:table.column>
                            <flux:table.column>Aktionen</flux:table.column>
                        </flux:table.columns>
                        <flux:table.rows>
                            @foreach($this->categories as $cat)
                                <flux:table.row>
                                    <flux:table.cell>{{ $cat->name }}</flux:table.cell>
                                    <flux:table.cell>{{ $cat->sort_order }}</flux:table.cell>
                                    <flux:table.cell>
                                        <flux:button wire:click="startEditCategory({{ $cat->id }})" size="sm" variant="ghost">Bearbeiten</flux:button>
                                        @if($cat->documents()->count() === 0)
                                            <flux:button wire:click="deleteCategory({{ $cat->id }})" wire:confirm="Kategorie wirklich löschen?" size="sm" variant="danger">Löschen</flux:button>
                                        @endif
                                    </flux:table.cell>
                                </flux:table.row>
                            @endforeach
                        </flux:table.rows>
                    </flux:table>
                </flux:card>
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="statistiken">
            <div class="min-h-[400px]">
                <flux:card>
                    <flux:heading size="lg" class="mb-4">App-Statistiken</flux:heading>
                    <flux:text class="mb-6">
                        Übersicht über die Nutzung der Dokumente App.
                    </flux:text>

                    <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        <div class="rounded-lg border p-4">
                            <flux:heading size="md">Dokumente gesamt</flux:heading>
                            <flux:text size="xl" class="mt-2">{{ \Hwkdo\IntranetAppDokumente\Models\Document::count() }}</flux:text>
                        </div>
                        <div class="rounded-lg border p-4">
                            <flux:heading size="md">Kategorien</flux:heading>
                            <flux:text size="xl" class="mt-2">{{ \Hwkdo\IntranetAppDokumente\Models\DocumentCategory::count() }}</flux:text>
                        </div>
                    </div>
                </flux:card>
            </div>
        </flux:tab.panel>
    </flux:tab.group>
</div>
</x-intranet-app-dokumente::dokumente-layout>
