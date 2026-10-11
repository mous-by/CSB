{{-- E-mail de notification d'une nouvelle demande (styles en ligne : clients de messagerie). --}}
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>{{ $demande->type->label() }}</title></head>
<body style="margin:0;padding:24px;background:#f5f7fb;font-family:Arial,Helvetica,sans-serif;color:#14213d">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden">
        <tr><td style="background:#103b8e;color:#ffffff;padding:20px 24px">
            <div style="font-size:22px;font-weight:bold">{{ $site->get('company.short_name') }}<span style="color:#f58220">.</span></div>
            <div style="font-size:15px;margin-top:6px">{{ $demande->type->label() }} — {{ $demande->reference() }}</div>
        </td></tr>
        <tr><td style="padding:24px">
            <table role="presentation" width="100%" cellpadding="6" cellspacing="0" style="font-size:14px">
                <tr><td style="color:#6b7690;width:160px">Nom</td><td><strong>{{ $demande->name }}</strong></td></tr>
                @if ($demande->organization)<tr><td style="color:#6b7690">Organisation</td><td>{{ $demande->organization }}</td></tr>@endif
                @if ($demande->email)<tr><td style="color:#6b7690">E-mail</td><td>{{ $demande->email }}</td></tr>@endif
                @if ($demande->phone)<tr><td style="color:#6b7690">Téléphone</td><td>{{ $demande->phone }}</td></tr>@endif
                @if ($demande->subject_label)<tr><td style="color:#6b7690">Concerne</td><td>{{ $demande->subject_label }}</td></tr>@endif
                @if ($demande->need_type)<tr><td style="color:#6b7690">Besoin</td><td>{{ $demande->need_type->label() }}</td></tr>@endif
                @if ($demande->subject)<tr><td style="color:#6b7690">Objet</td><td>{{ $demande->subject }}</td></tr>@endif
                @if ($demande->budget)<tr><td style="color:#6b7690">Budget</td><td>{{ $demande->budget }}</td></tr>@endif
                @if ($demande->deadline)<tr><td style="color:#6b7690">Délai</td><td>{{ $demande->deadline }}</td></tr>@endif
                @if ($demande->availability)<tr><td style="color:#6b7690">Disponibilités</td><td>{{ $demande->availability }}</td></tr>@endif
                @if ($demande->attachment_name)<tr><td style="color:#6b7690">Pièce jointe</td><td>{{ $demande->attachment_name }} (dans l'administration)</td></tr>@endif
            </table>
            @if ($demande->message)
                <div style="margin-top:16px;padding:14px;background:#f5f7fb;border-radius:8px;font-size:14px;line-height:1.6">{!! nl2br(e($demande->message)) !!}</div>
            @endif
            <p style="margin-top:24px"><a href="{{ route('demandes.show', $demande) }}" style="display:inline-block;background:#f58220;color:#1b1203;text-decoration:none;font-weight:bold;padding:12px 20px;border-radius:8px">Traiter la demande</a></p>
            <p style="font-size:12px;color:#6b7690">Reçue le {{ $demande->created_at->format('d/m/Y à H:i') }}{{ $demande->source_url ? ' depuis '.$demande->source_url : '' }}.</p>
        </td></tr>
    </table>
</body>
</html>
