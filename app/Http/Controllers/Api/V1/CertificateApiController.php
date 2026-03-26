<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ApiClient;
use App\Models\ApiRequestLog;
use App\Models\CourseMapping;
use App\Services\ImageCertificateService;
use App\Services\TemplateResolver;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CertificateApiController extends Controller
{
    protected ImageCertificateService $imageCerts;
    protected TemplateResolver $resolver;

    public function __construct(ImageCertificateService $imageCerts, TemplateResolver $resolver)
    {
        $this->imageCerts = $imageCerts;
        $this->resolver = $resolver;
    }

    /**
     * Generate a certificate for an external platform user.
     *
     * POST /api/v1/certificates/generate
     */
    public function generate(Request $request): JsonResponse
    {
        $startTime = $request->attributes->get('api_start_time', microtime(true));
        $client = $request->attributes->get('api_client');

        try {
            // Validate request
            $validated = $request->validate([
                'course_id' => 'required|string|max:100',
                'recipient_name_ar' => 'required|string|max:255',
                'recipient_name_en' => 'required|string|max:255',
                'recipient_email' => 'nullable|email|max:255',
                'recipient_id' => 'nullable|string|max:100',
                'gender' => 'nullable|in:male,female',
                'completion_date' => 'nullable|date',
                'duration_from' => 'nullable|date',
                'duration_to' => 'nullable|date',
                'output_format' => 'nullable|in:pdf,image,both',
                'callback_url' => 'nullable|url',
                'custom_fields' => 'nullable|array',
            ]);

            // Find course mapping
            $courseMapping = CourseMapping::findByExternalId($client->id, $validated['course_id']);

            if (!$courseMapping) {
                return $this->errorResponse(
                    $request,
                    $client,
                    404,
                    'course_not_found',
                    "Course '{$validated['course_id']}' is not mapped to any certificate track",
                    $startTime
                );
            }

            if (!$courseMapping->active) {
                return $this->errorResponse(
                    $request,
                    $client,
                    400,
                    'course_inactive',
                    'This course mapping is currently inactive',
                    $startTime
                );
            }

            // Check if track is active
            if (!$courseMapping->track || !$courseMapping->track->active) {
                return $this->errorResponse(
                    $request,
                    $client,
                    400,
                    'track_inactive',
                    'The certificate track for this course is currently inactive',
                    $startTime
                );
            }

            // Determine gender
            $gender = $validated['gender'] ?? $courseMapping->default_gender ?? 'male';

            // Get track settings
            $track = $courseMapping->track;
            $certificateType = $courseMapping->certificate_type;

            try {
                $tpl = $this->resolver->resolve($certificateType, $track->key, $gender);
            } catch (\Exception $e) {
                return $this->errorResponse(
                    $request,
                    $client,
                    500,
                    'template_error',
                    'Failed to load certificate template: ' . $e->getMessage(),
                    $startTime
                );
            }

            $pos = $tpl['positions'];
            $bg = $tpl['bg_abs'];
            $style = $tpl['style'];

            // Apply style overrides from course mapping
            if (!empty($courseMapping->style_overrides)) {
                $style = array_merge($style, $courseMapping->style_overrides);
            }

            // Set duration mode and dates
            $style['duration_mode'] = isset($validated['duration_from']) ? 'range' : 'end';
            $style['duration_from'] = $validated['duration_from'] ?? null;
            $style['duration_to'] = $validated['duration_to'] ?? null;

            // Apply custom fields if provided
            if (!empty($validated['custom_fields'])) {
                $style = array_merge($style, $validated['custom_fields']);
            }

            // Generate certificate image
            $certificateDate = $validated['completion_date'] ?? now()->format('Y-m-d');

            $imageRel = $this->imageCerts->generateSingle(
                $validated['recipient_name_ar'],
                $validated['recipient_name_en'],
                $track->name_ar,
                $track->name_en,
                $certificateDate,
                $validated['duration_from'] ?? null,
                $pos,
                $bg,
                $style,
                null // No photo for API-generated certificates
            );

            $imageAbs = Storage::disk('local')->path($imageRel);

            // Generate unique certificate ID
            $certificateId = 'cert_' . Str::random(16);

            // Prepare response data
            $outputFormat = $validated['output_format'] ?? 'pdf';
            $response = [
                'success' => true,
                'certificate_id' => $certificateId,
                'recipient' => [
                    'name_ar' => $validated['recipient_name_ar'],
                    'name_en' => $validated['recipient_name_en'],
                    'email' => $validated['recipient_email'] ?? null,
                ],
                'course' => [
                    'external_id' => $validated['course_id'],
                    'name' => $courseMapping->external_course_name,
                ],
                'generated_at' => now()->toIso8601String(),
            ];

            // Generate PDF if requested
            if ($outputFormat === 'pdf' || $outputFormat === 'both') {
                $pdfAbs = $this->convertImageToPdf($imageAbs);

                // Create safe filename
                $namePart = $this->safeFilename($validated['recipient_name_ar'] ?: $validated['recipient_name_en']);
                $trackPart = $this->safeFilename($track->name_ar ?: $track->name_en);
                $pdfName = $namePart . '_' . $trackPart . '.pdf';
                $pdfRel = 'api_certificates/' . $client->slug . '/' . date('Y-m') . '/' . $certificateId . '/' . $pdfName;
                $pdfDestAbs = Storage::disk('local')->path($pdfRel);

                // Ensure directory exists
                $pdfDir = dirname($pdfDestAbs);
                if (!is_dir($pdfDir)) {
                    @mkdir($pdfDir, 0775, true);
                }

                rename($pdfAbs, $pdfDestAbs);

                // Generate download URL (valid for 24 hours)
                $pdfUrl = URL::temporarySignedRoute('download', now()->addHours(24), [
                    'p' => Crypt::encryptString($pdfRel),
                ]);

                $response['pdf'] = [
                    'url' => $pdfUrl,
                    'filename' => $pdfName,
                    'expires_at' => now()->addHours(24)->toIso8601String(),
                ];
            }

            // Generate image URL if requested
            if ($outputFormat === 'image' || $outputFormat === 'both') {
                $imageName = $this->safeFilename($validated['recipient_name_ar']) . '_' . $this->safeFilename($track->name_ar) . '.png';
                $imageStorageRel = 'api_certificates/' . $client->slug . '/' . date('Y-m') . '/' . $certificateId . '/' . $imageName;
                $imageDestAbs = Storage::disk('local')->path($imageStorageRel);

                $imageDir = dirname($imageDestAbs);
                if (!is_dir($imageDir)) {
                    @mkdir($imageDir, 0775, true);
                }

                copy($imageAbs, $imageDestAbs);

                $imageUrl = URL::temporarySignedRoute('download', now()->addHours(24), [
                    'p' => Crypt::encryptString($imageStorageRel),
                ]);

                $response['image'] = [
                    'url' => $imageUrl,
                    'filename' => $imageName,
                    'expires_at' => now()->addHours(24)->toIso8601String(),
                ];
            }

            // Clean up temporary image
            @unlink($imageAbs);

            // Update statistics
            $courseMapping->recordCertificateGenerated();
            $client->recordUsage(true);

            // Log the successful request
            $executionTime = (int)((microtime(true) - $startTime) * 1000);
            ApiRequestLog::log(
                $client,
                $request->path(),
                $request->method(),
                $request->ip(),
                $request->except(['api_secret']),
                200,
                ['certificate_id' => $certificateId],
                $executionTime,
                null,
                $certificateId
            );

            // Log to activity log
            ActivityLogService::logGenerate(
                $courseMapping->external_course_name,
                1,
                $outputFormat,
                [
                    'api_client' => $client->name,
                    'certificate_id' => $certificateId,
                    'recipient' => $validated['recipient_name_ar'],
                ]
            );

            // Send webhook if configured
            if ($client->webhook_url && $client->hasScope(ApiClient::SCOPE_WEBHOOK_RECEIVE)) {
                $this->sendWebhook($client, $response, $validated);
            }

            // Also send to callback URL if provided
            if (!empty($validated['callback_url'])) {
                $this->sendCallback($validated['callback_url'], $response);
            }

            return response()->json($response);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->errorResponse(
                $request,
                $client,
                422,
                'validation_error',
                'Validation failed',
                $startTime,
                ['errors' => $e->errors()]
            );
        } catch (\Exception $e) {
            \Log::error('API Certificate Generation Error', [
                'client' => $client->slug,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse(
                $request,
                $client,
                500,
                'generation_error',
                'Failed to generate certificate: ' . $e->getMessage(),
                $startTime
            );
        }
    }

    /**
     * Get list of mapped courses for this client.
     *
     * GET /api/v1/courses
     */
    public function listCourses(Request $request): JsonResponse
    {
        $client = $request->attributes->get('api_client');

        $courses = CourseMapping::where('api_client_id', $client->id)
            ->where('active', true)
            ->with('track:id,key,name_ar,name_en,active')
            ->get()
            ->map(function ($mapping) {
                return [
                    'external_course_id' => $mapping->external_course_id,
                    'course_name' => $mapping->external_course_name,
                    'course_name_en' => $mapping->external_course_name_en,
                    'certificate_type' => $mapping->certificate_type,
                    'default_gender' => $mapping->default_gender,
                    'track' => [
                        'key' => $mapping->track->key,
                        'name_ar' => $mapping->track->name_ar,
                        'name_en' => $mapping->track->name_en,
                    ],
                    'certificates_generated' => $mapping->certificates_generated,
                ];
            });

        return response()->json([
            'success' => true,
            'courses' => $courses,
            'total' => $courses->count(),
        ]);
    }

    /**
     * Get client statistics.
     *
     * GET /api/v1/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $client = $request->attributes->get('api_client');

        $todayStart = now()->startOfDay();
        $monthStart = now()->startOfMonth();

        $stats = [
            'total_requests' => $client->total_requests,
            'total_certificates' => $client->total_certificates,
            'today_certificates' => ApiRequestLog::where('api_client_id', $client->id)
                ->where('created_at', '>=', $todayStart)
                ->whereNotNull('certificate_id')
                ->count(),
            'month_certificates' => ApiRequestLog::where('api_client_id', $client->id)
                ->where('created_at', '>=', $monthStart)
                ->whereNotNull('certificate_id')
                ->count(),
            'rate_limit' => [
                'per_minute' => $client->rate_limit,
                'remaining' => $request->attributes->get('rate_limit_remaining'),
            ],
            'daily_limit' => [
                'per_day' => $client->daily_limit,
                'remaining' => $request->attributes->get('daily_limit_remaining'),
            ],
            'courses_mapped' => CourseMapping::where('api_client_id', $client->id)->count(),
            'last_used_at' => $client->last_used_at?->toIso8601String(),
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * Verify certificate by ID.
     *
     * GET /api/v1/certificates/{id}/verify
     */
    public function verify(Request $request, string $certificateId): JsonResponse
    {
        $client = $request->attributes->get('api_client');

        // Find the certificate in logs
        $log = ApiRequestLog::where('api_client_id', $client->id)
            ->where('certificate_id', $certificateId)
            ->first();

        if (!$log) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'certificate_not_found',
                    'message' => 'Certificate not found',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'certificate' => [
                'id' => $certificateId,
                'generated_at' => $log->created_at->toIso8601String(),
                'request_data' => $log->request_data,
                'valid' => true,
            ],
        ]);
    }

    /**
     * Convert image to PDF.
     */
    private function convertImageToPdf(string $imageAbs): string
    {
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 0,
            'margin_right' => 0,
            'margin_top' => 0,
            'margin_bottom' => 0,
            'margin_header' => 0,
            'margin_footer' => 0,
            'dpi' => 300,
        ]);

        $html = sprintf(
            '<div style="margin:0;padding:0;width:100%%;height:100%%;position:absolute;top:0;left:0;">' .
            '<img src="%s" style="width:100%%;height:100%%;display:block;object-fit:fill;" />' .
            '</div>',
            $imageAbs
        );

        $mpdf->WriteHTML($html);

        $pdfPath = str_replace('.png', '.pdf', $imageAbs);
        $pdfPath = str_replace('certificates', 'api_certificates_temp', $pdfPath);

        $dir = dirname($pdfPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $mpdf->Output($pdfPath, \Mpdf\Output\Destination::FILE);

        return $pdfPath;
    }

    /**
     * Create safe filename preserving Arabic.
     */
    private function safeFilename(string $name, string $fallback = 'certificate'): string
    {
        $name = trim($name) ?: $fallback;
        $name = preg_replace('/[\\\\\\/\\:\\*\\?\\"\\<\\>\\|\\r\\n]+/u', ' ', $name);
        $name = preg_replace('/\\s+/u', ' ', $name);
        $name = mb_substr($name, 0, 80, 'UTF-8');
        return trim($name);
    }

    /**
     * Send webhook to client.
     */
    private function sendWebhook(ApiClient $client, array $certificateData, array $requestData): void
    {
        try {
            $payload = [
                'event' => 'certificate.generated',
                'timestamp' => now()->toIso8601String(),
                'certificate' => $certificateData,
                'request' => [
                    'course_id' => $requestData['course_id'],
                    'recipient_id' => $requestData['recipient_id'] ?? null,
                    'recipient_email' => $requestData['recipient_email'] ?? null,
                ],
            ];

            $signature = $client->signWebhookPayload($payload);

            Http::withHeaders([
                'X-Webhook-Signature' => $signature,
                'Content-Type' => 'application/json',
            ])->timeout(10)->post($client->webhook_url, $payload);

        } catch (\Exception $e) {
            \Log::warning('Webhook delivery failed', [
                'client' => $client->slug,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send callback to provided URL.
     */
    private function sendCallback(string $callbackUrl, array $data): void
    {
        try {
            Http::timeout(10)->post($callbackUrl, $data);
        } catch (\Exception $e) {
            \Log::warning('Callback delivery failed', [
                'url' => $callbackUrl,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Return error response.
     */
    private function errorResponse(
        Request $request,
        ?ApiClient $client,
        int $statusCode,
        string $errorCode,
        string $message,
        float $startTime,
        array $extra = []
    ): JsonResponse {
        $executionTime = (int)((microtime(true) - $startTime) * 1000);

        ApiRequestLog::log(
            $client,
            $request->path(),
            $request->method(),
            $request->ip(),
            $request->except(['api_secret']),
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
