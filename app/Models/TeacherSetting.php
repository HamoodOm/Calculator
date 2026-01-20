<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeacherSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'track_id',
        'gender',
        'certificate_bg',
        'positions',
        'style',
        'print_defaults',
        'date_type',
        'notes',
    ];

    protected $casts = [
        'positions' => 'array',
        'style' => 'array',
        'print_defaults' => 'array',
    ];

    /**
     * Get the track that owns this setting.
     */
    public function track()
    {
        return $this->belongsTo(Track::class);
    }
}
