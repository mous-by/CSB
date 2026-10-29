{{-- Accusé de réception d'une demande, pour le visiteur (styles en ligne : clients de messagerie). $demande, $what, $url (ou null). --}}
@php
    $phone = $site->get('contact.phone_1');
    $whatsapp = $site->whatsappLink('Bonjour CSB, je vous écris au sujet de ma demande '.$demande->reference().'.');
@endphp
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>Demande bien reçue</title></head>
<body style="margin:0;padding:24px;background:#f5f7fb;font-family:Arial,Helvetica,sans-serif;color:#14213d">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden">
        <tr><td style="background:#103b8e;color:#ffffff;padding:20px 24px">
            <div style="font-size:22px;font-weight:bold">{{ $site->get('company.short_name') }}<span style="color:#f58220">.</span></div>
            <div style="font-size:15px;margin-top:6px">Demande bien reçue — {{ $demande->reference() }}</div>
        </td></tr>
        <tr><td style="padding:24px;font-size:15px;line-height:1.6">
            <p style="margin-top:0">Bonjour {{ $demande->name }},</p>
            <p>Merci d'avoir contacté {{ $site->get('company.name') }}. Nous avons bien reçu {{ $what }}, enregistrée sous la référence <strong>{{ $demande->reference() }}</strong>.</p>
            <p>Notre équipe l'étudie et vous recontacte rapidement{{ $demande->phone ? ' par téléphone ou par e-mail' : ' par e-mail' }}.</p>

            <table role="presentation" width="100%" cellpadding="6" cellspacing="0" style="font-size:14px;margin-top:8px">
                @if ($demande->subject_label)<tr><td style="color:#6b7690;width:140px">Concerne</td><td>{{ $demande->subject_label }}</td></tr>@endif
                @if ($demande->need_type)<tr><td style="color:#6b7690;width:140px">Besoin</td><td>{{ $demande->need_type->label() }}</td></tr>@endif
                @if ($demande->subject)<tr><td style="color:#6b7690;width:140px">Objet</td><td>{{ $demande->subject }}</td></tr>@endif
                @if ($demande->availability)<tr><td style="color:#6b7690;width:140px">Disponibilités</td><td>{{ $demande->availability }}</td></tr>@endif
                @if ($demande->attachment_name)<tr><td style="color:#6b7690;width:140px">Pièce jointe</td><td>{{ $demande->attachment_name }} (bien reçue)</td></tr>@endif
            </table>
            @if ($demande->message)
                <div style="margin-top:12px;padding:14px;background:#f5f7fb;border-radius:8px;font-size:14px;line-height:1.6">{!! nl2br(e($demande->message)) !!}</div>
            @endif

            @if ($url)
                <p style="margin:24px 0 0"><a href="{{ $url }}" style="display:inline-block;background:#f58220;color:#1b1203;text-decoration:none;font-weight:bold;padding:12px 20px;border-radius:8px">Suivre mes demandes</a></p>
            @endif

            @if ($phone || $whatsapp)
                <p style="margin:24px 0 0;font-size:14px">Besoin d'une réponse urgente ?
                    @if ($phone) Appelez-nous au <strong>{{ $phone }}</strong>@endif{{ $phone && $whatsapp ? ' ou ' : '' }}@if ($whatsapp)<a href="{{ $whatsapp }}" style="color:#1fa855;font-weight:bold">écrivez-nous sur WhatsApp</a>@endif
                    en rappelant la référence {{ $demande->reference() }}.
                </p>
            @endif
            <p style="margin:24px 0 0;font-size:12px;color:#6b7690">Vous recevez cet e-mail parce que vous avez rempli un formulaire sur le site de {{ $site->get('company.name') }}. Si ce n'est pas vous, ignorez-le simplement.</p>
        </td></tr>
    </table>
</body>
</html>
