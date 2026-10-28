{{-- Notification d'un ticket (équipe ou demandeur). $ticket, $message (ou null), $intro, $url (ou null), $button, $outro (facultatif). --}}
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>{{ $ticket->number }}</title></head>
<body style="margin:0;padding:24px;background:#f5f7fb;font-family:Arial,Helvetica,sans-serif;color:#14213d">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden">
        <tr><td style="background:#103b8e;color:#ffffff;padding:18px 24px">
            <div style="font-size:20px;font-weight:bold">{{ $site->get('company.short_name') }}<span style="color:#f58220">.</span> Assistance</div>
            <div style="font-size:14px;margin-top:4px">{{ $ticket->number }} — {{ $ticket->subject }}</div>
        </td></tr>
        <tr><td style="padding:24px;font-size:15px;line-height:1.6">
            <p style="margin-top:0">{{ $intro }}</p>
            @if ($message)
                <div style="padding:14px;background:#f5f7fb;border-left:4px solid #f58220;border-radius:6px">{!! nl2br(e($message->body)) !!}</div>
            @endif
            @if ($url)
                <p style="margin:24px 0 0"><a href="{{ $url }}" style="display:inline-block;background:#f58220;color:#1b1203;text-decoration:none;font-weight:bold;padding:12px 20px;border-radius:8px">{{ $button }}</a></p>
            @endif
            @if ($outro ?? null)
                <p style="margin:24px 0 0">{{ $outro }}</p>
            @endif
        </td></tr>
    </table>
</body>
</html>
