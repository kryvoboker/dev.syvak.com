<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Models\Orders\OrderStatuses;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class OrderStatusManagementService
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): OrderStatuses
    {
        return DB::transaction(function () use ($data): OrderStatuses {
            $descriptions = (array) Arr::pull($data, 'descriptions', []);
            $status = OrderStatuses::query()->create($data);

            $this->syncDescriptions($status, $descriptions);

            return $status;
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(OrderStatuses $status, array $data): OrderStatuses
    {
        return DB::transaction(function () use ($status, $data): OrderStatuses {
            $descriptions = (array) Arr::pull($data, 'descriptions', []);
            $status->update($data);

            $this->syncDescriptions($status, $descriptions);

            return $status->fresh(['descriptions']);
        });
    }

    /**
     * @param Collection<int, OrderStatuses> $statuses
     */
    public function activate(Collection $statuses): void
    {
        DB::transaction(function () use ($statuses): void {
            $statuses->each(function (OrderStatuses $status): void {
                $status->update(['is_active' => true]);
            });
        });
    }

    /**
     * @param Collection<int, OrderStatuses> $statuses
     */
    public function deactivate(Collection $statuses): void
    {
        DB::transaction(function () use ($statuses): void {
            $statuses->each(function (OrderStatuses $status): void {
                $status->update(['is_active' => false]);
            });
        });
    }

    /**
     * @param Collection<int, OrderStatuses> $statuses
     */
    public function delete(Collection $statuses): void
    {
        DB::transaction(function () use ($statuses): void {
            $statuses->each(function (OrderStatuses $status): void {
                $status->delete();
            });
        });
    }

    /**
     * @param array<int|string, mixed> $descriptions
     */
    private function syncDescriptions(OrderStatuses $status, array $descriptions): void
    {
        foreach ($descriptions as $language_id => $description) {
            $name = trim((string) Arr::get((array) $description, 'name', ''));

            if ($name === '') {
                continue;
            }

            $status->descriptions()->updateOrCreate(
                ['language_id' => (int) $language_id],
                ['name' => $name],
            );
        }
    }
}
