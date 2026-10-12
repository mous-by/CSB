{{-- Champ d'envoi d'une vidéo par morceaux (public/assets/js/video-upload.js). $url (route qui reçoit les morceaux),
     $id (unique dans la page), $label, $help. Le jeton rendu à la fin est envoyé avec le formulaire (video_token). --}}
<div class="video-upload" data-video-upload data-url="{{ $url }}">
    <label for="{{ $id }}" class="form-label fw-semibold">{{ $label ?? 'Vidéo' }}</label>
    <input type="file" class="form-control @error('video_token') is-invalid @enderror" id="{{ $id }}" accept="video/mp4,video/quicktime,video/webm,video/3gpp,video/*">
    <input type="hidden" name="video_token" value="">
    <div class="progress mt-2" style="height: 8px" hidden>
        <div class="progress-bar progress-bar-striped progress-bar-animated" data-video-bar role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0" style="width: 0"></div>
    </div>
    <div class="d-flex align-items-center gap-2 mt-1">
        <span class="video-upload-status small text-muted" data-video-status aria-live="polite">{{ $help ?? 'MP4, MOV (iPhone) ou WebM, 300 Mo au plus. L\'envoi se fait en arrière-plan, même avec une connexion lente.' }}</span>
        <button type="button" class="btn btn-sm btn-link text-danger p-0 ms-auto" data-video-cancel hidden>Annuler</button>
    </div>
    @error('video_token')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    <video class="video-upload-preview mt-2" data-video-preview controls playsinline muted hidden></video>
</div>
