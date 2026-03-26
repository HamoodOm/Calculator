<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    // Action types
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_GENERATE = 'generate';
    const ACTION_DOWNLOAD = 'download';
    const ACTION_LOGIN = 'login';
    const ACTION_LOGOUT = 'logout';
    const ACTION_LOGIN_FAILED = 'login_failed';
    const ACTION_TOGGLE = 'toggle';
    const ACTION_SAVE_SETTINGS = 'save_settings';
    const ACTION_UPLOAD = 'upload';

    protected $fillable = [
        'user_id',
        'user_name',
        'action',
        'model_type',
        'model_id',
        'model_name',
        'description',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'institution_id',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Get the user that performed this activity.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the institution associated with this activity.
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the subject of the activity (polymorphic).
     */
    public function subject()
    {
        if (!$this->model_type || !$this->model_id) {
            return null;
        }
        return $this->model_type::find($this->model_id);
    }

    /**
     * Get human-readable action name (Arabic).
     */
    public function getActionLabelAttribute(): string
    {
        $labels = [
            self::ACTION_CREATE => 'إنشاء',
            self::ACTION_UPDATE => 'تعديل',
            self::ACTION_DELETE => 'حذف',
            self::ACTION_GENERATE => 'إنشاء شهادات',
            self::ACTION_DOWNLOAD => 'تحميل',
            self::ACTION_LOGIN => 'تسجيل دخول',
            self::ACTION_LOGOUT => 'تسجيل خروج',
            self::ACTION_LOGIN_FAILED => 'محاولة دخول فاشلة',
            self::ACTION_TOGGLE => 'تبديل الحالة',
            self::ACTION_SAVE_SETTINGS => 'حفظ الإعدادات',
            self::ACTION_UPLOAD => 'رفع ملف',
        ];

        return $labels[$this->action] ?? $this->action;
    }

    /**
     * Get human-readable model type name (Arabic).
     */
    public function getModelTypeLabelAttribute(): string
    {
        $labels = [
            User::class => 'مستخدم',
            Role::class => 'دور',
            Institution::class => 'مؤسسة',
            Track::class => 'مسار',
            Permission::class => 'صلاحية',
        ];

        return $labels[$this->model_type] ?? class_basename($this->model_type ?? '');
    }

    /**
     * Get action color class for UI.
     */
    public function getActionColorAttribute(): string
    {
        $colors = [
            self::ACTION_CREATE => 'text-green-600 bg-green-100',
            self::ACTION_UPDATE => 'text-blue-600 bg-blue-100',
            self::ACTION_DELETE => 'text-red-600 bg-red-100',
            self::ACTION_GENERATE => 'text-purple-600 bg-purple-100',
            self::ACTION_DOWNLOAD => 'text-indigo-600 bg-indigo-100',
            self::ACTION_LOGIN => 'text-green-600 bg-green-100',
            self::ACTION_LOGOUT => 'text-gray-600 bg-gray-100',
            self::ACTION_LOGIN_FAILED => 'text-red-600 bg-red-100',
            self::ACTION_TOGGLE => 'text-yellow-600 bg-yellow-100',
            self::ACTION_SAVE_SETTINGS => 'text-blue-600 bg-blue-100',
            self::ACTION_UPLOAD => 'text-cyan-600 bg-cyan-100',
        ];

        return $colors[$this->action] ?? 'text-gray-600 bg-gray-100';
    }

    /**
     * Scope to filter by action.
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by institution.
     */
    public function scopeByInstitution($query, int $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    /**
     * Get the display name for the actor (user or API client/institution).
     * Returns user name, institution name (for API actions), or 'غير معروف'.
     */
    public function getDisplayUserNameAttribute(): string
    {
        // If user_name is stored directly, use it
        if (!empty($this->user_name)) {
            return $this->user_name;
        }

        // If no user but institution is known (API-generated actions)
        if ($this->institution_id && $this->institution) {
            return 'منصة ' . $this->institution->name . ' (API)';
        }

        // Check metadata for api_client info
        if (!empty($this->metadata['api_client_name'])) {
            return $this->metadata['api_client_name'] . ' (API)';
        }

        return 'غير معروف';
    }

    /**
     * Scope to filter by model type.
     */
    public function scopeForModel($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateRange($query, $from, $to = null)
    {
        if ($from) {
            $query->where('created_at', '>=', $from);
        }
        if ($to) {
            $query->where('created_at', '<=', $to);
        }
        return $query;
    }
}
