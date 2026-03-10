<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_client_id',
        'external_course_id',
        'external_course_name',
        'external_course_name_en',
        'track_id',
        'certificate_type',
        'default_gender',
        'custom_fields',
        'style_overrides',
        'active',
        'certificates_generated',
    ];

    protected $casts = [
        'custom_fields' => 'array',
        'style_overrides' => 'array',
        'active' => 'boolean',
    ];

    /**
     * Get the API client that owns this mapping.
     */
    public function apiClient()
    {
        return $this->belongsTo(ApiClient::class);
    }

    /**
     * Get the track that this course maps to.
     */
    public function track()
    {
        return $this->belongsTo(Track::class);
    }

    /**
     * Get the settings for this course's certificate.
     */
    public function getSettings(string $gender)
    {
        $settingsClass = $this->certificate_type === 'student'
            ? StudentSetting::class
            : TeacherSetting::class;

        return $settingsClass::where('track_id', $this->track_id)
            ->where('gender', $gender)
            ->first();
    }

    /**
     * Increment certificates generated counter.
     */
    public function recordCertificateGenerated(): void
    {
        $this->increment('certificates_generated');
    }

    /**
     * Get display name (Arabic or English).
     */
    public function getDisplayName(): string
    {
        return $this->external_course_name ?: $this->external_course_name_en;
    }

    /**
     * Scope for active mappings.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope for a specific API client.
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('api_client_id', $clientId);
    }

    /**
     * Find by external course ID for a client.
     */
    public static function findByExternalId(int $clientId, string $externalCourseId): ?self
    {
        return static::where('api_client_id', $clientId)
            ->where('external_course_id', $externalCourseId)
            ->first();
    }
}
