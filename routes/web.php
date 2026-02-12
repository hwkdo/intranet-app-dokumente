<?php

use Hwkdo\IntranetAppDokumente\Http\Controllers\DownloadDocumentController;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'can:see-app-dokumente'])->group(function () {
    Route::livewire('apps/dokumente', 'intranet-app-dokumente::apps.dokumente.index')->name('apps.dokumente.index');
    Route::livewire('apps/dokumente/example', 'intranet-app-dokumente::apps.dokumente.example')->name('apps.dokumente.example');
    Route::livewire('apps/dokumente/settings/user', 'intranet-app-dokumente::apps.dokumente.settings.user')->name('apps.dokumente.settings.user');
    Route::get('apps/dokumente/download/{document}', DownloadDocumentController::class)
        ->name('apps.dokumente.download')
        ->scopeBindings();
});

Route::middleware(['web', 'auth', 'can:manage-app-dokumente'])->group(function () {
    Route::livewire('apps/dokumente/admin', 'intranet-app-dokumente::apps.dokumente.admin.index')->name('apps.dokumente.admin.index');
});
