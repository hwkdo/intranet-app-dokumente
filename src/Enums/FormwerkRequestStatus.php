<?php

declare(strict_types=1);

namespace Hwkdo\IntranetAppDokumente\Enums;

enum FormwerkRequestStatus: string
{
    case Pending = 'pending';
    case WebhookReceived = 'webhook_received';
    case Completed = 'completed';
}
