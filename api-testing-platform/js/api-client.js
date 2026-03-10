/**
 * Certificate API Client
 * Handles all HTTP communication with the Certificate Platform API
 */
class ApiClient {
    constructor() {
        this.baseUrl = '';
        this.apiKey = '';
        this.apiSecret = '';
        this.timeout = 30000;
        this.retries = 0;
        this.requestHistory = [];
    }

    /**
     * Configure the client with connection settings
     */
    configure(config) {
        this.baseUrl = (config.baseUrl || '').replace(/\/+$/, '');
        this.apiKey = config.apiKey || '';
        this.apiSecret = config.apiSecret || '';
        this.timeout = config.timeout || 30000;
        this.retries = config.retries || 0;
    }

    /**
     * Get current configuration
     */
    getConfig() {
        return {
            baseUrl: this.baseUrl,
            apiKey: this.apiKey,
            apiSecret: this.apiSecret,
            timeout: this.timeout,
            retries: this.retries,
        };
    }

    /**
     * Check if the client is configured
     */
    isConfigured() {
        return !!this.baseUrl;
    }

    /**
     * Check if authentication credentials are set
     */
    hasCredentials() {
        return !!(this.apiKey && this.apiSecret);
    }

    /**
     * Build the default headers for authenticated requests
     */
    _buildHeaders(authenticated = true) {
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        };
        if (authenticated && this.apiKey && this.apiSecret) {
            headers['X-API-Key'] = this.apiKey;
            headers['X-API-Secret'] = this.apiSecret;
        }
        return headers;
    }

    /**
     * Execute an HTTP request with timing, retries, and history logging
     */
    async request(method, endpoint, options = {}) {
        const url = this.baseUrl + endpoint;
        const authenticated = options.authenticated !== false;
        const headers = options.headers || this._buildHeaders(authenticated);
        const startTime = performance.now();

        const fetchOptions = {
            method: method.toUpperCase(),
            headers,
        };

        if (options.body && method.toUpperCase() !== 'GET') {
            fetchOptions.body = JSON.stringify(options.body);
        }

        let lastError = null;
        const maxAttempts = 1 + (this.retries || 0);

        for (let attempt = 1; attempt <= maxAttempts; attempt++) {
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), this.timeout);
                fetchOptions.signal = controller.signal;

                const response = await fetch(url, fetchOptions);
                clearTimeout(timeoutId);

                const duration = Math.round(performance.now() - startTime);
                let responseData;
                const contentType = response.headers.get('content-type') || '';

                if (contentType.includes('application/json')) {
                    responseData = await response.json();
                } else {
                    const text = await response.text();
                    try {
                        responseData = JSON.parse(text);
                    } catch {
                        responseData = { raw: text };
                    }
                }

                const result = {
                    success: response.ok,
                    status: response.status,
                    statusText: response.statusText,
                    headers: Object.fromEntries(response.headers.entries()),
                    data: responseData,
                    duration,
                    url,
                    method: method.toUpperCase(),
                    requestBody: options.body || null,
                    requestHeaders: { ...headers },
                    timestamp: new Date().toISOString(),
                    attempt,
                };

                this._addToHistory(result);
                return result;
            } catch (err) {
                lastError = err;
                if (attempt < maxAttempts) {
                    await this._sleep(Math.pow(2, attempt) * 1000);
                }
            }
        }

        const duration = Math.round(performance.now() - startTime);
        const errorResult = {
            success: false,
            status: 0,
            statusText: 'Network Error',
            headers: {},
            data: { error: { message: lastError.message || 'Request failed' } },
            duration,
            url,
            method: method.toUpperCase(),
            requestBody: options.body || null,
            requestHeaders: { ...headers },
            timestamp: new Date().toISOString(),
            attempt: maxAttempts,
            networkError: true,
        };

        this._addToHistory(errorResult);
        return errorResult;
    }

    /**
     * Add a request/response to history
     */
    _addToHistory(result) {
        this.requestHistory.unshift(result);
        if (this.requestHistory.length > 200) {
            this.requestHistory.pop();
        }
    }

    /**
     * Sleep utility for retries
     */
    _sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    // ----- API Endpoint Methods -----

    /**
     * GET /api/v1/status - Check API health (no auth required)
     */
    async checkStatus() {
        return this.request('GET', '/api/v1/status', { authenticated: false });
    }

    /**
     * POST /api/v1/certificates/generate - Generate a certificate
     */
    async generateCertificate(data) {
        return this.request('POST', '/api/v1/certificates/generate', { body: data });
    }

    /**
     * GET /api/v1/certificates/{id}/verify - Verify a certificate
     */
    async verifyCertificate(certificateId) {
        return this.request('GET', `/api/v1/certificates/${encodeURIComponent(certificateId)}/verify`);
    }

    /**
     * GET /api/v1/courses - List mapped courses
     */
    async listCourses(params = {}) {
        const query = new URLSearchParams();
        if (params.active !== undefined && params.active !== '') query.set('active', params.active);
        if (params.page) query.set('page', params.page);
        if (params.per_page) query.set('per_page', params.per_page);
        const qs = query.toString();
        return this.request('GET', '/api/v1/courses' + (qs ? '?' + qs : ''));
    }

    /**
     * GET /api/v1/stats - Get client statistics
     */
    async getStats(period = 'all') {
        return this.request('GET', `/api/v1/stats?period=${encodeURIComponent(period)}`);
    }

    // ----- Custom Header Requests (for auth testing) -----

    /**
     * Make a request with custom/overridden headers (useful for testing invalid auth)
     */
    async requestWithCustomHeaders(method, endpoint, customHeaders, body = null) {
        const options = {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                ...customHeaders,
            },
            authenticated: false, // we handle headers manually
        };
        if (body) {
            options.body = body;
        }
        return this.request(method, endpoint, options);
    }

    /**
     * Clear request history
     */
    clearHistory() {
        this.requestHistory = [];
    }

    /**
     * Get request history
     */
    getHistory() {
        return this.requestHistory;
    }

    /**
     * Export history as JSON string
     */
    exportHistory() {
        return JSON.stringify(this.requestHistory, null, 2);
    }
}

// Singleton instance
window.apiClient = new ApiClient();
