<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Policies;

use Hwkdo\IntranetAppDokumente\Models\Document;
use Hwkdo\IntranetAppDokumente\Support\DocumentPermissions;
use Illuminate\Contracts\Auth\Access\Authorizable;

class DocumentPolicy
{
    public function view(Authorizable $user, Document $document): bool
    {
        return $user->can('see-app-dokumente');
    }

    public function create(Authorizable $user): bool
    {
        return $user->can(DocumentPermissions::upload());
    }

    public function update(Authorizable $user, Document $document): bool
    {
        return $this->canManageDocument($user, $document);
    }

    public function delete(Authorizable $user, Document $document): bool
    {
        return $this->canManageDocument($user, $document);
    }

    public function review(Authorizable $user, Document $document): bool
    {
        return $this->canManageDocument($user, $document);
    }

    public function setAcknowledgment(Authorizable $user): bool
    {
        return $user->can(DocumentPermissions::kenntnisnahme());
    }

    public function viewAcknowledgmentReport(Authorizable $user, Document $document): bool
    {
        if (! $document->requires_acknowledgment) {
            return false;
        }

        return $this->canManageDocument($user, $document);
    }

    protected function canManageDocument(Authorizable $user, Document $document): bool
    {
        if ($user->can('manage-app-dokumente')) {
            return true;
        }

        $userId = method_exists($user, 'getAuthIdentifier') ? (int) $user->getAuthIdentifier() : 0;

        return $userId > 0 && (
            (int) $document->uploader_id === $userId
            || (int) $document->responsible_id === $userId
        );
    }
}
