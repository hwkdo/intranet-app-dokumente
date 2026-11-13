<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Livewire\Apps\Dokumente;

use App\Data\UserSettings;
use Hwkdo\IntranetAppBase\Support\AiUsage;
use Hwkdo\OpenwebuiApiLaravel\Services\OpenWebUiModelService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Chat extends Component
{
    public string $selectedModel = '';

    public string $modelError = '';

    /**
     * @var list<array{id: string, name: string}>
     */
    public array $models = [];

    public function mount(OpenWebUiModelService $models): void
    {
        $this->authorize('see-app-dokumente');

        if (! AiUsage::allowed() || $this->apiKey === '') {
            return;
        }

        try {
            $this->models = $models->listForToken($this->apiKey);
            $this->selectedModel = $this->models[0]['id'] ?? '';
        } catch (\Throwable $exception) {
            $this->modelError = $exception->getMessage();
        }
    }

    public function updatedSelectedModel(string $model): void
    {
        $ids = array_column($this->models, 'id');
        if (! in_array($model, $ids, true)) {
            $this->selectedModel = $ids[0] ?? '';
        }
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
    public function ready(): bool
    {
        return AiUsage::allowed()
            && $this->apiKey !== ''
            && $this->modelError === ''
            && $this->selectedModel !== '';
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

        if ($this->modelError !== '') {
            return $this->modelError;
        }

        return 'OpenWebUI hat für diesen Token keine Modelle geliefert.';
    }

    public function render(): View
    {
        return view('intranet-app-dokumente::livewire.apps.dokumente.chat')
            ->layout('components.layouts.app', [
                'title' => 'Dokumente - KI-Chat',
            ]);
    }
}
