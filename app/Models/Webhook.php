<?php

namespace App\Models;

use Database\Factories\WebhookFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Webhook represents a user's event subscription endpoint.
 *
 * When a subscribed event fires (e.g., link.clicked), a WebhookDispatchJob
 * posts a signed JSON payload to the configured URL. The HMAC-SHA256 signature
 * is included in the X-Signature-256 header using the `secret` column value.
 *
 * Automatic disabling: if `failure_count` exceeds the platform threshold
 * (configurable via config/webhooks.php), `is_active` is set to false and
 * the user is notified by email.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $workspace_id
 * @property string $url
 * @property array $events
 * @property string $secret
 * @property bool $is_active
 * @property Carbon|null $last_triggered_at
 * @property int $failure_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @property-read Workspace|null $workspace
 */
class Webhook extends Model
{
    /** @use HasFactory<WebhookFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'workspace_id',
        'url',
        'events',
        'secret',
        'is_active',
        'last_triggered_at',
        'failure_count',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'secret', // Never expose the signing secret in API responses
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
            'last_triggered_at' => 'datetime',
            'failure_count' => 'integer',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The user who owns this webhook subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The workspace this webhook is scoped to, if any.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
