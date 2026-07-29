<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BioPage extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'alias',
        'title',
        'description',
        'theme',
        'profile_image_path',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function links(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Link::class)
            ->withPivot('display_order')
            ->orderByPivot('display_order');
    }
}
