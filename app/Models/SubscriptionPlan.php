<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * SubscriptionPlan defines the feature tiers available on the platform.
 *
 * Plans are seeded from SubscriptionPlanSeeder and rarely change at runtime.
 * Feature flags stored in the JSON `features` column are checked by the
 * feature-gating middleware introduced in Day 28.
 *
 * @property int $id
 * @property string $name
 * @property string $display_name
 * @property array|null $features
 * @property float|null $price_monthly
 * @property float|null $price_yearly
 * @property string|null $stripe_monthly_price_id
 * @property string|null $stripe_yearly_price_id
 * @property int $max_links
 * @property int $max_workspaces
 * @property int $max_custom_domains
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 */
class SubscriptionPlan extends Model
{
    /** @use HasFactory<\Database\Factories\SubscriptionPlanFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'display_name',
        'features',
        'price_monthly',
        'price_yearly',
        'stripe_monthly_price_id',
        'stripe_yearly_price_id',
        'max_links',
        'max_workspaces',
        'max_custom_domains',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'features'      => 'array',
            'price_monthly' => 'decimal:2',
            'price_yearly'  => 'decimal:2',
            'is_active'     => 'boolean',
            'max_links'     => 'integer',
            'max_workspaces' => 'integer',
            'max_custom_domains' => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * Users currently subscribed to this plan.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
