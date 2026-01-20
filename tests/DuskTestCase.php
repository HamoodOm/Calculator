<?php

namespace Tests;

use Laravel\Dusk\TestCase as BaseTestCase;
use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Chrome\ChromeOptions;

abstract class DuskTestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Start ChromeDriver on the port you chose (9516).
     */
    public static function prepare(): void
    {
        // Ask Dusk to start its bundled chromedriver with the port we want.
        static::startChromeDriver(['--port=9516']);

        // Extra safety: if it didn’t start (firewall, etc.), try to spawn it manually.
        $host = '127.0.0.1';
        $port = 9516;
        if (!self::isPortOpen($host, $port, 800)) {
            $exe = base_path('vendor/laravel/dusk/bin/chromedriver-win.exe');
            if (is_file($exe)) {
                // launch detached
                pclose(popen('start "" "' . $exe . '" --port=' . $port, 'r'));
                self::waitForPort($host, $port, 5000);
            }
        }
    }

    /**
     * Create Remote WebDriver on http://localhost:9516.
     */
    protected function driver()
    {
        $options = new ChromeOptions();
        $options->addArguments([
            '--headless',                // use --headless=new if your Chrome supports it
            '--disable-gpu',
            '--window-size=1366,768',
            '--no-sandbox',
            '--disable-dev-shm-usage',
            '--remote-allow-origins=*',
        ]);

        return RemoteWebDriver::create(
            // Read from env, fall back to 9516
            env('DUSK_DRIVER_URL', 'http://localhost:9516'),
            DesiredCapabilities::chrome()->setCapability(ChromeOptions::CAPABILITY, $options)
        );
    }

    // ---- helpers -----------------------------------------------------------

    private static function isPortOpen(string $host, int $port, int $timeoutMs): bool
    {
        $t1 = microtime(true);
        do {
            $fp = @fsockopen($host, $port, $errno, $errstr, 0.2);
            if (is_resource($fp)) { fclose($fp); return true; }
            usleep(150000);
        } while ((microtime(true) - $t1) * 1000 < $timeoutMs);
        return false;
    }

    private static function waitForPort(string $host, int $port, int $timeoutMs): void
    {
        $t1 = microtime(true);
        do {
            $fp = @fsockopen($host, $port, $errno, $errstr, 0.25);
            if (is_resource($fp)) { fclose($fp); return; }
            usleep(150000);
        } while ((microtime(true) - $t1) * 1000 < $timeoutMs);
    }
}
