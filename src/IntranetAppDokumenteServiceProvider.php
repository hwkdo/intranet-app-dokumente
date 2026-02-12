<?php

namespace Hwkdo\IntranetAppDokumente;

use Livewire\Livewire;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class IntranetAppDokumenteServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('intranet-app-dokumente')
            ->hasConfigFile()
            ->hasViews()
            ->discoversMigrations();
    }

    public function boot(): void
    {
        parent::boot();

        Livewire::addNamespace(
            'intranet-app-dokumente',
            __DIR__.'/../resources/views/livewire',
            \Hwkdo\IntranetAppDokumente\Livewire::class
        );

        $this->app->booted(function () {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });
    }
}
