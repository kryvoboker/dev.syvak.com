<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

class AppSettingsData extends Data
{
	public function __construct(
        public ?array $titles,
        public ?array $meta_titles,
        public ?array $meta_descriptions,
        public ?array $meta_keywords,
        public ?array $contact_emails,
        public ?array $contact_phones,
        public ?array $socials,
        public ?array $work_time,
        public ?array $contact_addresses,
        public ?string $coordinates,
        public ?string $iframe_map,
        public ?string $timezone,
        public ?array $image_sizes,
	) {}
}
