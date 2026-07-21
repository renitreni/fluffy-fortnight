<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * CustomDomain represents a user-owned branded domain for short links.
 *
 * Before a domain can serve redirects, its ownership must be proven via
 * a DNS TXT record containing the `verification_token`. SSL provisioning
 * is handled asynchronously by a background job (Day 22).
 *
 * @property int $id
 * @property int|null $workspace_id
 * @property int $user_id
 * @property string $domain
 * @property bool $is_verified
 * @property \Illuminate\Support\Carbon|null $verified_at
 * @property string|null $verification_token
 * @property string $ssl_status  One of: pending, active, failed
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Workspace|null $workspace
 * @property-read \App\Models\User $user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Link> $links
 */
class CustomDomain extends Model
{
    /** @use HasFactory<\Database\Factories\CustomDomainFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'workspace_id',
        'user_id',
        'domain',
        'is_verified',
        'verified_at',
        'verification_token',
        'ssl_status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The workspace this domain belongs to, if any.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * The user who registered this domain.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Links that use this custom domain as their host.
     */
    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }
}
