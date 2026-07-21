<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ApiKey stores a hashed programmatic access credential for the public API.
 *
 * The raw key is shown to the user exactly once (at creation) and never stored.
 * Authentication checks hash the incoming Bearer token with SHA-256 and compare
 * against `key_hash`. The `key_prefix` (first 8 chars) is displayed in the UI
 * so users can identify which key they're managing.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $workspace_id
 * @property string $name
 * @property string $key_hash
 * @property string $key_prefix
 * @property array|null $abilities
 * @property \Illuminate\Support\Carbon|null $last_used_at
 * @property \Illuminate\Support\Carbon|null $expires_at
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @property-read \App\Models\Workspace|null $workspace
 */
class ApiKey extends Model
{
    /** @use HasFactory<\Database\Factories\ApiKeyFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'workspace_id',
        'name',
        'key_hash',
        'key_prefix',
        'abilities',
        'last_used_at',
        'expires_at',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'key_hash', // Never expose the hash in API responses
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'abilities'    => 'array',
            'last_used_at' => 'datetime',
            'expires_at'   => 'datetime',
            'is_active'    => 'boolean',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The user who owns this API key.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The workspace this key is scoped to, if any.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
