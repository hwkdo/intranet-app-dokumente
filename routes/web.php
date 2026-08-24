<?php

declare(strict_types=1);

use Hwkdo\IntranetAppDokumente\Http\Controllers\DownloadDocumentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'can:see-app-dokumente'])->group(function (): void {
    Route::livewire('apps/dokumente', 'intranet-app-dokumente::apps.dokumente.index')->name('apps.dokumente.index');
    Route::livewire('apps/dokumente/meine', 'intranet-app-dokumente::apps.dokumente.meine-dokumente')->name('apps.dokumente.meine-dokumente');
    Route::livewire('apps/dokumente/settings/user', 'intranet-app-dokumente::apps.dokumente.settings.user')->name('apps.dokumente.settings.user');
    Route::livewire('apps/dokumente/info', 'intranet-app-dokumente::apps.dokumente.info')->name('apps.dokumente.info');
    Route::livewire('apps/dokumente/suche', 'intranet-app-dokumente::apps.dokumente.search')->name('apps.dokumente.search');

    Route::get('apps/dokumente/download/{document}', DownloadDocumentController::class)
        ->whereNumber('document')
        ->name('apps.dokumente.download');
    Route::get('apps/dokumente/download/{document}/version/{version}', DownloadDocumentController::class)
        ->whereNumber('document')
        ->whereNumber('version')
        ->name('apps.dokumente.download.version');

    Route::livewire('apps/dokumente/{document}/review', 'intranet-app-dokumente::apps.dokumente.review')
        ->whereNumber('document')
        ->name('apps.dokumente.review');
    Route::livewire('apps/dokumente/{document}/acknowledge-by-password', 'intranet-app-dokumente::apps.dokumente.acknowledge-by-password')
        ->middleware('ldap.password.confirm')
        ->whereNumber('document')
        ->name('apps.dokumente.acknowledge');
    Route::livewire('apps/dokumente/{document}', 'intranet-app-dokumente::apps.dokumente.show')
        ->whereNumber('document')
        ->name('apps.dokumente.show');
});

Route::middleware(['web', 'auth', 'can:manage-app-dokumente'])->group(function (): void {
    Route::livewire('apps/dokumente/admin', 'intranet-app-dokumente::apps.dokumente.admin.index')->name('apps.dokumente.admin.index');
    Route::get('apps/dokumente/admin/documents', function () {
        return redirect()->route('apps.dokumente.admin.index', ['tab' => 'dokumente']);
    })->name('apps.dokumente.admin.documents');
});
