<?php

namespace Tests\Feature\Modules;

use App\Mail\ArticleNewsletter;
use App\Models\Abonne;
use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\User;
use App\Support\SpamGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Tests\Concerns\BuildsFoundation;
use Tests\TestCase;

/**
 * Actualités par e-mail (maquette : « Restez informé de nos actualités ») : inscription, désinscription en un clic,
 * envoi d'un article publié une seule fois.
 */
class NewsletterTest extends TestCase
{
    use BuildsFoundation, RefreshDatabase;

    private function token(): string
    {
        return Crypt::encryptString((string) now()->subMinute()->getTimestamp());
    }

    public function test_a_visitor_subscribes_from_the_footer_and_unsubscribes_in_one_click(): void
    {
        $this->get('/')->assertSee('Restez informé de nos actualités');

        $this->postJson('/newsletter', ['email' => 'Awa@Exemple.ML', SpamGuard::TOKEN => $this->token()])->assertOk()->assertJsonPath('title', 'Inscription confirmée');
        $this->postJson('/newsletter', ['email' => 'awa@exemple.ml', SpamGuard::TOKEN => $this->token()])->assertOk();
        $this->postJson('/newsletter', ['email' => 'pas-un-email', SpamGuard::TOKEN => $this->token()])->assertUnprocessable();

        $abonne = Abonne::sole();
        $this->assertSame('awa@exemple.ml', $abonne->email);

        $this->get('/newsletter/desinscription/'.$abonne->token)->assertOk()->assertSee('Désinscription confirmée');
        $this->assertFalse($abonne->fresh()->isActive());
        $this->get('/newsletter/desinscription/'.str_repeat('x', 48))->assertOk()->assertSee('Lien inconnu');

        $this->postJson('/newsletter', ['email' => 'awa@exemple.ml', SpamGuard::TOKEN => $this->token()])->assertOk();
        $this->assertTrue($abonne->fresh()->isActive(), 'Se réinscrire réactive l\'abonnement.');
    }

    public function test_a_published_article_is_sent_once_to_active_subscribers(): void
    {
        Mail::fake();
        $this->syncPermissions();
        Abonne::create(['email' => 'a@x.ml']);
        Abonne::create(['email' => 'b@x.ml']);
        Abonne::create(['email' => 'c@x.ml', 'unsubscribed_at' => now()]);
        $article = Article::factory()->create();
        $draft = Article::factory()->draft()->create();

        $this->actingAs(User::factory()->gestionnaire()->create());
        $this->post("/admin/articles/{$draft->id}/newsletter")->assertSessionHas('error');
        $this->post("/admin/articles/{$article->id}/newsletter")->assertSessionHas('status', fn ($s) => str_contains($s, '2 abonné(s)'));
        Mail::assertQueued(ArticleNewsletter::class, 2);
        Mail::assertQueued(ArticleNewsletter::class, fn ($mail) => $mail->hasTo('a@x.ml'));
        Mail::assertNotQueued(ArticleNewsletter::class, fn ($mail) => $mail->hasTo('c@x.ml'));

        $this->post("/admin/articles/{$article->id}/newsletter")->assertSessionHas('error');
        Mail::assertQueued(ArticleNewsletter::class, 2);
        $this->assertTrue(ActivityLog::where('action', 'abonnes.sent')->exists());
    }

    public function test_subscribers_are_managed_in_bulk_and_exported(): void
    {
        $this->syncPermissions();
        $this->actingAs(User::factory()->administrateur()->create());

        $this->post('/admin/abonnes/lot', ['rows' => [['email' => 'x@y.ml', 'name' => 'X'], ['email' => 'z@y.ml']]])->assertRedirect('/admin/abonnes');
        $this->assertSame(2, Abonne::count());
        $this->get('/admin/abonnes')->assertOk()->assertSee('x@y.ml');
        $this->assertStringContainsString('z@y.ml', $this->get('/admin/abonnes/export')->streamedContent());

        $this->actingAs(User::factory()->gestionnaire()->create())->post('/admin/abonnes/lot', ['rows' => [['email' => 'w@y.ml']]])->assertForbidden();
    }
}
