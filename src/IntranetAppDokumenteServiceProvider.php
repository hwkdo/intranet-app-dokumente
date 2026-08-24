<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente;

use Hwkdo\IntranetAppDokumente\Commands\ImportLegacyDokumenteCommand;
use Hwkdo\IntranetAppDokumente\Commands\SendDocumentReviewRemindersCommand;
use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Policies\DocumentPolicy;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
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
            ->hasCommand(ImportLegacyDokumenteCommand::class)
            ->hasCommand(SendDocumentReviewRemindersCommand::class)
            ->discoversMigrations();
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Document::class, DocumentPolicy::class);

        Livewire::addNamespace(
            'intranet-app-dokumente',
            __DIR__.'/../resources/views/livewire',
            \Hwkdo\IntranetAppDokumente\Livewire::class
        );

        $this->app->booted(function (): void {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        });

        $this->app->resolving(Schedule::class, function (): void {
            require __DIR__.'/../routes/console.php';
        });

        $this->configureTypesenseIndexSettings();
    }

    protected function configureTypesenseIndexSettings(): void
    {
        $modelSettings = Config::get('scout.typesense.model-settings', []);

        $modelSettings[Document::class] = [
            'collection-schema' => [
                'fields' => [
                    ['name' => 'id', 'type' => 'string'],
                    ['name' => 'title', 'type' => 'string', 'infix' => true],
                    ['name' => 'description', 'type' => 'string', 'infix' => true],
                    ['name' => 'category_name', 'type' => 'string', 'infix' => true],
                    ['name' => 'uploader_display', 'type' => 'string'],
                    ['name' => 'created_at', 'type' => 'int64'],
                ],
                'default_sorting_field' => 'created_at',
            ],
            'search-parameters' => [
                'query_by' => 'title,description,category_name',
                'prefix' => true,
            ],
        ];

        Config::set('scout.typesense.model-settings', $modelSettings);
    }
}
