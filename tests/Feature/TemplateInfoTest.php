<?php

namespace Tests\Feature;

use Tests\TestCase;

class TemplateInfoTest extends TestCase
{
    public function test_template_info_returns_bg_positions_style(): void
    {
        $resp = $this->get(route('template.info', [
            'role'      => 'teacher',
            'track_key' => 't_laravel_fundamentals',
            'gender'    => 'male',
        ]));

        $resp->assertOk();
        $data = $resp->json();

        $this->assertTrue($data['success'] ?? false);
        $this->assertIsString($data['background_url'] ?? null);
        $this->assertNotEmpty($data['background_url'] ?? '');
        $this->assertIsArray($data['positions'] ?? null);
        $this->assertIsArray($data['style'] ?? null);
    }
}
