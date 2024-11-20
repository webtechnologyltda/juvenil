<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\RoleEnum;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory;
    use Notifiable;
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function hasPermission(Permission $permission)
    {
        return $this->hasAnyRoles($permission->roles());
    }

    public function hasAnyRoles($roles)
    {

        if (is_array($roles) || is_object($roles)) {

            foreach ($roles as $role) {
                if ($this->roles->contains('name', $role->name)) {
                    return $this->roles->contains('name', $role->name);
                }
            }
        }

        return $this->roles->contains('name', $roles);

    }

    public function isSuperAdmin()
    {
        return $this->roles->contains('id', RoleEnum::SuperAdministrador->value);
    }
}
