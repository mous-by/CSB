{{-- Article envoyé aux abonnés (styles en ligne : clients de messagerie). --}}
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>{{ $article->title }}</title></head>
<body style="margin:0;padding:24px;background:#f5f7fb;font-family:Arial,Helvetica,sans-serif;color:#14213d">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden">
        <tr><td style="background:#103b8e;color:#ffffff;padding:18px 24px;font-size:20px;font-weight:bold">{{ $site->get('company.name') }}<span style="color:#f58220">.</span></td></tr>
        @if ($article->cover)
            <tr><td><img src="{{ $article->cover->url() }}" alt="" width="620" style="display:block;width:100%;height:auto"></td></tr>
        @endif
        <tr><td style="padding:24px">
            <p style="margin:0 0 6px;color:#c8620c;font-size:12px;font-weight:bold;text-transform:uppercase">{{ $article->category?->name }}</p>
            <h1 style="margin:0 0 14px;font-size:22px;color:#14213d">{{ $article->title }}</h1>
            <p style="font-size:15px;line-height:1.6;color:#3d4a63">{{ $article->excerptText(300) }}</p>
            <p style="margin:24px 0"><a href="{{ route('site.actualites.show', $article->slug) }}" style="display:inline-block;background:#f58220;color:#1b1203;text-decoration:none;font-weight:bold;padding:12px 20px;border-radius:8px">Lire l'article</a></p>
        </td></tr>
        <tr><td style="padding:16px 24px;background:#f5f7fb;font-size:12px;color:#6b7690">
            Vous recevez ce message car vous êtes abonné(e) aux actualités de {{ $site->get('company.short_name') }}.
            <a href="{{ route('site.newsletter.unsubscribe', $abonne->token) }}" style="color:#103b8e">Se désinscrire</a>
        </td></tr>
    </table>
</body>
</html>
