<?php

declare(strict_types=1);

namespace App\Models\Users;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use BezhanSalleh\FilamentShield\Traits\HasPanelShield;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Override;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string|null $password
 * @property bool $is_active
 * @property string $name
 * @property string|null $lastname
 * @property int|null $user_group_id
 * @property string $email
 */
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasPanelShield;
    use HasRoles;
    use Notifiable;

    /**
     * The attributes that are mass-assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_group_id',
        'name',
        'lastname',
        'email',
        'email_verified_at',
        'telephone',
        'avatar',
        'avatar_file_name',
        'is_active',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    #[Override]
    protected function casts(): array
    {
        return [
            'user_group_id' => 'integer',
            'email_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * @param Panel $panel
     *
     * @return bool
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    #[Override]
    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->is_active === false) {
            return false;
        }

        /** @var array<int, string> $role_names */
        $role_names = $this->getRoleNames()->toArray();

        return $this->hasAnyRole($role_names);
    }

    #[Override]
    protected static function booted(): void
    {
        static::saving(function (User $user) {
            // If password is null and it's being changed, prevent saving it
            if ($user->password === null && $user->isDirty('password')) {
                // Restore the original password value from database
                $original_password = $user->getOriginal('password');
                $user->password = is_string($original_password) ? $original_password : null;
            }
        });
    }

    /**
     * @phpstan-return Attribute<mixed, mixed>
     * @psalm-return Attribute
     */
    public function telephone(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value) => $value === null ? null : clear_telephone($value),
        );
    }

    /**
     * For \App\Filament\Resources\Users\UserResource
     */
    public function getFullNameAttribute(): string
    {
        return Str::trim($this->name . ' ' . ($this->lastname ?? ''));
    }

    /**
     * For \App\Filament\Resources\Users\UserForm
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (empty($this->avatar)) {
            return null;
        }

        return Storage::url($this->avatar);
    }
}
