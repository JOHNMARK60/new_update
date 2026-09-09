<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    public function test_landing_page_retains_feature_content(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Run your store with confidence.')
            ->assertSee('Product management')
            ->assertSee('Sales reporting');
    }

    public function test_login_and_password_reset_request_pages_are_available(): void
    {
        $this->get('/login')->assertOk()->assertSee('Forgot password?');
        $this->get('/forgot-password')->assertOk()->assertSee('Password reset');
    }

    public function test_application_features_require_authentication(): void
    {
        foreach (['/dashboard', '/products', '/inventory-status', '/reports', '/closings'] as $path) {
            $this->get($path)->assertRedirect('/login');
        }
    }
}
