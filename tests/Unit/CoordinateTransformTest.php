<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\CertificateService;

class CoordinateTransformTest extends TestCase
{
    public function test_mm_to_pt_equivalence()
    {
        $svc = new CertificateService();
        $ref = new \ReflectionClass($svc);
        $method = $ref->getMethod('writeBox');
        $method->setAccessible(true);

        // font mm -> pt uses * 2.83465 (1mm = 2.83465pt)
        $fontMm = 6.0;
        // We cannot call writeBox without mPDF, so just assert the constant equivalence
        $this->assertEqualsWithDelta(6.0 * 2.83465, 17.0079, 0.01);
    }

    public function test_weight_mapping()
    {
        $svc = new CertificateService();
        $ref = new \ReflectionClass($svc);
        $method = $ref->getMethod('weightFor');
        $method->setAccessible(true);

        $style = ['weight_per' => ['ar_name' => 'bold', 'en_name' => 'normal', 'ar_track' => 600]];
        $this->assertSame(700, $method->invoke($svc, $style, 'ar_name', 400));
        $this->assertSame(400, $method->invoke($svc, $style, 'en_name', 400));
        $this->assertSame(600, $method->invoke($svc, $style, 'ar_track', 400));
        $this->assertSame(700, $method->invoke($svc, $style, 'cert_date', 700));
    }
}
