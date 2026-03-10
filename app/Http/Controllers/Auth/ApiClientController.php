<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Models\ApiRequestLog;
use App\Models\CourseMapping;
use App\Models\Institution;
use App\Models\Permission;
use App\Models\Track;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiClientController extends Controller
{
    /**
     * Display a listing of API clients.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = ApiClient::with('institution')
            ->withCount('courseMappings');

        // Filter by institution for non-super users
        if (!$user->isSuperUser()) {
            $query->where('institution_id', $user->institution_id);
        }

        // Search filter
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->has('status')) {
            $query->where('active', $request->get('status') === 'active');
        }

        $clients = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        // Statistics
        $stats = [
            'total' => ApiClient::when(!$user->isSuperUser(), fn($q) => $q->where('institution_id', $user->institution_id))->count(),
            'active' => ApiClient::when(!$user->isSuperUser(), fn($q) => $q->where('institution_id', $user->institution_id))->where('active', true)->count(),
            'total_certificates' => ApiClient::when(!$user->isSuperUser(), fn($q) => $q->where('institution_id', $user->institution_id))->sum('total_certificates'),
        ];

        return view('auth.api-clients.index', compact('clients', 'stats'));
    }

    /**
     * Show the form for creating a new API client.
     */
    public function create()
    {
        $user = auth()->user();
        $institutions = $user->isSuperUser()
            ? Institution::where('is_active', true)->orderBy('name')->get()
            : collect([$user->institution]);

        $scopes = ApiClient::getAvailableScopes();

        return view('auth.api-clients.create', compact('institutions', 'scopes'));
    }

    /**
     * Store a newly created API client.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:50|unique:api_clients|regex:/^[a-z0-9-]+$/',
            'description' => 'nullable|string|max:1000',
            'institution_id' => 'required|exists:institutions,id',
            'webhook_url' => 'nullable|url',
            'allowed_ips' => 'nullable|string',
            'scopes' => 'required|array|min:1',
            'rate_limit' => 'required|integer|min:1|max:1000',
            'daily_limit' => 'required|integer|min:1|max:100000',
        ]);

        // Generate API credentials
        $apiKey = ApiClient::generateApiKey();
        $apiSecret = ApiClient::generateApiSecret();
        $webhookSecret = $request->webhook_url ? ApiClient::generateWebhookSecret() : null;

        // Parse allowed IPs
        $allowedIps = null;
        if ($request->allowed_ips) {
            $allowedIps = array_filter(array_map('trim', explode(',', $request->allowed_ips)));
        }

        $client = ApiClient::create([
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'api_key' => $apiKey,
            'api_secret' => hash('sha256', $apiSecret),
            'institution_id' => $user->isSuperUser() ? $request->institution_id : $user->institution_id,
            'webhook_url' => $request->webhook_url,
            'webhook_secret' => $webhookSecret,
            'allowed_ips' => $allowedIps,
            'scopes' => $request->scopes,
            'rate_limit' => $request->rate_limit,
            'daily_limit' => $request->daily_limit,
            'active' => true,
        ]);

        // Log creation
        ActivityLogService::logCreate($client, 'إنشاء عميل API جديد');

        // Store credentials in session to show once
        session()->flash('new_credentials', [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'webhook_secret' => $webhookSecret,
        ]);

        return redirect()->route('api-clients.show', $client)
            ->with('status', 'تم إنشاء عميل API بنجاح! احفظ بيانات الاعتماد - لن تظهر مرة أخرى.');
    }

    /**
     * Display the specified API client.
     */
    public function show(ApiClient $apiClient)
    {
        $user = auth()->user();

        // Authorization check
        if (!$user->isSuperUser() && $apiClient->institution_id !== $user->institution_id) {
            abort(403);
        }

        $apiClient->load(['institution', 'courseMappings.track']);

        // Recent logs
        $recentLogs = $apiClient->requestLogs()
            ->latest()
            ->limit(10)
            ->get();

        // Statistics
        $stats = [
            'today_requests' => $apiClient->requestLogs()->where('created_at', '>=', now()->startOfDay())->count(),
            'today_certificates' => $apiClient->requestLogs()->where('created_at', '>=', now()->startOfDay())->whereNotNull('certificate_id')->count(),
            'success_rate' => $this->calculateSuccessRate($apiClient),
        ];

        return view('auth.api-clients.show', compact('apiClient', 'recentLogs', 'stats'));
    }

    /**
     * Show the form for editing an API client.
     */
    public function edit(ApiClient $apiClient)
    {
        $user = auth()->user();

        if (!$user->isSuperUser() && $apiClient->institution_id !== $user->institution_id) {
            abort(403);
        }

        $institutions = $user->isSuperUser()
            ? Institution::where('is_active', true)->orderBy('name')->get()
            : collect([$user->institution]);

        $scopes = ApiClient::getAvailableScopes();

        return view('auth.api-clients.edit', compact('apiClient', 'institutions', 'scopes'));
    }

    /**
     * Update the specified API client.
     */
    public function update(Request $request, ApiClient $apiClient)
    {
        $user = auth()->user();

        if (!$user->isSuperUser() && $apiClient->institution_id !== $user->institution_id) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'webhook_url' => 'nullable|url',
            'allowed_ips' => 'nullable|string',
            'scopes' => 'required|array|min:1',
            'rate_limit' => 'required|integer|min:1|max:1000',
            'daily_limit' => 'required|integer|min:1|max:100000',
        ]);

        $oldValues = $apiClient->toArray();

        // Parse allowed IPs
        $allowedIps = null;
        if ($request->allowed_ips) {
            $allowedIps = array_filter(array_map('trim', explode(',', $request->allowed_ips)));
        }

        $apiClient->update([
            'name' => $request->name,
            'description' => $request->description,
            'webhook_url' => $request->webhook_url,
            'allowed_ips' => $allowedIps,
            'scopes' => $request->scopes,
            'rate_limit' => $request->rate_limit,
            'daily_limit' => $request->daily_limit,
        ]);

        // Log update
        ActivityLogService::logUpdate($apiClient, $oldValues, 'تعديل عميل API');

        return redirect()->route('api-clients.show', $apiClient)
            ->with('status', 'تم تحديث عميل API بنجاح!');
    }

    /**
     * Toggle API client active status.
     */
    public function toggle(ApiClient $apiClient)
    {
        $user = auth()->user();

        if (!$user->isSuperUser() && $apiClient->institution_id !== $user->institution_id) {
            abort(403);
        }

        $apiClient->update(['active' => !$apiClient->active]);

        ActivityLogService::logToggle($apiClient, $apiClient->active);

        return back()->with('status', $apiClient->active ? 'تم تفعيل عميل API' : 'تم تعطيل عميل API');
    }

    /**
     * Regenerate API credentials.
     */
    public function regenerateCredentials(ApiClient $apiClient)
    {
        $user = auth()->user();

        if (!$user->isSuperUser() && $apiClient->institution_id !== $user->institution_id) {
            abort(403);
        }

        $apiKey = ApiClient::generateApiKey();
        $apiSecret = ApiClient::generateApiSecret();

        $apiClient->update([
            'api_key' => $apiKey,
            'api_secret' => hash('sha256', $apiSecret),
        ]);

        ActivityLogService::log('update', $apiClient, 'إعادة إنشاء بيانات اعتماد API');

        session()->flash('new_credentials', [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
        ]);

        return redirect()->route('api-clients.show', $apiClient)
            ->with('status', 'تم إعادة إنشاء بيانات الاعتماد! احفظها - لن تظهر مرة أخرى.');
    }

    /**
     * Delete the specified API client.
     */
    public function destroy(ApiClient $apiClient)
    {
        $user = auth()->user();

        if (!$user->isSuperUser() && $apiClient->institution_id !== $user->institution_id) {
            abort(403);
        }

        ActivityLogService::logDelete($apiClient);

        $apiClient->delete();

        return redirect()->route('api-clients.index')
            ->with('status', 'تم حذف عميل API بنجاح!');
    }

    /**
     * Show API logs for a client.
     */
    public function logs(Request $request, ApiClient $apiClient)
    {
        $user = auth()->user();

        if (!$user->isSuperUser() && $apiClient->institution_id !== $user->institution_id) {
            abort(403);
        }

        $query = $apiClient->requestLogs();

        // Filter by status
        if ($request->get('status') === 'success') {
            $query->successful();
        } elseif ($request->get('status') === 'error') {
            $query->failed();
        }

        // Filter by date
        if ($request->get('date')) {
            $date = $request->get('date');
            $query->whereDate('created_at', $date);
        }

        $logs = $query->latest()->paginate(50)->withQueryString();

        return view('auth.api-clients.logs', compact('apiClient', 'logs'));
    }

    // ===== Course Mapping Methods =====

    /**
     * List course mappings for a client.
     */
    public function courseMappings(ApiClient $apiClient)
    {
        $user = auth()->user();

        if (!$user->isSuperUser() && $apiClient->institution_id !== $user->institution_id) {
            abort(403);
        }

        $mappings = $apiClient->courseMappings()
            ->with('track')
            ->orderBy('external_course_name')
            ->get();

        $tracks = Track::where('active', true)
            ->accessibleBy($user)
            ->orderBy('name_ar')
            ->get();

        return view('auth.api-clients.course-mappings', compact('apiClient', 'mappings', 'tracks'));
    }

    /**
     * Store a new course mapping.
     */
    public function storeCourseMapping(Request $request, ApiClient $apiClient)
    {
        $user = auth()->user();

        if (!$user->isSuperUser() && $apiClient->institution_id !== $user->institution_id) {
            abort(403);
        }

        $request->validate([
            'external_course_id' => 'required|string|max:100',
            'external_course_name' => 'required|string|max:255',
            'external_course_name_en' => 'nullable|string|max:255',
            'track_id' => 'required|exists:tracks,id',
            'certificate_type' => 'required|in:student,teacher',
            'default_gender' => 'nullable|in:male,female',
        ]);

        // Check for duplicate
        $exists = CourseMapping::where('api_client_id', $apiClient->id)
            ->where('external_course_id', $request->external_course_id)
            ->exists();

        if ($exists) {
            return back()->withErrors(['external_course_id' => 'معرف الدورة موجود بالفعل لهذا العميل']);
        }

        $mapping = CourseMapping::create([
            'api_client_id' => $apiClient->id,
            'external_course_id' => $request->external_course_id,
            'external_course_name' => $request->external_course_name,
            'external_course_name_en' => $request->external_course_name_en,
            'track_id' => $request->track_id,
            'certificate_type' => $request->certificate_type,
            'default_gender' => $request->default_gender,
            'active' => true,
        ]);

        ActivityLogService::logCreate($mapping, 'ربط دورة جديدة بعميل API');

        return back()->with('status', 'تم ربط الدورة بنجاح!');
    }

    /**
     * Update a course mapping.
     */
    public function updateCourseMapping(Request $request, ApiClient $apiClient, CourseMapping $mapping)
    {
        $user = auth()->user();

        if (!$user->isSuperUser() && $apiClient->institution_id !== $user->institution_id) {
            abort(403);
        }

        if ($mapping->api_client_id !== $apiClient->id) {
            abort(404);
        }

        $request->validate([
            'external_course_name' => 'required|string|max:255',
            'external_course_name_en' => 'nullable|string|max:255',
            'track_id' => 'required|exists:tracks,id',
            'certificate_type' => 'required|in:student,teacher',
            'default_gender' => 'nullable|in:male,female',
        ]);

        $oldValues = $mapping->toArray();

        $mapping->update([
            'external_course_name' => $request->external_course_name,
            'external_course_name_en' => $request->external_course_name_en,
            'track_id' => $request->track_id,
            'certificate_type' => $request->certificate_type,
            'default_gender' => $request->default_gender,
        ]);

        ActivityLogService::logUpdate($mapping, $oldValues, 'تعديل ربط دورة');

        return back()->with('status', 'تم تحديث ربط الدورة بنجاح!');
    }

    /**
     * Toggle course mapping active status.
     */
    public function toggleCourseMapping(ApiClient $apiClient, CourseMapping $mapping)
    {
        $user = auth()->user();

        if (!$user->isSuperUser() && $apiClient->institution_id !== $user->institution_id) {
            abort(403);
        }

        if ($mapping->api_client_id !== $apiClient->id) {
            abort(404);
        }

        $mapping->update(['active' => !$mapping->active]);

        return back()->with('status', $mapping->active ? 'تم تفعيل ربط الدورة' : 'تم تعطيل ربط الدورة');
    }

    /**
     * Delete a course mapping.
     */
    public function destroyCourseMapping(ApiClient $apiClient, CourseMapping $mapping)
    {
        $user = auth()->user();

        if (!$user->isSuperUser() && $apiClient->institution_id !== $user->institution_id) {
            abort(403);
        }

        if ($mapping->api_client_id !== $apiClient->id) {
            abort(404);
        }

        ActivityLogService::logDelete($mapping, 'حذف ربط دورة');

        $mapping->delete();

        return back()->with('status', 'تم حذف ربط الدورة بنجاح!');
    }

    /**
     * Calculate success rate for API client.
     */
    private function calculateSuccessRate(ApiClient $client): float
    {
        $total = $client->requestLogs()->count();
        if ($total === 0) {
            return 100.0;
        }

        $successful = $client->requestLogs()->successful()->count();
        return round(($successful / $total) * 100, 1);
    }
}
