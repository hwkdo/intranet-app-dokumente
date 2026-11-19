<div>
<x-intranet-app-dokumente::dokumente-layout heading="Administration" subheading="Dokumente, Indexierung, Rahmenbilder und Einstellungen.">
<div>
    <flux:tab.group>
        <div class="glass-card sticky top-3 z-20 mb-4 p-3" style="--glass-opacity: 0.92">
            <flux:tabs variant="pills" class="hwk-pills" wire:model.live="activeTab">
                <flux:tab name="dokumente" icon="document-text">Dokumente</flux:tab>
                <flux:tab name="lightrag" icon="circle-stack">LightRAG</flux:tab>
                <flux:tab name="news-rahmen" icon="newspaper">News-Rahmen</flux:tab>
                <flux:tab name="hintergrundbild" icon="photo">Hintergrundbild</flux:tab>
                <flux:tab name="einstellungen" icon="cog-6-tooth">Einstellungen</flux:tab>
                <flux:tab name="kategorien" icon="folder">Kategorien</flux:tab>
                <flux:tab name="statistiken" icon="chart-bar">Statistiken</flux:tab>
                <flux:tab name="hintergrund" icon="clock">Hintergrund</flux:tab>
            </flux:tabs>
        </div>

        <flux:tab.panel name="dokumente">
            @if($activeTab === 'dokumente')
                <div class="min-h-[400px]">
                    @livewire('intranet-app-dokumente::apps.dokumente.admin.documents', key('dokumente-admin-documents'))
                </div>
            @endif
        </flux:tab.panel>

        <flux:tab.panel name="lightrag">
            @if($activeTab === 'lightrag')
                <div class="min-h-[400px]">
                    @livewire('intranet-app-dokumente::apps.dokumente.admin.light-rag', key('dokumente-admin-lightrag'))
                </div>
            @endif
        </flux:tab.panel>

        <flux:tab.panel name="news-rahmen">
            <div class="min-h-[400px]">
                @livewire('intranet-app-dokumente::apps.dokumente.admin.news-rahmen')
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="hintergrundbild">
            <div class="min-h-[400px]">
                <section class="glass-card overflow-hidden p-5">
                    @livewire('intranet-app-base::app-background-image', [
                        'appIdentifier' => 'dokumente',
                    ])
                </section>
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="einstellungen">
            <div class="min-h-[400px] space-y-4">
                @livewire('intranet-app-dokumente::apps.dokumente.admin.berechtigungen', key('dokumente-admin-berechtigungen'))

                <section class="glass-card overflow-hidden p-5">
                    @livewire('intranet-app-base::document-parse-settings', [
                        'appIdentifier' => 'dokumente',
                        'settingsModelClass' => \Hwkdo\IntranetAppDokumente\Models\IntranetAppDokumenteSettings::class,
                        'appSettingsClass' => \Hwkdo\IntranetAppDokumente\Data\AppSettings::class,
                    ])
                </section>

                <section class="glass-card overflow-hidden p-5">
                    @livewire('intranet-app-base::admin-settings', [
                        'appIdentifier' => 'dokumente',
                        'settingsModelClass' => \Hwkdo\IntranetAppDokumente\Models\IntranetAppDokumenteSettings::class,
                        'appSettingsClass' => \Hwkdo\IntranetAppDokumente\Data\AppSettings::class,
                        'excludedKeys' => [
                            'newsFrameSlotX',
                            'newsFrameSlotY',
                            'newsFrameSlotWidth',
                            'newsFrameSlotHeight',
                            'permissionUpload',
                            'permissionKenntnisnahme',
                            'permissionChooseGvp',
                            'documentParseEngineOverride',
                            'documentParseTierOverride',
                        ],
                    ])
                </section>
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="kategorien">
            <div class="min-h-[400px] space-y-4">
                @if($showCategoryForm)
                    <x-intranet-app-dokumente::hwk-card
                        :title="$editingCategoryId ? 'Kategorie bearbeiten' : 'Neue Kategorie'"
                        icon="folder"
                        description="Name und Sortierung der Kategorie."
                        padded
                    >
                        <form wire:submit="saveCategory" class="space-y-4">
                            <flux:input wire:model="editingName" label="Name" required />
                            <flux:input wire:model="editingSortOrder" type="number" min="0" label="Sortierung" required />
                            <div class="flex gap-2">
                                <flux:button type="submit" variant="primary">Speichern</flux:button>
                                <flux:button type="button" wire:click="cancelEditCategory" variant="ghost">Abbrechen</flux:button>
                            </div>
                        </form>
                    </x-intranet-app-dokumente::hwk-card>
                @endif

                <x-intranet-app-dokumente::hwk-card title="Kategorien" icon="folder" description="Reihenfolge in der Matrix.">
                    <x-slot:actions>
                        <flux:button wire:click="startNewCategory" variant="primary" size="sm">Neue Kategorie</flux:button>
                    </x-slot:actions>

                    @if($this->categories->isEmpty())
                        <x-intranet-app-dokumente::hwk-empty bare icon="folder" title="Keine Kategorien">
                            Legen Sie die erste Kategorie an.
                        </x-intranet-app-dokumente::hwk-empty>
                    @else
                        <flux:table>
                            <flux:table.columns>
                                <flux:table.column>Name</flux:table.column>
                                <flux:table.column>Sortierung</flux:table.column>
                                <flux:table.column>Aktionen</flux:table.column>
                            </flux:table.columns>
                            <flux:table.rows>
                                @foreach($this->categories as $cat)
                                    <flux:table.row wire:key="category-{{ $cat->id }}">
                                        <flux:table.cell class="font-medium text-[#073070] dark:text-white">{{ $cat->name }}</flux:table.cell>
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
                    @endif
                </x-intranet-app-dokumente::hwk-card>
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="statistiken">
            <div class="grid gap-4 md:grid-cols-2">
                <section class="glass-card px-5 py-4">
                    <p class="text-xs font-medium text-slate-600 dark:text-white/70">Dokumente gesamt</p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums text-[#073070] dark:text-white">{{ \Hwkdo\IntranetAppDokumente\Models\Document::count() }}</p>
                </section>
                <section class="glass-card px-5 py-4">
                    <p class="text-xs font-medium text-slate-600 dark:text-white/70">Kategorien</p>
                    <p class="mt-1 text-2xl font-semibold tabular-nums text-[#073070] dark:text-white">{{ \Hwkdo\IntranetAppDokumente\Models\DocumentCategory::count() }}</p>
                </section>
            </div>
        </flux:tab.panel>

        <flux:tab.panel name="hintergrund">
            @if ($activeTab === 'hintergrund')
                <div class="min-h-[400px]">
                    @livewire('intranet-app-base::background-activities', ['appIdentifier' => 'dokumente'], key('dokumente-admin-hintergrund'))
                </div>
            @endif
        </flux:tab.panel>
    </flux:tab.group>
</div>
</x-intranet-app-dokumente::dokumente-layout>
</div>
