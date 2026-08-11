<?php

declare(strict_types=1);

namespace App\Enums\Inquiries;

enum InquiryResponseDeliveryStatusEnum: string
{
    case Sent = 'sent';
    case NotSent = 'not_sent';
    case Failed = 'failed';
}
