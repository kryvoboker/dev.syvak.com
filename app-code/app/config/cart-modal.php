<?php

declare(strict_types=1);

return [
    // The cart item lifetime is configured in days.
    'item_ttl_days' => 30,

    // Cleanup chunk size for deleting expired cart rows.
    'cleanup_chunk_size' => 500,
];
