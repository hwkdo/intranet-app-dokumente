@props([
    'heading' => '',
    'subheading' => '',
    'navItems' => []
])

@php
    $defaultNavItems = [
        ['label' => 'Übersicht', 'href' => route('apps.formwerk.index'), 'icon' => 'home', 'description' => 'Zurück zur Übersicht', 'buttonText' => 'Übersicht anzeigen'],
        ['label' => 'Beispielseite', 'href' => route('apps.formwerk.example'), 'icon' => 'document-text', 'description' => 'Beispielseite anzeigen', 'buttonText' => 'Beispielseite öffnen'],
        ['label' => 'Typen', 'href' => route('apps.formwerk.typen.index'), 'icon' => 'document-duplicate', 'description' => 'Formwerk-Typen verwalten', 'buttonText' => 'Typen öffnen'],
        ['label' => 'Webhooks', 'href' => route('apps.formwerk.webhooks.index'), 'icon' => 'bell', 'description' => 'Webhook-Übersicht anzeigen', 'buttonText' => 'Webhooks öffnen'],
        ['label' => 'Meine Einstellungen', 'href' => route('apps.formwerk.settings.user'), 'icon' => 'cog-6-tooth', 'description' => 'Persönliche Einstellungen anpassen', 'buttonText' => 'Einstellungen öffnen'],
        ['label' => 'App-Info', 'href' => route('apps.formwerk.info'), 'icon' => 'information-circle', 'description' => 'Installierte Version und Release-Historie', 'buttonText' => 'App-Info anzeigen'],
        ['label' => 'Admin', 'href' => route('apps.formwerk.admin.index'), 'icon' => 'shield-check', 'description' => 'Administrationsbereich verwalten', 'buttonText' => 'Admin öffnen', 'permission' => 'manage-app-formwerk']
    ];
    
    $navItems = !empty($navItems) ? $navItems : $defaultNavItems;
    $customBgUrl = \Hwkdo\IntranetAppBase\Models\AppBackground::getCustomBackgroundUrl('formwerk');
@endphp

@if($customBgUrl)
    @push('app-styles')
    <style data-app-bg data-ts="{{ uniqid() }}">
        :root { --app-bg-image: url('{{ $customBgUrl }}'); }
    </style>
    @endpush
@endif

@if(request()->routeIs('apps.formwerk.index'))
    <x-intranet-app-base::app-layout 
        app-identifier="formwerk"
        :heading="$heading"
        :subheading="$subheading"
        :nav-items="$navItems"
        :wrap-in-card="false"
    >
        <x-intranet-app-base::app-index-auto 
            app-identifier="formwerk"
            app-name="Formwerk App"
            app-description="Generated app: Formwerk"
            :nav-items="$navItems"
            welcome-title="Willkommen zur Formwerk App"
            welcome-description="Dies ist eine Beispiel-App, die als Formwerk für neue Intranet-Apps dient."
        />
    </x-intranet-app-base::app-layout>
@else
    <x-intranet-app-base::app-layout 
        app-identifier="formwerk"
        :heading="$heading"
        :subheading="$subheading"
        :nav-items="$navItems"
        :wrap-in-card="true"
    >
        {{ $slot }}
    </x-intranet-app-base::app-layout>
@endif
