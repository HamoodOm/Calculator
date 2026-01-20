<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherImageSetting extends Model
{
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

    public function track()
    {
        return $this->belongsTo(Track::class);
    }
}
