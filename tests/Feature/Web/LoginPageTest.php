<?php

namespace Tests\Feature\Web;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Page de connexion de l'administration (demande de CSB, 2026-10-11) : le logo de Computer Service Barry, sans diaporama,
 * et le formulaire à droite.
 */
class LoginPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_login_page_shows_only_the_csb_logo_next_to_the_form(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('login-page login-page--right', false)
            ->assertSee('csb-login-brand', false)
            ->assertSee('assets/site/img/logo-csb.png', false)
            ->assertSee('id="loginForm"', false)
            ->assertDontSee('bg-slideshow', false)
            ->assertDontSee('bg-dot', false);
    }

    public function test_the_logo_file_is_shipped_with_the_project(): void
    {
        $this->assertFileExists(public_path('assets/site/img/logo-csb.png'));
    }
}
