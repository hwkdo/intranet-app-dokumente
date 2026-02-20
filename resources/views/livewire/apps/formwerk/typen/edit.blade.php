<?php

use Hwkdo\IntranetAppFormwerk\Models\Typ;
use function Livewire\Volt\{state, title, mount};

state([
    'typ' => null,
    'name' => '',
    'form_identifier' => '',
    'identifier' => '',
    'identifier_xml_field' => '',
    'subject' => '',
    'jobClass' => '',
    'filepath' => '',
    'token' => '',
    'config' => '',
    'is_active' => true,
]);

mount(function ($typ = null) {
    if ($typ) {
        $this->typ = Typ::findOrFail($typ);
        $this->name = $this->typ->name;
        $this->form_identifier = $this->typ->form_identifier;
        $this->identifier = $this->typ->identifier;
        $this->identifier_xml_field = $this->typ->identifier_xml_field;
        $this->subject = $this->typ->subject ?? '';
        $this->jobClass = $this->typ->jobClass ?? '';
        $this->filepath = $this->typ->filepath ?? '';
        $this->token = ''; // Token wird aus Sicherheitsgründen nicht angezeigt
        $this->config = $this->typ->config ? json_encode($this->typ->config, JSON_PRETTY_PRINT) : '';
        $this->is_active = (bool) $this->typ->is_active;
    }
});

$save = function() {
    $rules = [
        'name' => 'required|string|max:255',
        'form_identifier' => 'required|string|max:255|unique:intranet_app_formwerk_typs,form_identifier' . ($this->typ ? ',' . $this->typ->id : ''),
        'identifier' => 'required|string|max:255',
        'identifier_xml_field' => 'nullable|string|max:255',
        'subject' => 'nullable|string|max:255',
        'jobClass' => 'nullable|string|max:255',
        'filepath' => 'nullable|string|max:255',
        'token' => $this->typ ? 'nullable|string' : 'nullable|string',
        'config' => 'nullable|json',
        'is_active' => 'boolean',
    ];

    $messages = [
        'name.required' => 'Der Name ist erforderlich.',
        'form_identifier.required' => 'Der Form Identifier ist erforderlich.',
        'form_identifier.unique' => 'Dieser Form Identifier wird bereits verwendet.',
        'identifier.required' => 'Der Identifier ist erforderlich.',
        'config.json' => 'Die Konfiguration muss ein gültiges JSON-Format haben.',
    ];

    $validated = $this->validate($rules, $messages);

    // Parse config JSON
    $configData = null;
    if (!empty($validated['config'])) {
        $configData = json_decode($validated['config'], true);
    }

    $data = [
        'name' => $validated['name'],
        'form_identifier' => $validated['form_identifier'],
        'identifier' => $validated['identifier'],
        'identifier_xml_field' => $validated['identifier_xml_field'],
        'subject' => $validated['subject'],
        'jobClass' => $validated['jobClass'],
        'filepath' => $validated['filepath'],
        'config' => $configData,
        'is_active' => $validated['is_active'],
    ];

    // Nur Token setzen, wenn ein Wert eingegeben wurde
    if (!empty($validated['token'])) {
        $data['token'] = $validated['token'];
    }

    if ($this->typ) {
        $this->typ->update($data);
        session()->flash('message', 'Typ erfolgreich aktualisiert.');
    } else {
        Typ::create($data);
        session()->flash('message', 'Typ erfolgreich erstellt.');
    }

    $this->redirect(route('apps.formwerk.typen.index'), navigate: true);
};

title(fn () => $this->typ ? 'Typ bearbeiten' : 'Neuer Typ');

?>

<x-intranet-app-formwerk::formwerk-layout 
    :heading="$this->typ ? 'Typ bearbeiten' : 'Neuer Typ'" 
    :subheading="$this->typ ? 'Bearbeiten Sie die Typ-Eigenschaften' : 'Erstellen Sie einen neuen Formwerk-Typ'"
>
    <form wire:submit="save" class="space-y-6">
        <flux:card class="glass-card">
            <div class="space-y-4">
                <flux:heading size="lg">Grundlegende Informationen</flux:heading>
                
                <flux:input 
                    wire:model="name" 
                    label="Name" 
                    placeholder="z.B. Asset Rückgabe"
                    required 
                />

                <flux:input 
                    wire:model="form_identifier" 
                    label="Form Identifier" 
                    placeholder="z.B. asset-rueckgabe"
                    description="Eindeutiger Identifier für diesen Typ"
                    required 
                />

                <flux:input 
                    wire:model="identifier" 
                    label="Identifier" 
                    placeholder="z.B. AR-"
                    required 
                />

                <flux:input 
                    wire:model="identifier_xml_field" 
                    label="Identifier XML Field" 
                    placeholder="z.B. field_1"
                />

                <flux:input 
                    wire:model="subject" 
                    label="Subject" 
                    placeholder="z.B. Asset Rückgabe"
                />
            </div>
        </flux:card>

        <flux:card class="glass-card">
            <div class="space-y-4">
                <flux:heading size="lg">Technische Konfiguration</flux:heading>
                
                <flux:input 
                    wire:model="jobClass" 
                    label="Job Class" 
                    placeholder="z.B. App\Jobs\ProcessAssetRueckgabe"
                    description="Vollqualifizierter Klassenname des Jobs"
                />

                <flux:input 
                    wire:model="filepath" 
                    label="Dateipfad" 
                    placeholder="z.B. /storage/formwerk/asset"
                />

                <flux:input 
                    wire:model="token" 
                    label="Token" 
                    type="password"
                    placeholder="{{ $this->typ ? 'Leer lassen, um aktuellen Token zu behalten' : 'Token eingeben' }}"
                    description="Verschlüsselter Token für die Authentifizierung"
                />

                <flux:textarea 
                    wire:model="config" 
                    label="Konfiguration (JSON)" 
                    rows="6"
                    placeholder='{"key": "value"}'
                    description="JSON-Konfiguration für erweiterte Optionen"
                />

                <flux:switch 
                    wire:model="is_active" 
                    label="Aktiv" 
                    description="Ist dieser Typ aktiv und kann verwendet werden?"
                />
            </div>
        </flux:card>

        <div class="flex justify-between">
            <flux:button 
                :href="route('apps.formwerk.typen.index')" 
                variant="ghost"
            >
                Abbrechen
            </flux:button>
            
            <flux:button 
                type="submit" 
                variant="primary"
            >
                {{ $this->typ ? 'Aktualisieren' : 'Erstellen' }}
            </flux:button>
        </div>
    </form>
</x-intranet-app-formwerk::formwerk-layout>

