<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Propaganistas\LaravelPhone\Casts\RawPhoneNumberCast;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

/**
 * @property object $pivot
 * @method currentAccessToken()
 */
class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory;
    use HasProfilePhoto;
    use HasTeams;
    use Notifiable;
    use TwoFactorAuthenticatable;
    use HasRoles;
    use LogsActivity;

    const DEFAULT_NEW_USER = 1;
    const DEFAULT_ON_HOLD_USER = 2;
    const DEFAULT_REGISTERED_USER = 3;

    /**
     * The attributes that are mass assignable.
     *
     * @var string[]
     */
    protected $fillable = [
        'name',
        'surname',
        'email',
        'phone_number',
        'password',
        'church_id',
        'info',
        'profile_photo_path',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_number' => RawPhoneNumberCast::class.':AM',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = [
        'profile_photo_url',
        'full_name',
        'main_permissions',
        'churches_permissions',
    ];

    public function can($abilities, $arguments = []): ?bool
    {
        if (parent::can($abilities, $arguments)) {
            return true;
        }

        // For Churches, we are checking only 1 ability
        $ability = is_array($abilities) ? $abilities[0] : $abilities;

        if (! in_array($ability, [
            ...Role::defaultChurchPermissions(),
            ...Role::defaultChurchOtherPermissions(),
            ...Role::defaultApplicationPermissions(),
        ])) {
            return false;
        }

        $cacheData = $this->getAbilityCacheData($ability);
        if (!$cacheData['canAccess']) {
            return false;
        }

        $churchIds = $cacheData['abilityChurchIds'];
        if (!empty($cacheData['abilityChurchIds'])) {
            $cacheData['canAccess'] = true; // Important
            $reqChurchIds = $this->getRequestedChurchIds();

            // Church ids where user has access
            $churchIds = !empty($reqChurchIds)
                ? array_values(array_intersect($cacheData['abilityChurchIds'], $reqChurchIds))
                : $cacheData['abilityChurchIds'];
        }
        request()->merge(['church_ids' => array_unique($churchIds)]); // important
        return $cacheData['canAccess'];
    }

    /**
     * @return LogOptions
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'surname',
                'email',
                'email_verified_at',
                'status',
                'phone_number',
                'info',
                'church_id',
                'profile_photo_path',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return trim($this->name . ' ' . $this->surname);
    }

    /**
     * @return array
     */
    public function getMainPermissionsAttribute(): array
    {
        return $this->getAllPermissionNames();
    }

    /**
     * @return array
     */
    public function getChurchesPermissionsAttribute(): array
    {
        return $this->getChurchUserRolePermissions();
    }

    public function getChurchUserRolePermissions(array $churchIds = null): array
    {
        $tableNames = config('permission.table_names');
        $itemsQuery = ChurchUser::query()
            ->join($tableNames['model_has_permissions'], $tableNames['model_has_permissions'] . '.model_id', '=', 'church_users.church_role_id')
            ->join('permissions', $tableNames['model_has_permissions'] . '.permission_id', '=', 'permissions.id')
            ->where('church_users.user_id', $this->id) // important
            ->where($tableNames['model_has_permissions'] . '.model_type', 'App\\Models\\ChurchRole'); // important

        if (! empty($churchIds)) {
            if (! is_array($churchIds)) {
                $churchIds = [$churchIds];
            }
            $itemsQuery->whereIn('church_users.church_id', $churchIds);
        }

        $items = $itemsQuery->get(['church_users.church_id', 'permissions.name'])
            ->toArray();

        $permissions = [];
        foreach ($items as $item) {
            $permissions[$item['church_id']][] = $item['name'];
        }
        return $permissions;
    }

    /**
     * Get the church for the user.
     */
    public function church(): BelongsTo
    {
        return $this->belongsTo(Church::class);
    }

    /**
     * Get the churches for the user.
     */
    public function churches(): BelongsToMany
    {
        return $this->belongsToMany(Church::class, ChurchUser::class);
    }

    /**
     * Get the churches for the user.
     */
    public function churchesRoles(): BelongsToMany
    {
        return $this->belongsToMany(ChurchRole::class, ChurchUser::class);
    }

    /**
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::SUPER_ADMIN);
    }

    /**
     * @return bool
     */
    public function isBeliever(): bool
    {
        return $this->hasRole(Role::BELIEVER);
    }

    /**
     * @return bool
     */
    public function isPriest(): bool
    {
        return $this->hasRole(Role::PRIEST);
    }

    /**
     * @return array
     */
    public function getAllPermissionNames(): array
    {
        return $this->getAllPermissions()->map(fn($permission) => $permission->name)->toArray();
    }

    /**
     * @param string $ability
     * @return array
     */
    public function getAbilityCacheData(string $ability): array
    {
        try {
            $cacheKey =  'user_' . $this->id . '_permissions_' . str_replace(' ','_', $ability);
            // Important: here we are adding tag =  'permissions_user_id_' . $this->id for individual user permissions,
            // for example: when we want to remove all permissions for user we can use below flush command:
            // \Cache::store(config('cache.default'))->tags('permissions_user_id_' . $loggedUser->id)->flush();
            $cacheTags = ['permissions',  'permissions_user_id_' . $this->id];
            $cacheData = Cache::store(config('cache.default'))->tags($cacheTags)->get($cacheKey);
            if (!empty($cacheData)) return $cacheData;

            // this is calling one time if cache is empty
            $abilityIds = $this->getAbilityChurchIds($ability);
            $cacheData = [
                'abilityChurchIds' => $abilityIds['abilityChurchIds'],
                'canAccess' => !empty($abilityIds['abilityChurchIds']),
            ];
            // only first time we are adding needed permissions to cache
            Cache::store(config('cache.default'))->tags($cacheTags)->put($cacheKey, $cacheData, now()->addHours(24));
            return $cacheData;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
        return [
            'abilityChurchIds' => [],
            'canAccess' => false,
        ];
    }

    /**
     * @return array
     */
    public function getChurchIds(): array
    {
        return $this->churches()->pluck('churches.id')->toArray();
    }

    /**
     * @param string $ability
     * @param array|null $churchIds
     * @return array
     */
    public function getChurchIdsWithAbility(string $ability, array $churchIds = null): array
    {
        $abilityChurchIds = [];
        if (!$churchIds) {
            $churchIds = $this->getChurchIds();
        }
        $churchUserRolePermissions = $this->getChurchUserRolePermissions($churchIds);
        foreach ($churchUserRolePermissions as $churchId => $churchPermissions) {
            if (in_array($ability, $churchPermissions)) {
                $abilityChurchIds[] = $churchId;
            }
        }
        return $abilityChurchIds;
    }

    /**
     * @param string $ability
     * @return array
     */
    private function getAbilityChurchIds(string $ability): array
    {
        $churchIds = $this->getChurchIds();
        $abilityChurchIds = $this->getChurchIdsWithAbility($ability, $churchIds);
        return [
            'abilityChurchIds' => $abilityChurchIds,
        ];
    }

    /**
     * @return array
     */
    private function getRequestedChurchIds(): array
    {
        $churchIds = [];
        if (!empty(request()->church_ids)) {
            $churchIds = is_array(request()->church_ids) ? request()->church_ids : [request()->church_ids];
        }
        return $churchIds;
    }
}
