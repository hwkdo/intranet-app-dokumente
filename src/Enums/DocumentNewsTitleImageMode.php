<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Enums;

enum DocumentNewsTitleImageMode: string
{
    case Auto = 'auto';
    case Custom = 'custom';
    case Default = 'default';

    public function label(): string
    {
        return match ($this) {
            self::Auto => 'Automatisch generiertes Titelbild verwenden',
            self::Custom => 'Eigenes Titelbild',
            self::Default => 'Standard-News-Titelbild verwenden',
        };
    }
}
