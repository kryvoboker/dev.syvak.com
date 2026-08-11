<?php

declare(strict_types=1);

namespace App\Enums\Inquiries;

enum InquiryStatusEnum: string
{
    case New = 'new';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Rejected = 'rejected';
}
