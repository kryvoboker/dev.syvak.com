<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class AppSettingsData extends Data
{
    /**
     * @param Collection<int, mixed>|null $titles
     * @param Collection<int, mixed>|null $meta_titles
     * @param Collection<int, mixed>|null $meta_descriptions
     * @param Collection<int, mixed>|null $meta_keywords
     * @param Collection<int, mixed>|null $contact_emails
     * @param Collection<int, mixed>|null $contact_phones
     * @param Collection<int, mixed>|null $socials
     * @param Collection<int, mixed>|null $work_time
     * @param Collection<int, mixed>|null $contact_addresses
     * @param Collection<int, mixed>|null $image_sizes
     * @param Collection<int, mixed>|null $system_settings
     * @param Collection<int, mixed>|null $user_settings
     * @param Collection<int, mixed>|null $ai_settings
     * @param Collection<int, mixed>|null $global_configs
     */
    public function __construct(
        public ?Collection $titles,
        public ?Collection $meta_titles,
        public ?Collection $meta_descriptions,
        public ?Collection $meta_keywords,
        public ?Collection $contact_emails,
        public ?Collection $contact_phones,
        public ?Collection $socials,
        public ?Collection $work_time,
        public ?Collection $contact_addresses,
        public ?string $coordinates,
        public ?string $iframe_map,
        public ?string $timezone,
        public ?Collection $image_sizes,
        public ?Collection $system_settings,
        public ?Collection $user_settings,
        public ?Collection $ai_settings,
        public ?Collection $global_configs,
        public ?int $language_id,
        public ?int $user_group_id,
    ) {
    }

    /**
     * Cast arrays to collections when creating from array.
     */
    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            titles           : isset($data['titles']) ? collect((array) $data['titles']) : null,
            meta_titles      : isset($data['meta_titles']) ? collect((array) $data['meta_titles']) : null,
            meta_descriptions: isset($data['meta_descriptions']) ? collect((array) $data['meta_descriptions']) : null,
            meta_keywords    : isset($data['meta_keywords']) ? collect((array) $data['meta_keywords']) : null,
            contact_emails   : isset($data['contact_emails']) ? collect((array) $data['contact_emails']) : null,
            contact_phones   : isset($data['contact_phones']) ? collect((array) $data['contact_phones']) : null,
            socials          : isset($data['socials']) ? collect((array) $data['socials']) : null,
            work_time        : isset($data['work_time']) ? collect((array) $data['work_time']) : null,
            contact_addresses: isset($data['contact_addresses']) ? collect((array) $data['contact_addresses']) : null,
            coordinates      : $data['coordinates'] ?? null,
            iframe_map       : $data['iframe_map'] ?? null,
            timezone         : $data['timezone'] ?? null,
            image_sizes      : isset($data['image_sizes']) ? collect((array) $data['image_sizes']) : null,
            system_settings  : isset($data['system_settings']) ? collect((array) $data['system_settings']) : null,
            user_settings    : isset($data['user_settings']) ? collect((array) $data['user_settings']) : null,
            ai_settings      : isset($data['ai_settings']) ? collect((array) $data['ai_settings']) : null,
            global_configs   : isset($data['global_configs']) ? collect((array) $data['global_configs']) : null,
            language_id      : $data['language_id'] ?? null,
            user_group_id    : $data['user_group_id'] ?? null,
        );
    }
}
