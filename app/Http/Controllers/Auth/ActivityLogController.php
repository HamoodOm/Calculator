<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Institution;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Display activity logs.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Build query
        $query = ActivityLog::with(['user', 'institution'])
            ->latest();

        // Super users can see all logs, others see only their institution's logs
        if (!$user->isSuperUser()) {
            $query->where(function ($q) use ($user) {
                $q->where('institution_id', $user->institution_id)
                  ->orWhere('user_id', $user->id);
            });
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by institution
        if ($request->filled('institution') && $user->isSuperUser()) {
            $query->where('institution_id', $request->institution);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        // Search by description or model name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('model_name', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort', 'created_at');
        $sortDir = $request->get('dir', 'desc');
        $allowedSorts = ['created_at', 'action', 'user_name'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $logs = $query->paginate(25)->appends($request->query());

        // Get filter options
        $users = $user->isSuperUser()
            ? User::orderBy('name')->get(['id', 'name'])
            : User::where('institution_id', $user->institution_id)->orderBy('name')->get(['id', 'name']);

        $institutions = $user->isSuperUser()
            ? Institution::orderBy('name')->get(['id', 'name'])
            : collect();

        $actions = [
            ActivityLog::ACTION_CREATE => 'إنشاء',
            ActivityLog::ACTION_UPDATE => 'تعديل',
            ActivityLog::ACTION_DELETE => 'حذف',
            ActivityLog::ACTION_GENERATE => 'إنشاء شهادات',
            ActivityLog::ACTION_DOWNLOAD => 'تحميل',
            ActivityLog::ACTION_LOGIN => 'تسجيل دخول',
            ActivityLog::ACTION_LOGOUT => 'تسجيل خروج',
            ActivityLog::ACTION_LOGIN_FAILED => 'محاولة دخول فاشلة',
            ActivityLog::ACTION_TOGGLE => 'تبديل الحالة',
            ActivityLog::ACTION_SAVE_SETTINGS => 'حفظ الإعدادات',
        ];

        return view('auth.activity-logs.index', compact('logs', 'users', 'institutions', 'actions'));
    }

    /**
     * Show activity log details.
     */
    public function show(ActivityLog $activityLog)
    {
        $user = auth()->user();

        // Check authorization
        if (!$user->isSuperUser()) {
            if ($activityLog->institution_id !== $user->institution_id && $activityLog->user_id !== $user->id) {
                abort(403);
            }
        }

        return view('auth.activity-logs.show', compact('activityLog'));
    }

    /**
     * Export activity logs to CSV.
     */
    public function export(Request $request)
    {
        $user = auth()->user();

        // Build query (same as index)
        $query = ActivityLog::with(['user', 'institution'])->latest();

        if (!$user->isSuperUser()) {
            $query->where(function ($q) use ($user) {
                $q->where('institution_id', $user->institution_id)
                  ->orWhere('user_id', $user->id);
            });
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
        }

        $logs = $query->limit(5000)->get();

        $callback = function () use ($logs) {
            echo "\xEF\xBB\xBF"; // UTF-8 BOM
            $out = fopen('php://output', 'w');

            // Header
            fputcsv($out, ['التاريخ', 'المستخدم', 'الإجراء', 'الوصف', 'المؤسسة', 'عنوان IP']);

            foreach ($logs as $log) {
                fputcsv($out, [
                    $log->created_at->format('Y-m-d H:i:s'),
                    $log->user_name ?? 'غير معروف',
                    $log->action_label,
                    $log->description,
                    $log->institution?->name ?? '-',
                    $log->ip_address ?? '-',
                ]);
            }

            fclose($out);
        };

        $filename = 'activity_logs_' . date('Ymd_His') . '.csv';

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
