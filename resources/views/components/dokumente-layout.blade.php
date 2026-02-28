@props([
    'heading' => '',
    'subheading' => '',
    'navItems' => []
])

@php
    $defaultNavItems = [
        ['label' => 'Übersicht', 'href' => route('apps.dokumente.index'), 'icon' => 'home', 'description' => 'Zurück zur Übersicht', 'buttonText' => 'Übersicht anzeigen'],
        ['label' => 'Beispielseite', 'href' => route('apps.dokumente.example'), 'icon' => 'document-text', 'description' => 'Beispielseite anzeigen', 'buttonText' => 'Beispielseite öffnen'],
        ['label' => 'Meine Einstellungen', 'href' => route('apps.dokumente.settings.user'), 'icon' => 'cog-6-tooth', 'description' => 'Persönliche Einstellungen anpassen', 'buttonText' => 'Einstellungen öffnen'],
        ['label' => 'Admin', 'href' => route('apps.dokumente.admin.index'), 'icon' => 'shield-check', 'description' => 'Administrationsbereich verwalten', 'buttonText' => 'Admin öffnen', 'permission' => 'manage-app-dokumente']
    ];
    
    $navItems = !empty($navItems) ? $navItems : $defaultNavItems;
    $customBgUrl = \Hwkdo\IntranetAppBase\Models\AppBackground::getCustomBackgroundUrl('dokumente');
@endphp

@if($customBgUrl)
    @push('app-styles')
    <style data-app-bg data-ts="{{ uniqid() }}">
        :root { --app-bg-image: url('{{ $customBgUrl }}'); }
    </style>
    @endpush
@endif

@push('app-styles')
<style>
    /*
     * Dokumente-Matrix: Spaltenköpfe und Datenzellen erzwingen weiße Schrift.
     * Flux setzt intern text-zinc-800 / text-zinc-500 auf th/td-Elementen.
     * Da text-zinc-* alphabetisch nach text-white im generierten CSS steht,
     * würde es text-white überschreiben – daher !important hier im Package.
     */
    [data-dokumente-matrix] [data-flux-column] {
        color: white !important;
    }
</style>
@endpush

<x-intranet-app-base::app-layout
    app-identifier="dokumente"
    :heading="$heading"
    :subheading="$subheading"
    :nav-items="$navItems"
    :wrap-in-card="false"
>
    {{ $slot }}
</x-intranet-app-base::app-layout>
