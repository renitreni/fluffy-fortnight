<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * WorkspaceUser is the Eloquent pivot model for the workspace_user table.
 *
 * Provides typed access to the `role` and `joined_at` pivot columns and
 * allows direct querying of workspace memberships without loading the full
 * BelongsToMany relation.
 *
 * @property int $id
 * @property int $workspace_id
 * @property int $user_id
 * @property string $role One of: admin, editor, viewer
 * @property Carbon|null $joined_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Workspace $workspace
 * @property-read User $user
 */
class WorkspaceUser extends Pivot
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'workspace_user';

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'workspace_id',
        'user_id',
        'role',
        'joined_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relationships
    // -------------------------------------------------------------------------

    /**
     * The workspace this membership belongs to.
     */
    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    /**
     * The member user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
