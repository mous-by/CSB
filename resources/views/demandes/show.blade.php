@extends('layouts.admin')

@section('title', 'Demande '.$demande->reference())

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Commercial</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('demandes.index') }}">Demandes</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $demande->reference() }}</li>
                </ol>
            </nav>
        </div>
        <div class="ms-auto d-flex flex-wrap gap-2">
            @if ($demande->email)
                <a href="mailto:{{ $demande->email }}?subject={{ rawurlencode('Votre demande '.$demande->reference()) }}" class="btn btn-primary btn-sm"><i class='bx bx-envelope me-1'></i>Répondre par e-mail</a>
            @endif
            @if ($demande->phone && \App\Support\PhoneNumber::normalize($demande->phone))
                <a href="tel:{{ \App\Support\PhoneNumber::normalize($demande->phone) }}" class="btn btn-outline-primary btn-sm"><i class='bx bx-phone me-1'></i>Appeler</a>
            @endif
        </div>
    </div>
    <hr />

    @if ($demande->notification_error)
        <div class="alert alert-warning d-flex flex-wrap align-items-center gap-2">
            <i class='bx bx-error fs-4'></i>
            <div class="flex-grow-1"><strong>Notification e-mail non envoyée.</strong> {{ $demande->notification_error }}</div>
            @can('update', $demande)
                <form method="POST" action="{{ route('demandes.notify', $demande) }}">@csrf<button class="btn btn-sm btn-warning" type="submit"><i class='bx bx-refresh me-1'></i>Réessayer</button></form>
            @endcan
        </div>
    @endif

    <div class="row">
        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h6 class="mb-0 text-white"><i class='{{ $demande->type->icon() }} me-2'></i>{{ mb_strtoupper($demande->type->label()) }}</h6>
                    <span class="badge {{ $demande->status->badge() }}">{{ $demande->status->label() }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="nv-panel h-100">
                                <div class="nv-panel-title">Demandeur</div>
                                <dl class="nv-dl">
                                    <dt>Nom</dt><dd>{{ $demande->name }}</dd>
                                    <dt>Organisation</dt><dd>{{ $demande->organization ?? '—' }}</dd>
                                    <dt>E-mail</dt><dd class="text-break">{{ $demande->email ?? '—' }}</dd>
                                    <dt>Téléphone</dt><dd>{{ $demande->phone ?? '—' }}</dd>
                                </dl>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="nv-panel h-100">
                                <div class="nv-panel-title">Demande</div>
                                <dl class="nv-dl">
                                    <dt>Reçue le</dt><dd>{{ $demande->created_at->format('d/m/Y à H:i') }}</dd>
                                    @if ($demande->subject_label)
                                        <dt>Concerne</dt>
                                        <dd>
                                            @if ($demande->subjectModel && $demande->subjectModel->is_published)
                                                <a href="{{ $demande->subjectModel instanceof \App\Models\Logiciel ? route('site.logiciels.show', $demande->subjectModel->slug) : route('site.materiels.show', $demande->subjectModel->slug) }}" target="_blank" rel="noopener">{{ $demande->subject_label }}</a>
                                            @else
                                                {{ $demande->subject_label }}
                                            @endif
                                        </dd>
                                    @endif
                                    @if ($demande->need_type)<dt>Besoin</dt><dd>{{ $demande->need_type->label() }}</dd>@endif
                                    @if ($demande->budget)<dt>Budget</dt><dd>{{ $demande->budget }}</dd>@endif
                                    @if ($demande->deadline)<dt>Délai</dt><dd>{{ $demande->deadline }}</dd>@endif
                                    @if ($demande->availability)<dt>Disponibilités</dt><dd>{{ $demande->availability }}</dd>@endif
                                    <dt>Consentement</dt><dd>{{ $demande->consent_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                    @if ($demande->subject)
                        <h6 class="mt-4">{{ $demande->subject }}</h6>
                    @endif
                    @if ($demande->message)
                        <div class="nv-panel mt-3 text-break">{!! nl2br(e($demande->message)) !!}</div>
                    @endif
                    @if ($demande->attachment_path)
                        <a href="{{ route('demandes.attachment', $demande) }}" class="btn btn-outline-primary btn-sm mt-3"><i class='bx bx-paperclip me-1'></i>{{ $demande->attachment_name }} ({{ \Illuminate\Support\Number::fileSize((int) $demande->attachment_size, precision: 1) }})</a>
                    @endif
                    @if ($demande->source_url)
                        <p class="small text-muted mt-3 mb-0 text-break">Envoyée depuis : {{ $demande->source_url }}</p>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header card-header-brand d-flex align-items-center justify-content-between">
                    <h6 class="mb-0 text-white"><i class='bx bx-note me-2'></i>NOTES INTERNES <span class="badge bg-light text-dark ms-1" id="notes-count">{{ $demande->notes->count() }}</span></h6>
                </div>
                <div class="card-body">
                    <p class="small text-muted">Visibles uniquement par l'équipe : échanges avec le prospect, relances, informations utiles.</p>
                    @can('update', $demande)
                        <form method="POST" action="{{ route('demandes.notes.store', $demande) }}" id="note-form" class="mb-3">
                            @csrf
                            <label for="note-body" class="visually-hidden">Nouvelle note</label>
                            <textarea class="form-control @error('body') is-invalid @enderror" id="note-body" name="body" rows="3" maxlength="3000" placeholder="Ajouter une note…" required></textarea>
                            @error('body')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="d-flex justify-content-end mt-2">
                                <button type="submit" class="btn btn-primary btn-sm"><i class='bx bx-plus me-1'></i>Ajouter la note</button>
                            </div>
                        </form>
                    @endcan
                    <div id="notes-list">
                        @forelse ($demande->notes as $note)
                            @include('demandes._note', ['note' => $note])
                        @empty
                            <p class="text-muted small mb-0" id="notes-empty">Aucune note pour l'instant.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            @can('update', $demande)
                <div class="card">
                    <div class="card-header card-header-brand">
                        <h6 class="mb-0 text-white"><i class='bx bx-transfer-alt me-2'></i>TRAITEMENT</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('demandes.status', $demande) }}">
                            @csrf
                            @method('PUT')
                            <label for="status" class="form-label fw-semibold">Statut</label>
                            <select class="form-select mb-2" id="status" name="status">
                                @foreach (\App\Enums\DemandeStatus::cases() as $case)
                                    <option value="{{ $case->value }}" @selected($demande->status === $case) title="{{ $case->meaning() }}">{{ $case->label() }}</option>
                                @endforeach
                            </select>
                            <div id="closed-reason-group" class="{{ $demande->status === \App\Enums\DemandeStatus::Perdue ? '' : 'd-none' }}">
                                <label for="closed_reason" class="form-label fw-semibold">Motif (facultatif)</label>
                                <textarea class="form-control mb-2" id="closed_reason" name="closed_reason" rows="2" maxlength="500" placeholder="Budget insuffisant, sans réponse, doublon…">{{ $demande->closed_reason }}</textarea>
                            </div>
                            <p class="small text-muted" id="status-meaning">{{ $demande->status->meaning() }}</p>
                            <button type="submit" class="btn btn-primary w-100"><i class='bx bx-check me-1'></i>Mettre à jour le statut</button>
                        </form>
                        <hr>
                        <form method="POST" action="{{ route('demandes.assign', $demande) }}">
                            @csrf
                            @method('PUT')
                            <label for="assigned_to" class="form-label fw-semibold">Suivie par</label>
                            <select class="form-select single-select mb-2" id="assigned_to" name="assigned_to">
                                <option value="">— Personne —</option>
                                @foreach ($assignees as $user)
                                    <option value="{{ $user->id }}" @selected($demande->assigned_to === $user->id)>{{ $user->displayName() }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-outline-primary w-100">Attribuer</button>
                        </form>
                    </div>
                </div>
            @endcan

            <div class="card">
                <div class="card-header card-header-brand">
                    <h6 class="mb-0 text-white"><i class='bx bx-history me-2'></i>HISTORIQUE</h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled small mb-0">
                        @foreach ($history as $log)
                            <li class="mb-2 pb-2 border-bottom">
                                <div class="text-muted">{{ $log->created_at->format('d/m/Y H:i') }} · {{ $log->isBySuperadmin() && ! auth()->user()->isSuperadmin() ? config('brand.system_actor') : ($log->user_name ?? 'Site public') }}</div>
                                <div>{{ $log->description }}</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            @can('delete', $demande)
                <form method="POST" action="{{ route('demandes.destroy', $demande) }}" class="js-confirm-delete" data-title="Supprimer cette demande ?" data-name="La demande {{ $demande->reference() }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger w-100"><i class='bx bx-trash me-1'></i>Supprimer (spam, doublon)</button>
                </form>
            @endcan
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const meanings = @json(collect(\App\Enums\DemandeStatus::cases())->mapWithKeys(fn ($s) => [$s->value => $s->meaning()]));
            const status = document.getElementById('status');
            if (status) {
                status.addEventListener('change', () => {
                    document.getElementById('closed-reason-group').classList.toggle('d-none', status.value !== 'perdue');
                    document.getElementById('status-meaning').textContent = meanings[status.value] || '';
                });
            }

            // Note interne ajoutée sans rechargement (AJAX) ; sans JavaScript, le formulaire s'envoie normalement.
            const form = document.getElementById('note-form');
            if (!form) return;
            form.addEventListener('submit', (event) => {
                event.preventDefault();
                const body = form.querySelector('textarea');
                if (!body.value.trim()) { body.focus(); return; }
                const button = form.querySelector('button[type=submit]');
                button.disabled = true;
                $.ajax({ url: form.action, method: 'POST', data: $(form).serialize(), headers: { Accept: 'application/json' } })
                    .done((data) => {
                        document.getElementById('notes-empty')?.remove();
                        document.getElementById('notes-list').insertAdjacentHTML('afterbegin', data.html);
                        document.getElementById('notes-count').textContent = data.count;
                        body.value = '';
                        Swal.fire({ icon: 'success', text: 'Note ajoutée.', timer: 1500, showConfirmButton: false });
                    })
                    .fail((xhr) => Swal.fire({ icon: 'error', text: xhr.responseJSON?.message || 'La note n\'a pas été enregistrée.' }))
                    .always(() => { button.disabled = false; });
            });
        })();
    </script>
    @include('partials.confirm-delete')
@endpush
