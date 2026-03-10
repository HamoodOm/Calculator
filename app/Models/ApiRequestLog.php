<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiRequestLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_client_id',
        'endpoint',
        'method',
        'ip_address',
        'request_data',
        'response_code',
        'response_data',
        'execution_time',
        'error_message',
        'certificate_id',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
    ];

    /**
     * Get the API client for this log.
     */
    public function apiClient()
    {
        return $this->belongsTo(ApiClient::class);
    }

    /**
     * Create a log entry.
     */
    public static function log(
        ?ApiClient $client,
        string $endpoint,
        string $method,
        ?string $ip,
        ?array $requestData,
        int $responseCode,
        ?array $responseData = null,
        ?int $executionTime = null,
        ?string $errorMessage = null,
        ?string $certificateId = null
    ): self {
        // Sanitize request data - remove sensitive fields
        if ($requestData) {
            unset($requestData['api_secret']);
            unset($requestData['password']);
            // Truncate large fields
            foreach ($requestData as $key => $value) {
                if (is_string($value) && strlen($value) > 1000) {
                    $requestData[$key] = substr($value, 0, 1000) . '...[truncated]';
                }
            }
        }

        return static::create([
            'api_client_id' => $client?->id,
            'endpoint' => $endpoint,
            'method' => $method,
            'ip_address' => $ip,
            'request_data' => $requestData,
            'response_code' => $responseCode,
            'response_data' => $responseData,
            'execution_time' => $executionTime,
            'error_message' => $errorMessage,
            'certificate_id' => $certificateId,
        ]);
    }

    /**
     * Scope for successful requests.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('response_code', '>=', 200)
                     ->where('response_code', '<', 300);
    }

    /**
     * Scope for failed requests.
     */
    public function scopeFailed($query)
    {
        return $query->where('response_code', '>=', 400);
    }

    /**
     * Scope for certificate generation requests.
     */
    public function scopeWithCertificates($query)
    {
        return $query->whereNotNull('certificate_id');
    }

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        if ($this->response_code >= 200 && $this->response_code < 300) {
            return 'success';
        } elseif ($this->response_code >= 400 && $this->response_code < 500) {
            return 'client_error';
        } elseif ($this->response_code >= 500) {
            return 'server_error';
        }
        return 'unknown';
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status_label) {
            'success' => 'green',
            'client_error' => 'yellow',
            'server_error' => 'red',
            default => 'gray',
        };
    }
}
