<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Services;

use App\Models\Kategorie;
use App\Models\User;
use Hwkdo\IntranetAppBase\Models\IntranetNotificationPreference;
use Hwkdo\IntranetAppBase\Services\NotificationTypeCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

/**
 * Empfängerlogik muss synchron bleiben mit NewsPublishNotificationService::shouldNotifyUser()
 * und DocumentNotificationDispatcher::notifyAcknowledgmentRequired().
 */
class DocumentUploadRecipientEstimator
{
    public function __construct(
        private readonly NotificationTypeCatalog $catalog,
    ) {}

    public function newsCategoryName(): string
    {
        return DocumentNewsService::CATEGORY_NAME;
    }

    public function newsRecipients(): int
    {
        $kategorie = Kategorie::query()
            ->where('name', DocumentNewsService::CATEGORY_NAME)
            ->where('parent_id', Kategorie::NEWS_ROOT_ID)
            ->first(['id', 'subscription_required']);

        if ($kategorie === null || (bool) $kategorie->subscription_required) {
            return $this->activeUsers()->count();
        }

        $typeKey = 'news.category.'.$kategorie->id;
        $definition = $this->catalog->find($typeKey);

        if ($definition === null || $definition->mandatory || ! Schema::hasTable('intranet_notification_preferences')) {
            return $definition === null || $definition->mandatory || $definition->defaultEnabled
                ? $this->activeUsers()->count()
                : 0;
        }

        $overrides = IntranetNotificationPreference::query()
            ->where('type_key', $typeKey)
            ->where('enabled', ! $definition->defaultEnabled)
            ->select('user_id');

        return $definition->defaultEnabled
            ? $this->activeUsers()->whereNotIn('id', $overrides)->count()
            : $this->activeUsers()->whereIn('id', $overrides)->count();
    }

    public function acknowledgmentRecipients(): int
    {
        $userClass = config('intranet-app-dokumente.user_model', User::class);

        return $userClass::permission('see-app-dokumente')->aktiv()->count();
    }

    private function activeUsers(): Builder
    {
        $userClass = config('intranet-app-dokumente.user_model', User::class);

        return $userClass::query()->aktiv();
    }
}
