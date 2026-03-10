/**
 * Main Application Controller
 * Handles UI interactions, navigation, form submissions, and orchestrates the testing platform
 */
const App = {
    testRunner: null,
    currentSection: 'dashboard',

    // ============================
    // INITIALIZATION
    // ============================
    init() {
        this.testRunner = new TestRunner();
        this._setupNavigation();
        this._setupForms();
        this._setupTestRunner();
        this._loadConfig();
        this._renderAllTestLists();
        this._setupSidebarToggle();
    },

    // ============================
    // NAVIGATION
    // ============================
    _setupNavigation() {
        document.querySelectorAll('.nav-item[data-section]').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                this.navigateTo(item.dataset.section);
            });
        });
    },

    navigateTo(sectionId) {
        // Update nav
        document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
        const navItem = document.querySelector(`.nav-item[data-section="${sectionId}"]`);
        if (navItem) navItem.classList.add('active');

        // Update sections
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
        const section = document.getElementById(`section-${sectionId}`);
        if (section) section.classList.add('active');

        // Update topbar title
        const titles = {
            'dashboard': 'Dashboard',
            'config': 'Configuration',
            'endpoint-status': 'Status Check',
            'endpoint-generate': 'Generate Certificate',
            'endpoint-verify': 'Verify Certificate',
            'endpoint-courses': 'List Courses',
            'endpoint-stats': 'Statistics',
            'suite-auth': 'Authentication Tests',
            'suite-validation': 'Validation Tests',
            'suite-integration': 'Integration Tests',
            'suite-fep': 'FEP Workflow',
            'suite-all': 'Run All Tests',
            'history': 'Request History',
            'webhook-tester': 'Webhook Tester',
        };
        document.getElementById('topbarTitle').textContent = titles[sectionId] || sectionId;
        this.currentSection = sectionId;

        // Close sidebar on mobile
        document.getElementById('sidebar').classList.remove('open');
    },

    _setupSidebarToggle() {
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('open');
        });
    },

    // ============================
    // CONFIGURATION
    // ============================
    _setupForms() {
        // Config form
        document.getElementById('configForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this._saveConfig();
        });

        // Generate form
        document.getElementById('generateForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.executeEndpoint('generate');
        });

        // Verify form
        document.getElementById('verifyForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.executeEndpoint('verify');
        });

        // Courses form
        document.getElementById('coursesForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.executeEndpoint('courses');
        });

        // Stats form
        document.getElementById('statsForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.executeEndpoint('stats');
        });

        // Webhook form
        document.getElementById('webhookForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.verifyWebhookSignature();
        });
    },

    _saveConfig() {
        const config = {
            baseUrl: document.getElementById('config-base-url').value.trim(),
            apiKey: document.getElementById('config-api-key').value.trim(),
            apiSecret: document.getElementById('config-api-secret').value.trim(),
            timeout: parseInt(document.getElementById('config-timeout').value) || 30000,
            retries: parseInt(document.getElementById('config-retries').value) || 0,
        };

        if (!config.baseUrl) {
            this.showToast('Please enter a Base URL', 'error');
            return;
        }

        window.apiClient.configure(config);
        localStorage.setItem('api-tester-config', JSON.stringify(config));
        this.showToast('Configuration saved', 'success');
        this._updateConnectionStatus('disconnected', 'Configured');
    },

    _loadConfig() {
        try {
            const saved = localStorage.getItem('api-tester-config');
            if (saved) {
                const config = JSON.parse(saved);
                document.getElementById('config-base-url').value = config.baseUrl || '';
                document.getElementById('config-api-key').value = config.apiKey || '';
                document.getElementById('config-api-secret').value = config.apiSecret || '';
                document.getElementById('config-timeout').value = config.timeout || 30000;
                document.getElementById('config-retries').value = config.retries || 0;
                window.apiClient.configure(config);
                if (config.baseUrl) {
                    this._updateConnectionStatus('disconnected', 'Configured');
                }
            }
        } catch {
            // ignore parse errors
        }
    },

    clearConfig() {
        localStorage.removeItem('api-tester-config');
        document.getElementById('config-base-url').value = 'http://localhost:8000';
        document.getElementById('config-api-key').value = '';
        document.getElementById('config-api-secret').value = '';
        document.getElementById('config-timeout').value = 30000;
        document.getElementById('config-retries').value = 0;
        window.apiClient.configure({ baseUrl: '', apiKey: '', apiSecret: '' });
        this._updateConnectionStatus('disconnected', 'Not Connected');
        this.showToast('Configuration cleared', 'info');
    },

    toggleSecretVisibility() {
        const input = document.getElementById('config-api-secret');
        const btn = input.nextElementSibling;
        if (input.type === 'password') {
            input.type = 'text';
            btn.textContent = 'Hide';
        } else {
            input.type = 'password';
            btn.textContent = 'Show';
        }
    },

    async testConnection() {
        if (!window.apiClient.isConfigured()) {
            this.showToast('Please configure the Base URL first', 'warning');
            return;
        }

        this._saveConfig();
        const resultCard = document.getElementById('connection-test-result');
        const resultBody = document.getElementById('connection-test-body');
        resultCard.style.display = 'block';
        resultBody.innerHTML = '<div class="spinner"></div> Testing connection...';

        const response = await window.apiClient.checkStatus();

        if (response.success) {
            this._updateConnectionStatus('connected', `Connected (${response.duration}ms)`);
            resultBody.innerHTML = `
                <div style="color: var(--color-success);">
                    <strong>Connection successful!</strong>
                </div>
                <p class="mt-1">Status: ${response.data?.data?.status || 'OK'}</p>
                <p>Response time: ${response.duration}ms</p>
                <pre class="response-body mt-1">${this._formatJSON(response.data)}</pre>
            `;
            this.showToast('Connection successful', 'success');
        } else {
            this._updateConnectionStatus('error', 'Connection Failed');
            resultBody.innerHTML = `
                <div style="color: var(--color-danger);">
                    <strong>Connection failed!</strong>
                </div>
                <p class="mt-1">Status: HTTP ${response.status} ${response.statusText}</p>
                <p>Error: ${response.data?.error?.message || response.statusText || 'Network error'}</p>
                ${response.networkError ? '<p class="text-warning mt-1">This may be a CORS or network connectivity issue.</p>' : ''}
            `;
            this.showToast('Connection failed', 'error');
        }
    },

    _updateConnectionStatus(state, text) {
        const el = document.getElementById('connectionStatus');
        el.innerHTML = `<span class="status-dot ${state}"></span> ${text}`;
    },

    // ============================
    // ENDPOINT EXECUTION
    // ============================
    async executeEndpoint(endpoint) {
        if (!window.apiClient.isConfigured()) {
            this.showToast('Please configure API connection first', 'warning');
            this.navigateTo('config');
            return;
        }

        let response;

        switch (endpoint) {
            case 'status':
                response = await window.apiClient.checkStatus();
                break;

            case 'generate': {
                if (!window.apiClient.hasCredentials()) {
                    this.showToast('API credentials required for this endpoint', 'warning');
                    return;
                }
                const body = {
                    course_id: document.getElementById('gen-course-id').value.trim(),
                    recipient_name_ar: document.getElementById('gen-recipient-name-ar').value.trim(),
                    recipient_name_en: document.getElementById('gen-recipient-name-en').value.trim(),
                };
                const gender = document.getElementById('gen-gender').value;
                const date = document.getElementById('gen-completion-date').value;
                const extId = document.getElementById('gen-external-id').value.trim();
                const format = document.getElementById('gen-output-format').value;
                const callback = document.getElementById('gen-callback-url').value.trim();
                const customFields = document.getElementById('gen-custom-fields').value.trim();

                if (gender) body.gender = gender;
                if (date) body.completion_date = date;
                if (extId) body.recipient_id = extId;
                if (format) body.output_format = format;
                if (callback) body.callback_url = callback;
                if (customFields) {
                    try { body.custom_fields = JSON.parse(customFields); } catch { /* ignore */ }
                }

                if (!body.course_id || !body.recipient_name_ar || !body.recipient_name_en) {
                    this.showToast('Course ID, Arabic Name, and English Name are all required', 'warning');
                    return;
                }

                response = await window.apiClient.generateCertificate(body);
                break;
            }

            case 'verify': {
                if (!window.apiClient.hasCredentials()) {
                    this.showToast('API credentials required for this endpoint', 'warning');
                    return;
                }
                const certId = document.getElementById('verify-cert-id').value.trim();
                if (!certId) {
                    this.showToast('Certificate ID is required', 'warning');
                    return;
                }
                response = await window.apiClient.verifyCertificate(certId);
                break;
            }

            case 'courses': {
                if (!window.apiClient.hasCredentials()) {
                    this.showToast('API credentials required for this endpoint', 'warning');
                    return;
                }
                const params = {};
                const active = document.getElementById('courses-active').value;
                const page = document.getElementById('courses-page').value;
                const perPage = document.getElementById('courses-per-page').value;
                if (active) params.active = active;
                if (page) params.page = page;
                if (perPage) params.per_page = perPage;
                response = await window.apiClient.listCourses(params);
                break;
            }

            case 'stats': {
                if (!window.apiClient.hasCredentials()) {
                    this.showToast('API credentials required for this endpoint', 'warning');
                    return;
                }
                const period = document.getElementById('stats-period').value;
                response = await window.apiClient.getStats(period);
                break;
            }
        }

        if (response) {
            this._displayResponse(endpoint, response);
            this._refreshHistory();
        }
    },

    _displayResponse(endpoint, response) {
        const card = document.getElementById(`response-${endpoint}`);
        const meta = document.getElementById(`response-meta-${endpoint}`);
        const body = document.getElementById(`response-body-${endpoint}`);

        card.style.display = 'block';

        const statusColor = response.success ? 'var(--color-success)' : 'var(--color-danger)';
        meta.innerHTML = `<span style="color:${statusColor}">HTTP ${response.status}</span> | ${response.duration}ms`;
        body.innerHTML = this._syntaxHighlight(response.data);
    },

    fillSampleGenerateData() {
        document.getElementById('gen-course-id').value = 'course-python-101';
        document.getElementById('gen-recipient-name-ar').value = 'محمد أحمد علي';
        document.getElementById('gen-recipient-name-en').value = 'Mohammed Ahmed Ali';
        document.getElementById('gen-gender').value = 'male';
        document.getElementById('gen-completion-date').value = new Date().toISOString().split('T')[0];
        document.getElementById('gen-external-id').value = 'user-12345';
        document.getElementById('gen-output-format').value = 'pdf';
    },

    // ============================
    // TEST SUITE EXECUTION
    // ============================
    _setupTestRunner() {
        this.testRunner.on('testStart', (data) => {
            this._updateTestItemUI(data.suiteId, data.testIndex, 'running');
        });

        this.testRunner.on('testEnd', (data) => {
            this._updateTestItemUI(data.suiteId, data.testIndex, data.test.status, data.test);
            this._updateDashboardStats();
            this._refreshHistory();
        });

        this.testRunner.on('suiteEnd', (data) => {
            const summary = data.summary;
            const message = `Suite complete: ${summary.passed}/${summary.total} passed`;
            if (summary.failed > 0) {
                this.showToast(message, 'warning');
            } else {
                this.showToast(message, 'success');
            }
        });
    },

    _renderAllTestLists() {
        Object.keys(TestSuites).forEach(suiteId => {
            this._renderTestList(suiteId, TestSuites[suiteId].tests);
        });
    },

    _renderTestList(suiteId, tests) {
        const container = document.getElementById(`test-list-${suiteId}`);
        if (!container) return;

        container.innerHTML = tests.map((test, index) => `
            <div class="test-item pending" id="test-${suiteId}-${index}" onclick="App.toggleTestDetail('${suiteId}', ${index})">
                <div class="test-status-icon">-</div>
                <div class="test-info">
                    <div class="test-name">${test.name}</div>
                    <div class="test-description">${test.description || ''}</div>
                    <div class="test-detail" id="test-detail-${suiteId}-${index}"></div>
                </div>
                <div class="test-time" id="test-time-${suiteId}-${index}"></div>
            </div>
        `).join('');
    },

    _updateTestItemUI(suiteId, index, status, testResult) {
        // Update in the suite-specific list
        this._updateSingleTestItem(suiteId, index, status, testResult);

        // Also update in the "all" list if visible
        const allContainer = document.getElementById('test-list-all');
        if (allContainer) {
            const allItem = allContainer.querySelector(`#test-all-${suiteId}-${index}`);
            if (allItem) {
                this._applyStylingToItem(allItem, suiteId, index, status, testResult, 'all');
            }
        }
    },

    _updateSingleTestItem(suiteId, index, status, testResult) {
        const item = document.getElementById(`test-${suiteId}-${index}`);
        if (!item) return;
        this._applyStylingToItem(item, suiteId, index, status, testResult, suiteId);
    },

    _applyStylingToItem(item, suiteId, index, status, testResult, prefix) {
        item.className = `test-item ${status}`;

        const icon = item.querySelector('.test-status-icon');
        const timeEl = item.querySelector('.test-time') || document.getElementById(`test-time-${prefix}-${index}`);

        const icons = {
            pending: '-',
            running: '...',
            passed: '\u2713',
            failed: '\u2717',
            skipped: '!',
        };
        if (icon) icon.textContent = icons[status] || '-';

        if (testResult && timeEl) {
            timeEl.textContent = `${testResult.duration}ms`;
        }

        // Render detail panel
        if (testResult && (status === 'passed' || status === 'failed')) {
            const detailEl = item.querySelector('.test-detail');
            if (detailEl) {
                detailEl.innerHTML = this._renderTestDetail(testResult);
            }
        }
    },

    _renderTestDetail(testResult) {
        let html = '';

        // Assertions
        if (testResult.assertions && testResult.assertions.length > 0) {
            html += '<div class="test-detail-section"><h4>Assertions</h4>';
            html += testResult.assertions.map(a => `
                <div style="margin-bottom:0.35rem;font-size:0.82rem;">
                    <span style="color:${a.passed ? 'var(--color-success)' : 'var(--color-danger)'}">
                        ${a.passed ? '\u2713' : '\u2717'}
                    </span>
                    ${this._escapeHtml(a.message)}
                    ${!a.passed ? `<br><span style="color:var(--color-text-dim);font-size:0.78rem;">Expected: ${this._escapeHtml(String(a.expected))} | Actual: ${this._escapeHtml(String(a.actual))}</span>` : ''}
                </div>
            `).join('');
            html += '</div>';
        }

        // Error
        if (testResult.error) {
            html += `<div class="test-error-message">${this._escapeHtml(testResult.error)}</div>`;
        }

        // Response
        if (testResult.response && testResult.response.data) {
            html += '<div class="test-detail-section"><h4>Response (HTTP ' + (testResult.response.status || '?') + ')</h4>';
            html += `<pre>${this._syntaxHighlight(testResult.response.data)}</pre>`;
            html += '</div>';
        }

        return html;
    },

    toggleTestDetail(suiteId, index) {
        const item = document.getElementById(`test-${suiteId}-${index}`);
        if (item) item.classList.toggle('expanded');

        // Also toggle in "all" list
        const allItem = document.querySelector(`#test-all-${suiteId}-${index}`);
        if (allItem) allItem.classList.toggle('expanded');
    },

    async runTestSuite(suiteId) {
        if (this.testRunner.isRunning) {
            this.showToast('Tests are already running', 'warning');
            return;
        }

        if (!window.apiClient.isConfigured()) {
            this.showToast('Please configure API connection first', 'warning');
            this.navigateTo('config');
            return;
        }

        const suite = TestSuites[suiteId];
        if (!suite) return;

        // Reset the test list UI
        this._renderTestList(suiteId, suite.tests);
        this.testRunner.clearSuiteResults(suiteId);

        this.showToast(`Running ${suite.name}...`, 'info');
        await this.testRunner.runSuite(suiteId, suite.tests);
    },

    async runAllSuites() {
        if (this.testRunner.isRunning) {
            this.showToast('Tests are already running', 'warning');
            return;
        }

        if (!window.apiClient.isConfigured()) {
            this.showToast('Please configure API connection first', 'warning');
            this.navigateTo('config');
            return;
        }

        this.testRunner.clearResults();

        const allContainer = document.getElementById('test-list-all');
        const progressContainer = document.getElementById('all-tests-progress');
        const summaryContainer = document.getElementById('all-tests-summary');
        const progressFill = document.getElementById('all-tests-progress-fill');
        const progressText = document.getElementById('all-tests-progress-text');

        progressContainer.style.display = 'flex';
        summaryContainer.style.display = 'none';
        progressFill.style.width = '0%';
        progressText.textContent = '0%';

        // Build the combined test list in "all" view
        const suiteOrder = ['auth', 'validation', 'integration', 'fep'];
        let allHtml = '';
        suiteOrder.forEach(suiteId => {
            const suite = TestSuites[suiteId];
            if (!suite) return;
            allHtml += `<div class="suite-header-row">${suite.name}</div>`;
            allHtml += suite.tests.map((test, index) => `
                <div class="test-item pending" id="test-all-${suiteId}-${index}" onclick="App.toggleTestDetail('all-${suiteId}', ${index})">
                    <div class="test-status-icon">-</div>
                    <div class="test-info">
                        <div class="test-name">${test.name}</div>
                        <div class="test-description">${test.description || ''}</div>
                        <div class="test-detail"></div>
                    </div>
                    <div class="test-time"></div>
                </div>
            `).join('');
        });
        allContainer.innerHTML = allHtml;

        // Listen for progress
        const progressHandler = (data) => {
            progressFill.style.width = data.percent + '%';
            progressText.textContent = `${data.completed}/${data.total} (${data.percent}%)`;
        };
        this.testRunner.on('progress', progressHandler);

        // Override testEnd to update "all" view
        const allTestEndHandler = (data) => {
            const suiteId = data.suiteId;
            const index = data.testIndex;
            const allItem = allContainer.querySelector(`#test-all-${suiteId}-${index}`);
            if (allItem) {
                this._applyStylingToItem(allItem, suiteId, index, data.test.status, data.test, `all-${suiteId}`);
            }
        };
        this.testRunner.on('testEnd', allTestEndHandler);

        this.showToast('Running all test suites...', 'info');

        const suites = suiteOrder
            .filter(id => TestSuites[id])
            .map(id => ({ id, tests: TestSuites[id].tests }));

        await this.testRunner.runAllSuites(suites);

        // Cleanup listeners
        this.testRunner.listeners['progress'] = (this.testRunner.listeners['progress'] || []).filter(cb => cb !== progressHandler);
        this.testRunner.listeners['testEnd'] = (this.testRunner.listeners['testEnd'] || []).filter(cb => cb !== allTestEndHandler);

        // Show summary
        const stats = this.testRunner.getGlobalStats();
        summaryContainer.style.display = 'block';
        document.getElementById('all-total').textContent = stats.totalTests;
        document.getElementById('all-passed').textContent = stats.passed;
        document.getElementById('all-failed').textContent = stats.failed;
        document.getElementById('all-skipped').textContent = stats.skipped;

        this._updateDashboardStats();

        if (stats.failed > 0) {
            this.showToast(`All suites complete: ${stats.passed}/${stats.totalTests} passed, ${stats.failed} failed`, 'warning');
        } else {
            this.showToast(`All suites complete: ${stats.passed}/${stats.totalTests} passed`, 'success');
        }
    },

    clearAllResults() {
        this.testRunner.clearResults();
        document.getElementById('test-list-all').innerHTML = '';
        document.getElementById('all-tests-progress').style.display = 'none';
        document.getElementById('all-tests-summary').style.display = 'none';
        this._renderAllTestLists();
        this._updateDashboardStats();
        this.showToast('Results cleared', 'info');
    },

    // ============================
    // QUICK HEALTH CHECK
    // ============================
    async runQuickHealthCheck() {
        if (!window.apiClient.isConfigured()) {
            this.showToast('Please configure API connection first', 'warning');
            this.navigateTo('config');
            return;
        }

        this.showToast('Running health check...', 'info');
        const response = await window.apiClient.checkStatus();

        if (response.success) {
            this._updateConnectionStatus('connected', `Connected (${response.duration}ms)`);
            this.showToast('API is operational', 'success');
        } else {
            this._updateConnectionStatus('error', 'Health check failed');
            this.showToast('API health check failed', 'error');
        }

        this._refreshHistory();
        this._updateDashboardStats();
    },

    // ============================
    // DASHBOARD STATS
    // ============================
    _updateDashboardStats() {
        const stats = this.testRunner.getGlobalStats();
        document.getElementById('stat-total-tests').textContent = stats.totalTests;
        document.getElementById('stat-passed').textContent = stats.passed;
        document.getElementById('stat-failed').textContent = stats.failed;

        const avgTime = stats.totalTests > 0
            ? Math.round(stats.totalTime / stats.totalTests)
            : 0;
        document.getElementById('stat-avg-time').textContent = avgTime + 'ms';

        // Update recent results on dashboard
        this._updateDashboardRecentResults();
    },

    _updateDashboardRecentResults() {
        const container = document.getElementById('dashboard-recent-results');
        const allResults = Object.entries(this.testRunner.results).flatMap(
            ([suiteId, results]) => results.map(r => ({ ...r, suiteId }))
        );

        if (allResults.length === 0) {
            container.innerHTML = '<p class="text-muted">No tests run yet.</p>';
            return;
        }

        const recent = allResults.slice(-10).reverse();
        container.innerHTML = recent.map(r => `
            <div style="display:flex;align-items:center;gap:0.5rem;padding:0.35rem 0;border-bottom:1px solid var(--color-surface-border);font-size:0.85rem;">
                <span style="color:${r.status === 'passed' ? 'var(--color-success)' : 'var(--color-danger)'}">
                    ${r.status === 'passed' ? '\u2713' : '\u2717'}
                </span>
                <span style="flex:1">${this._escapeHtml(r.name)}</span>
                <span style="color:var(--color-text-dim);font-family:var(--font-mono);font-size:0.78rem">${r.duration}ms</span>
            </div>
        `).join('');
    },

    // ============================
    // REQUEST HISTORY
    // ============================
    _refreshHistory() {
        const container = document.getElementById('history-list');
        const history = window.apiClient.getHistory();

        if (history.length === 0) {
            container.innerHTML = '<p class="text-muted">No requests recorded yet.</p>';
            return;
        }

        container.innerHTML = history.map((item, index) => {
            const methodClass = `method-${item.method.toLowerCase()}`;
            const statusClass = item.status >= 200 && item.status < 300 ? 'status-2xx'
                : item.status >= 400 && item.status < 500 ? 'status-4xx'
                : item.status >= 500 ? 'status-5xx' : 'status-0xx';

            return `
                <div class="history-item" id="history-item-${index}" onclick="App.toggleHistoryItem(${index})">
                    <div class="history-item-header">
                        <span class="history-item-method"><span class="method-badge ${methodClass}">${item.method}</span></span>
                        <span class="history-item-url">${this._escapeHtml(item.url)}</span>
                        <span class="history-item-status ${statusClass}">${item.status || 'ERR'}</span>
                        <span class="history-item-time">${item.duration}ms</span>
                    </div>
                    <div class="history-item-detail">
                        ${item.requestBody ? `
                            <div class="history-detail-label">Request Body</div>
                            <pre>${this._syntaxHighlight(item.requestBody)}</pre>
                        ` : ''}
                        <div class="history-detail-label">Response</div>
                        <pre>${this._syntaxHighlight(item.data)}</pre>
                        <div class="history-detail-label" style="margin-top:0.5rem;">Timestamp</div>
                        <div style="font-size:0.8rem;color:var(--color-text-dim)">${item.timestamp}</div>
                    </div>
                </div>
            `;
        }).join('');
    },

    toggleHistoryItem(index) {
        const item = document.getElementById(`history-item-${index}`);
        if (item) item.classList.toggle('expanded');
    },

    exportHistory() {
        const data = window.apiClient.exportHistory();
        const blob = new Blob([data], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `api-test-history-${new Date().toISOString().replace(/[:.]/g, '-')}.json`;
        a.click();
        URL.revokeObjectURL(url);
        this.showToast('History exported', 'success');
    },

    clearHistory() {
        window.apiClient.clearHistory();
        this._refreshHistory();
        this.showToast('History cleared', 'info');
    },

    // ============================
    // WEBHOOK TESTER
    // ============================
    fillSampleWebhook() {
        document.getElementById('webhook-payload').value = JSON.stringify({
            event: 'certificate.generated',
            timestamp: new Date().toISOString(),
            data: {
                certificate_id: 'cert_abc123xyz',
                external_id: 'user-12345',
                recipient_name: '\u0645\u062d\u0645\u062f \u0623\u062d\u0645\u062f \u0639\u0644\u064a',
                course_id: 'course-python-101',
                course_name: '\u062f\u0648\u0631\u0629 \u0628\u0627\u064a\u062b\u0648\u0646 \u0644\u0644\u0645\u0628\u062a\u062f\u0626\u064a\u0646',
                download_url: 'https://certificates.example.com/download?signature=abc123',
                verification_url: 'https://certificates.example.com/verify/cert_abc123xyz',
            },
        }, null, 2);
    },

    async generateWebhookSignature() {
        const secret = document.getElementById('webhook-secret').value.trim();
        const payload = document.getElementById('webhook-payload').value.trim();

        if (!secret || !payload) {
            this.showToast('Both secret and payload are required', 'warning');
            return;
        }

        try {
            const encoder = new TextEncoder();
            const keyData = encoder.encode(secret);
            const data = encoder.encode(payload);

            const key = await crypto.subtle.importKey(
                'raw', keyData, { name: 'HMAC', hash: 'SHA-256' }, false, ['sign']
            );
            const signature = await crypto.subtle.sign('HMAC', key, data);
            const hex = Array.from(new Uint8Array(signature))
                .map(b => b.toString(16).padStart(2, '0')).join('');

            document.getElementById('webhook-signature').value = `sha256=${hex}`;
            this.showToast('Signature generated', 'success');
        } catch (err) {
            this.showToast('Error generating signature: ' + err.message, 'error');
        }
    },

    async verifyWebhookSignature() {
        const secret = document.getElementById('webhook-secret').value.trim();
        const payload = document.getElementById('webhook-payload').value.trim();
        const signature = document.getElementById('webhook-signature').value.trim();

        if (!secret || !payload || !signature) {
            this.showToast('Secret, payload, and signature are all required', 'warning');
            return;
        }

        const resultCard = document.getElementById('webhook-result');
        const resultBody = document.getElementById('webhook-result-body');
        resultCard.style.display = 'block';

        try {
            const encoder = new TextEncoder();
            const keyData = encoder.encode(secret);
            const data = encoder.encode(payload);

            const key = await crypto.subtle.importKey(
                'raw', keyData, { name: 'HMAC', hash: 'SHA-256' }, false, ['sign']
            );
            const sigBuf = await crypto.subtle.sign('HMAC', key, data);
            const expectedHex = Array.from(new Uint8Array(sigBuf))
                .map(b => b.toString(16).padStart(2, '0')).join('');
            const expectedSignature = `sha256=${expectedHex}`;

            const isValid = signature === expectedSignature;

            resultBody.innerHTML = `
                <div class="${isValid ? 'webhook-valid' : 'webhook-invalid'}">
                    <strong>${isValid ? 'Signature is VALID' : 'Signature is INVALID'}</strong>
                    ${!isValid ? `
                        <p style="margin-top:0.5rem;font-size:0.85rem;">
                            Expected: <code>${expectedSignature}</code><br>
                            Received: <code>${this._escapeHtml(signature)}</code>
                        </p>
                    ` : '<p style="margin-top:0.5rem;font-size:0.85rem;">The webhook payload signature matches.</p>'}
                </div>
            `;
        } catch (err) {
            resultBody.innerHTML = `<div class="webhook-invalid"><strong>Error:</strong> ${this._escapeHtml(err.message)}</div>`;
        }
    },

    // ============================
    // TOAST NOTIFICATIONS
    // ============================
    showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(1rem)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    },

    // ============================
    // UTILITIES
    // ============================
    _formatJSON(obj) {
        try {
            return JSON.stringify(obj, null, 2);
        } catch {
            return String(obj);
        }
    },

    _syntaxHighlight(obj) {
        let json;
        try {
            json = typeof obj === 'string' ? obj : JSON.stringify(obj, null, 2);
        } catch {
            return this._escapeHtml(String(obj));
        }

        return json.replace(
            /("(\\u[\da-fA-F]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+-]?\d+)?)/g,
            (match) => {
                let cls = 'json-number';
                if (/^"/.test(match)) {
                    if (/:$/.test(match)) {
                        cls = 'json-key';
                    } else {
                        cls = 'json-string';
                    }
                } else if (/true|false/.test(match)) {
                    cls = 'json-boolean';
                } else if (/null/.test(match)) {
                    cls = 'json-null';
                }
                return `<span class="${cls}">${match}</span>`;
            }
        );
    },

    _escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => App.init());
