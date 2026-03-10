# Certificate Platform API Documentation

## Overview

The Certificate Platform API allows external platforms (like FEP, online course platforms, etc.) to programmatically generate certificates for their users without requiring them to register on the certificate platform.

**Base URL:** `https://your-domain.com/api/v1`

**API Version:** v1

---

## Table of Contents

1. [Authentication](#authentication)
2. [Rate Limiting](#rate-limiting)
3. [Endpoints](#endpoints)
   - [Status Check](#status-check)
   - [Generate Certificate](#generate-certificate)
   - [Verify Certificate](#verify-certificate)
   - [List Courses](#list-courses)
   - [Get Statistics](#get-statistics)
4. [Webhook Integration](#webhook-integration)
5. [Error Handling](#error-handling)
6. [Code Examples](#code-examples)
7. [Setup Guide](#setup-guide)

---

## Authentication

The API uses **API Key + Secret** authentication with HMAC signature verification.

### Required Headers

| Header | Description |
|--------|-------------|
| `X-API-Key` | Your API Key (provided by administrator) |
| `X-API-Secret` | Your API Secret (provided by administrator) |
| `Content-Type` | `application/json` |
| `Accept` | `application/json` |

### Example Request Headers

```http
POST /api/v1/certificates/generate HTTP/1.1
Host: certificates.example.com
X-API-Key: ck_abc123def456ghi789
X-API-Secret: cs_your_secret_key_here
Content-Type: application/json
Accept: application/json
```

### Security Notes

- **Never expose your API Secret** in client-side code
- All API calls should be made server-to-server
- Use HTTPS for all requests
- Store credentials securely (environment variables recommended)

---

## Rate Limiting

The API enforces rate limits to ensure fair usage:

| Limit Type | Default | Description |
|------------|---------|-------------|
| Per Minute | 60 requests | Maximum requests per minute |
| Per Day | 1000 requests | Maximum requests per day |

### Rate Limit Headers

Each response includes rate limit information:

```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 55
X-RateLimit-Reset: 1704067200
```

### Rate Limit Exceeded Response

```json
{
    "success": false,
    "error": {
        "code": "RATE_LIMIT_EXCEEDED",
        "message": "Rate limit exceeded. Please try again later.",
        "retry_after": 60
    }
}
```

---

## Endpoints

### Status Check

Check if the API is operational and your credentials are valid.

**Endpoint:** `GET /api/v1/status`

**Authentication:** Not required (public endpoint)

**Response:**
```json
{
    "success": true,
    "data": {
        "status": "operational",
        "version": "1.0",
        "timestamp": "2024-01-15T10:30:00Z"
    }
}
```

---

### Generate Certificate

Generate a certificate for a user who completed a course.

**Endpoint:** `POST /api/v1/certificates/generate`

**Required Scope:** `certificates.generate`

**Request Body:**

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| `course_id` | string | Yes | External course ID (mapped in admin panel) |
| `recipient_name` | string | Yes | Full name of certificate recipient (Arabic) |
| `recipient_name_en` | string | No | Full name in English |
| `gender` | string | No | `male` or `female` (uses course default if not provided) |
| `completion_date` | string | No | Date in `YYYY-MM-DD` format (defaults to today) |
| `external_id` | string | No | Your system's user/enrollment ID for reference |
| `custom_fields` | object | No | Additional custom fields for certificate |
| `output_format` | string | No | `pdf` or `image` (default: `pdf`) |
| `callback_url` | string | No | URL to receive certificate when ready |

**Example Request:**

```json
{
    "course_id": "course-python-101",
    "recipient_name": "محمد أحمد علي",
    "recipient_name_en": "Mohammed Ahmed Ali",
    "gender": "male",
    "completion_date": "2024-01-15",
    "external_id": "user-12345",
    "output_format": "pdf"
}
```

**Success Response:**

```json
{
    "success": true,
    "data": {
        "certificate_id": "cert_abc123xyz",
        "recipient_name": "محمد أحمد علي",
        "course_name": "دورة بايثون للمبتدئين",
        "track": "programming-basics",
        "completion_date": "2024-01-15",
        "download_url": "https://certificates.example.com/download?signature=...",
        "download_expires_at": "2024-01-16T10:30:00Z",
        "verification_url": "https://certificates.example.com/verify/cert_abc123xyz",
        "file_format": "pdf",
        "file_size": 245678
    }
}
```

**With Callback URL:**

If you provide a `callback_url`, the certificate will be generated asynchronously and sent to your webhook:

```json
{
    "success": true,
    "data": {
        "certificate_id": "cert_abc123xyz",
        "status": "processing",
        "message": "Certificate is being generated. You will receive it at the callback URL.",
        "estimated_time": "5 seconds"
    }
}
```

---

### Verify Certificate

Verify if a certificate is authentic.

**Endpoint:** `GET /api/v1/certificates/{certificate_id}/verify`

**Required Scope:** `certificates.generate`

**Response:**

```json
{
    "success": true,
    "data": {
        "valid": true,
        "certificate_id": "cert_abc123xyz",
        "recipient_name": "محمد أحمد علي",
        "course_name": "دورة بايثون للمبتدئين",
        "completion_date": "2024-01-15",
        "issued_at": "2024-01-15T10:30:00Z",
        "issuer": "منصة FEP التعليمية"
    }
}
```

**Invalid Certificate Response:**

```json
{
    "success": true,
    "data": {
        "valid": false,
        "message": "Certificate not found or invalid"
    }
}
```

---

### List Courses

Get all courses mapped to your API client.

**Endpoint:** `GET /api/v1/courses`

**Required Scope:** `courses.view`

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `active` | boolean | Filter by active status |
| `page` | integer | Page number for pagination |
| `per_page` | integer | Items per page (max: 100) |

**Response:**

```json
{
    "success": true,
    "data": {
        "courses": [
            {
                "course_id": "course-python-101",
                "name": "دورة بايثون للمبتدئين",
                "name_en": "Python for Beginners",
                "track": "programming-basics",
                "certificate_type": "student",
                "default_gender": null,
                "active": true
            },
            {
                "course_id": "course-web-dev",
                "name": "تطوير الويب",
                "name_en": "Web Development",
                "track": "web-development",
                "certificate_type": "student",
                "default_gender": null,
                "active": true
            }
        ],
        "pagination": {
            "current_page": 1,
            "total_pages": 1,
            "total_items": 2,
            "per_page": 20
        }
    }
}
```

---

### Get Statistics

Get usage statistics for your API client.

**Endpoint:** `GET /api/v1/stats`

**Required Scope:** `stats.view`

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `period` | string | `today`, `week`, `month`, `year`, `all` (default: `all`) |

**Response:**

```json
{
    "success": true,
    "data": {
        "period": "month",
        "statistics": {
            "total_certificates": 1250,
            "total_requests": 3500,
            "success_rate": 98.5,
            "certificates_by_course": [
                {
                    "course_id": "course-python-101",
                    "course_name": "دورة بايثون للمبتدئين",
                    "count": 450
                },
                {
                    "course_id": "course-web-dev",
                    "course_name": "تطوير الويب",
                    "count": 380
                }
            ],
            "daily_breakdown": [
                {"date": "2024-01-15", "certificates": 45},
                {"date": "2024-01-14", "certificates": 52}
            ]
        }
    }
}
```

---

## Webhook Integration

Webhooks allow you to receive certificates asynchronously and get notified about events.

### Webhook Setup

1. Provide a webhook URL when creating your API client in the admin panel
2. You'll receive a `webhook_secret` for signature verification
3. Ensure your endpoint accepts POST requests with JSON body

### Webhook Payload

When a certificate is generated (especially with `callback_url`):

```json
{
    "event": "certificate.generated",
    "timestamp": "2024-01-15T10:30:00Z",
    "data": {
        "certificate_id": "cert_abc123xyz",
        "external_id": "user-12345",
        "recipient_name": "محمد أحمد علي",
        "course_id": "course-python-101",
        "course_name": "دورة بايثون للمبتدئين",
        "download_url": "https://certificates.example.com/download?signature=...",
        "download_expires_at": "2024-01-16T10:30:00Z",
        "verification_url": "https://certificates.example.com/verify/cert_abc123xyz"
    }
}
```

### Webhook Signature Verification

Each webhook request includes a signature header:

```http
X-Webhook-Signature: sha256=abc123...
```

**Verify the signature (PHP example):**

```php
function verifyWebhookSignature($payload, $signature, $secret) {
    $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
    return hash_equals($expectedSignature, $signature);
}

// Usage
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'];
$secret = 'your_webhook_secret';

if (!verifyWebhookSignature($payload, $signature, $secret)) {
    http_response_code(401);
    exit('Invalid signature');
}

$data = json_decode($payload, true);
// Process webhook...
```

### Webhook Response

Your endpoint should return:
- **200 OK** - Webhook received successfully
- **Any other status** - Webhook will be retried (up to 3 times)

---

## Error Handling

### Error Response Format

```json
{
    "success": false,
    "error": {
        "code": "ERROR_CODE",
        "message": "Human-readable error message",
        "details": {}
    }
}
```

### Error Codes

| Code | HTTP Status | Description |
|------|-------------|-------------|
| `UNAUTHORIZED` | 401 | Invalid or missing API credentials |
| `FORBIDDEN` | 403 | Insufficient permissions (scope) |
| `NOT_FOUND` | 404 | Resource not found |
| `VALIDATION_ERROR` | 422 | Invalid request data |
| `RATE_LIMIT_EXCEEDED` | 429 | Too many requests |
| `COURSE_NOT_FOUND` | 404 | Course mapping not found |
| `COURSE_INACTIVE` | 400 | Course mapping is disabled |
| `GENERATION_FAILED` | 500 | Certificate generation failed |
| `INTERNAL_ERROR` | 500 | Internal server error |

### Validation Error Example

```json
{
    "success": false,
    "error": {
        "code": "VALIDATION_ERROR",
        "message": "The given data was invalid.",
        "details": {
            "recipient_name": ["The recipient name field is required."],
            "course_id": ["The selected course is invalid or inactive."]
        }
    }
}
```

---

## Code Examples

### PHP (using cURL)

```php
<?php

class CertificateAPI {
    private $baseUrl;
    private $apiKey;
    private $apiSecret;

    public function __construct($baseUrl, $apiKey, $apiSecret) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    private function request($method, $endpoint, $data = null) {
        $url = $this->baseUrl . $endpoint;

        $headers = [
            'X-API-Key: ' . $this->apiKey,
            'X-API-Secret: ' . $this->apiSecret,
            'Content-Type: application/json',
            'Accept: application/json',
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'status' => $httpCode,
            'data' => json_decode($response, true)
        ];
    }

    /**
     * Generate a certificate
     */
    public function generateCertificate($courseId, $recipientName, $options = []) {
        $data = array_merge([
            'course_id' => $courseId,
            'recipient_name' => $recipientName,
        ], $options);

        return $this->request('POST', '/api/v1/certificates/generate', $data);
    }

    /**
     * Verify a certificate
     */
    public function verifyCertificate($certificateId) {
        return $this->request('GET', '/api/v1/certificates/' . $certificateId . '/verify');
    }

    /**
     * Get mapped courses
     */
    public function getCourses() {
        return $this->request('GET', '/api/v1/courses');
    }

    /**
     * Get statistics
     */
    public function getStats($period = 'all') {
        return $this->request('GET', '/api/v1/stats?period=' . $period);
    }
}

// Usage Example
$api = new CertificateAPI(
    'https://certificates.example.com',
    'ck_your_api_key',
    'cs_your_api_secret'
);

// Generate a certificate
$result = $api->generateCertificate(
    'course-python-101',
    'محمد أحمد علي',
    [
        'recipient_name_en' => 'Mohammed Ahmed Ali',
        'gender' => 'male',
        'external_id' => 'user-12345'
    ]
);

if ($result['data']['success']) {
    $certificate = $result['data']['data'];
    echo "Certificate generated: " . $certificate['certificate_id'];
    echo "Download URL: " . $certificate['download_url'];
} else {
    echo "Error: " . $result['data']['error']['message'];
}
```

### JavaScript (Node.js using Axios)

```javascript
const axios = require('axios');

class CertificateAPI {
    constructor(baseUrl, apiKey, apiSecret) {
        this.client = axios.create({
            baseURL: baseUrl,
            headers: {
                'X-API-Key': apiKey,
                'X-API-Secret': apiSecret,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
    }

    async generateCertificate(courseId, recipientName, options = {}) {
        try {
            const response = await this.client.post('/api/v1/certificates/generate', {
                course_id: courseId,
                recipient_name: recipientName,
                ...options
            });
            return response.data;
        } catch (error) {
            return error.response?.data || { success: false, error: { message: error.message } };
        }
    }

    async verifyCertificate(certificateId) {
        try {
            const response = await this.client.get(`/api/v1/certificates/${certificateId}/verify`);
            return response.data;
        } catch (error) {
            return error.response?.data || { success: false, error: { message: error.message } };
        }
    }

    async getCourses() {
        try {
            const response = await this.client.get('/api/v1/courses');
            return response.data;
        } catch (error) {
            return error.response?.data || { success: false, error: { message: error.message } };
        }
    }

    async getStats(period = 'all') {
        try {
            const response = await this.client.get(`/api/v1/stats?period=${period}`);
            return response.data;
        } catch (error) {
            return error.response?.data || { success: false, error: { message: error.message } };
        }
    }
}

// Usage Example
const api = new CertificateAPI(
    'https://certificates.example.com',
    'ck_your_api_key',
    'cs_your_api_secret'
);

// Generate a certificate
async function generateUserCertificate(user, courseId) {
    const result = await api.generateCertificate(
        courseId,
        user.nameAr,
        {
            recipient_name_en: user.nameEn,
            gender: user.gender,
            external_id: user.id
        }
    );

    if (result.success) {
        console.log('Certificate URL:', result.data.download_url);
        return result.data;
    } else {
        console.error('Error:', result.error.message);
        return null;
    }
}

// With async/await
(async () => {
    const certificate = await generateUserCertificate({
        id: 'user-12345',
        nameAr: 'محمد أحمد علي',
        nameEn: 'Mohammed Ahmed Ali',
        gender: 'male'
    }, 'course-python-101');
})();
```

### Python (using requests)

```python
import requests
from typing import Optional, Dict, Any

class CertificateAPI:
    def __init__(self, base_url: str, api_key: str, api_secret: str):
        self.base_url = base_url.rstrip('/')
        self.headers = {
            'X-API-Key': api_key,
            'X-API-Secret': api_secret,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        }

    def _request(self, method: str, endpoint: str, data: Optional[Dict] = None) -> Dict[str, Any]:
        url = f"{self.base_url}{endpoint}"
        try:
            if method == 'GET':
                response = requests.get(url, headers=self.headers, params=data)
            else:
                response = requests.post(url, headers=self.headers, json=data)
            return response.json()
        except Exception as e:
            return {'success': False, 'error': {'message': str(e)}}

    def generate_certificate(
        self,
        course_id: str,
        recipient_name: str,
        recipient_name_en: Optional[str] = None,
        gender: Optional[str] = None,
        completion_date: Optional[str] = None,
        external_id: Optional[str] = None,
        output_format: str = 'pdf'
    ) -> Dict[str, Any]:
        """Generate a certificate for a course completion."""
        data = {
            'course_id': course_id,
            'recipient_name': recipient_name,
            'output_format': output_format
        }

        if recipient_name_en:
            data['recipient_name_en'] = recipient_name_en
        if gender:
            data['gender'] = gender
        if completion_date:
            data['completion_date'] = completion_date
        if external_id:
            data['external_id'] = external_id

        return self._request('POST', '/api/v1/certificates/generate', data)

    def verify_certificate(self, certificate_id: str) -> Dict[str, Any]:
        """Verify a certificate by its ID."""
        return self._request('GET', f'/api/v1/certificates/{certificate_id}/verify')

    def get_courses(self, active_only: bool = True) -> Dict[str, Any]:
        """Get all mapped courses."""
        params = {'active': 'true'} if active_only else {}
        return self._request('GET', '/api/v1/courses', params)

    def get_stats(self, period: str = 'all') -> Dict[str, Any]:
        """Get usage statistics."""
        return self._request('GET', f'/api/v1/stats?period={period}')


# Usage Example
if __name__ == '__main__':
    api = CertificateAPI(
        base_url='https://certificates.example.com',
        api_key='ck_your_api_key',
        api_secret='cs_your_api_secret'
    )

    # Generate a certificate
    result = api.generate_certificate(
        course_id='course-python-101',
        recipient_name='محمد أحمد علي',
        recipient_name_en='Mohammed Ahmed Ali',
        gender='male',
        external_id='user-12345'
    )

    if result.get('success'):
        certificate = result['data']
        print(f"Certificate ID: {certificate['certificate_id']}")
        print(f"Download URL: {certificate['download_url']}")
    else:
        print(f"Error: {result['error']['message']}")
```

### Laravel Integration Example

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CertificateService
{
    protected $baseUrl;
    protected $apiKey;
    protected $apiSecret;

    public function __construct()
    {
        $this->baseUrl = config('services.certificates.url');
        $this->apiKey = config('services.certificates.key');
        $this->apiSecret = config('services.certificates.secret');
    }

    protected function client()
    {
        return Http::withHeaders([
            'X-API-Key' => $this->apiKey,
            'X-API-Secret' => $this->apiSecret,
        ])->acceptJson();
    }

    public function generateCertificate(array $data)
    {
        try {
            $response = $this->client()
                ->post("{$this->baseUrl}/api/v1/certificates/generate", $data);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Certificate generation failed', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('Certificate API error', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => ['message' => $e->getMessage()]
            ];
        }
    }

    public function verifyCertificate(string $certificateId)
    {
        $response = $this->client()
            ->get("{$this->baseUrl}/api/v1/certificates/{$certificateId}/verify");

        return $response->json();
    }
}

// In config/services.php
return [
    // ... other services

    'certificates' => [
        'url' => env('CERTIFICATE_API_URL'),
        'key' => env('CERTIFICATE_API_KEY'),
        'secret' => env('CERTIFICATE_API_SECRET'),
    ],
];

// In .env
// CERTIFICATE_API_URL=https://certificates.example.com
// CERTIFICATE_API_KEY=ck_your_api_key
// CERTIFICATE_API_SECRET=cs_your_api_secret

// Usage in Controller
class CourseController extends Controller
{
    public function completeCourse(Course $course, User $user, CertificateService $certificateService)
    {
        // Mark course as completed
        $user->courses()->updateExistingPivot($course->id, [
            'completed_at' => now()
        ]);

        // Generate certificate
        $result = $certificateService->generateCertificate([
            'course_id' => $course->external_course_id,
            'recipient_name' => $user->name_ar,
            'recipient_name_en' => $user->name_en,
            'gender' => $user->gender,
            'external_id' => $user->id,
        ]);

        if ($result['success']) {
            // Store certificate info
            $user->certificates()->create([
                'course_id' => $course->id,
                'certificate_id' => $result['data']['certificate_id'],
                'download_url' => $result['data']['download_url'],
            ]);

            return response()->json([
                'message' => 'Course completed! Certificate generated.',
                'certificate' => $result['data']
            ]);
        }

        return response()->json([
            'message' => 'Course completed but certificate generation failed.',
            'error' => $result['error']
        ], 500);
    }
}
```

---

## Setup Guide

### For Platform Administrators

1. **Create Institution**
   - Go to Institutions management
   - Create a new institution for the external platform (e.g., "FEP Platform")

2. **Create API Client**
   - Navigate to "API العملاء" (API Clients) in admin menu
   - Click "إضافة عميل API" (Add API Client)
   - Fill in:
     - Name: Platform name
     - Slug: Unique identifier (e.g., `fep-platform`)
     - Institution: Select the created institution
     - Scopes: Select required permissions
     - Rate Limits: Set appropriate limits
     - Webhook URL: (Optional) Platform's webhook endpoint

3. **Save Credentials**
   - After creation, you'll see API Key and Secret
   - **IMPORTANT:** Save these immediately - the secret won't be shown again

4. **Map Courses**
   - Go to the API client's detail page
   - Click "ربط الدورات" (Course Mappings)
   - Add course mappings:
     - External Course ID: The ID used in your platform
     - Course Name: Name for display on certificates
     - Track: Select the certificate track/template
     - Certificate Type: Student or Teacher

### For Platform Developers

1. **Receive Credentials**
   - Get API Key and Secret from administrator
   - Store securely (environment variables)

2. **Test Connection**
   ```bash
   curl -X GET "https://certificates.example.com/api/v1/status"
   ```

3. **List Available Courses**
   ```bash
   curl -X GET "https://certificates.example.com/api/v1/courses" \
     -H "X-API-Key: your_api_key" \
     -H "X-API-Secret: your_api_secret"
   ```

4. **Integrate Certificate Generation**
   - Use the code examples above
   - Call the API when a user completes a course
   - Store the certificate URL for user access

5. **Set Up Webhook (Optional)**
   - Create an endpoint to receive webhooks
   - Verify signatures for security
   - Process certificate notifications

---

## Best Practices

### Security
- Never expose API credentials in frontend code
- Always use HTTPS
- Verify webhook signatures
- Store credentials in environment variables
- Rotate credentials periodically

### Performance
- Cache course mappings locally
- Handle rate limit errors with exponential backoff
- Use webhooks for async processing of large batches

### Error Handling
- Always check the `success` field in responses
- Log errors for debugging
- Provide meaningful feedback to users
- Implement retry logic for transient failures

### Testing
- Use a test/staging environment first
- Verify course mappings before going live
- Test webhook delivery and signature verification
- Monitor API usage statistics

---

## Support

For API support or to request new features:
- Contact the platform administrator
- Check the admin panel for logs and statistics
- Review error messages for troubleshooting

---

**Document Version:** 1.0
**Last Updated:** 2026-01-15
