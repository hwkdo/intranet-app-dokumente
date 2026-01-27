<?php

namespace Hwkdo\IntranetAppFormwerk;
use Hwkdo\IntranetAppBase\Interfaces\IntranetAppInterface;
use Illuminate\Support\Collection;

class IntranetAppFormwerk implements IntranetAppInterface 
{
    public static function app_name(): string
    {
        return 'Formwerk';
    }

    public static function app_icon(): string
    {
        return 'magnifying-glass';
    }

    public static function identifier(): string
    {
        return 'formwerk';
    }

    public static function roles_admin(): Collection
    {
        return collect(config('intranet-app-formwerk.roles.admin'));
    }

    public static function roles_user(): Collection
    {
        return collect(config('intranet-app-formwerk.roles.user'));
    }
    
    public static function userSettingsClass(): ?string
    {
        return \Hwkdo\IntranetAppFormwerk\Data\UserSettings::class;
    }
    
    public static function appSettingsClass(): ?string
    {
        return \Hwkdo\IntranetAppFormwerk\Data\AppSettings::class;
    }

    public static function mcpServers(): array
    {
        return [];
    }
}
