{{-- Récapitulatif d'une commande (équipe ou client). $commande, $intro, $url, $button, $instructions. --}}
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="utf-8"><title>{{ $commande->number }}</title></head>
<body style="margin:0;padding:24px;background:#f5f7fb;font-family:Arial,Helvetica,sans-serif;color:#14213d">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:620px;margin:0 auto;background:#ffffff;border-radius:12px;overflow:hidden">
        <tr><td style="background:#103b8e;color:#ffffff;padding:18px 24px">
            <div style="font-size:20px;font-weight:bold">{{ $site->get('company.short_name') }}<span style="color:#f58220">.</span> Boutique</div>
            <div style="font-size:14px;margin-top:4px">Commande {{ $commande->number }} — {{ $commande->status->label() }}</div>
        </td></tr>
        <tr><td style="padding:24px;font-size:15px;line-height:1.6">
            <p style="margin-top:0">{{ $intro }}</p>
            <table role="presentation" width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse;font-size:14px">
                @foreach ($commande->lignes as $ligne)
                    <tr style="border-bottom:1px solid #e5eaf3">
                        <td>{{ $ligne->designation }} × {{ $ligne->quantity }}</td>
                        <td align="right" style="white-space:nowrap">{{ \App\Models\Materiel::money($ligne->line_total) }}</td>
                    </tr>
                @endforeach
                @if ($commande->discount_amount > 0)
                    <tr><td>Remise (code {{ $commande->promo_code }})</td><td align="right" style="white-space:nowrap">− {{ \App\Models\Materiel::money($commande->discount_amount) }}</td></tr>
                @endif
                <tr><td style="font-weight:bold">Total</td><td align="right" style="font-weight:bold;white-space:nowrap">{{ $commande->totalForDisplay() }}</td></tr>
            </table>
            <p style="font-size:14px;color:#55607a">{{ $commande->delivery_method->label() }}@if ($commande->address) — {{ $commande->address }}@endif · Paiement : {{ $commande->payment_method->label() }}</p>
            @if ($instructions)
                <div style="padding:14px;background:#fff7ef;border-left:4px solid #f58220;border-radius:6px;font-size:14px">{{ $instructions }}</div>
            @endif
            <p style="margin:24px 0 0"><a href="{{ $url }}" style="display:inline-block;background:#f58220;color:#1b1203;text-decoration:none;font-weight:bold;padding:12px 20px;border-radius:8px">{{ $button }}</a></p>
        </td></tr>
    </table>
</body>
</html>
