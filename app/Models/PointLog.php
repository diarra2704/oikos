<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointLog extends Model
{
    protected $table = 'points_log';

    protected $fillable = [
        'user_id',
        'action',
        'points',
        'description',
        'reference_id',
        'reference_type',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
