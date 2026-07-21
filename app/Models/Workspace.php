<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Workspace is an organizational unit grouping links, domains, and team members.
 *
 * Each workspace is owned by one user (the `owner_id`) and can have additional
 * members with granular roles via the workspace_user pivot. Feature limits
 * (custom_domain_limit) can be overridden per-workspace to support enterprise deals.
 *
 * @property int $id
 * @property int $owner_id
 * @property string $name
 * @property string $slug
 * @property string|null $logo
 * @property int $custom_domain_limit
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User $owner
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $members
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Link> $links
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CustomDomain> $customDomains
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ApiKey> $apiKeys
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Webhook> $webhooks
 */
class Workspace extends Model
{
    /** @use HasFactory<\Database\Factories\WorkspaceFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'logo',
        'custom_domain_limit',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'custom_domain_limit' => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The user who owns this workspace.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * All members of this workspace (including the owner via the pivot).
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Short links scoped to this workspace.
     */
    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }

    /**
     * Custom domains registered under this workspace.
     */
    public function customDomains(): HasMany
    {
        return $this->hasMany(CustomDomain::class);
    }

    /**
     * API keys scoped to this workspace.
     */
    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    /**
     * Webhook subscriptions scoped to this workspace.
     */
    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }
}
