<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Enums;

enum AcknowledgmentReportStatusFilter: string
{
    case All = 'all';
    case Acknowledged = 'acknowledged';
    case Pending = 'pending';

    public function label(): string
    {
        return match ($this) {
            self::All => 'Alle',
            self::Acknowledged => 'Nur erfolgt',
            self::Pending => 'Nur nicht erfolgt',
        };
    }
}
