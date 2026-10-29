{{-- Remerciement à l'auteur d'un témoignage (styles en ligne : clients de messagerie). $temoignage. --}}
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Merci pour votre témoignage</title></head>
<body style="margin:0;padding:24px;background:#f5f7fb;font-family:Arial,Helvetica,sans-serif;color:#14213d">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden">
        <tr><td style="background:#103b8e;color:#ffffff;padding:20px 24px">
            <div style="font-size:22px;font-weight:bold">{{ $site->get('company.short_name') }}<span style="color:#f58220">.</span></div>
            <div style="font-size:15px;margin-top:6px">Merci pour votre témoignage</div>
        </td></tr>
        <tr><td style="padding:24px;font-size:15px;line-height:1.6">
            <p style="margin-top:0">Bonjour {{ $temoignage->author_name }},</p>
            <p>Merci d'avoir pris le temps de partager votre expérience avec {{ $site->get('company.name') }} : votre avis compte beaucoup pour nous.</p>
            <p>Notre équipe relit votre témoignage{{ $temoignage->video_path ? ' et prépare votre vidéo' : '' }} avant de le publier sur le site. Vos coordonnées ne sont jamais publiées.</p>
            @if ($temoignage->content)
                <div style="margin-top:12px;padding:14px;background:#f5f7fb;border-left:4px solid #f58220;border-radius:6px;font-size:14px;line-height:1.6">{!! nl2br(e($temoignage->content)) !!}</div>
            @endif
            <p style="margin:24px 0 0;font-size:12px;color:#6b7690">Vous recevez cet e-mail parce que vous avez envoyé un témoignage sur le site de {{ $site->get('company.name') }}. Si ce n'est pas vous, ignorez-le simplement.</p>
        </td></tr>
    </table>
</body>
</html>
