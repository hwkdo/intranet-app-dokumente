<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Enums;

enum AcknowledgmentMethod: string
{
    case Password = 'password';
    case Formwerk = 'formwerk';
}
