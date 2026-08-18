<?php

declare(strict_types=1);

namespace App\Models\Users;

use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class UserGroup extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_active',
        'is_default',
    ];

    /**
     * @return array<string, \Stringable|string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    #[\Override]
    protected static function booted(): void
    {
        // Ensure only one default user group
        static::saving(function (UserGroup $user_group) {
            if ($user_group->is_default) {
                // Set all other user groups as non-default
                static::where('id', '!=', $user_group->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);

                // Default user group must be active
                $user_group->is_active = true;
            } else {
                // Ensure there is always one default user group
                $default_exists = static::where('is_default', true)
                    ->where('id', '!=', $user_group->id)
                    ->exists();

                if (! $default_exists) {
                    $user_group->is_default = true;
                }
            }
        });

        // Prevent deletion of default user group
        static::deleting(function (UserGroup $user_group) {
            if ($user_group->is_default) {
                throw new Exception(__('admin/users/user_groups.error_cant_delete_default_user_group'));
            }

            $active_langs = static::where('is_active', true)->count();

            if ($active_langs == 1) {
                throw new Exception(__('admin/users/user_groups.error_cant_delete_last_active_user_group'));
            }
        });
    }

    /**
     * @return Collection<int, UserGroup>
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     */
    public function getAllActiveUserGroups(): Collection
    {
        return self::query()
            ->where('is_active', true)
            ->orderByDesc('name')
            ->get();
    }

    public function getDefaultUserGroupId(): ?int
    {
        $group_id = self::query()
            ->where('is_active', true)
            ->where('is_default', true)
            ->value('id');

        return is_numeric($group_id) ? (int) $group_id : null;
    }

    public function getDefaultUserGroup(): ?self
    {
        return self::query()
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();
    }

    /** @return Collection<int, self> */
    public function getActiveUserGroups(): Collection
    {
        return self::where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}
