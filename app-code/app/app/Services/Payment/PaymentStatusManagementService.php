<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Models\Payment\PaymentStatuses;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

final class PaymentStatusManagementService
{
    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): PaymentStatuses
    {
        return DB::transaction(function () use ($data): PaymentStatuses {
            $descriptions = (array) Arr::pull($data, 'descriptions', []);
            $status = PaymentStatuses::query()->create($data);

            $this->syncDescriptions($status, $descriptions);

            return $status;
        });
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(PaymentStatuses $status, array $data): PaymentStatuses
    {
        return DB::transaction(function () use ($status, $data): PaymentStatuses {
            $descriptions = (array) Arr::pull($data, 'descriptions', []);
            $status->update($data);

            $this->syncDescriptions($status, $descriptions);

            return $status->fresh(['descriptions']);
        });
    }

    /**
     * @param Collection<int, PaymentStatuses> $statuses
     */
    public function activate(Collection $statuses): void
    {
        DB::transaction(function () use ($statuses): void {
            $statuses->each(function (PaymentStatuses $status): void {
                $status->update(['is_active' => true]);
            });
        });
    }

    /**
     * @param Collection<int, PaymentStatuses> $statuses
     */
    public function deactivate(Collection $statuses): void
    {
        DB::transaction(function () use ($statuses): void {
            $statuses->each(function (PaymentStatuses $status): void {
                $status->update(['is_active' => false]);
            });
        });
    }

    /**
     * @param Collection<int, PaymentStatuses> $statuses
     */
    public function delete(Collection $statuses): void
    {
        DB::transaction(function () use ($statuses): void {
            $statuses->each(function (PaymentStatuses $status): void {
                $status->delete();
            });
        });
    }

    /**
     * @param array<int|string, mixed> $descriptions
     */
    private function syncDescriptions(PaymentStatuses $status, array $descriptions): void
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
