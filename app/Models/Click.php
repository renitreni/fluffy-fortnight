<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Click records a single redirect event for a short link.
 *
 * Rows in this table are append-only and immutable after creation.
 * IP addresses are hashed with SHA-256 before storage for GDPR compliance.
 * Geo and device data are populated asynchronously by the ClickTrackingJob.
 *
 * There is intentionally no FK constraint on `link_id` at the database level
 * to maximise write throughput on the redirect hot path; referential integrity
 * is enforced by the application.
 *
 * @property int $id
 * @property int $link_id
 * @property string|null $ip_hash
 * @property string|null $country
 * @property string|null $region
 * @property string|null $city
 * @property float|null $latitude
 * @property float|null $longitude
 * @property string $device_type
 * @property string|null $os
 * @property string|null $browser
 * @property string|null $referer
 * @property string|null $referer_domain
 * @property string|null $user_agent
 * @property \Illuminate\Support\Carbon $clicked_at
 * @property-read \App\Models\Link $link
 */
class Click extends Model
{
    /** @use HasFactory<\Database\Factories\ClickFactory> */
    use HasFactory;

    /**
     * Indicates that this model does not use the default updated_at column.
     * Rows are append-only and never mutated after creation.
     *
     * @var bool
     */
    public const UPDATED_AT = null;

    /**
     * The name of the "created at" column. We use clicked_at instead.
     *
     * @var string
     */
    public const CREATED_AT = 'clicked_at';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'link_id',
        'ip_hash',
        'country',
        'region',
        'city',
        'latitude',
        'longitude',
        'device_type',
        'os',
        'browser',
        'referer',
        'referer_domain',
        'user_agent',
        'clicked_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'clicked_at' => 'datetime',
            'latitude'   => 'decimal:7',
            'longitude'  => 'decimal:7',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The short link this click event belongs to.
     */
    public function link(): BelongsTo
    {
        return $this->belongsTo(Link::class);
    }
}
