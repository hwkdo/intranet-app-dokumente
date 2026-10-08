<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Enums;

enum DocumentLightRagStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Processed = 'processed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'wartet',
            self::Processing => 'wird verarbeitet',
            self::Processed => 'in LightRAG',
            self::Failed => 'fehlgeschlagen',
        };
    }
}
