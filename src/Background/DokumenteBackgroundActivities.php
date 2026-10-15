<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Background;

use Hwkdo\IntranetAppBase\Data\BackgroundActivityDefinition;
use Hwkdo\IntranetAppBase\Data\BackgroundActivityFrequency;
use Hwkdo\IntranetAppBase\Enums\BackgroundActivityCadence;
use Hwkdo\IntranetAppDokumente\IntranetAppDokumente;

final class DokumenteBackgroundActivities
{
    /**
     * @return list<BackgroundActivityDefinition>
     */
    public static function definitions(): array
    {
        $id = IntranetAppDokumente::identifier();
        $name = IntranetAppDokumente::app_name();
        $all = BackgroundActivityCadence::cases();

        return [
            BackgroundActivityDefinition::scheduled(
                key: 'dokumente.review-reminders',
                label: 'Prüf-Erinnerungen',
                appIdentifier: $id,
                appName: $name,
                command: 'dokumente:send-review-reminders',
                frequency: BackgroundActivityFrequency::dailyAt('07:30'),
                description: 'Pflicht-Benachrichtigungen im Prüf- und Gültigkeitsfenster.',
                allowedCadences: $all,
                sort: 10,
            ),
            BackgroundActivityDefinition::scheduled(
                key: 'dokumente.lightrag-status',
                label: 'LightRAG-Status',
                appIdentifier: $id,
                appName: $name,
                command: 'dokumente:sync-lightrag-status',
                frequency: BackgroundActivityFrequency::everyMinutes(1),
                description: 'Synchronisiert den LightRAG-Status von Dokumenten.',
                allowedCadences: $all,
                minEvery: 1,
                maxEvery: 60,
                withoutOverlappingMinutes: 1440,
                sort: 20,
            ),
        ];
    }
}
