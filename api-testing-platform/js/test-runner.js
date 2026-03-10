/**
 * Test Runner Engine
 * Manages test execution, assertions, result tracking, and reporting
 */
class TestRunner {
    constructor() {
        this.results = {};       // suiteId -> [testResult]
        this.listeners = {};     // event -> [callbacks]
        this.isRunning = false;
        this.currentSuite = null;
        this.abortController = null;
        this.globalStats = {
            totalTests: 0,
            passed: 0,
            failed: 0,
            skipped: 0,
            totalTime: 0,
        };
    }

    /**
     * Register an event listener
     * Events: testStart, testEnd, suiteStart, suiteEnd, progress
     */
    on(event, callback) {
        if (!this.listeners[event]) this.listeners[event] = [];
        this.listeners[event].push(callback);
    }

    /**
     * Emit an event
     */
    _emit(event, data) {
        (this.listeners[event] || []).forEach(cb => cb(data));
    }

    /**
     * Run a single test suite
     */
    async runSuite(suiteId, tests) {
        if (this.isRunning) return;
        this.isRunning = true;
        this.currentSuite = suiteId;
        this.results[suiteId] = [];

        this._emit('suiteStart', { suiteId, total: tests.length });

        let context = {}; // shared context between tests in a suite

        for (let i = 0; i < tests.length; i++) {
            const test = tests[i];
            const testResult = {
                id: test.id,
                name: test.name,
                description: test.description || '',
                status: 'running',
                duration: 0,
                request: null,
                response: null,
                assertions: [],
                error: null,
            };

            this._emit('testStart', { suiteId, testIndex: i, test: testResult });

            const startTime = performance.now();

            try {
                // Execute the test function, passing apiClient and context
                const result = await test.run(window.apiClient, context);

                testResult.duration = Math.round(performance.now() - startTime);
                testResult.request = result.request || null;
                testResult.response = result.response || null;
                testResult.assertions = result.assertions || [];

                // Determine pass/fail from assertions
                const allPassed = testResult.assertions.every(a => a.passed);
                testResult.status = allPassed ? 'passed' : 'failed';

                if (!allPassed) {
                    const firstFail = testResult.assertions.find(a => !a.passed);
                    testResult.error = firstFail ? firstFail.message : 'Assertion failed';
                }

                // Merge any context updates
                if (result.context) {
                    context = { ...context, ...result.context };
                }
            } catch (err) {
                testResult.duration = Math.round(performance.now() - startTime);
                testResult.status = 'failed';
                testResult.error = err.message || 'Unknown error';
                testResult.assertions = [{
                    passed: false,
                    message: `Exception: ${err.message}`,
                    expected: 'No exception',
                    actual: err.message,
                }];
            }

            this.results[suiteId].push(testResult);
            this._updateGlobalStats();

            this._emit('testEnd', {
                suiteId,
                testIndex: i,
                test: testResult,
                progress: Math.round(((i + 1) / tests.length) * 100),
            });

            // Small delay between tests for UI updates
            await new Promise(r => setTimeout(r, 100));
        }

        this._emit('suiteEnd', {
            suiteId,
            results: this.results[suiteId],
            summary: this._getSuiteSummary(suiteId),
        });

        this.isRunning = false;
        this.currentSuite = null;
    }

    /**
     * Run multiple suites sequentially
     */
    async runAllSuites(suites) {
        if (this.isRunning) return;

        const totalTests = suites.reduce((sum, s) => sum + s.tests.length, 0);
        let completedTests = 0;

        this._emit('progress', { completed: 0, total: totalTests, percent: 0 });

        for (const suite of suites) {
            this.isRunning = false; // allow runSuite to proceed
            const originalListener = this.listeners['testEnd'] || [];

            // Temporarily add progress tracking
            const progressHandler = () => {
                completedTests++;
                this._emit('progress', {
                    completed: completedTests,
                    total: totalTests,
                    percent: Math.round((completedTests / totalTests) * 100),
                });
            };

            this.on('testEnd', progressHandler);
            await this.runSuite(suite.id, suite.tests);

            // Remove the temporary listener
            this.listeners['testEnd'] = this.listeners['testEnd'].filter(cb => cb !== progressHandler);
        }
    }

    /**
     * Get summary for a specific suite
     */
    _getSuiteSummary(suiteId) {
        const results = this.results[suiteId] || [];
        return {
            total: results.length,
            passed: results.filter(r => r.status === 'passed').length,
            failed: results.filter(r => r.status === 'failed').length,
            skipped: results.filter(r => r.status === 'skipped').length,
            totalTime: results.reduce((sum, r) => sum + r.duration, 0),
        };
    }

    /**
     * Update global statistics
     */
    _updateGlobalStats() {
        const allResults = Object.values(this.results).flat();
        this.globalStats = {
            totalTests: allResults.length,
            passed: allResults.filter(r => r.status === 'passed').length,
            failed: allResults.filter(r => r.status === 'failed').length,
            skipped: allResults.filter(r => r.status === 'skipped').length,
            totalTime: allResults.reduce((sum, r) => sum + r.duration, 0),
        };
    }

    /**
     * Get global stats
     */
    getGlobalStats() {
        return { ...this.globalStats };
    }

    /**
     * Get results for a suite
     */
    getSuiteResults(suiteId) {
        return this.results[suiteId] || [];
    }

    /**
     * Clear all results
     */
    clearResults() {
        this.results = {};
        this.globalStats = { totalTests: 0, passed: 0, failed: 0, skipped: 0, totalTime: 0 };
    }

    /**
     * Clear results for a specific suite
     */
    clearSuiteResults(suiteId) {
        delete this.results[suiteId];
        this._updateGlobalStats();
    }
}

/**
 * Assertion Helper
 * Provides assertion functions that return { passed, message, expected, actual }
 */
const Assert = {
    /**
     * Assert that a value is truthy
     */
    isTrue(value, message) {
        return {
            passed: !!value,
            message: message || 'Expected value to be truthy',
            expected: true,
            actual: value,
        };
    },

    /**
     * Assert that a value is falsy
     */
    isFalse(value, message) {
        return {
            passed: !value,
            message: message || 'Expected value to be falsy',
            expected: false,
            actual: value,
        };
    },

    /**
     * Assert equality
     */
    equals(actual, expected, message) {
        return {
            passed: actual === expected,
            message: message || `Expected ${JSON.stringify(expected)}, got ${JSON.stringify(actual)}`,
            expected,
            actual,
        };
    },

    /**
     * Assert not equal
     */
    notEquals(actual, notExpected, message) {
        return {
            passed: actual !== notExpected,
            message: message || `Expected value to not be ${JSON.stringify(notExpected)}`,
            expected: `not ${JSON.stringify(notExpected)}`,
            actual,
        };
    },

    /**
     * Assert that HTTP status matches
     */
    statusEquals(response, expectedStatus, message) {
        return {
            passed: response.status === expectedStatus,
            message: message || `Expected HTTP ${expectedStatus}, got HTTP ${response.status}`,
            expected: expectedStatus,
            actual: response.status,
        };
    },

    /**
     * Assert response has success: true
     */
    isSuccessResponse(response, message) {
        const data = response.data || {};
        return {
            passed: data.success === true,
            message: message || `Expected success: true, got ${JSON.stringify(data.success)}`,
            expected: true,
            actual: data.success,
        };
    },

    /**
     * Assert response has success: false
     */
    isErrorResponse(response, message) {
        const data = response.data || {};
        return {
            passed: data.success === false,
            message: message || `Expected success: false, got ${JSON.stringify(data.success)}`,
            expected: false,
            actual: data.success,
        };
    },

    /**
     * Assert that response data contains a specific key
     */
    hasKey(obj, key, message) {
        const has = obj != null && key in obj;
        return {
            passed: has,
            message: message || `Expected object to have key "${key}"`,
            expected: `key "${key}" present`,
            actual: has ? 'present' : 'missing',
        };
    },

    /**
     * Assert that response data has nested property
     */
    hasNestedKey(obj, path, message) {
        const keys = path.split('.');
        let current = obj;
        let found = true;
        for (const key of keys) {
            if (current == null || !(key in current)) {
                found = false;
                break;
            }
            current = current[key];
        }
        return {
            passed: found,
            message: message || `Expected object to have path "${path}"`,
            expected: `path "${path}" present`,
            actual: found ? 'present' : 'missing',
        };
    },

    /**
     * Assert that response error code matches
     */
    errorCodeEquals(response, expectedCode, message) {
        const code = response.data?.error?.code;
        return {
            passed: code === expectedCode,
            message: message || `Expected error code "${expectedCode}", got "${code}"`,
            expected: expectedCode,
            actual: code,
        };
    },

    /**
     * Assert that a value is of a given type
     */
    isType(value, expectedType, message) {
        const actualType = typeof value;
        return {
            passed: actualType === expectedType,
            message: message || `Expected type "${expectedType}", got "${actualType}"`,
            expected: expectedType,
            actual: actualType,
        };
    },

    /**
     * Assert that a value is an array
     */
    isArray(value, message) {
        const isArr = Array.isArray(value);
        return {
            passed: isArr,
            message: message || `Expected an array, got ${typeof value}`,
            expected: 'array',
            actual: isArr ? 'array' : typeof value,
        };
    },

    /**
     * Assert that a string includes a substring
     */
    includes(str, substr, message) {
        const includes = typeof str === 'string' && str.includes(substr);
        return {
            passed: includes,
            message: message || `Expected string to include "${substr}"`,
            expected: `contains "${substr}"`,
            actual: typeof str === 'string' ? str.substring(0, 100) : typeof str,
        };
    },

    /**
     * Assert that response time is under a threshold
     */
    responseTimeUnder(response, maxMs, message) {
        return {
            passed: response.duration <= maxMs,
            message: message || `Expected response under ${maxMs}ms, took ${response.duration}ms`,
            expected: `<= ${maxMs}ms`,
            actual: `${response.duration}ms`,
        };
    },

    /**
     * Assert that a value is greater than
     */
    greaterThan(actual, threshold, message) {
        return {
            passed: actual > threshold,
            message: message || `Expected ${actual} to be greater than ${threshold}`,
            expected: `> ${threshold}`,
            actual,
        };
    },

    /**
     * Assert value is greater than or equal
     */
    greaterThanOrEqual(actual, threshold, message) {
        return {
            passed: actual >= threshold,
            message: message || `Expected ${actual} to be >= ${threshold}`,
            expected: `>= ${threshold}`,
            actual,
        };
    },
};

// Export globally
window.TestRunner = TestRunner;
window.Assert = Assert;
