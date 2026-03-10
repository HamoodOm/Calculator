/**
 * Test Suites Definition
 * All test cases aligned with actual API response formats from CertificateApiController
 *
 * Actual response shapes:
 *   GET  /api/v1/status                  -> {status, version, timestamp, documentation}
 *   POST /api/v1/certificates/generate   -> {success, certificate_id, recipient, course, generated_at, pdf?, image?}
 *   GET  /api/v1/certificates/{id}/verify-> {success, certificate: {id, generated_at, request_data, valid}}
 *   GET  /api/v1/courses                 -> {success, courses: [...], total}
 *   GET  /api/v1/stats                   -> {success, stats: {...}}
 *   Errors                               -> {success: false, error: {code, message}}
 */

const TestSuites = {

    // ============================
    // AUTHENTICATION TEST SUITE
    // ============================
    auth: {
        id: 'auth',
        name: 'Authentication Tests',
        tests: [
            {
                id: 'auth-no-credentials',
                name: 'Request without credentials',
                description: 'Should return 401 when no API key/secret provided',
                async run(client) {
                    const response = await client.requestWithCustomHeaders(
                        'GET', '/api/v1/courses', {}
                    );
                    return {
                        response,
                        request: { method: 'GET', url: '/api/v1/courses', headers: {} },
                        assertions: [
                            Assert.statusEquals(response, 401, 'Should return 401 Unauthorized'),
                            Assert.isErrorResponse(response, 'Should have success: false'),
                        ],
                    };
                },
            },
            {
                id: 'auth-invalid-api-key',
                name: 'Request with invalid API key',
                description: 'Should return 401 when API key is invalid',
                async run(client) {
                    const response = await client.requestWithCustomHeaders(
                        'GET', '/api/v1/courses',
                        { 'X-API-Key': 'ck_invalid_key_12345', 'X-API-Secret': 'cs_invalid_secret_12345' }
                    );
                    return {
                        response,
                        request: { method: 'GET', url: '/api/v1/courses', headers: { 'X-API-Key': 'ck_invalid_key_12345' } },
                        assertions: [
                            Assert.statusEquals(response, 401, 'Should return 401 Unauthorized'),
                            Assert.isErrorResponse(response, 'Should have success: false'),
                        ],
                    };
                },
            },
            {
                id: 'auth-missing-api-secret',
                name: 'Request with API key but missing secret',
                description: 'Should return 401 when API secret is missing',
                async run(client) {
                    const config = client.getConfig();
                    const response = await client.requestWithCustomHeaders(
                        'GET', '/api/v1/courses',
                        { 'X-API-Key': config.apiKey }
                    );
                    return {
                        response,
                        request: { method: 'GET', url: '/api/v1/courses', headers: { 'X-API-Key': config.apiKey } },
                        assertions: [
                            Assert.statusEquals(response, 401, 'Should return 401 Unauthorized'),
                            Assert.isErrorResponse(response, 'Should have success: false'),
                        ],
                    };
                },
            },
            {
                id: 'auth-missing-api-key',
                name: 'Request with API secret but missing key',
                description: 'Should return 401 when API key is missing',
                async run(client) {
                    const config = client.getConfig();
                    const response = await client.requestWithCustomHeaders(
                        'GET', '/api/v1/courses',
                        { 'X-API-Secret': config.apiSecret }
                    );
                    return {
                        response,
                        request: { method: 'GET', url: '/api/v1/courses', headers: { 'X-API-Secret': config.apiSecret } },
                        assertions: [
                            Assert.statusEquals(response, 401, 'Should return 401 Unauthorized'),
                            Assert.isErrorResponse(response, 'Should have success: false'),
                        ],
                    };
                },
            },
            {
                id: 'auth-valid-credentials',
                name: 'Request with valid credentials',
                description: 'Should return 200 when valid API key and secret are provided',
                async run(client) {
                    const response = await client.listCourses();
                    return {
                        response,
                        request: { method: 'GET', url: '/api/v1/courses' },
                        assertions: [
                            Assert.statusEquals(response, 200, 'Should return 200 OK'),
                            Assert.isSuccessResponse(response, 'Should have success: true'),
                        ],
                    };
                },
            },
            {
                id: 'auth-status-no-auth',
                name: 'Status endpoint without authentication',
                description: 'Status endpoint should work without credentials (public endpoint)',
                async run(client) {
                    const response = await client.checkStatus();
                    return {
                        response,
                        request: { method: 'GET', url: '/api/v1/status' },
                        assertions: [
                            Assert.statusEquals(response, 200, 'Should return 200 OK'),
                            Assert.equals(response.data?.status, 'operational', 'Should have status: operational'),
                            Assert.hasKey(response.data, 'version', 'Should have version field'),
                            Assert.hasKey(response.data, 'timestamp', 'Should have timestamp field'),
                        ],
                    };
                },
            },
            {
                id: 'auth-empty-credentials',
                name: 'Request with empty credential strings',
                description: 'Should return 401 when credentials are empty strings',
                async run(client) {
                    const response = await client.requestWithCustomHeaders(
                        'GET', '/api/v1/courses',
                        { 'X-API-Key': '', 'X-API-Secret': '' }
                    );
                    return {
                        response,
                        request: { method: 'GET', url: '/api/v1/courses', headers: { 'X-API-Key': '', 'X-API-Secret': '' } },
                        assertions: [
                            Assert.statusEquals(response, 401, 'Should return 401 Unauthorized'),
                        ],
                    };
                },
            },
            {
                id: 'auth-swapped-credentials',
                name: 'Request with swapped key/secret',
                description: 'Should return 401 when API key and secret are swapped',
                async run(client) {
                    const config = client.getConfig();
                    const response = await client.requestWithCustomHeaders(
                        'GET', '/api/v1/courses',
                        { 'X-API-Key': config.apiSecret, 'X-API-Secret': config.apiKey }
                    );
                    return {
                        response,
                        request: { method: 'GET', url: '/api/v1/courses' },
                        assertions: [
                            Assert.statusEquals(response, 401, 'Should return 401 Unauthorized'),
                        ],
                    };
                },
            },
        ],
    },

    // ============================
    // VALIDATION TEST SUITE
    // ============================
    validation: {
        id: 'validation',
        name: 'Validation Tests',
        tests: [
            {
                id: 'val-generate-missing-all',
                name: 'Generate: missing all required fields',
                description: 'Should return 422 when no fields are provided',
                async run(client) {
                    const response = await client.generateCertificate({});
                    return {
                        response,
                        request: { method: 'POST', url: '/api/v1/certificates/generate', body: {} },
                        assertions: [
                            Assert.statusEquals(response, 422, 'Should return 422 Validation Error'),
                            Assert.isErrorResponse(response, 'Should have success: false'),
                        ],
                    };
                },
            },
            {
                id: 'val-generate-missing-course-id',
                name: 'Generate: missing course_id',
                description: 'Should return 422 when course_id is not provided',
                async run(client) {
                    const response = await client.generateCertificate({
                        recipient_name_ar: 'محمد أحمد علي',
                        recipient_name_en: 'Mohammed Ahmed Ali',
                    });
                    return {
                        response,
                        request: { method: 'POST', url: '/api/v1/certificates/generate', body: { recipient_name_ar: 'محمد أحمد علي' } },
                        assertions: [
                            Assert.statusEquals(response, 422, 'Should return 422 Validation Error'),
                            Assert.isErrorResponse(response, 'Should have success: false'),
                        ],
                    };
                },
            },
            {
                id: 'val-generate-missing-recipient-name-ar',
                name: 'Generate: missing recipient_name_ar',
                description: 'Should return validation error when Arabic name is missing',
                async run(client) {
                    const response = await client.generateCertificate({
                        course_id: 'course-test-101',
                        recipient_name_en: 'Test User',
                    });
                    return {
                        response,
                        request: { method: 'POST', url: '/api/v1/certificates/generate' },
                        assertions: [
                            Assert.statusEquals(response, 422, 'Should return 422 Validation Error'),
                            Assert.isErrorResponse(response, 'Should have success: false'),
                        ],
                    };
                },
            },
            {
                id: 'val-generate-missing-recipient-name-en',
                name: 'Generate: missing recipient_name_en',
                description: 'Should return validation error when English name is missing',
                async run(client) {
                    const response = await client.generateCertificate({
                        course_id: 'course-test-101',
                        recipient_name_ar: 'محمد أحمد',
                    });
                    return {
                        response,
                        request: { method: 'POST', url: '/api/v1/certificates/generate' },
                        assertions: [
                            Assert.statusEquals(response, 422, 'Should return 422 Validation Error'),
                            Assert.isErrorResponse(response, 'Should have success: false'),
                        ],
                    };
                },
            },
            {
                id: 'val-generate-invalid-course-id',
                name: 'Generate: non-existent course_id',
                description: 'Should return error when course does not exist in mappings',
                async run(client) {
                    const response = await client.generateCertificate({
                        course_id: 'non-existent-course-xyz-999',
                        recipient_name_ar: 'محمد أحمد',
                        recipient_name_en: 'Mohammed Ahmed',
                    });
                    return {
                        response,
                        request: { method: 'POST', url: '/api/v1/certificates/generate' },
                        assertions: [
                            Assert.isTrue(
                                response.status === 404 || response.status === 422 || response.status === 400,
                                'Should return 404, 422, or 400 for non-existent course'
                            ),
                            Assert.isErrorResponse(response, 'Should have success: false'),
                        ],
                    };
                },
            },
            {
                id: 'val-generate-invalid-gender',
                name: 'Generate: invalid gender value',
                description: 'Should return validation error for invalid gender',
                async run(client) {
                    const response = await client.generateCertificate({
                        course_id: 'course-test-101',
                        recipient_name_ar: 'محمد أحمد',
                        recipient_name_en: 'Mohammed Ahmed',
                        gender: 'invalid_gender',
                    });
                    return {
                        response,
                        request: { method: 'POST', url: '/api/v1/certificates/generate' },
                        assertions: [
                            Assert.isTrue(
                                response.status === 422 || response.status === 400,
                                'Should return validation error for invalid gender'
                            ),
                            Assert.isErrorResponse(response, 'Should have success: false'),
                        ],
                    };
                },
            },
            {
                id: 'val-generate-invalid-date-format',
                name: 'Generate: invalid date format',
                description: 'Should return validation error for malformed completion_date',
                async run(client) {
                    const response = await client.generateCertificate({
                        course_id: 'course-test-101',
                        recipient_name_ar: 'محمد أحمد',
                        recipient_name_en: 'Mohammed Ahmed',
                        completion_date: 'not-a-date',
                    });
                    return {
                        response,
                        request: { method: 'POST', url: '/api/v1/certificates/generate' },
                        assertions: [
                            Assert.isTrue(
                                response.status === 422 || response.status === 400,
                                'Should return validation error for invalid date'
                            ),
                            Assert.isErrorResponse(response, 'Should have success: false'),
                        ],
                    };
                },
            },
            {
                id: 'val-generate-invalid-output-format',
                name: 'Generate: invalid output_format',
                description: 'Should return validation error for unsupported output format',
                async run(client) {
                    const response = await client.generateCertificate({
                        course_id: 'course-test-101',
                        recipient_name_ar: 'محمد أحمد',
                        recipient_name_en: 'Mohammed Ahmed',
                        output_format: 'docx',
                    });
                    return {
                        response,
                        request: { method: 'POST', url: '/api/v1/certificates/generate' },
                        assertions: [
                            Assert.isTrue(
                                response.status === 422 || response.status === 400,
                                'Should return validation error for invalid output format'
                            ),
                            Assert.isErrorResponse(response, 'Should have success: false'),
                        ],
                    };
                },
            },
            {
                id: 'val-generate-empty-recipient-name',
                name: 'Generate: empty recipient_name_ar string',
                description: 'Should return validation error for empty Arabic recipient name',
                async run(client) {
                    const response = await client.generateCertificate({
                        course_id: 'course-test-101',
                        recipient_name_ar: '',
                        recipient_name_en: 'Test',
                    });
                    return {
                        response,
                        request: { method: 'POST', url: '/api/v1/certificates/generate' },
                        assertions: [
                            Assert.statusEquals(response, 422, 'Should return 422 Validation Error'),
                            Assert.isErrorResponse(response, 'Should have success: false'),
                        ],
                    };
                },
            },
            {
                id: 'val-verify-nonexistent',
                name: 'Verify: non-existent certificate ID',
                description: 'Should return 404 for non-existent certificate',
                async run(client) {
                    const response = await client.verifyCertificate('cert_nonexistent_xyz_000');
                    return {
                        response,
                        request: { method: 'GET', url: '/api/v1/certificates/cert_nonexistent_xyz_000/verify' },
                        assertions: [
                            Assert.statusEquals(response, 404, 'Should return 404 Not Found'),
                            Assert.isErrorResponse(response, 'Should have success: false'),
                        ],
                    };
                },
            },
            {
                id: 'val-courses-invalid-pagination',
                name: 'Courses: per_page exceeds maximum',
                description: 'Should handle per_page over the maximum of 100',
                async run(client) {
                    const response = await client.listCourses({ per_page: 999 });
                    return {
                        response,
                        request: { method: 'GET', url: '/api/v1/courses?per_page=999' },
                        assertions: [
                            Assert.isTrue(
                                response.status === 200 || response.status === 422,
                                'Should either cap the value or return validation error'
                            ),
                        ],
                    };
                },
            },
            {
                id: 'val-stats-invalid-period',
                name: 'Stats: invalid period value',
                description: 'Should handle invalid period parameter',
                async run(client) {
                    const response = await client.getStats('invalid_period');
                    return {
                        response,
                        request: { method: 'GET', url: '/api/v1/stats?period=invalid_period' },
                        assertions: [
                            Assert.isTrue(
                                response.status === 200 || response.status === 422 || response.status === 400,
                                'Should either use default or return validation error'
                            ),
                        ],
                    };
                },
            },
            {
                id: 'val-generate-very-long-name',
                name: 'Generate: extremely long recipient name',
                description: 'Should reject name exceeding max:255 validation rule',
                async run(client) {
                    const longName = 'أ'.repeat(5000);
                    const response = await client.generateCertificate({
                        course_id: 'course-test-101',
                        recipient_name_ar: longName,
                        recipient_name_en: 'A'.repeat(5000),
                    });
                    return {
                        response,
                        request: { method: 'POST', url: '/api/v1/certificates/generate', body: { recipient_name_ar: '(5000 chars)' } },
                        assertions: [
                            Assert.statusEquals(response, 422, 'Should return 422 for name exceeding max length'),
                            Assert.isErrorResponse(response, 'Should have success: false'),
                        ],
                    };
                },
            },
            {
                id: 'val-generate-special-chars-name',
                name: 'Generate: special characters in name',
                description: 'Should handle special characters and HTML in recipient name',
                async run(client) {
                    const response = await client.generateCertificate({
                        course_id: 'course-test-101',
                        recipient_name_ar: '<script>alert("xss")</script>',
                        recipient_name_en: '<script>alert("xss")</script>',
                    });
                    return {
                        response,
                        request: { method: 'POST', url: '/api/v1/certificates/generate' },
                        assertions: [
                            Assert.isTrue(
                                response.status === 422 || response.status === 400 || response.status === 200 || response.status === 404,
                                'Should sanitize or reject HTML/script injection in name'
                            ),
                        ],
                    };
                },
            },
            {
                id: 'val-generate-sql-injection',
                name: 'Generate: SQL injection attempt in course_id',
                description: 'Should safely handle SQL injection attempt',
                async run(client) {
                    const response = await client.generateCertificate({
                        course_id: "' OR 1=1 --",
                        recipient_name_ar: 'اختبار أمان',
                        recipient_name_en: 'Security Test',
                    });
                    return {
                        response,
                        request: { method: 'POST', url: '/api/v1/certificates/generate' },
                        assertions: [
                            Assert.isTrue(
                                response.status === 422 || response.status === 404 || response.status === 400,
                                'Should safely reject SQL injection attempt'
                            ),
                            Assert.isErrorResponse(response, 'Should have success: false'),
                        ],
                    };
                },
            },
        ],
    },

    // ============================
    // INTEGRATION TEST SUITE
    // ============================
    integration: {
        id: 'integration',
        name: 'Integration Tests',
        tests: [
            {
                id: 'int-status-check',
                name: 'API Status health check',
                description: 'Verify the API is operational and returns correct structure',
                async run(client) {
                    const response = await client.checkStatus();
                    return {
                        response,
                        request: { method: 'GET', url: '/api/v1/status' },
                        assertions: [
                            Assert.statusEquals(response, 200, 'Should return 200 OK'),
                            Assert.equals(response.data?.status, 'operational', 'Should have status: operational'),
                            Assert.hasKey(response.data, 'version', 'Should have version field'),
                            Assert.responseTimeUnder(response, 5000, 'Should respond within 5s'),
                        ],
                    };
                },
            },
            {
                id: 'int-list-courses',
                name: 'List available courses',
                description: 'Fetch all courses mapped to this API client',
                async run(client, context) {
                    const response = await client.listCourses();
                    const courses = response.data?.courses || [];
                    return {
                        response,
                        request: { method: 'GET', url: '/api/v1/courses' },
                        assertions: [
                            Assert.statusEquals(response, 200, 'Should return 200 OK'),
                            Assert.isSuccessResponse(response, 'Should have success: true'),
                            Assert.hasKey(response.data, 'courses', 'Should have courses field'),
                            Assert.isArray(courses, 'courses should be an array'),
                        ],
                        context: {
                            courses,
                            firstCourseId: Array.isArray(courses) && courses.length > 0
                                ? courses[0].external_course_id
                                : null,
                        },
                    };
                },
            },
            {
                id: 'int-generate-certificate',
                name: 'Generate a certificate',
                description: 'Generate a certificate using the first available course',
                async run(client, context) {
                    const courseId = context.firstCourseId || 'course-test-101';
                    const response = await client.generateCertificate({
                        course_id: courseId,
                        recipient_name_ar: 'اختبار النظام',
                        recipient_name_en: 'System Test',
                        gender: 'male',
                        output_format: 'pdf',
                    });

                    const certId = response.data?.certificate_id;

                    return {
                        response,
                        request: { method: 'POST', url: '/api/v1/certificates/generate' },
                        assertions: [
                            Assert.statusEquals(response, 200, 'Should return 200 OK'),
                            Assert.isSuccessResponse(response, 'Should have success: true'),
                            Assert.hasKey(response.data, 'certificate_id', 'Should return a certificate_id'),
                        ],
                        context: {
                            certificateId: certId,
                        },
                    };
                },
            },
            {
                id: 'int-verify-certificate',
                name: 'Verify the generated certificate',
                description: 'Verify the certificate we just generated is valid',
                async run(client, context) {
                    const certId = context.certificateId;
                    if (!certId) {
                        return {
                            response: { status: 0, data: {} },
                            assertions: [{
                                passed: false,
                                message: 'No certificate_id from previous test - cannot verify',
                                expected: 'certificate_id',
                                actual: 'none',
                            }],
                        };
                    }

                    const response = await client.verifyCertificate(certId);
                    return {
                        response,
                        request: { method: 'GET', url: `/api/v1/certificates/${certId}/verify` },
                        assertions: [
                            Assert.statusEquals(response, 200, 'Should return 200 OK'),
                            Assert.isSuccessResponse(response, 'Should have success: true'),
                            Assert.hasNestedKey(response.data, 'certificate.valid', 'Should have certificate.valid field'),
                            Assert.equals(response.data?.certificate?.valid, true, 'Certificate should be valid'),
                        ],
                    };
                },
            },
            {
                id: 'int-get-stats',
                name: 'Fetch API statistics',
                description: 'Verify statistics endpoint returns data',
                async run(client) {
                    const response = await client.getStats('all');
                    return {
                        response,
                        request: { method: 'GET', url: '/api/v1/stats?period=all' },
                        assertions: [
                            Assert.statusEquals(response, 200, 'Should return 200 OK'),
                            Assert.isSuccessResponse(response, 'Should have success: true'),
                            Assert.hasKey(response.data, 'stats', 'Should have stats field'),
                            Assert.hasNestedKey(response.data, 'stats.total_requests', 'Should have total_requests in stats'),
                        ],
                    };
                },
            },
            {
                id: 'int-generate-female-certificate',
                name: 'Generate certificate for female recipient',
                description: 'Test gender-specific certificate generation',
                async run(client, context) {
                    const courseId = context.firstCourseId || 'course-test-101';
                    const response = await client.generateCertificate({
                        course_id: courseId,
                        recipient_name_ar: 'فاطمة محمد',
                        recipient_name_en: 'Fatima Mohammed',
                        gender: 'female',
                        output_format: 'pdf',
                    });
                    return {
                        response,
                        request: { method: 'POST', url: '/api/v1/certificates/generate' },
                        assertions: [
                            Assert.statusEquals(response, 200, 'Should return 200 OK'),
                            Assert.isSuccessResponse(response, 'Should have success: true'),
                            Assert.hasKey(response.data, 'certificate_id', 'Should return certificate_id'),
                        ],
                    };
                },
            },
            {
                id: 'int-generate-image-format',
                name: 'Generate certificate as image',
                description: 'Test image output format for certificate generation',
                async run(client, context) {
                    const courseId = context.firstCourseId || 'course-test-101';
                    const response = await client.generateCertificate({
                        course_id: courseId,
                        recipient_name_ar: 'اختبار صورة',
                        recipient_name_en: 'Image Test',
                        gender: 'male',
                        output_format: 'image',
                    });
                    return {
                        response,
                        request: { method: 'POST', url: '/api/v1/certificates/generate', body: { output_format: 'image' } },
                        assertions: [
                            Assert.statusEquals(response, 200, 'Should return 200 OK'),
                            Assert.isSuccessResponse(response, 'Should have success: true'),
                            Assert.hasNestedKey(response.data, 'image.url', 'Should return image URL'),
                        ],
                    };
                },
            },
            {
                id: 'int-stats-today',
                name: 'Statistics for today',
                description: 'Fetch today stats to confirm recent generation is reflected',
                async run(client) {
                    const response = await client.getStats('today');
                    return {
                        response,
                        request: { method: 'GET', url: '/api/v1/stats?period=today' },
                        assertions: [
                            Assert.statusEquals(response, 200, 'Should return 200 OK'),
                            Assert.isSuccessResponse(response, 'Should have success: true'),
                            Assert.hasKey(response.data, 'stats', 'Should have stats field'),
                        ],
                    };
                },
            },
        ],
    },

    // ============================
    // FEP PLATFORM WORKFLOW SUITE
    // ============================
    fep: {
        id: 'fep',
        name: 'FEP Platform Workflow',
        tests: [
            {
                id: 'fep-step1-health',
                name: 'Step 1: Check API Health',
                description: 'FEP platform checks if the certificate API is available before proceeding',
                async run(client) {
                    const response = await client.checkStatus();
                    return {
                        response,
                        request: { method: 'GET', url: '/api/v1/status' },
                        assertions: [
                            Assert.statusEquals(response, 200, 'API should be operational'),
                            Assert.equals(response.data?.status, 'operational', 'Status should be operational'),
                            Assert.hasKey(response.data, 'version', 'Should have API version'),
                        ],
                    };
                },
            },
            {
                id: 'fep-step2-courses',
                name: 'Step 2: Fetch Available Courses',
                description: 'FEP platform fetches the list of courses mapped to its API client',
                async run(client, context) {
                    const response = await client.listCourses();
                    const courses = response.data?.courses || [];
                    const firstCourse = Array.isArray(courses) && courses.length > 0 ? courses[0] : null;

                    return {
                        response,
                        request: { method: 'GET', url: '/api/v1/courses' },
                        assertions: [
                            Assert.statusEquals(response, 200, 'Should return 200'),
                            Assert.isSuccessResponse(response, 'Should return success'),
                            Assert.hasKey(response.data, 'courses', 'Should have courses array'),
                        ],
                        context: {
                            courses,
                            selectedCourseId: firstCourse
                                ? firstCourse.external_course_id
                                : null,
                            selectedCourseName: firstCourse
                                ? (firstCourse.course_name || firstCourse.course_name_en)
                                : null,
                        },
                    };
                },
            },
            {
                id: 'fep-step3-generate-male',
                name: 'Step 3: User completes course - Generate certificate (Male)',
                description: 'A male student finishes the course on FEP, platform generates his certificate',
                async run(client, context) {
                    const courseId = context.selectedCourseId || 'course-test-101';
                    const response = await client.generateCertificate({
                        course_id: courseId,
                        recipient_name_ar: 'عبدالله محمد الأحمد',
                        recipient_name_en: 'Abdullah Mohammed Al-Ahmad',
                        gender: 'male',
                        completion_date: new Date().toISOString().split('T')[0],
                        output_format: 'pdf',
                    });

                    const certId = response.data?.certificate_id;

                    return {
                        response,
                        request: { method: 'POST', url: '/api/v1/certificates/generate' },
                        assertions: [
                            Assert.statusEquals(response, 200, 'Should generate certificate successfully'),
                            Assert.isSuccessResponse(response, 'Should return success'),
                            Assert.hasKey(response.data, 'certificate_id', 'Should return certificate_id'),
                            Assert.hasNestedKey(response.data, 'pdf.url', 'Should return PDF download URL'),
                        ],
                        context: {
                            maleCertificateId: certId,
                            maleDownloadUrl: response.data?.pdf?.url,
                        },
                    };
                },
            },
            {
                id: 'fep-step4-generate-female',
                name: 'Step 4: User completes course - Generate certificate (Female)',
                description: 'A female student finishes the course on FEP, platform generates her certificate',
                async run(client, context) {
                    const courseId = context.selectedCourseId || 'course-test-101';
                    const response = await client.generateCertificate({
                        course_id: courseId,
                        recipient_name_ar: 'نورة سعد العتيبي',
                        recipient_name_en: 'Noura Saad Al-Otaibi',
                        gender: 'female',
                        completion_date: new Date().toISOString().split('T')[0],
                        output_format: 'pdf',
                    });

                    const certId = response.data?.certificate_id;

                    return {
                        response,
                        request: { method: 'POST', url: '/api/v1/certificates/generate' },
                        assertions: [
                            Assert.statusEquals(response, 200, 'Should generate certificate successfully'),
                            Assert.isSuccessResponse(response, 'Should return success'),
                            Assert.hasKey(response.data, 'certificate_id', 'Should return certificate_id'),
                        ],
                        context: {
                            femaleCertificateId: certId,
                        },
                    };
                },
            },
            {
                id: 'fep-step5-verify',
                name: 'Step 5: Verify Generated Certificate',
                description: 'FEP platform verifies the male student certificate is authentic',
                async run(client, context) {
                    const certId = context.maleCertificateId;
                    if (!certId) {
                        return {
                            response: { status: 0, data: {} },
                            assertions: [{
                                passed: false,
                                message: 'No certificate_id from Step 3 - generation may have failed',
                                expected: 'certificate_id',
                                actual: 'none',
                            }],
                        };
                    }
                    const response = await client.verifyCertificate(certId);
                    return {
                        response,
                        request: { method: 'GET', url: `/api/v1/certificates/${certId}/verify` },
                        assertions: [
                            Assert.statusEquals(response, 200, 'Should return 200'),
                            Assert.isSuccessResponse(response, 'Should return success'),
                            Assert.equals(response.data?.certificate?.valid, true, 'Certificate should be valid'),
                        ],
                    };
                },
            },
            {
                id: 'fep-step6-stats',
                name: 'Step 6: Check Updated Statistics',
                description: 'FEP platform checks its stats to confirm new certificates are counted',
                async run(client) {
                    const response = await client.getStats('today');
                    return {
                        response,
                        request: { method: 'GET', url: '/api/v1/stats?period=today' },
                        assertions: [
                            Assert.statusEquals(response, 200, 'Should return 200'),
                            Assert.isSuccessResponse(response, 'Should return success'),
                            Assert.hasNestedKey(response.data, 'stats.today_certificates', 'Should have today_certificates stat'),
                        ],
                    };
                },
            },
            {
                id: 'fep-step7-bilingual',
                name: 'Step 7: Generate Bilingual Certificate',
                description: 'Generate certificate with both Arabic and English names',
                async run(client, context) {
                    const courseId = context.selectedCourseId || 'course-test-101';
                    const response = await client.generateCertificate({
                        course_id: courseId,
                        recipient_name_ar: 'خالد عبدالرحمن',
                        recipient_name_en: 'Khalid Abdulrahman',
                        gender: 'male',
                        completion_date: new Date().toISOString().split('T')[0],
                        output_format: 'pdf',
                    });
                    return {
                        response,
                        request: { method: 'POST', url: '/api/v1/certificates/generate' },
                        assertions: [
                            Assert.statusEquals(response, 200, 'Should generate bilingual certificate'),
                            Assert.isSuccessResponse(response, 'Should return success'),
                            Assert.hasKey(response.data, 'certificate_id', 'Should return certificate_id'),
                        ],
                    };
                },
            },
            {
                id: 'fep-step8-image-format',
                name: 'Step 8: Generate Image Format Certificate',
                description: 'Generate certificate as image instead of PDF',
                async run(client, context) {
                    const courseId = context.selectedCourseId || 'course-test-101';
                    const response = await client.generateCertificate({
                        course_id: courseId,
                        recipient_name_ar: 'سارة أحمد',
                        recipient_name_en: 'Sara Ahmad',
                        gender: 'female',
                        output_format: 'image',
                    });
                    return {
                        response,
                        request: { method: 'POST', url: '/api/v1/certificates/generate', body: { output_format: 'image' } },
                        assertions: [
                            Assert.statusEquals(response, 200, 'Should generate image certificate'),
                            Assert.isSuccessResponse(response, 'Should return success'),
                            Assert.hasNestedKey(response.data, 'image.url', 'Should return image download URL'),
                        ],
                    };
                },
            },
        ],
    },
};

// Export globally
window.TestSuites = TestSuites;
