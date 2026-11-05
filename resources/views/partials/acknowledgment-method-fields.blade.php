@php
    $requiresModel = $requiresModel ?? 'uploadRequiresAcknowledgment';
    $methodModel = $methodModel ?? 'uploadAcknowledgmentMethod';
    $urlModel = $urlModel ?? 'uploadFormwerkFormUrl';
    $customModel = $customModel ?? 'uploadFormwerkUseCustomPayload';
    $rowsModel = $rowsModel ?? 'uploadFormwerkPayload';
    $onboardingItModel = $onboardingItModel ?? 'uploadIsOnboardingIt';
    $onboardingPersoModel = $onboardingPersoModel ?? 'uploadIsOnboardingPerso';
    $target = $target ?? 'upload';
    $showOnboarding = $showOnboarding ?? true;
    $showAcknowledgment = $showAcknowledgment ?? true;
@endphp

@if($showOnboarding)
    <flux:switch wire:model.live="{{ $onboardingItModel }}" label="Onboarding IT" />
    <flux:switch wire:model.live="{{ $onboardingPersoModel }}" label="Onboarding Personal" />
@endif

@if($showAcknowledgment && $this->{$requiresModel})
    <flux:radio.group wire:model.live="{{ $methodModel }}" label="Art der Kenntnisnahme">
        <flux:radio value="password" label="Passworteingabe" description="Bestätigung nach Download durch Passworteingabe" />
        <flux:radio value="formwerk" label="Unterschrift (Formwerk)" description="Bestätigung erst nach Webhook und hinterlegtem PDF" />
    </flux:radio.group>

    @if($this->{$methodModel} === 'formwerk')
        <flux:input
            wire:model="{{ $urlModel }}"
            type="url"
            label="Abweichende Formular-URL"
            description="Leer lassen, dann gilt die Standard-URL aus den Einstellungen."
            placeholder="https://my.formwerk.app/…"
        />
        <flux:switch
            wire:model.live="{{ $customModel }}"
            label="Abweichender Datensatz"
            description="Leer lassen, dann gilt der Standard-Datensatz. Werte sind fester Text oder Platzhalter wie {vorname}."
        />
        @if($this->{$customModel})
            <div class="space-y-2">
                @foreach($this->{$rowsModel} as $index => $row)
                    <div class="flex items-end gap-2" wire:key="{{ $rowsModel }}-{{ $index }}">
                        <div class="w-40">
                            <flux:input wire:model="{{ $rowsModel }}.{{ $index }}.key" label="Feld" placeholder="abteilung" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <flux:input wire:model="{{ $rowsModel }}.{{ $index }}.value" label="Wert" placeholder="{abteilung}" />
                        </div>
                        <flux:button
                            type="button"
                            variant="ghost"
                            icon="x-mark"
                            wire:click="removeFormwerkPayloadRow('{{ $target }}', {{ $index }})"
                        />
                    </div>
                @endforeach
                <flux:button type="button" variant="filled" size="sm" wire:click="addFormwerkPayloadRow('{{ $target }}')">
                    Zeile hinzufügen
                </flux:button>
            </div>
        @endif
    @endif
@endif
