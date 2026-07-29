<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ClickHourlySummary extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'link_id',
        'hour',
        'country',
        'device_type',
        'os',
        'browser',
        'referer_domain',
        'clicks',
    ];

    protected $casts = [
        'hour' => 'datetime',
        'clicks' => 'integer',
    ];

    public function link()
    {
        return $this->belongsTo(Link::class);
    }
}
