<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * The User model represents an authenticated platform user.
 *
 * A user can own multiple workspaces, create short links, manage custom
 * domains, generate API keys, and subscribe to webhook events. Their
 * feature access is governed by the associated SubscriptionPlan.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $avatar
 * @property string $timezone
 * @property string $locale
 * @property bool $is_active
 * @property int|null $subscription_plan_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\SubscriptionPlan|null $subscriptionPlan
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Workspace> $ownedWorkspaces
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Workspace> $workspaces
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Link> $links
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CustomDomain> $customDomains
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ApiKey> $apiKeys
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Webhook> $webhooks
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'timezone',
        'locale',
        'is_active',
        'subscription_plan_id',
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
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The subscription plan this user is currently on.
     */
    public function subscriptionPlan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class);
    }

    /**
     * Workspaces that this user created and owns.
     */
    public function ownedWorkspaces(): HasMany
    {
        return $this->hasMany(Workspace::class, 'owner_id');
    }

    /**
     * All workspaces this user is a member of (owned + invited).
     */
    public function workspaces(): BelongsToMany
    {
        return $this->belongsToMany(Workspace::class)
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Short links created by this user.
     */
    public function links(): HasMany
    {
        return $this->hasMany(Link::class);
    }

    /**
     * Custom domains registered by this user.
     */
    public function customDomains(): HasMany
    {
        return $this->hasMany(CustomDomain::class);
    }

    /**
     * API keys owned by this user.
     */
    public function apiKeys(): HasMany
    {
        return $this->hasMany(ApiKey::class);
    }

    /**
     * Webhook subscriptions configured by this user.
     */
    public function webhooks(): HasMany
    {
        return $this->hasMany(Webhook::class);
    }
}
