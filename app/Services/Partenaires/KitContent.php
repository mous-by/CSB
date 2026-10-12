<?php

namespace App\Services\Partenaires;

use App\Models\Article;
use App\Models\Logiciel;
use App\Models\Materiel;
use App\Models\Media;
use App\Models\Partenaire;
use App\Models\Realisation;
use App\Support\SiteSettings;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Kit de publication d'un contenu publié (matériel, logiciel, réalisation, actualité) : textes prêts à copier pour
 * chaque réseau social (avec le lien personnel du partenaire), image principale, données des visuels générés.
 */
class KitContent
{
    public const TYPES = ['materiel' => Materiel::class, 'logiciel' => Logiciel::class, 'realisation' => Realisation::class, 'article' => Article::class];

    public function __construct(private readonly SiteSettings $settings) {}

    public function find(string $type, int $id): Model
    {
        $class = self::TYPES[$type] ?? abort(404);
        $model = $class::query()->published()->findOrFail($id);

        return $model;
    }

    /** @return array{title: string, subtitle: ?string, url: string, image: ?Media, price: ?string, old_price: ?string, badge: ?string, points: list<string>, tags: list<string>} */
    public function data(string $type, Model $model, ?Partenaire $partenaire): array
    {
        $data = match ($type) {
            'materiel' => [
                'title' => $model->designation, 'subtitle' => $model->brandModel() ?: $model->condition->label(),
                'url' => route('site.materiels.show', $model->slug), 'image' => $model->images()->first(),
                'price' => $model->isOnPromo() ? $model->promoPriceForDisplay() : $model->priceForDisplay(),
                'old_price' => $model->isOnPromo() ? $model->priceForDisplay() : null,
                'badge' => $model->isOnPromo() ? 'PROMO -'.$model->promoPercent().' %' : ($model->condition->label() === 'Neuf' ? 'NEUF' : null),
                'points' => array_map(fn ($s) => $s[0].' : '.$s[1], array_slice($model->specList(), 0, 4)),
                'tags' => ['#Informatique', '#Ordinateur', '#Ségou', '#Mali', '#CSB'],
            ],
            'logiciel' => [
                'title' => $model->name, 'subtitle' => $model->tagline ?? $model->summary,
                'url' => route('site.logiciels.show', $model->slug), 'image' => $model->gallery->first() ?? $model->logo,
                'price' => $model->price_text, 'old_price' => null, 'badge' => 'LOGICIEL',
                'points' => array_slice(array_values(array_filter((array) $model->features)), 0, 4),
                'tags' => ['#Logiciel', '#Digitalisation', '#Mali', '#'.Str::studly(Str::ascii($model->name)), '#CSB'],
            ],
            'realisation' => [
                'title' => $model->title, 'subtitle' => $model->sector,
                'url' => route('site.realisations.show', $model->slug), 'image' => $model->images()->first(),
                'price' => null, 'old_price' => null, 'badge' => 'RÉALISATION',
                'points' => array_slice($model->technologyList(), 0, 4),
                'tags' => ['#Réalisation', '#Développement', '#Mali', '#CSB'],
            ],
            'article' => [
                'title' => $model->title, 'subtitle' => $model->excerpt,
                'url' => route('site.actualites.show', $model->slug), 'image' => $model->cover,
                'price' => null, 'old_price' => null, 'badge' => 'ACTUALITÉ', 'points' => [],
                'tags' => ['#Actualité', '#Informatique', '#Mali', '#CSB'],
            ],
        };
        $data['url'] = $partenaire ? $partenaire->link($data['url']) : $data['url'];
        $data['promo_code'] = $partenaire?->promoActive() && $type === 'materiel' ? $partenaire->promo_code : null;
        $data['promo_discount'] = $data['promo_code'] ? rtrim(rtrim(number_format($partenaire->promo_discount, 2, ',', ''), '0'), ',') : null;

        return $data;
    }

    /** Textes par réseau. @return array<string, array{label: string, icon: string, text: string}> */
    public function texts(array $d): array
    {
        $phones = implode(' / ', array_filter([$this->settings->get('contact.phone_1'), $this->settings->get('contact.phone_2')]));
        $price = $d['price'] ? ($d['old_price'] ? "💥 {$d['price']} au lieu de {$d['old_price']} !" : "💰 {$d['price']}") : null;
        $points = $d['points'] ? implode("\n", array_map(fn ($p) => '✅ '.$p, $d['points'])) : null;
        $promo = $d['promo_code'] ? "🎁 Code promo {$d['promo_code']} : -{$d['promo_discount']} % sur votre commande en ligne !" : null;
        $tags = implode(' ', $d['tags']);
        $lines = fn (array $parts) => implode("\n\n", array_values(array_filter($parts)));

        return [
            'facebook' => ['label' => 'Facebook', 'icon' => 'bxl-facebook-circle', 'text' => $lines([
                "🔥 {$d['title']}".($d['subtitle'] ? "\n".Str::limit($d['subtitle'], 160) : ''), $points, $price, $promo,
                "👉 Tous les détails et la commande ici : {$d['url']}", "📞 Computer Service Barry — {$phones}", $tags,
            ])],
            'instagram' => ['label' => 'Instagram', 'icon' => 'bxl-instagram', 'text' => $lines([
                "✨ {$d['title']} ✨".($d['subtitle'] ? "\n".Str::limit($d['subtitle'], 120) : ''), $points, $price, $promo,
                '🔗 Lien dans la bio : '.$d['url'], "📞 {$phones}", $tags.' #Bamako #Tech #Afrique',
            ])],
            'tiktok' => ['label' => 'TikTok', 'icon' => 'bxl-tiktok', 'text' => $lines([
                "{$d['title']} 😮".($price ? ' '.$price : ''), $promo, "Lien : {$d['url']}", $tags.' #pourtoi #fyp',
            ])],
            'whatsapp' => ['label' => 'Statut WhatsApp', 'icon' => 'bxl-whatsapp', 'text' => $lines([
                "*{$d['title']}*".($price ? "\n{$price}" : ''), $promo, "👉 {$d['url']}", "📞 {$phones}",
            ])],
            'linkedin' => ['label' => 'LinkedIn', 'icon' => 'bxl-linkedin-square', 'text' => $lines([
                "{$d['title']}".($d['subtitle'] ? ' — '.Str::limit($d['subtitle'], 200) : ''),
                $d['points'] ? implode("\n", array_map(fn ($p) => '• '.$p, $d['points'])) : null,
                $d['price'] ? 'Tarif : '.$d['price'].($d['old_price'] ? " (au lieu de {$d['old_price']})" : '') : null,
                "En savoir plus : {$d['url']}", 'Computer Service Barry (CSB) — Qualité • Fiabilité • Performance', implode(' ', array_slice($d['tags'], 0, 4)),
            ])],
        ];
    }
}
