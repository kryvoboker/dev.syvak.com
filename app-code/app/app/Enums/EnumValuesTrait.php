<?php

declare(strict_types=1);

namespace App\Enums;

trait EnumValuesTrait
{
    /**
     * @return array
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
