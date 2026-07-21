<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Link is the core entity of the URL shortener.
 *
 * Each link maps a unique `short_code` to an `original_url`. Links support
 * a wide set of optional features including password protection, expiry,
 * mobile deep linking, UTM parameter injection, and per-workspace scoping.
 *
 * The `click_count` column is a denormalized cache kept in sync by the
 * click-tracking queue job to avoid real-time aggregation queries on the
 * high-volume `clicks` table during dashboard renders.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $workspace_id
 * @property int|null $custom_domain_id
 * @property string $short_code
 * @property string $original_url
 * @property string|null $title
 * @property string|null $description
 * @property bool $is_custom_alias
 * @property string|null $password
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property string|null $ios_deep_link
 * @property string|null $android_deep_link
 * @property string|null $utm_source
 * @property string|null $utm_medium
 * @property string|null $utm_campaign
 * @property string|null $utm_term
 * @property string|null $utm_content
 * @property int $click_count
 * @property bool $is_active
 * @property array|null $tags
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Workspace|null $workspace
 * @property-read \App\Models\CustomDomain|null $customDomain
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Click> $clicks
 */
class Link extends Model
{
    /** @use HasFactory<\Database\Factories\LinkFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'workspace_id',
        'custom_domain_id',
        'short_code',
        'original_url',
        'title',
        'description',
        'is_custom_alias',
        'password',
        'expires_at',
        'ios_deep_link',
        'android_deep_link',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'utm_term',
        'utm_content',
        'click_count',
        'is_active',
        'tags',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password', // Never expose the hashed password gate in API responses
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_custom_alias' => 'boolean',
            'password'        => 'hashed',
            'expires_at'      => 'datetime',
            'is_active'       => 'boolean',
            'tags'            => 'array',
            'click_count'     => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The user who created this link.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The workspace this link belongs to, if any.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * The custom domain used for this link's short URL, if any.
     */
    public function customDomain(): BelongsTo
    {
        return $this->belongsTo(CustomDomain::class);
    }

    /**
     * All individual click events recorded for this link.
     */
    public function clicks(): HasMany
    {
        return $this->hasMany(Click::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    /**
     * Scope to only active, non-expired links.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
