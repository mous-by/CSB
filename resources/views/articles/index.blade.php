@extends('layouts.admin')

@section('title', 'Actualités')

@section('content')
    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Contenus du site</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item active" aria-current="page">Actualités</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 text-white"><i class='bx bx-news me-2'></i>ACTUALITÉS ET CONSEILS</h6>
            <div class="d-flex flex-wrap gap-2">
                @can('viewAny', [\App\Models\Category::class, \App\Enums\CategoryType::Article])
                    <a href="{{ route('categories.index', 'article') }}" class="btn btn-light btn-sm d-flex align-items-center gap-1"><i class='bx bx-category'></i> Catégories</a>
                @endcan
                @can('create', \App\Models\Article::class)
                    <a href="{{ route('articles.create') }}" class="btn btn-light btn-sm d-flex align-items-center gap-1"><i class='bx bx-pencil'></i> Rédiger</a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            @php
                $state = fn ($a) => $a->isVisible() ? 'publie' : ($a->isScheduled() ? 'programme' : 'brouillon');
                $counts = $articles->countBy($state);
            @endphp
            <div class="nv-chips mb-3" id="articles-filter">
                <button type="button" class="nv-chip active" data-token="">Tous <b>{{ $articles->count() }}</b></button>
                <button type="button" class="nv-chip" data-token="etat-publie">Publiés <b>{{ $counts['publie'] ?? 0 }}</b></button>
                <button type="button" class="nv-chip" data-token="etat-programme">Programmés <b>{{ $counts['programme'] ?? 0 }}</b></button>
                <button type="button" class="nv-chip" data-token="etat-brouillon">Brouillons <b>{{ $counts['brouillon'] ?? 0 }}</b></button>
            </div>
            <div class="table-responsive">
                <table class="table" id="articles-table">
                    <thead>
                        <tr>
                            <th>ARTICLE</th>
                            <th>CATÉGORIE</th>
                            <th>AUTEUR</th>
                            <th>PUBLICATION</th>
                            <th width="16%">ACTIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($articles as $article)
                            <tr>
                                <td class="cell-wrap text-break">
                                    <div class="d-flex align-items-center gap-2">
                                        @if ($article->cover)
                                            <img src="{{ $article->cover->thumbUrl() }}" alt="" class="rounded border" style="width: 56px; height: 40px; object-fit: cover">
                                        @endif
                                        <div>
                                            <div class="fw-semibold">{{ $article->title }}</div>
                                            <div class="small text-muted">{{ \Illuminate\Support\Str::limit($article->excerptText(), 90) }}</div>
                                            {{-- Téléphone : la colonne Actions est hors de l'écran, « Publier » est repris ici. --}}
                                            @if (! $article->is_published)
                                                @can('publish', $article)
                                                    <div class="d-lg-none mt-2">@include('admin._publish-button', ['action' => route('articles.publication', $article), 'published' => false, 'label' => 'Publier'])</div>
                                                @endcan
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $article->category?->name ?? '—' }}</td>
                                <td>{{ $article->author?->displayName() ?? '—' }}</td>
                                <td data-order="{{ $article->published_at?->timestamp ?? 0 }}">
                                    <span class="d-none">etat-{{ $state($article) }}</span>
                                    @if ($article->isVisible())
                                        <span class="badge bg-success-subtle">Publié</span> <span class="small text-muted">{{ $article->published_at->format('d/m/Y') }}</span>
                                    @elseif ($article->isScheduled())
                                        <span class="badge bg-info-subtle">Programmé</span> <span class="small text-muted">{{ $article->published_at->format('d/m/Y H:i') }}</span>
                                    @else
                                        <span class="badge bg-secondary-subtle">Brouillon</span>
                                    @endif
                                </td>
                                <td class="d-flex gap-1">
                                    <a href="{{ route('articles.preview', $article) }}" target="_blank" rel="noopener" class="btn btn-primary btn-sm" title="Aperçu"><i class='bx bx-show'></i></a>
                                    @can('update', $article)
                                        <a href="{{ route('articles.edit', $article) }}" class="btn btn-primary btn-sm" title="Modifier"><i class='bx bx-edit'></i></a>
                                    @endcan
                                    @can('publish', $article)
                                        @include('admin._publish-button', ['action' => route('articles.publication', $article), 'published' => $article->is_published, 'label' => 'Publier'])
                                    @endcan
                                    @can('abonnes.send')
                                        @if ($article->isVisible() && ! $article->newsletter_sent_at)
                                            <form method="POST" action="{{ route('articles.newsletter', $article) }}" class="js-confirm-send" data-name="{{ $article->title }}">
                                                @csrf
                                                <button type="submit" class="btn btn-primary btn-sm" title="Envoyer aux abonnés"><i class='bx bx-send'></i></button>
                                            </form>
                                        @elseif ($article->newsletter_sent_at)
                                            <span class="btn btn-light btn-sm disabled" title="Envoyé aux abonnés le {{ $article->newsletter_sent_at->format('d/m/Y') }}"><i class='bx bx-check-double'></i></span>
                                        @endif
                                    @endcan
                                    @can('delete', $article)
                                        <form method="POST" action="{{ route('articles.destroy', $article) }}" class="js-confirm-delete" data-title="Supprimer cet article ?" data-name="{{ $article->title }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-primary btn-sm" title="Supprimer"><i class='bx bx-trash'></i></button>
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
@endsection

@push('scripts')
    <script>
        const articlesTable = $('#articles-table').DataTable({ scrollX: false, order: [] });
        document.querySelectorAll('#articles-filter .nv-chip').forEach((chip) => {
            chip.addEventListener('click', () => {
                document.querySelectorAll('#articles-filter .nv-chip').forEach((other) => other.classList.toggle('active', other === chip));
                articlesTable.column(3).search(chip.dataset.token || '', false, true).draw();
            });
        });
    </script>
    <script>
        $(document).on('submit', '.js-confirm-send', function (event) {
            event.preventDefault();
            const form = this;
            Swal.fire({ icon: 'question', title: 'Envoyer aux abonnés ?', text: '« ' + $(form).data('name') + ' » sera envoyé par e-mail à tous les abonnés actifs. Un article ne s\'envoie qu\'une fois.', showCancelButton: true, confirmButtonText: 'Envoyer', cancelButtonText: 'Annuler' })
                .then((result) => { if (result.isConfirmed) form.submit(); });
        });
    </script>
    @include('partials.confirm-delete')
@endpush
