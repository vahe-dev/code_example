<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * App\Models\Group
 *
 * @property int $id
 * @property int|null $parent_id
 * @property string $name
 * @property int $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Group> $childrenGroups
 * @property-read int|null $children_groups_count
 * @property-read Group|null $parentGroup
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reservoir> $reservoirs
 * @property-read int|null $reservoirs_count
 * @method static \Illuminate\Database\Eloquent\Builder|Group newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Group newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Group query()
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereUpdatedAt($value)
 * @property int $is_company
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserGroup> $mapping
 * @property-read int|null $mapping_count
 * @method static \Illuminate\Database\Eloquent\Builder|Group whereIsCompany($value)
 * @mixin \Eloquent
 */
class Group extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'parent_id',
        'is_company',
        'is_sub_group',
        'status',
    ];

    protected $hidden = [
        'laravel_through_key'
    ];

    public function childrenGroups(): HasMany
    {
        return $this->hasMany(Group::class, 'parent_id');
    }

    public function subGroups(): HasMany
    {
        return $this->hasMany(Group::class, 'parent_id')->where('is_sub_group', true);
    }

    public function parentGroup(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'parent_id');
    }

    public function reservoirs(): HasMany
    {
        return $this->hasMany(Reservoir::class);
    }

    public function mapping(): HasMany
    {
        return $this->hasMany(UserGroup::class, 'group_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_groups')->withTimestamps();
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Group::class, 'parent_id');
    }
}
