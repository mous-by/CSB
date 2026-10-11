<div class="nv-panel mb-2 demande-note">
    <div class="d-flex justify-content-between small text-muted mb-1">
        <span><i class='bx bx-user me-1'></i>{{ $note->author?->displayName() ?? '—' }}</span>
        <span>{{ $note->created_at->format('d/m/Y H:i') }}</span>
    </div>
    <div class="text-break">{!! nl2br(e($note->body)) !!}</div>
</div>
