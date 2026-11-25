<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Dashboard;

use Hwkdo\IntranetAppBase\Data\DashboardWidgetDefinition;
use Hwkdo\IntranetAppBase\Interfaces\DashboardWidgetProviderInterface;
use Hwkdo\IntranetAppBase\Support\AiUsage;

class DokumenteDashboardWidgetProvider implements DashboardWidgetProviderInterface
{
    /**
     * @return array<DashboardWidgetDefinition>
     */
    public static function widgets(): array
    {
        return [
            new DashboardWidgetDefinition(
                key: 'ki-chat',
                title: 'Dokumente Chat',
                description: 'Dokumente-KI-Chat mit LightRAG (gleiche Konfiguration wie die Chat-Seite)',
                component: 'intranet-app-dokumente::apps.dokumente.widgets.ki-chat',
                permission: AiUsage::PERMISSION,
                defaultW: 12,
                defaultH: 10,
                minW: 6,
                minH: 6,
                defaultEnabled: false,
                supportsItemCount: false,
            ),
        ];
    }
}
