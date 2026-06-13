<?php

namespace App\Models;

use App\Support\SubscriptionModuleCatalog;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'current_company_id',
        'name',
        'email',
        'email_verified_at',
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

    public function currentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'current_company_id');
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot('role', 'joined_at')
            ->withTimestamps();
    }

    public function tenantNotifications(): HasMany
    {
        return $this->hasMany(TenantNotification::class);
    }

    public function currentCompanyRole(): ?string
    {
        if (! $this->current_company_id) {
            return null;
        }

        return $this->companies()
            ->whereKey($this->current_company_id)
            ->value('company_user.role');
    }

    /**
     * @param  array<int, string>|string  $roles
     */
    public function hasCurrentCompanyRole(array|string $roles): bool
    {
        $allowedRoles = is_array($roles) ? $roles : [$roles];

        return in_array($this->currentCompanyRole(), $allowedRoles, true);
    }

    public function hasCurrentCompanyPermission(string $permission): bool
    {
        $role = $this->currentCompanyRole();
        $company = $this->currentCompany;

        if (! $company || ! app(SubscriptionModuleCatalog::class)->allowsPermission($company, $permission)) {
            return false;
        }

        if ($role === 'owner') {
            return true;
        }

        if (! $this->current_company_id || ! $role) {
            return false;
        }

        $companyRole = CompanyRole::where('company_id', $this->current_company_id)
            ->where('slug', $role)
            ->first();

        return in_array($permission, $companyRole?->permissions ?? [], true);
    }
}
