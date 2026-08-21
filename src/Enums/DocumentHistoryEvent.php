<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Enums;

enum DocumentHistoryEvent: string
{
    case Created = 'created';
    case Extended = 'extended';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Erstellt',
            self::Extended => 'Gültigkeit verlängert',
            self::Updated => 'Aktualisiert',
            self::Deleted => 'Gelöscht',
            self::Restored => 'Wiederhergestellt',
        };
    }
}
