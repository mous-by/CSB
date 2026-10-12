{{-- Témoignage reçu du site (équipe). $temoignage. --}}
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Nouveau témoignage</title></head>
<body style="margin:0;padding:24px;background:#f5f7fb;font-family:Arial,Helvetica,sans-serif;color:#14213d">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden">
        <tr><td style="background:#103b8e;color:#ffffff;padding:18px 24px;font-size:20px;font-weight:bold">{{ $site->get('company.short_name') }}<span style="color:#f58220">.</span> Nouveau témoignage</td></tr>
        <tr><td style="padding:24px;font-size:15px;line-height:1.6">
            <p style="margin-top:0"><strong>{{ $temoignage->author_name }}</strong>{{ $temoignage->organization ? ' — '.$temoignage->organization : '' }} ({{ $temoignage->contact }}) a envoyé un témoignage{{ $temoignage->video_path ? ' en vidéo' : '' }}{{ $temoignage->rating ? ', note '.$temoignage->rating.'/5' : '' }}.</p>
            @if ($temoignage->content)
                <div style="padding:14px;background:#f5f7fb;border-left:4px solid #f58220;border-radius:6px">{!! nl2br(e($temoignage->content)) !!}</div>
            @endif
            <p>Il reste masqué : vérifiez-le, puis publiez-le dans l'administration.</p>
            <p style="margin:24px 0 0"><a href="{{ route('temoignages.index') }}" style="display:inline-block;background:#f58220;color:#1b1203;text-decoration:none;font-weight:bold;padding:12px 20px;border-radius:8px">Voir les témoignages</a></p>
        </td></tr>
    </table>
</body>
</html>
