<?php

namespace Hwkdo\IntranetAppDokumente;
use Hwkdo\IntranetAppBase\Interfaces\IntranetAppInterface;
use Illuminate\Support\Collection;

class IntranetAppDokumente implements IntranetAppInterface 
{
    public static function app_name(): string
    {
        return 'Dokumente';
    }

    public static function app_icon(): string
    {
        return 'magnifying-glass';
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
        return \Hwkdo\IntranetAppDokumente\Data\UserSettings::class;
    }
    
    public static function appSettingsClass(): ?string
    {
        return \Hwkdo\IntranetAppDokumente\Data\AppSettings::class;
    }

    public static function mcpServers(): array
    {
        return [];
    }
}
