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
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    use HasPanelShield {
        HasPanelShield::canAccessPanel as shieldCanAccessPanel;
    }

    /**
     * The attributes that are mass assignable.
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
    protected function casts(): array
    {
        return [
            'user_group_id'     => 'integer',
            'email_verified_at' => 'datetime',
            'is_active'         => 'boolean',
            'password'          => 'hashed',
        ];
    }

    /**
     * @param Panel $panel
     *
     * @return bool
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->is_active === false) {
            return false;
        }

        return $this->shieldCanAccessPanel($panel);
    }

    /**
     * @return void
     */
    protected static function booted(): void
    {
        static::saving(function (User $user) {
            // If password is null and it's being changed, prevent saving it
            if ($user->password === null && $user->isDirty('password')) {
                // Restore the original password value from database
                $user->password = $user->getOriginal('password');
            }
        });
    }

    /**
     * @return Attribute
     */
    public function telephone(): Attribute
    {
        return Attribute::make(
            set: fn(?string $value) => $value === null ? null : clear_telephone($value),
        );
    }

    /**
     * For \App\Filament\Resources\Users\UserResource
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->name . ' ' . ($this->lastname ?? ''));
    }

    /**
     * For \App\Filament\Resources\Users\UserForm
     *
     * @return string|null
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if (empty($this->avatar)) {
            return null;
        }

        return Storage::url($this->avatar);
    }
}
