{{-- Modale de création ($item null) ou de modification d'un partenaire. $accounts : comptes du rôle Communication. --}}
@php
    $modalId = $item ? 'edit-partenaire-'.$item->id : 'create-partenaire';
    $failed = $errors->any() && ($item ? old('partenaire_id') == $item->id : (old('_form') === 'partenaire' && ! old('partenaire_id')));
    $v = fn (string $field, $default = null) => $failed ? old($field, $default) : ($item ? ($item->{$field} instanceof \Carbon\CarbonInterface ? $item->{$field}->format('Y-m-d') : $item->{$field}) : $default);
    $e = fn (string $field) => $failed && $errors->has($field) ? 'is-invalid' : '';
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ $item ? route('partenaires.update', $item) : route('partenaires.store') }}">
                @csrf
                <input type="hidden" name="_form" value="partenaire">
                @if ($item)
                    @method('PUT')
                    <input type="hidden" name="partenaire_id" value="{{ $item->id }}">
                @endif
                <div class="modal-header">
                    <h5 class="modal-title"><i class='bx bx-share-alt me-2'></i>{{ $item ? 'Modifier — '.$item->name : 'Nouveau partenaire' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body p-4">
                    @if ($failed)
                        <div class="alert alert-danger py-2 small">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
                    @endif
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="p-name{{ $item?->id }}">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control {{ $e('name') }}" id="p-name{{ $item?->id }}" name="name" value="{{ $v('name') }}" maxlength="150" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="p-ref{{ $item?->id }}">Code du lien <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text small">?ref=</span>
                                <input type="text" class="form-control {{ $e('ref_code') }}" id="p-ref{{ $item?->id }}" name="ref_code" value="{{ $v('ref_code') }}" maxlength="40" required placeholder="hamara">
                            </div>
                            <div class="form-text">Lettres minuscules, chiffres, tirets.</div>
                        </div>

                        <div class="col-12"><hr class="my-1"><h6 class="mb-0"><i class='bx bx-wallet me-1'></i>Taux de commission (% du montant encaissé)</h6></div>
                        @foreach (\App\Enums\CommissionKind::cases() as $kind)
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" for="p-{{ $kind->rateColumn() }}{{ $item?->id }}">{{ $kind->label() }}</label>
                                <div class="input-group">
                                    <input type="number" step="0.01" min="0" max="100" class="form-control {{ $e($kind->rateColumn()) }}" id="p-{{ $kind->rateColumn() }}{{ $item?->id }}" name="{{ $kind->rateColumn() }}" value="{{ $v($kind->rateColumn(), 0) }}" required>
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        @endforeach
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="p-cookie{{ $item?->id }}">Durée d'attribution</label>
                            <div class="input-group">
                                <input type="number" min="1" max="365" class="form-control {{ $e('cookie_days') }}" id="p-cookie{{ $item?->id }}" name="cookie_days" value="{{ $v('cookie_days', 30) }}" required>
                                <span class="input-group-text">jours</span>
                            </div>
                            <div class="form-text">Une vente faite dans ce délai après un clic sur son lien lui est attribuée.</div>
                        </div>

                        <div class="col-12"><hr class="my-1"><h6 class="mb-0"><i class='bx bx-purchase-tag me-1'></i>Code promo (facultatif)</h6></div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="p-promo{{ $item?->id }}">Code</label>
                            <input type="text" class="form-control text-uppercase {{ $e('promo_code') }}" id="p-promo{{ $item?->id }}" name="promo_code" value="{{ $v('promo_code') }}" maxlength="30" placeholder="HAMARA10">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="p-discount{{ $item?->id }}">Remise</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="1" max="90" class="form-control {{ $e('promo_discount') }}" id="p-discount{{ $item?->id }}" name="promo_discount" value="{{ $v('promo_discount') }}">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold" for="p-ends{{ $item?->id }}">Valable jusqu'au</label>
                            <input type="date" class="form-control" id="p-ends{{ $item?->id }}" name="promo_ends_on" value="{{ $v('promo_ends_on') }}">
                        </div>

                        <div class="col-12"><hr class="my-1"><h6 class="mb-0"><i class='bx bx-user me-1'></i>Compte et coordonnées</h6></div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="p-user{{ $item?->id }}">Compte de l'administration</label>
                            <select class="form-select {{ $e('user_id') }}" id="p-user{{ $item?->id }}" name="user_id">
                                <option value="">— Aucun —</option>
                                @foreach ($accounts as $account)
                                    <option value="{{ $account->id }}" @selected((int) $v('user_id') === $account->id)>{{ $account->displayName() }}</option>
                                @endforeach
                            </select>
                            <div class="form-text">Compte au rôle « Communication » (Paramètres → Utilisateurs) : il voit son espace et son kit.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="p-pay{{ $item?->id }}">Paiement des commissions</label>
                            <input type="text" class="form-control" id="p-pay{{ $item?->id }}" name="payment_info" value="{{ $v('payment_info') }}" maxlength="200" placeholder="Orange Money 7x xx xx xx">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="p-phone{{ $item?->id }}">Téléphone</label>
                            <input type="tel" class="form-control" id="p-phone{{ $item?->id }}" name="phone" value="{{ $v('phone') }}" maxlength="30">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="p-email{{ $item?->id }}">E-mail</label>
                            <input type="email" class="form-control {{ $e('email') }}" id="p-email{{ $item?->id }}" name="email" value="{{ $v('email') }}" maxlength="150">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="p-notes{{ $item?->id }}">Notes internes</label>
                            <textarea class="form-control" id="p-notes{{ $item?->id }}" name="notes" rows="2" maxlength="2000">{{ $v('notes') }}</textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" value="1" id="p-active{{ $item?->id }}" name="is_active" @checked($v('is_active', true))>
                                <label class="form-check-label" for="p-active{{ $item?->id }}">Actif (lien et code promo valables)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class='bx bx-save me-1'></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
