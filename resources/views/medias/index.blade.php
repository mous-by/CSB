@extends('layouts.admin')

@section('title', 'Médias')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Contenus du site</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Médias</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 text-white"><i class='bx bx-images me-2'></i>BIBLIOTHÈQUE D'IMAGES</h6>
            @can('create', \App\Models\Media::class)
                <button type="button" class="btn btn-light btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#uploadMediaModal">
                    <i class='bx bx-upload'></i> Téléverser
                </button>
            @endcan
        </div>
        <div class="card-body">
            @php
                $used = $usages->filter()->count();
            @endphp
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div class="nv-chips" id="medias-filter">
                    <button type="button" class="nv-chip active" data-token="">Toutes <b>{{ $medias->count() }}</b></button>
                    <button type="button" class="nv-chip" data-token="usage-oui">Utilisées <b>{{ $used }}</b></button>
                    <button type="button" class="nv-chip" data-token="usage-non">Inutilisées <b>{{ $medias->count() - $used }}</b></button>
                    <button type="button" class="nv-chip" data-token="sans-alt">Sans texte alternatif <b>{{ $medias->whereNull('alt')->count() }}</b></button>
                </div>
                <div style="min-width: 220px">
                    <select class="form-select form-select-sm" id="medias-folder-filter" aria-label="Filtrer par dossier">
                        <option value="">Tous les dossiers</option>
                        @foreach ($medias->pluck('folder')->filter()->unique()->sort() as $folder)
                            <option value="{{ $folder }}">{{ $folder }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table" id="medias-table">
                    <thead>
                        <tr>
                            <th>APERÇU</th>
                            <th>IMAGE</th>
                            <th>DOSSIER</th>
                            <th>FORMAT</th>
                            <th>UTILISATION</th>
                            <th width="12%">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($medias as $media)
                            @php
                                $usage = $usages[$media->id] ?? 0;
                            @endphp
                            <tr>
                                <td><img src="{{ $media->thumbUrl() }}" alt="{{ $media->altText() }}" loading="lazy" class="rounded border" style="width: 72px; height: 54px; object-fit: cover"></td>
                                <td class="cell-wrap text-break">
                                    <div class="fw-semibold">{{ $media->original_name }}</div>
                                    <div class="small {{ $media->alt ? 'text-muted' : 'text-warning' }}">{{ $media->alt ?? 'Sans texte alternatif' }}</div>
                                </td>
                                <td>{{ $media->folder ?? '—' }}</td>
                                <td class="small">{{ $media->width }} × {{ $media->height }}<br><span class="text-muted">{{ $media->sizeForDisplay() }}</span></td>
                                <td>
                                    <span class="d-none">{{ $usage ? 'usage-oui' : 'usage-non' }}{{ $media->alt ? '' : ' sans-alt' }}</span>
                                    @if ($usage)
                                        <span class="badge bg-success-subtle">{{ $usage }} contenu(s)</span>
                                    @else
                                        <span class="badge bg-secondary-subtle">Inutilisée</span>
                                    @endif
                                </td>
                                <td class="d-flex gap-1">
                                    <a href="{{ $media->url() }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm" title="Voir l'image"><i class='bx bx-show'></i></a>
                                    @can('update', $media)
                                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editMedia-{{ $media->id }}" title="Modifier"><i class='bx bx-edit'></i></button>
                                    @endcan
                                    @can('delete', $media)
                                        <form method="POST" action="{{ route('medias.destroy', $media) }}" class="js-confirm-delete" data-title="Supprimer cette image ?" data-name="{{ $media->original_name }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-primary btn-sm" title="Supprimer" @disabled($usage > 0)><i class='bx bx-trash'></i></button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <datalist id="media-folders">
        @foreach ($folders as $folder)
            <option value="{{ $folder }}">
        @endforeach
    </datalist>

    @can('create', \App\Models\Media::class)
        <div class="modal fade" id="uploadMediaModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" action="{{ route('medias.store') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title"><i class='bx bx-upload me-2'></i>Téléverser des images</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                        </div>
                        <div class="modal-body p-4">
                            @php
                                $uploadErrors = collect($errors->getMessages())->filter(fn ($messages, $key) => str_starts_with($key, 'fichiers'))->flatten()->unique();
                            @endphp
                            <div class="mb-3">
                                <label for="fichiers" class="form-label fw-semibold">Images <span class="text-danger">*</span></label>
                                <input type="file" class="form-control @if ($uploadErrors->isNotEmpty()) is-invalid @endif" id="fichiers" name="fichiers[]" accept="image/jpeg,image/png,image/webp" multiple required>
                                @foreach ($uploadErrors as $message)
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @endforeach
                                <div class="form-text">JPEG, PNG ou WebP, {{ (int) (config('medias.max_kilobytes') / 1024) }} Mo au plus par image, 20 images par envoi. Les images sont redimensionnées ({{ config('medias.max_width') }} px de large au plus) et leurs métadonnées retirées.</div>
                            </div>
                            <div class="mb-3">
                                <label for="folder" class="form-label fw-semibold">Dossier</label>
                                <input type="text" class="form-control" id="folder" name="folder" list="media-folders" maxlength="60" value="{{ old('folder') }}" placeholder="Logiciels">
                            </div>
                            <div>
                                <label for="alt" class="form-label fw-semibold">Texte alternatif</label>
                                <input type="text" class="form-control" id="alt" name="alt" maxlength="255" value="{{ old('alt') }}" placeholder="Capture de l'écran d'accueil de KalanNet">
                                <div class="form-text">Décrit l'image pour les lecteurs d'écran et les moteurs de recherche. Modifiable ensuite image par image.</div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary"><i class='bx bx-upload me-1'></i>Téléverser</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @if ($uploadErrors->isNotEmpty())
            <script>
                window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('uploadMediaModal')).show());
            </script>
        @endif
    @endcan

    @foreach ($medias as $media)
        @can('update', $media)
            <div class="modal fade" id="editMedia-{{ $media->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('medias.update', $media) }}">
                            @csrf
                            @method('PUT')
                            <div class="modal-header">
                                <h5 class="modal-title"><i class='bx bx-image me-2'></i>{{ $media->original_name }}</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                            </div>
                            <div class="modal-body p-4">
                                <div class="nv-panel text-center mb-3">
                                    <img src="{{ $media->thumbUrl() }}" alt="{{ $media->altText() }}" style="max-height: 180px; max-width: 100%">
                                </div>
                                <div class="mb-3">
                                    <label for="alt-{{ $media->id }}" class="form-label fw-semibold">Texte alternatif</label>
                                    <input type="text" class="form-control" id="alt-{{ $media->id }}" name="alt" maxlength="255" value="{{ $media->alt }}">
                                </div>
                                <div>
                                    <label for="folder-{{ $media->id }}" class="form-label fw-semibold">Dossier</label>
                                    <input type="text" class="form-control" id="folder-{{ $media->id }}" name="folder" list="media-folders" maxlength="60" value="{{ $media->folder }}">
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                                <button type="submit" class="btn btn-primary">Enregistrer</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    @endforeach
@endsection

@push('scripts')
    <script>
        const mediasTable = $('#medias-table').DataTable({ scrollX: false, order: [], columnDefs: [{ orderable: false, targets: [0, 5] }] });

        document.querySelectorAll('#medias-filter .nv-chip').forEach((chip) => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('#medias-filter .nv-chip').forEach((other) => other.classList.toggle('active', other === chip));
                mediasTable.column(4).search(chip.dataset.token || '', false, true).draw();
            });
        });
        $('#medias-folder-filter').on('change', function () {
            mediasTable.column(2).search(this.value ? '^' + $.fn.dataTable.util.escapeRegex(this.value) + '$' : '', true, false).draw();
        });
    </script>
    @include('partials.confirm-delete')
@endpush
