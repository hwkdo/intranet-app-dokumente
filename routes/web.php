<?php

use Hwkdo\IntranetAppFormwerk\Http\Controllers\WebhookAttachmentController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::webhooks('kunden/webhooks/formwerk', 'formwerk');

Route::middleware(['web','auth','can:see-app-formwerk'])->group(function () {        
    Volt::route('apps/formwerk', 'apps.formwerk.index')->name('apps.formwerk.index');
    Volt::route('apps/formwerk/example', 'apps.formwerk.example')->name('apps.formwerk.example');
    Volt::route('apps/formwerk/settings/user', 'apps.formwerk.settings.user')->name('apps.formwerk.settings.user');
    Volt::route('apps/formwerk/info', 'apps.formwerk.info')->name('apps.formwerk.info');
    
    // Typen-Verwaltung
    Volt::route('apps/formwerk/typen', 'apps.formwerk.typen.index')->name('apps.formwerk.typen.index');
    Volt::route('apps/formwerk/typen/{typ}/edit', 'apps.formwerk.typen.edit')->name('apps.formwerk.typen.edit');
    
    // Webhooks-Übersicht
    Volt::route('apps/formwerk/webhooks', 'apps.formwerk.webhooks.index')->name('apps.formwerk.webhooks.index');
    Volt::route('apps/formwerk/webhooks/{id}/mail', 'apps.formwerk.webhooks.show-mail')->name('apps.formwerk.webhooks.show-mail');
    Volt::route('apps/formwerk/webhooks/{id}/payload', 'apps.formwerk.webhooks.show-payload')->name('apps.formwerk.webhooks.show-payload');
    Route::get('apps/formwerk/webhooks/{webhookId}/attachment', WebhookAttachmentController::class)
        ->name('apps.formwerk.webhooks.attachments.download');
});

Route::middleware(['web','auth','can:manage-app-formwerk'])->group(function () {
    Volt::route('apps/formwerk/admin', 'apps.formwerk.admin.index')->name('apps.formwerk.admin.index');
    
    // Typen erstellen (nur für Admins)
    Volt::route('apps/formwerk/typen/create', 'apps.formwerk.typen.create')->name('apps.formwerk.typen.create');
});
