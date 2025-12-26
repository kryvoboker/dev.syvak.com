<?php

declare(strict_types=1);

namespace App\Data;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class AppSettingsData extends Data
{
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
        public ?string     $coordinates,
        public ?string     $iframe_map,
        public ?string     $timezone,
        public ?Collection $image_sizes,
        public ?int        $language_id,
        public ?int        $user_group_id,
    ) {}

    /**
     * Cast arrays to collections when creating from array.
     *
     * @param array $data
     *
     * @return static
     */
    public static function fromArray(array $data): static
    {
        return new static(
            titles           : isset($data['titles']) ? collect($data['titles']) : null,
            meta_titles      : isset($data['meta_titles']) ? collect($data['meta_titles']) : null,
            meta_descriptions: isset($data['meta_descriptions']) ? collect($data['meta_descriptions']) : null,
            meta_keywords    : isset($data['meta_keywords']) ? collect($data['meta_keywords']) : null,
            contact_emails   : isset($data['contact_emails']) ? collect($data['contact_emails']) : null,
            contact_phones   : isset($data['contact_phones']) ? collect($data['contact_phones']) : null,
            socials          : isset($data['socials']) ? collect($data['socials']) : null,
            work_time        : isset($data['work_time']) ? collect($data['work_time']) : null,
            contact_addresses: isset($data['contact_addresses']) ? collect($data['contact_addresses']) : null,
            coordinates      : $data['coordinates'] ?? null,
            iframe_map       : $data['iframe_map'] ?? null,
            timezone         : $data['timezone'] ?? null,
            image_sizes      : isset($data['image_sizes']) ? collect($data['image_sizes']) : null,
            language_id      : $data['language_id'] ?? null,
            user_group_id    : $data['user_group_id'] ?? null,
        );
    }
}
