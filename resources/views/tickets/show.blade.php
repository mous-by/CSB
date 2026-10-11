@extends('layouts.admin')

@section('title', 'Ticket '.$ticket->number)

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Commercial</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route('tickets.index') }}">Tickets</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $ticket->number }}</li>
                </ol>
            </nav>
        </div>
        @if ($ticket->client?->phone && \App\Support\PhoneNumber::normalize($ticket->client->phone))
            <div class="ms-auto"><a href="tel:{{ \App\Support\PhoneNumber::normalize($ticket->client->phone) }}" class="btn btn-outline-primary btn-sm"><i class='bx bx-phone me-1'></i>Appeler le client</a></div>
        @endif
    </div>
    <hr />

    <div class="row">
        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h6 class="mb-0 text-white"><i class='bx bx-support me-2'></i>{{ mb_strtoupper($ticket->subject) }}</h6>
                    <span class="badge {{ $ticket->status->badge() }}">{{ $ticket->status->teamLabel() }}</span>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2 mb-3 small">
                        <span class="badge bg-light text-dark">{{ $ticket->category->label() }}</span>
                        <span class="badge {{ $ticket->priority->badge() }}">{{ $ticket->priority->label() }}</span>
                        @if ($ticket->device)<span class="badge bg-light text-dark"><i class='bx bx-devices me-1'></i>{{ $ticket->device }}</span>@endif
                    </div>
                    <div class="ticket-thread-admin">
                        @foreach ($ticket->messages as $message)
                            <div class="nv-panel mb-2 {{ $message->is_internal ? 'border-warning bg-warning-subtle' : ($message->fromClient() ? '' : 'border-primary-subtle bg-primary-subtle') }}">
                                <div class="d-flex justify-content-between flex-wrap gap-2 small mb-1">
                                    <strong>
                                        @if ($message->fromClient())
                                            <i class='bx bx-user me-1'></i>{{ $message->client?->name ?? 'Client' }}
                                        @else
                                            <i class='bx bx-headphone me-1'></i>{{ $message->author?->displayName() ?? 'Équipe' }}
                                            @if ($message->is_internal)<span class="badge bg-warning text-dark ms-1">Note interne</span>@endif
                                        @endif
                                    </strong>
                                    <span class="text-muted">{{ $message->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                                <div class="text-break">{!! nl2br(e($message->body)) !!}</div>
                                @if ($message->attachment_path)
                                    <a href="{{ route('tickets.attachment', [$ticket, $message]) }}" class="btn btn-outline-primary btn-sm mt-2"><i class='bx bx-paperclip me-1'></i>{{ $message->attachment_name }} ({{ \Illuminate\Support\Number::fileSize((int) $message->attachment_size, precision: 1) }})</a>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            @can('update', $ticket)
                <div class="card">
                    <div class="card-header card-header-brand"><h6 class="mb-0 text-white"><i class='bx bx-reply me-2'></i>RÉPONDRE</h6></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('tickets.reply', $ticket) }}" enctype="multipart/form-data">
                            @csrf
                            <label for="reply-body" class="visually-hidden">Message</label>
                            <textarea class="form-control mb-2 @error('body') is-invalid @enderror" id="reply-body" name="body" rows="5" maxlength="10000" required placeholder="Votre réponse au client…">{{ old('body') }}</textarea>
                            @error('body')<div class="invalid-feedback mb-2">{{ $message }}</div>@enderror
                            <div class="row g-2 align-items-end">
                                <div class="col-md-5">
                                    <label for="reply-status" class="form-label small fw-semibold">Statut après envoi</label>
                                    <select class="form-select form-select-sm" id="reply-status" name="status">
                                        <option value="">Inchangé ({{ $ticket->status->teamLabel() }})</option>
                                        @foreach (\App\Enums\TicketStatus::cases() as $case)
                                            <option value="{{ $case->value }}" @selected(old('status') === $case->value)>{{ $case->teamLabel() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-7">
                                    <input type="file" class="form-control form-control-sm @error('attachment') is-invalid @enderror" name="attachment" aria-label="Pièce jointe">
                                    @error('attachment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                </div>
                            </div>
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="reply-internal" name="internal" @checked(old('internal'))>
                                    <label class="form-check-label" for="reply-internal">Note interne (invisible du client, aucun e-mail)</label>
                                </div>
                                <button type="submit" class="btn btn-primary" id="reply-submit"><i class='bx bx-send me-1'></i>Envoyer au client</button>
                            </div>
                        </form>
                    </div>
                </div>
            @endcan
        </div>

        <div class="col-12 col-xl-4">
            <div class="card">
                <div class="card-header card-header-brand"><h6 class="mb-0 text-white"><i class='bx bx-user-pin me-2'></i>CLIENT</h6></div>
                <div class="card-body">
                    <dl class="nv-dl mb-0">
                        <dt>Nom</dt><dd>{{ $ticket->client?->name ?? '—' }}</dd>
                        <dt>Organisation</dt><dd>{{ $ticket->client?->organization ?? '—' }}</dd>
                        <dt>E-mail</dt><dd class="text-break">{{ $ticket->client?->email ?? '—' }}</dd>
                        <dt>Téléphone</dt><dd>{{ $ticket->client?->phone ?? '—' }}</dd>
                        <dt>Ouvert le</dt><dd>{{ $ticket->created_at->format('d/m/Y H:i') }}</dd>
                    </dl>
                    @if ($ticket->client && auth()->user()->can('clients.view') && Route::has('clients.show'))
                        <a href="{{ route('clients.show', $ticket->client) }}" class="btn btn-outline-primary btn-sm mt-2 w-100">Fiche du client</a>
                    @endif
                </div>
            </div>

            @can('update', $ticket)
                <div class="card">
                    <div class="card-header card-header-brand"><h6 class="mb-0 text-white"><i class='bx bx-transfer-alt me-2'></i>TRAITEMENT</h6></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('tickets.status', $ticket) }}">
                            @csrf
                            @method('PUT')
                            <label for="status" class="form-label fw-semibold">Statut</label>
                            <select class="form-select mb-2" id="status" name="status">
                                @foreach (\App\Enums\TicketStatus::cases() as $case)
                                    <option value="{{ $case->value }}" @selected($ticket->status === $case)>{{ $case->teamLabel() }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-primary w-100"><i class='bx bx-check me-1'></i>Mettre à jour le statut</button>
                            <p class="small text-muted mt-2 mb-0">Le client est prévenu par e-mail du changement.</p>
                        </form>
                        <hr>
                        <form method="POST" action="{{ route('tickets.update', $ticket) }}">
                            @csrf
                            @method('PUT')
                            <label for="priority" class="form-label fw-semibold">Priorité</label>
                            <select class="form-select mb-2" id="priority" name="priority">
                                @foreach (\App\Enums\TicketPriority::cases() as $case)
                                    <option value="{{ $case->value }}" @selected($ticket->priority === $case)>{{ $case->label() }}</option>
                                @endforeach
                            </select>
                            <label for="assigned_to" class="form-label fw-semibold">Suivi par</label>
                            <select class="form-select single-select mb-2" id="assigned_to" name="assigned_to">
                                <option value="">— Personne —</option>
                                @foreach ($assignees as $user)
                                    <option value="{{ $user->id }}" @selected($ticket->assigned_to === $user->id)>{{ $user->displayName() }}</option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-outline-primary w-100">Enregistrer</button>
                        </form>
                    </div>
                </div>
            @endcan

            <div class="card">
                <div class="card-header card-header-brand"><h6 class="mb-0 text-white"><i class='bx bx-history me-2'></i>HISTORIQUE</h6></div>
                <div class="card-body">
                    <ul class="list-unstyled small mb-0">
                        @foreach ($history as $log)
                            <li class="mb-2 pb-2 border-bottom">
                                <div class="text-muted">{{ $log->created_at->format('d/m/Y H:i') }} · {{ $log->isBySuperadmin() && ! auth()->user()->isSuperadmin() ? config('brand.system_actor') : ($log->user_name ?? 'Client') }}</div>
                                <div>{{ $log->description }}</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            @can('delete', $ticket)
                <form method="POST" action="{{ route('tickets.destroy', $ticket) }}" class="js-confirm-delete" data-title="Supprimer ce ticket ?" data-name="Le ticket {{ $ticket->number }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger w-100"><i class='bx bx-trash me-1'></i>Supprimer (doublon, erreur)</button>
                </form>
            @endcan
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const internal = document.getElementById('reply-internal');
            const submit = document.getElementById('reply-submit');
            if (!internal || !submit) return;
            const sync = () => { submit.innerHTML = internal.checked ? "<i class='bx bx-note me-1'></i>Ajouter la note interne" : "<i class='bx bx-send me-1'></i>Envoyer au client"; submit.classList.toggle('btn-warning', internal.checked); submit.classList.toggle('btn-primary', !internal.checked); };
            internal.addEventListener('change', sync);
            sync();
        })();
    </script>
    @include('partials.confirm-delete')
@endpush
