<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Livewire\Apps\Dokumente;

use App\Data\UserSettings;
use Hwkdo\IntranetAppBase\Support\AiUsage;
use Hwkdo\IntranetAppDokumente\Models\IntranetAppDokumenteSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Chat extends Component
{
    public function mount(): void
    {
        $this->authorize('see-app-dokumente');
    }

    #[Computed]
    public function apiKey(): string
    {
        $user = Auth::user();
        if ($user === null) {
            return '';
        }

        return trim((string) (UserSettings::from($user->settings)->ai->openWebUiApiToken ?? ''));
    }

    #[Computed]
    public function model(): string
    {
        return trim(IntranetAppDokumenteSettings::resolvedAppSettings()->openWebUiModel);
    }

    #[Computed]
    public function ready(): bool
    {
        return AiUsage::allowed()
            && $this->apiKey !== ''
            && $this->model !== '';
    }

    #[Computed]
    public function needsToken(): bool
    {
        return AiUsage::allowed() && $this->apiKey === '';
    }

    #[Computed]
    public function notice(): string
    {
        if (! AiUsage::allowed()) {
            return AiUsage::DENIED_MESSAGE;
        }

        if ($this->apiKey === '') {
            return 'Für den Chat fehlt der OpenWebUI API-Token in den Einstellungen.';
        }

        return 'Für den Chat ist in den App-Einstellungen kein OpenWebUI-Modell hinterlegt.';
    }

    public function render(): View
    {
        return view('intranet-app-dokumente::livewire.apps.dokumente.chat')
            ->layout('components.layouts.app', [
                'title' => 'Dokumente - KI-Chat',
            ]);
    }
}
