<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Billable;

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
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $avatar
 * @property string $timezone
 * @property string $locale
 * @property bool $is_active
 * @property int|null $subscription_plan_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SubscriptionPlan|null $subscriptionPlan
 * @property-read Collection<int, Workspace> $ownedWorkspaces
 * @property-read Collection<int, Workspace> $workspaces
 * @property-read Collection<int, Link> $links
 * @property-read Collection<int, CustomDomain> $customDomains
 * @property-read Collection<int, ApiKey> $apiKeys
 * @property-read Collection<int, Webhook> $webhooks
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, Billable;

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
        'ip_anonymization',
        'data_retention_days',
        'subscription_plan_id',
        'current_workspace_id',
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
            'password' => 'hashed',
            'is_active' => 'boolean',
            'ip_anonymization' => 'boolean',
            'data_retention_days' => 'integer',
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
        return $this->belongsToMany(Workspace::class, 'workspace_user')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * The user's link-in-bio pages.
     */
    public function bioPages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(BioPage::class);
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

    /**
     * The currently active workspace for this user.
     */
    public function currentWorkspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class, 'current_workspace_id');
    }

    /**
     * Switch the user's active workspace.
     */
    public function switchWorkspace(Workspace $workspace): void
    {
        if (! $this->isMemberOf($workspace)) {
            abort(403, 'You are not a member of this workspace.');
        }

        $this->current_workspace_id = $workspace->id;
        $this->save();
    }

    /**
     * Check if the user is a member of the given workspace.
     */
    public function isMemberOf(Workspace $workspace): bool
    {
        return $this->workspaces()->where('workspace_id', $workspace->id)->exists()
            || $this->ownedWorkspaces()->where('id', $workspace->id)->exists();
    }
}
