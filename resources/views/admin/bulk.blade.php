@extends('layouts.admin')

@section('title', 'Saisie en lot — '.$definition->label())

{{-- Saisie en masse (App\Services\Bulk) : grille « ajouter des lignes » (dupliquer, supprimer, coller un tableau copié
     depuis Excel), import de fichier en tout ou rien et modèle Excel. Un seul enregistrement crée toutes les lignes. --}}
@section('content')
    @php
        $oldRows = old('rows');
        $rows = is_array($oldRows) && $oldRows !== [] ? $oldRows : array_fill(0, 3, []);
        $nextIndex = (int) collect(array_keys($rows))->max() + 1;
        $routeParams = $definition->module() === 'categories' ? ['type' => 'logiciel'] : [];
        $inputName = fn ($index, $key) => "rows[{$index}][{$key}]";
    @endphp

    <div class="page-breadcrumb d-flex flex-wrap gap-2 align-items-center mb-3">
        <div class="breadcrumb-title pe-3">Contenus du site</div>
        <div class="ps-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 p-0">
                    <li class="breadcrumb-item"><a href="{{ route('home') }}"><i class="bx bx-home-alt"></i></a></li>
                    <li class="breadcrumb-item"><a href="{{ route($definition->indexRoute(), $routeParams) }}">{{ ucfirst($definition->label()) }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Saisie en lot</li>
                </ol>
            </nav>
        </div>
    </div>
    <hr />

    @if (session('import_errors'))
        <div class="alert alert-danger">
            <div class="fw-semibold mb-2"><i class='bx bx-error-circle me-1'></i>Import refusé — rien n'a été enregistré. Erreurs à corriger dans le fichier :</div>
            <div class="table-responsive" style="max-height: 320px">
                <table class="table table-sm mb-0">
                    <thead><tr><th>LIGNE</th><th>COLONNE</th><th>ERREUR</th></tr></thead>
                    <tbody>
                        @foreach (session('import_errors') as $importError)
                            <tr><td>{{ $importError['line'] ?: '—' }}</td><td>{{ $importError['column'] }}</td><td class="text-break" style="white-space: normal">{{ $importError['message'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header card-header-brand d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h6 class="mb-0 text-white"><i class='{{ $definition->icon() }} me-2'></i>SAISIE EN LOT — {{ mb_strtoupper($definition->label()) }}</h6>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route($definition->module().'.import.template') }}" class="btn btn-light btn-sm d-flex align-items-center gap-1"><i class='bx bx-download'></i> Modèle Excel</a>
                <button type="button" class="btn btn-light btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#importModal"><i class='bx bx-spreadsheet'></i> Importer un fichier</button>
            </div>
        </div>
        <div class="card-body">
            <div class="nv-panel mb-3 small">
                <i class='bx bx-bulb text-warning me-1'></i>
                Remplissez une ligne par élément, ajoutez autant de lignes que nécessaire, puis enregistrez tout en une fois.
                <strong>Astuce :</strong> copiez des cellules dans Excel et collez-les dans une case de la grille : les lignes et colonnes se remplissent automatiquement (dans l'ordre des colonnes ci-dessous).
                Raccourci <kbd>Alt</kbd> + <kbd>N</kbd> : nouvelle ligne.
            </div>
            @error('rows')<div class="alert alert-danger py-2">{{ $message }}</div>@enderror
            @if ($errors->any() && ! $errors->has('rows'))
                <div class="alert alert-danger py-2"><i class='bx bx-error-circle me-1'></i>Rien n'a été enregistré : corrigez les cases signalées en rouge ({{ count($errors->all()) }} erreur(s)).</div>
            @endif

            <form method="POST" action="{{ route($definition->module().'.bulk.store') }}" id="bulk-form">
                @csrf
                <div class="table-responsive bulk-grid-wrap">
                    <table class="table table-sm align-middle bulk-grid" id="bulk-grid">
                        <thead>
                            <tr>
                                <th style="width: 40px">#</th>
                                @foreach ($columns as $key => $column)
                                    <th style="min-width: {{ ($column['type'] ?? '') === 'bool' ? 70 : max(120, ($column['width'] ?? 20) * 7) }}px">
                                        {{ mb_strtoupper($column['label']) }}@if ($column['required'] ?? false) <span class="text-danger">*</span>@endif
                                    </th>
                                @endforeach
                                <th style="width: 80px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rows as $index => $row)
                                @include('admin._bulk-row', ['index' => $index, 'row' => $row])
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <template id="bulk-row-template">
                    @include('admin._bulk-row', ['index' => '__INDEX__', 'row' => [], 'template' => true])
                </template>

                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" data-add-rows="1"><i class='bx bx-plus'></i> Ajouter une ligne</button>
                        <button type="button" class="btn btn-outline-primary btn-sm" data-add-rows="5"><i class='bx bx-list-plus'></i> Ajouter 5 lignes</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="bulk-clear-empty"><i class='bx bx-eraser'></i> Retirer les lignes vides</button>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <span class="text-muted small" id="bulk-count"></span>
                        <a href="{{ route($definition->indexRoute(), $routeParams) }}" class="btn btn-secondary">Annuler</a>
                        <button type="submit" class="btn btn-primary"><i class='bx bx-save me-1'></i>Tout enregistrer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="importModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="{{ route($definition->module().'.import') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title"><i class='bx bx-spreadsheet me-2'></i>Importer des {{ $definition->label() }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body p-4">
                        <ol class="small ps-3">
                            <li>Téléchargez le modèle : ses listes déroulantes reprennent les valeurs de l'application.</li>
                            <li>Remplissez une ligne par élément (mode d'emploi dans le fichier).</li>
                            <li>Envoyez le fichier : s'il contient une erreur, rien n'est importé et les erreurs vous sont listées.</li>
                        </ol>
                        <a href="{{ route($definition->module().'.import.template') }}" class="btn btn-outline-primary btn-sm mb-3"><i class='bx bx-download me-1'></i>Télécharger le modèle Excel</a>
                        <label for="fichier" class="form-label fw-semibold d-block">Fichier rempli (.xlsx, .xls, .ods ou .csv)</label>
                        <input type="file" class="form-control @error('fichier') is-invalid @enderror" id="fichier" name="fichier" accept=".xlsx,.xls,.ods,.csv" required>
                        @error('fichier')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary"><i class='bx bx-upload me-1'></i>Importer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @error('fichier')
        <script>window.addEventListener('DOMContentLoaded', () => new bootstrap.Modal(document.getElementById('importModal')).show());</script>
    @enderror
@endsection

@push('styles')
    <style>
        .bulk-grid th { white-space: nowrap; font-size: .72rem; }
        .bulk-grid td { vertical-align: top; }
        .bulk-grid .form-control, .bulk-grid .form-select { font-size: .85rem; padding: .3rem .5rem; min-width: 100%; }
        .bulk-grid textarea.form-control { min-height: 34px; resize: vertical; }
        .bulk-grid .row-num { color: #8a94a6; font-weight: 600; padding-top: .55rem; }
        .bulk-grid tr.row-flash td { animation: bulkFlash 1s ease; }
        @keyframes bulkFlash { from { background: rgba(245, 130, 32, .18); } to { background: transparent; } }
        .bulk-grid-wrap { max-height: 65vh; overflow: auto; }
        .bulk-grid thead th { position: sticky; top: 0; z-index: 2; background: #f7f9fc; }
    </style>
@endpush

@push('scripts')
    <script>
        // Grille de saisie en lot : ajout, duplication et suppression de lignes, collage d'un tableau Excel, compteur.
        (function () {
            const tbody = document.querySelector('#bulk-grid tbody');
            const template = document.getElementById('bulk-row-template').innerHTML;
            const keys = @json(array_keys($columns));
            let next = {{ $nextIndex }};

            const addRow = (focus) => {
                const index = next++;
                tbody.insertAdjacentHTML('beforeend', template.replaceAll('__INDEX__', index));
                const row = tbody.lastElementChild;
                row.classList.add('row-flash');
                renumber();
                if (focus) row.querySelector('input:not([type=hidden]), select, textarea')?.focus();
                return row;
            };
            const renumber = () => {
                [...tbody.rows].forEach((row, i) => row.querySelector('.row-num').textContent = i + 1);
                const filled = [...tbody.rows].filter((row) => isFilled(row)).length;
                document.getElementById('bulk-count').textContent = filled + ' ligne(s) remplie(s) sur ' + tbody.rows.length;
            };
            const isFilled = (row) => [...row.querySelectorAll('input[type=text], input[type=number], textarea')].some((el) => el.value.trim() !== '');

            document.querySelectorAll('[data-add-rows]').forEach((btn) => btn.addEventListener('click', () => {
                for (let i = 0; i < Number(btn.dataset.addRows); i++) addRow(i === 0);
            }));
            document.getElementById('bulk-clear-empty').addEventListener('click', () => {
                [...tbody.rows].forEach((row) => { if (!isFilled(row) && tbody.rows.length > 1) row.remove(); });
                renumber();
            });
            tbody.addEventListener('click', (e) => {
                const remove = e.target.closest('[data-row-remove]');
                const copy = e.target.closest('[data-row-copy]');
                if (remove) { if (tbody.rows.length > 1) remove.closest('tr').remove(); else remove.closest('tr').querySelectorAll('input[type=text], input[type=number], textarea').forEach((el) => el.value = ''); renumber(); }
                if (copy) {
                    const source = copy.closest('tr');
                    const row = addRow(false);
                    source.after(row);
                    keys.forEach((key) => {
                        const from = source.querySelector('[data-key="' + key + '"]');
                        const to = row.querySelector('[data-key="' + key + '"]');
                        if (!from || !to) return;
                        if (from.type === 'checkbox') to.checked = from.checked; else to.value = from.value;
                    });
                    renumber();
                }
            });
            tbody.addEventListener('input', renumber);

            // Collage d'un tableau (Excel, LibreOffice, Google Sheets) : tabulations = colonnes, retours = lignes.
            tbody.addEventListener('paste', (e) => {
                const text = (e.clipboardData || window.clipboardData).getData('text');
                if (!text || (!text.includes('\t') && !text.includes('\n'))) return;
                const cell = e.target.closest('[data-key]');
                if (!cell) return;
                e.preventDefault();
                const lines = text.replace(/\r/g, '').replace(/\n$/, '').split('\n').map((line) => line.split('\t'));
                let row = cell.closest('tr');
                const startCol = keys.indexOf(cell.dataset.key);
                lines.forEach((values, li) => {
                    if (li > 0) row = row.nextElementSibling || addRow(false);
                    values.forEach((value, ci) => {
                        const key = keys[startCol + ci];
                        const field = key && row.querySelector('[data-key="' + key + '"]');
                        if (!field) return;
                        value = value.trim();
                        if (field.type === 'checkbox') { field.checked = /^(1|oui|vrai|x|true|yes)$/i.test(value); return; }
                        if (field.tagName === 'SELECT') {
                            const option = [...field.options].find((o) => o.value.toLowerCase() === value.toLowerCase() || o.text.trim().toLowerCase() === value.toLowerCase());
                            if (option) field.value = option.value;
                            return;
                        }
                        field.value = value;
                    });
                    row.classList.add('row-flash');
                });
                renumber();
            });

            document.addEventListener('keydown', (e) => {
                if (e.altKey && (e.key === 'n' || e.key === 'N')) { e.preventDefault(); addRow(true); }
            });
            renumber();
        })();
    </script>
@endpush
