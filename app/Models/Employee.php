<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable as AuthenticatableTrait;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model implements Authenticatable
{
    use HasFactory, AuthenticatableTrait;

    protected $table = 'employee';

    protected $primaryKey = 'employee_id';

    public $timestamps = false;

    protected $fillable = [
        'role_id',
        'first_name',
        'last_name',
        'username',
        'password',
        'contact_number',
        'hire_date',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'hire_date' => 'date',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id', 'role_id');
    }

    public function saleTransactions(): HasMany
    {
        return $this->hasMany(SaleTransaction::class, 'employee_id', 'employee_id');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * The employee's real name, e.g. "Juan Dela Cruz".
     *
     * The employee table is the auth table, so views that need a human-readable
     * identity must call this instead of reaching for `->employee` (there is no
     * such relation) or leaking the username/employee_id into the UI.
     */
    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    /**
     * Name for display, falling back to the username for seed rows that were
     * created without a first/last name.
     */
    public function displayName(): string
    {
        return $this->fullName() !== '' ? $this->fullName() : (string) $this->username;
    }

    public function hasRole(string ...$roles): bool
    {
        $roleName = $this->role?->role_name;

        if ($roleName === null) {
            return false;
        }

        $allowed = array_map(strtolower(...), $roles);

        return in_array(strtolower($roleName), $allowed, true);
    }

    public function allowedModules(): array
    {
        return collect(config('roles.modules', []))
            ->filter(fn (array $roles) => $this->hasRole(...$roles))
            ->keys()
            ->all();
    }
}
