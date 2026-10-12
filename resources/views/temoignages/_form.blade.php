{{-- Modale de création ($item null) ou de modification d'un témoignage. --}}
@php
    $modalId = $item ? "edit-temoignages-{$item->id}" : 'create-temoignages';
    $failed = $errors->any() && ($item ? old('temoignage_id') == $item->id : ! old('temoignage_id'));
    $sfx = $item ? '-'.$item->id : '';
    $v = fn (string $field, $default = null) => $failed ? old($field, $default) : ($item ? $item->{$field} : $default);
@endphp
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ $item ? route('temoignages.update', $item) : route('temoignages.store') }}" enctype="multipart/form-data">
                @csrf
                @if ($item)
                    @method('PUT')
                    <input type="hidden" name="temoignage_id" value="{{ $item->id }}">
                @endif
                <div class="modal-header">
                    <h5 class="modal-title"><i class='bx bx-message-square-dots me-2'></i>{{ $item ? 'Modifier — '.$item->author_name : 'Nouveau témoignage' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                </div>
                <div class="modal-body p-4">
                    @if ($item?->source === 'client')
                        <div class="alert alert-info small py-2">
                            Envoyé depuis le site le {{ $item->created_at->format('d/m/Y à H:i') }}{{ $item->consent_at ? ', avec l\'accord de publication de son auteur' : '' }}.
                            Contact (non publié) : <strong>{{ $item->contact }}</strong>.
                        </div>
                    @endif
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="author_name{{ $sfx }}">Nom de l'auteur <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @if ($failed) @error('author_name') is-invalid @enderror @endif" id="author_name{{ $sfx }}" name="author_name" value="{{ $v('author_name') }}" maxlength="150" required>
                            @if ($failed) @error('author_name')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="author_title{{ $sfx }}">Fonction</label>
                            <input type="text" class="form-control" id="author_title{{ $sfx }}" name="author_title" value="{{ $v('author_title') }}" maxlength="150" placeholder="Directeur">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold" for="organization{{ $sfx }}">Organisation</label>
                            <input type="text" class="form-control" id="organization{{ $sfx }}" name="organization" value="{{ $v('organization') }}" maxlength="150">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" for="rating{{ $sfx }}">Note</label>
                            <select class="form-select" id="rating{{ $sfx }}" name="rating">
                                <option value="">—</option>
                                @for ($i = 5; $i >= 1; $i--)
                                    <option value="{{ $i }}" @selected((int) $v('rating') === $i)>{{ str_repeat('★', $i) }} ({{ $i }}/5)</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold" for="sort_order{{ $sfx }}">Ordre</label>
                            <input type="number" class="form-control" id="sort_order{{ $sfx }}" name="sort_order" value="{{ $v('sort_order', 0) }}" min="0" max="9999" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold" for="content{{ $sfx }}">Témoignage écrit</label>
                            <textarea class="form-control @if ($failed) @error('content') is-invalid @enderror @endif" id="content{{ $sfx }}" name="content" rows="3" maxlength="1500">{{ $v('content') }}</textarea>
                            @if ($failed) @error('content')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                            <div class="form-text">Facultatif pour un témoignage vidéo (il s'affiche alors comme citation sous la vidéo).</div>
                        </div>

                        <div class="col-12"><hr class="my-1"><h6 class="mb-0"><i class='bx bx-video me-1'></i>Vidéo</h6></div>
                        @if ($item?->video_path)
                            <div class="col-12">
                                <div class="d-flex flex-wrap align-items-center gap-3 p-2 border rounded">
                                    @if ($item->hasVideo())
                                        <video src="{{ $item->videoUrl() }}" @if ($item->posterUrl()) poster="{{ $item->posterUrl() }}" @endif controls preload="none" playsinline style="width: 220px; border-radius: 8px; background: #000"></video>
                                    @elseif ($item->video_status === \App\Models\Temoignage::VIDEO_PROCESSING)
                                        <span class="badge bg-info-subtle"><i class='bx bx-loader-alt bx-spin me-1'></i>Vidéo en préparation…</span>
                                    @else
                                        <span class="badge bg-danger-subtle">Vidéo illisible : {{ \Illuminate\Support\Str::limit((string) $item->video_error, 120) }}</span>
                                    @endif
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" value="1" id="remove_video{{ $sfx }}" name="remove_video">
                                        <label class="form-check-label" for="remove_video{{ $sfx }}">Retirer cette vidéo</label>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="col-md-7">
                            @include('partials.video-upload', ['url' => route('temoignages.video'), 'id' => 'video'.$sfx, 'label' => $item?->video_path ? 'Remplacer par une autre vidéo' : 'Envoyer une vidéo'])
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold" for="video_url{{ $sfx }}">… ou lien vers la vidéo</label>
                            <input type="url" class="form-control @if ($failed) @error('video_url') is-invalid @enderror @endif" id="video_url{{ $sfx }}" name="video_url" value="{{ $v('video_url') }}" maxlength="500" placeholder="Lien YouTube, Facebook ou TikTok">
                            @if ($failed) @error('video_url')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                            <div class="form-text">YouTube, Facebook, TikTok : ouvert dans un nouvel onglet.</div>
                        </div>

                        <div class="col-12"><hr class="my-1"><h6 class="mb-0"><i class='bx bx-image me-1'></i>Photo de l'auteur</h6></div>
                        <div class="col-md-7">
                            <input type="file" class="form-control @if ($failed) @error('photo_upload') is-invalid @enderror @endif" name="photo_upload" accept="image/jpeg,image/png,image/webp" aria-label="Photo de l'auteur">
                            @if ($failed) @error('photo_upload')<div class="invalid-feedback">{{ $message }}</div>@enderror @endif
                            <div class="form-text">Sert aussi d'aperçu à une vidéo en lien.</div>
                        </div>
                        @if ($item?->photo)
                            <div class="col-md-5 d-flex align-items-center gap-2">
                                <img src="{{ $item->photo->thumbUrl() }}" alt="" width="48" height="48" class="rounded-circle object-fit-cover">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="1" id="remove_photo{{ $sfx }}" name="remove_photo">
                                    <label class="form-check-label" for="remove_photo{{ $sfx }}">Retirer la photo</label>
                                </div>
                            </div>
                        @endif

                        <div class="col-12"><hr class="my-1"></div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" value="1" id="is_published{{ $sfx }}" name="is_published" @checked($v('is_published', false))>
                                <label class="form-check-label" for="is_published{{ $sfx }}">Publié (accord de l'auteur obtenu)</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" value="1" id="is_featured{{ $sfx }}" name="is_featured" @checked($v('is_featured', false))>
                                <label class="form-check-label" for="is_featured{{ $sfx }}">Mis en avant (premier affiché)</label>
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
