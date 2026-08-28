<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente;

use Hwkdo\IntranetAppBase\Data\NotificationTypeDefinition;
use Hwkdo\IntranetAppBase\Interfaces\IntranetAppInterface;
use Hwkdo\IntranetAppBase\Interfaces\ProvidesNotificationsInterface;
use Hwkdo\IntranetAppBase\Interfaces\ProvidesSearchInterface;
use Hwkdo\IntranetAppBase\Interfaces\ProvidesTasksInterface;
use Hwkdo\IntranetAppBase\Interfaces\TaskProviderInterface;
use Hwkdo\IntranetAppDokumente\Data\AppSettings;
use Hwkdo\IntranetAppDokumente\Search\DocumentsSearchSource;
use Hwkdo\IntranetAppDokumente\Tasks\DocumentReviewTaskProvider;
use Hwkdo\IntranetAppDokumente\Tasks\PendingAcknowledgmentTaskProvider;
use Illuminate\Support\Collection;

class IntranetAppDokumente implements IntranetAppInterface, ProvidesNotificationsInterface, ProvidesSearchInterface, ProvidesTasksInterface
{
    public static function app_name(): string
    {
        return 'Dokumente';
    }

    public static function app_icon(): string
    {
        return 'document-text';
    }

    public static function identifier(): string
    {
        return 'dokumente';
    }

    public static function roles_admin(): Collection
    {
        return collect(config('intranet-app-dokumente.roles.admin'));
    }

    public static function roles_user(): Collection
    {
        return collect(config('intranet-app-dokumente.roles.user'));
    }

    public static function userSettingsClass(): ?string
    {
        return null;
    }

    public static function appSettingsClass(): ?string
    {
        return AppSettings::class;
    }

    public static function mcpServers(): array
    {
        return [];
    }

    /**
     * @return array<class-string<TaskProviderInterface>>
     */
    public static function taskProviders(): array
    {
        return [
            PendingAcknowledgmentTaskProvider::class,
            DocumentReviewTaskProvider::class,
        ];
    }

    public static function notificationTypes(): array
    {
        return [
            new NotificationTypeDefinition(
                key: 'dokumente.acknowledgment_required',
                label: 'Kenntnisnahme erforderlich',
                appIdentifier: self::identifier(),
                appName: self::app_name(),
                description: 'Ein Dokument erfordert Ihre Kenntnisnahme per Passwortbestätigung.',
                mandatory: true,
            ),
            new NotificationTypeDefinition(
                key: 'dokumente.review_required',
                label: 'Dokument prüfen',
                appIdentifier: self::identifier(),
                appName: self::app_name(),
                description: 'Ein Dokument muss geprüft werden (verlängern, aktualisieren oder löschen).',
                mandatory: true,
            ),
        ];
    }

    /**
     * @return list<class-string<\Hwkdo\IntranetAppBase\Interfaces\SearchSourceInterface>>
     */
    public static function searchSources(): array
    {
        return [
            DocumentsSearchSource::class,
        ];
    }
}
