<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BulkJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'original_filename',
        'result_file_path',
        'total_rows',
        'processed_rows',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
