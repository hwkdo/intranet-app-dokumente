<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Livewire\Apps\Dokumente;

use Hwkdo\IntranetAppDokumente\Support\DokumenteChatSession;
use Illuminate\Contracts\View\View;
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
        return DokumenteChatSession::apiKey();
    }

    #[Computed]
    public function model(): string
    {
        return DokumenteChatSession::model();
    }

    #[Computed]
    public function ready(): bool
    {
        return DokumenteChatSession::ready();
    }

    #[Computed]
    public function needsToken(): bool
    {
        return DokumenteChatSession::needsToken();
    }

    #[Computed]
    public function notice(): string
    {
        return DokumenteChatSession::notice();
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function prismParameters(): array
    {
        return DokumenteChatSession::prismParameters();
    }

    public function render(): View
    {
        return view('intranet-app-dokumente::livewire.apps.dokumente.chat')
            ->layout('components.layouts.app', [
                'title' => 'Dokumente - KI-Chat',
            ]);
    }
}
