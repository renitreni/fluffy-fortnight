<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BlockedUrl represents a URL that is banned from being shortened.
 *
 * Entries are populated by the malicious URL scanner (Day 10) and by
 * administrators via the admin panel. Lookups during link creation use
 * `url_hash` (SHA-256 of the normalized URL) so that the unique index
 * stays within MySQL's key length limits for arbitrary URL lengths.
 *
 * @property int $id
 * @property string $url_hash
 * @property string $url
 * @property string $reason  One of: malicious, phishing, spam, manual
 * @property string|null $source
 * @property int|null $blocked_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $blocker
 */
class BlockedUrl extends Model
{
    /** @use HasFactory<\Database\Factories\BlockedUrlFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'url_hash',
        'url',
        'reason',
        'source',
        'blocked_by',
    ];

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The admin user who manually added this entry, if applicable.
     */
    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_by');
    }
}
