<?php

namespace App\Http\Middleware;

use App\Models\ApiClient;
use App\Models\ApiRequestLog;
use Closure;
use Illuminate\Http\Request;

class AuthenticateApiClient
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $scope  Required scope for this endpoint
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ?string $scope = null)
    {
        $startTime = microtime(true);

        // Get API key from header or query
        $apiKey = $request->header('X-API-Key') ?? $request->query('api_key');
        $apiSecret = $request->header('X-API-Secret') ?? $request->query('api_secret');

        // Alternative: Bearer token format "api_key:api_secret"
        $bearerToken = $request->bearerToken();
        if ($bearerToken && !$apiKey) {
            $parts = explode(':', base64_decode($bearerToken));
            if (count($parts) === 2) {
                [$apiKey, $apiSecret] = $parts;
            }
        }

        // Validate presence of credentials
        if (!$apiKey || !$apiSecret) {
            return $this->errorResponse(
                $request,
                null,
                401,
                'missing_credentials',
                'API key and secret are required',
                $startTime
            );
        }

        // Find the API client
        $client = ApiClient::where('api_key', $apiKey)->first();

        if (!$client) {
            return $this->errorResponse(
                $request,
                null,
                401,
                'invalid_api_key',
                'Invalid API key',
                $startTime
            );
        }

        // Verify secret
        if (!$client->verifySecret($apiSecret)) {
            return $this->errorResponse(
                $request,
                $client,
                401,
                'invalid_api_secret',
                'Invalid API secret',
                $startTime
            );
        }

        // Check if client is active
        if (!$client->active) {
            return $this->errorResponse(
                $request,
                $client,
                403,
                'client_inactive',
                'API client is inactive',
                $startTime
            );
        }

        // Check if institution is active
        if ($client->institution && !$client->institution->is_active) {
            return $this->errorResponse(
                $request,
                $client,
                403,
                'institution_inactive',
                'Associated institution is inactive',
                $startTime
            );
        }

        // Check IP whitelist
        if (!$client->isIpAllowed($request->ip())) {
            return $this->errorResponse(
                $request,
                $client,
                403,
                'ip_not_allowed',
                'Request from this IP address is not allowed',
                $startTime
            );
        }

        // Check required scope
        if ($scope && !$client->hasScope($scope)) {
            return $this->errorResponse(
                $request,
                $client,
                403,
                'insufficient_scope',
                "This endpoint requires the '{$scope}' scope",
                $startTime
            );
        }

        // Check rate limit
        $remaining = $client->checkRateLimit();
        if ($remaining === false) {
            return $this->errorResponse(
                $request,
                $client,
                429,
                'rate_limit_exceeded',
                'Rate limit exceeded. Please try again later.',
                $startTime,
                ['retry_after' => 60]
            );
        }

        // Check daily limit
        $dailyRemaining = $client->checkDailyLimit();
        if ($dailyRemaining === false) {
            return $this->errorResponse(
                $request,
                $client,
                429,
                'daily_limit_exceeded',
                'Daily certificate limit exceeded. Please try again tomorrow.',
                $startTime
            );
        }

        // Attach client to request
        $request->attributes->set('api_client', $client);
        $request->attributes->set('api_start_time', $startTime);
        $request->attributes->set('rate_limit_remaining', $remaining);
        $request->attributes->set('daily_limit_remaining', $dailyRemaining);

        // Process request
        $response = $next($request);

        // Add rate limit headers
        $response->headers->set('X-RateLimit-Limit', $client->rate_limit);
        $response->headers->set('X-RateLimit-Remaining', max(0, $remaining - 1));
        $response->headers->set('X-DailyLimit-Limit', $client->daily_limit);
        $response->headers->set('X-DailyLimit-Remaining', max(0, $dailyRemaining - 1));

        return $response;
    }

    /**
     * Return an error response and log it.
     */
    protected function errorResponse(
        Request $request,
        ?ApiClient $client,
        int $statusCode,
        string $errorCode,
        string $message,
        float $startTime,
        array $extra = []
    ) {
        $executionTime = (int)((microtime(true) - $startTime) * 1000);

        // Log the failed request
        ApiRequestLog::log(
            $client,
            $request->path(),
            $request->method(),
            $request->ip(),
            $request->except(['api_secret', 'password']),
            $statusCode,
            ['error' => $errorCode, 'message' => $message],
            $executionTime,
            $message
        );

        $response = [
            'success' => false,
            'error' => [
                'code' => $errorCode,
                'message' => $message,
            ],
        ];

        if (!empty($extra)) {
            $response['error'] = array_merge($response['error'], $extra);
        }

        return response()->json($response, $statusCode);
    }
}
