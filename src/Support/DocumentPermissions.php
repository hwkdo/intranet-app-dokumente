<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Support;

use Hwkdo\IntranetAppDokumente\Models\IntranetAppDokumenteSettings;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;

final class DocumentPermissions
{
    public static function upload(): string
    {
        return self::resolve('upload', 'permissionUpload');
    }

    public static function kenntnisnahme(): string
    {
        return self::resolve('kenntnisnahme', 'permissionKenntnisnahme');
    }

    public static function chooseGvp(): string
    {
        return self::resolve('choose_gvp', 'permissionChooseGvp');
    }

    public static function defaultUpload(): string
    {
        return (string) config('intranet-app-dokumente.permissions.upload', 'upload-app-dokumente');
    }

    public static function defaultKenntnisnahme(): string
    {
        return (string) config('intranet-app-dokumente.permissions.kenntnisnahme', 'kenntnisnahme-app-dokumente');
    }

    public static function defaultChooseGvp(): string
    {
        return (string) config('intranet-app-dokumente.permissions.choose_gvp', 'choose-gvp-app-dokumente');
    }

    /**
     * @return Collection<int, string>
     */
    public static function roleNamesFor(string $permission): Collection
    {
        $permission = trim($permission);
        if ($permission === '') {
            return collect();
        }

        $model = Permission::query()
            ->where('name', $permission)
            ->where('guard_name', 'web')
            ->first();

        if ($model === null) {
            return collect();
        }

        return $model->roles()
            ->orderBy('name')
            ->pluck('name');
    }

    private static function resolve(string $configKey, string $settingsProperty): string
    {
        $settings = IntranetAppDokumenteSettings::resolvedAppSettings();
        $override = trim((string) ($settings->{$settingsProperty} ?? ''));

        if ($override !== '') {
            return $override;
        }

        return (string) config("intranet-app-dokumente.permissions.{$configKey}");
    }
}
