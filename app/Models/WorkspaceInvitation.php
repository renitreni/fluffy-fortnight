<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WorkspaceInvitation extends Model
{
    protected $fillable = [
        'workspace_id',
        'email',
        'role',
        'token',
    ];

    /**
     * Get the workspace that this invitation belongs to.
     */
    public function workspace(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
