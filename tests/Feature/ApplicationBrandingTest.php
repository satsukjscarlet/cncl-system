<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ApplicationBrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_authenticated_pages_use_tien_phong_branding(): void
    {
        $this->get('/login')->assertOk()
            ->assertSee('images/ntp-favicon.png?v=20261022', false)
            ->assertSee('images/logo.png', false);
        $response = $this->actingAs(User::factory()->create())->get('/profile');
        $response->assertOk()->assertSee('favicons/favicon.ico', false)->assertSee('images/logo.png', false)
            ->assertDontSee('AdminLTELogo.png', false);
        $this->assertStringContainsString('images/logo.png', Blade::render('<x-application-logo class="h-9" />'));
        $this->assertStringNotContainsString('<svg', Blade::render('<x-application-logo />'));
        if ($directory = getenv('CNCL_BRAND_UI_SNAPSHOT_DIR')) {
            if (!is_dir($directory)) mkdir($directory, 0777, true);
            file_put_contents($directory . '/brand-profile.html', $response->getContent());
        }
    }

    public function test_favicon_assets_are_valid_and_share_the_official_image(): void
    {
        $png = file_get_contents(public_path('images/ntp-favicon.png'));
        $this->assertSame(IMAGETYPE_PNG, getimagesizefromstring($png)[2]);
        $ico = file_get_contents(public_path('favicons/favicon.ico'));
        $this->assertSame("\x00\x00\x01\x00\x01\x00", substr($ico, 0, 6));
        $this->assertSame($png, substr($ico, 22));
        $this->assertSame($ico, file_get_contents(public_path('favicon.ico')));
    }
}
