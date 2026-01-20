<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Institution;

class Track extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name_ar',
        'name_en',
        'active',
        'institution_id',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get the teacher settings for this track.
     */
    public function teacherSettings()
    {
        return $this->hasMany(TeacherSetting::class);
    }

    /**
     * Get the institution for this track.
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get display name with institution prefix for super users.
     */
    public function getDisplayName(bool $showInstitution = false): string
    {
        if ($showInstitution && $this->institution) {
            return "({$this->institution->name}) {$this->name_ar}";
        }
        return $this->name_ar;
    }

    /**
     * Scope to filter tracks by institution.
     */
    public function scopeForInstitution($query, $institutionId)
    {
        if ($institutionId) {
            return $query->where('institution_id', $institutionId);
        }
        return $query;
    }

    /**
     * Scope to get tracks accessible by a user.
     */
    public function scopeAccessibleBy($query, User $user)
    {
        // Super users can see all tracks
        if ($user->isSuperUser()) {
            return $query;
        }

        // Regular users can only see tracks from their institution
        if ($user->institution_id) {
            return $query->where('institution_id', $user->institution_id);
        }

        // Users without institution see tracks without institution (legacy)
        return $query->whereNull('institution_id');
    }
}
