@extends('layouts.site')

@section('content')
    @component('client._layout', ['title' => $ticket->number.' — '.$ticket->subject])
        <div class="detail-panel">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="tag {{ $ticket->status->tag() }}">{{ $ticket->status->label() }}</span>
                <span class="tag">{{ $ticket->category->label() }}</span>
                @if ($ticket->device)<span class="tag tag-muted">{{ $ticket->device }}</span>@endif
            </div>
            <div class="ticket-thread">
                @foreach ($ticket->publicMessages as $message)
                    <div class="ticket-msg {{ $message->fromClient() ? 'from-client' : 'from-team' }}">
                        <div class="ticket-msg-head">
                            <strong>{{ $message->fromClient() ? 'Vous' : 'Équipe CSB'.($message->author ? ' — '.$message->author->displayName() : '') }}</strong>
                            <span>{{ $message->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="ticket-msg-body">{!! nl2br(e($message->body)) !!}</div>
                        @if ($message->attachment_path)
                            <a href="{{ route('client.tickets.attachment', [$ticket, $message]) }}" class="small"><i class='bx bx-paperclip' aria-hidden="true"></i> {{ $message->attachment_name }}</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        <div class="detail-panel">
            <h2 class="h6">Répondre</h2>
            @if (! $ticket->status->isOpen())
                <p class="small text-muted">Ce ticket est {{ mb_strtolower($ticket->status->label()) }}. Si le problème revient, répondez ci-dessous : il sera rouvert.</p>
            @endif
            <form method="POST" action="{{ route('client.tickets.reply', $ticket) }}" enctype="multipart/form-data" novalidate>
                @csrf
                <label for="f-body" class="visually-hidden">Votre message</label>
                <textarea class="form-control mb-2 @error('body') is-invalid @enderror" id="f-body" name="body" rows="4" maxlength="10000" required placeholder="Votre message…">{{ old('body') }}</textarea>
                @error('body')<div class="invalid-feedback d-block mb-2">{{ $message }}</div>@enderror
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <input type="file" class="form-control form-control-sm w-auto @error('attachment') is-invalid @enderror" name="attachment" aria-label="Pièce jointe">
                    <button type="submit" class="btn btn-primary ms-auto"><i class='bx bx-send' aria-hidden="true"></i> Envoyer</button>
                </div>
                @error('attachment')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </form>
        </div>
    @endcomponent
@endsection
