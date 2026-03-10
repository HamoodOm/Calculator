<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'api_key',
        'api_secret',
        'institution_id',
        'webhook_url',
        'webhook_secret',
        'allowed_ips',
        'scopes',
        'rate_limit',
        'daily_limit',
        'active',
        'last_used_at',
        'total_requests',
        'total_certificates',
    ];

    protected $casts = [
        'allowed_ips' => 'array',
        'scopes' => 'array',
        'active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'api_secret',
        'webhook_secret',
    ];

    /**
     * Available API scopes/permissions.
     */
    const SCOPE_GENERATE_CERTIFICATE = 'certificates.generate';
    const SCOPE_VIEW_COURSES = 'courses.view';
    const SCOPE_MANAGE_COURSES = 'courses.manage';
    const SCOPE_VIEW_STATS = 'stats.view';
    const SCOPE_WEBHOOK_RECEIVE = 'webhook.receive';

    /**
     * Get all available scopes.
     */
    public static function getAvailableScopes(): array
    {
        return [
            self::SCOPE_GENERATE_CERTIFICATE => [
                'name' => 'إنشاء الشهادات',
                'description' => 'السماح بإنشاء شهادات جديدة عبر API',
            ],
            self::SCOPE_VIEW_COURSES => [
                'name' => 'عرض الدورات',
                'description' => 'عرض الدورات المربوطة بهذا العميل',
            ],
            self::SCOPE_MANAGE_COURSES => [
                'name' => 'إدارة الدورات',
                'description' => 'إضافة وتعديل وحذف ربط الدورات',
            ],
            self::SCOPE_VIEW_STATS => [
                'name' => 'عرض الإحصائيات',
                'description' => 'عرض إحصائيات الاستخدام والشهادات',
            ],
            self::SCOPE_WEBHOOK_RECEIVE => [
                'name' => 'استلام Webhook',
                'description' => 'استلام الشهادات المولدة عبر Webhook',
            ],
        ];
    }

    /**
     * Get the institution that owns this API client.
     */
    public function institution()
    {
        return $this->belongsTo(Institution::class);
    }

    /**
     * Get the course mappings for this client.
     */
    public function courseMappings()
    {
        return $this->hasMany(CourseMapping::class);
    }

    /**
     * Get the API request logs for this client.
     */
    public function requestLogs()
    {
        return $this->hasMany(ApiRequestLog::class);
    }

    /**
     * Generate a new API key.
     */
    public static function generateApiKey(): string
    {
        return 'ck_' . Str::random(32);
    }

    /**
     * Generate a new API secret.
     */
    public static function generateApiSecret(): string
    {
        return 'cs_' . Str::random(64);
    }

    /**
     * Generate a new webhook secret.
     */
    public static function generateWebhookSecret(): string
    {
        return 'wh_' . Str::random(32);
    }

    /**
     * Verify API secret.
     */
    public function verifySecret(string $secret): bool
    {
        return hash_equals($this->api_secret, hash('sha256', $secret));
    }

    /**
     * Check if client has a specific scope.
     */
    public function hasScope(string $scope): bool
    {
        if (empty($this->scopes)) {
            return false;
        }
        return in_array($scope, $this->scopes);
    }

    /**
     * Check if client can access from given IP.
     */
    public function isIpAllowed(?string $ip): bool
    {
        // If no IP whitelist, allow all
        if (empty($this->allowed_ips)) {
            return true;
        }

        if (!$ip) {
            return false;
        }

        return in_array($ip, $this->allowed_ips);
    }

    /**
     * Check if client is within rate limit.
     * Returns remaining requests, or false if exceeded.
     */
    public function checkRateLimit(): int|false
    {
        $minuteAgo = now()->subMinute();
        $recentRequests = $this->requestLogs()
            ->where('created_at', '>=', $minuteAgo)
            ->count();

        if ($recentRequests >= $this->rate_limit) {
            return false;
        }

        return $this->rate_limit - $recentRequests;
    }

    /**
     * Check daily certificate limit.
     */
    public function checkDailyLimit(): int|false
    {
        $todayStart = now()->startOfDay();
        $todayCertificates = $this->requestLogs()
            ->where('created_at', '>=', $todayStart)
            ->whereNotNull('certificate_id')
            ->count();

        if ($todayCertificates >= $this->daily_limit) {
            return false;
        }

        return $this->daily_limit - $todayCertificates;
    }

    /**
     * Record API usage.
     */
    public function recordUsage(bool $certificateGenerated = false): void
    {
        $this->increment('total_requests');
        if ($certificateGenerated) {
            $this->increment('total_certificates');
        }
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Get masked API key for display.
     */
    public function getMaskedApiKeyAttribute(): string
    {
        return substr($this->api_key, 0, 10) . '...' . substr($this->api_key, -4);
    }

    /**
     * Get today's request count.
     */
    public function getDailyRequestsAttribute(): int
    {
        return $this->requestLogs()
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
    }

    /**
     * Generate signature for webhook payload.
     */
    public function signWebhookPayload(array $payload): string
    {
        $data = json_encode($payload);
        return hash_hmac('sha256', $data, $this->webhook_secret);
    }
}
