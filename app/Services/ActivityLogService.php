<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogService
{
    /**
     * Log an activity.
     *
     * @param string $action The action performed (use ActivityLog::ACTION_* constants)
     * @param Model|null $model The model being acted upon
     * @param string|null $description Human-readable description
     * @param array|null $oldValues Previous values (for updates)
     * @param array|null $newValues New values (for creates/updates)
     * @param array|null $metadata Additional context
     * @return ActivityLog
     */
    public static function log(
        string $action,
        ?Model $model = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null
    ): ActivityLog {
        $user = Auth::user();

        return ActivityLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'action' => $action,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'model_name' => $model ? self::getModelName($model) : null,
            'description' => $description,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'institution_id' => $user?->institution_id,
        ]);
    }

    /**
     * Log a create action.
     */
    public static function logCreate(Model $model, ?string $description = null, ?array $metadata = null): ActivityLog
    {
        return self::log(
            ActivityLog::ACTION_CREATE,
            $model,
            $description ?? 'تم إنشاء ' . self::getModelLabel($model),
            null,
            $model->toArray(),
            $metadata
        );
    }

    /**
     * Log an update action.
     */
    public static function logUpdate(Model $model, array $oldValues, ?string $description = null, ?array $metadata = null): ActivityLog
    {
        // Only keep changed values
        $newValues = [];
        $changedOld = [];
        foreach ($model->getDirty() as $key => $value) {
            if (isset($oldValues[$key])) {
                $changedOld[$key] = $oldValues[$key];
                $newValues[$key] = $value;
            }
        }

        return self::log(
            ActivityLog::ACTION_UPDATE,
            $model,
            $description ?? 'تم تعديل ' . self::getModelLabel($model),
            $changedOld ?: $oldValues,
            $newValues ?: $model->getDirty(),
            $metadata
        );
    }

    /**
     * Log a delete action.
     */
    public static function logDelete(Model $model, ?string $description = null, ?array $metadata = null): ActivityLog
    {
        return self::log(
            ActivityLog::ACTION_DELETE,
            $model,
            $description ?? 'تم حذف ' . self::getModelLabel($model),
            $model->toArray(),
            null,
            $metadata
        );
    }

    /**
     * Log certificate generation.
     */
    public static function logGenerate(string $trackName, int $count, string $type = 'pdf', ?array $metadata = null): ActivityLog
    {
        $description = "تم إنشاء {$count} شهادة ({$type}) للمسار: {$trackName}";

        return self::log(
            ActivityLog::ACTION_GENERATE,
            null,
            $description,
            null,
            [
                'track_name' => $trackName,
                'count' => $count,
                'type' => $type,
            ],
            $metadata
        );
    }

    /**
     * Log a download action.
     */
    public static function logDownload(string $filename, ?string $type = null, ?array $metadata = null): ActivityLog
    {
        $description = "تم تحميل الملف: {$filename}";

        return self::log(
            ActivityLog::ACTION_DOWNLOAD,
            null,
            $description,
            null,
            [
                'filename' => $filename,
                'type' => $type,
            ],
            $metadata
        );
    }

    /**
     * Log a login action.
     */
    public static function logLogin(User $user, ?array $metadata = null): ActivityLog
    {
        return self::log(
            ActivityLog::ACTION_LOGIN,
            $user,
            'تم تسجيل الدخول',
            null,
            null,
            $metadata
        );
    }

    /**
     * Log a logout action.
     */
    public static function logLogout(User $user, ?array $metadata = null): ActivityLog
    {
        return self::log(
            ActivityLog::ACTION_LOGOUT,
            $user,
            'تم تسجيل الخروج',
            null,
            null,
            $metadata
        );
    }

    /**
     * Log a failed login attempt.
     */
    public static function logLoginFailed(string $email, ?array $metadata = null): ActivityLog
    {
        return ActivityLog::create([
            'user_id' => null,
            'user_name' => null,
            'action' => ActivityLog::ACTION_LOGIN_FAILED,
            'model_type' => null,
            'model_id' => null,
            'model_name' => null,
            'description' => "محاولة دخول فاشلة للبريد: {$email}",
            'old_values' => null,
            'new_values' => ['email' => $email],
            'metadata' => $metadata,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'institution_id' => null,
        ]);
    }

    /**
     * Log a toggle action (enable/disable).
     */
    public static function logToggle(Model $model, bool $newState, ?string $description = null, ?array $metadata = null): ActivityLog
    {
        $stateText = $newState ? 'تفعيل' : 'تعطيل';

        return self::log(
            ActivityLog::ACTION_TOGGLE,
            $model,
            $description ?? "تم {$stateText} " . self::getModelLabel($model),
            ['is_active' => !$newState],
            ['is_active' => $newState],
            $metadata
        );
    }

    /**
     * Log settings save.
     */
    public static function logSaveSettings(string $settingType, ?Model $model = null, ?string $description = null, ?array $metadata = null): ActivityLog
    {
        return self::log(
            ActivityLog::ACTION_SAVE_SETTINGS,
            $model,
            $description ?? "تم حفظ إعدادات: {$settingType}",
            null,
            null,
            array_merge(['setting_type' => $settingType], $metadata ?? [])
        );
    }

    /**
     * Get a human-readable name for a model.
     */
    protected static function getModelName(Model $model): ?string
    {
        // Try common name attributes
        $nameAttributes = ['name', 'name_ar', 'title', 'email', 'key'];

        foreach ($nameAttributes as $attr) {
            if (!empty($model->$attr)) {
                return $model->$attr;
            }
        }

        return $model->id ? "#{$model->id}" : null;
    }

    /**
     * Get a human-readable label for a model type.
     */
    protected static function getModelLabel(Model $model): string
    {
        $labels = [
            \App\Models\User::class => 'المستخدم',
            \App\Models\Role::class => 'الدور',
            \App\Models\Institution::class => 'المؤسسة',
            \App\Models\Track::class => 'المسار',
            \App\Models\Permission::class => 'الصلاحية',
        ];

        $label = $labels[get_class($model)] ?? class_basename($model);
        $name = self::getModelName($model);

        return $name ? "{$label}: {$name}" : $label;
    }
}
