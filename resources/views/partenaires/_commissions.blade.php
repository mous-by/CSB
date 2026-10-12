{{-- Tableau des commissions. $commissions, $payable (cases à cocher pour le paiement), $formId --}}
<div class="table-responsive">
    <table class="table align-middle">
        <thead><tr>@if ($payable)<th width="30"></th>@endif<th>DATE</th><th>VENTE</th><th class="text-end">MONTANT ENCAISSÉ</th><th class="text-end">TAUX</th><th class="text-end">COMMISSION</th><th>STATUT</th></tr></thead>
        <tbody>
            @forelse ($commissions as $commission)
                <tr>
                    @if ($payable)
                        <td>@if ($commission->status === \App\Enums\CommissionStatus::APayer)<input type="checkbox" class="form-check-input" name="ids[]" value="{{ $commission->id }}" form="{{ $formId }}" aria-label="Payer cette commission">@endif</td>
                    @endif
                    <td class="text-nowrap">{{ $commission->created_at->format('d/m/Y') }}</td>
                    <td>{{ $commission->label }}<div class="small text-muted">{{ $commission->kind->label() }}@if ($commission->notes) · {{ $commission->notes }}@endif</div></td>
                    <td class="text-end text-nowrap">{{ \App\Models\Materiel::money($commission->base_amount) }}</td>
                    <td class="text-end">{{ rtrim(rtrim(number_format($commission->rate, 2, ',', ''), '0'), ',') }} %</td>
                    <td class="text-end text-nowrap fw-semibold">{{ \App\Models\Materiel::money($commission->amount) }}</td>
                    <td>
                        <span class="badge {{ $commission->status->badge() }}">{{ $commission->status->label() }}</span>
                        @if ($commission->paid_at)<div class="small text-muted">{{ $commission->paid_at->format('d/m/Y') }} · {{ $commission->payment_reference }}</div>@endif
                        @if ($payable && $commission->status === \App\Enums\CommissionStatus::APayer)
                            <form method="POST" action="{{ route('commissions.cancel', $commission) }}" class="d-inline js-cancel-commission">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="reason" value="">
                                <button type="submit" class="btn btn-link btn-sm text-danger p-0 ms-1">Annuler</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ $payable ? 7 : 6 }}" class="text-muted small">Aucune commission pour l'instant.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
