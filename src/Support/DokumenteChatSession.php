<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Support;

use App\Data\UserSettings;
use Hwkdo\IntranetAppBase\Support\AiUsage;
use Hwkdo\IntranetAppDokumente\Models\IntranetAppDokumenteSettings;
use Illuminate\Support\Facades\Auth;

final class DokumenteChatSession
{
    public const LIGHTRAG_INSTANCE = 'dokumente';

    public static function apiKey(): string
    {
        $user = Auth::user();
        if ($user === null) {
            return '';
        }

        return trim((string) (UserSettings::from($user->settings)->ai->openWebUiApiToken ?? ''));
    }

    public static function model(): string
    {
        return trim(IntranetAppDokumenteSettings::resolvedAppSettings()->openWebUiModel);
    }

    public static function ready(): bool
    {
        return AiUsage::allowed()
            && self::apiKey() !== ''
            && self::model() !== '';
    }

    public static function needsToken(): bool
    {
        return AiUsage::allowed() && self::apiKey() === '';
    }

    public static function notice(): string
    {
        if (! AiUsage::allowed()) {
            return AiUsage::DENIED_MESSAGE;
        }

        if (self::apiKey() === '') {
            return 'Für den Chat fehlt der OpenWebUI API-Token in den Einstellungen.';
        }

        return 'Für den Chat ist in den App-Einstellungen kein OpenWebUI-Modell hinterlegt.';
    }

    /**
     * @return list<string>
     */
    public static function suggestions(): array
    {
        return [
            'Welche Dokumente gibt es zur Reisekostenabrechnung?',
            'Fasse die Regelungen zur Arbeitszeit zusammen.',
            'Wo steht, wer eine Bestellung freigeben darf?',
        ];
    }

    /**
     * @return array{
     *     appIdentifier: string,
     *     appName: string,
     *     model: string,
     *     apiKey: string,
     *     baseUrl: mixed,
     *     useMcpTools: false,
     *     lightragInstance: string,
     *     embedded: bool,
     *     suggestions: list<string>,
     *     embeddedTitle?: string,
     *     fullscreenUrl?: string
     * }
     */
    public static function prismParameters(bool $embedded = false): array
    {
        $parameters = [
            'appIdentifier' => 'dokumente',
            'appName' => 'Dokumente',
            'model' => self::model(),
            'apiKey' => self::apiKey(),
            'baseUrl' => config('openwebui-api-laravel.base_api_url'),
            'useMcpTools' => false,
            'lightragInstance' => self::LIGHTRAG_INSTANCE,
            'embedded' => $embedded,
            'suggestions' => self::suggestions(),
        ];

        if ($embedded) {
            $parameters['embeddedTitle'] = 'Dokumente Chat';
            $parameters['fullscreenUrl'] = route('apps.dokumente.chat');
        }

        return $parameters;
    }
}
