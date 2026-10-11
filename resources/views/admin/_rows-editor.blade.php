{{-- Liste de lignes éditable dans un formulaire (« ajouter une ligne ») : fonctionnalités, caractéristiques, technologies.
     $name (champ), $label, $columns ([['key' => …, 'placeholder' => …, 'width' => …], …] ; une colonne sans clé = liste
     simple « name[] »), $values (liste de chaînes, ou de tableaux clé => valeur), $help, $id, $addLabel.
     Coller plusieurs lignes dans une case les répartit (et « Libellé : valeur » se coupe en deux colonnes). --}}
@php
    $simple = count($columns) === 1 && empty($columns[0]['key']);
    $values = array_values(array_filter((array) $values, fn ($v) => is_array($v) ? array_filter($v, fn ($x) => $x !== null && $x !== '') !== [] : trim((string) $v) !== ''));
    if ($values === []) {
        $values = [$simple ? '' : []];
    }
    $fieldName = fn ($index, $column) => $simple ? $name.'[]' : $name.'['.$index.']['.$column['key'].']';
    $errorFor = fn ($index) => $errors->first($name.'.'.$index) ?: collect($columns)->map(fn ($c) => ! empty($c['key']) ? $errors->first($name.'.'.$index.'.'.$c['key']) : null)->filter()->first();
@endphp
<div class="rows-editor" data-rows-editor data-simple="{{ $simple ? '1' : '0' }}" data-name="{{ $name }}" id="{{ $id }}">
    <span class="form-label fw-semibold d-block">{{ $label }}</span>
    <div class="rows-editor-list">
        @foreach ($values as $index => $value)
            <div class="rows-editor-row input-group input-group-sm mb-1">
                <span class="input-group-text rows-editor-handle" title="Glisser pour réordonner"><i class='bx bx-grid-vertical'></i></span>
                @foreach ($columns as $column)
                    <input type="text" class="form-control @if ($errorFor($index)) is-invalid @endif" name="{{ $fieldName($index, $column) }}" data-col="{{ $column['key'] ?? '' }}"
                           value="{{ $simple ? $value : ($value[$column['key']] ?? '') }}" placeholder="{{ $column['placeholder'] ?? '' }}" aria-label="{{ $column['placeholder'] ?? $label }}"
                           @if (! empty($column['width'])) style="max-width: {{ $column['width'] }}" @endif>
                @endforeach
                <button type="button" class="btn btn-outline-secondary" data-row-up title="Monter"><i class='bx bx-chevron-up'></i></button>
                <button type="button" class="btn btn-outline-danger" data-row-del title="Supprimer la ligne"><i class='bx bx-x'></i></button>
            </div>
            @if ($errorFor($index))<div class="text-danger small mb-1">Ligne {{ $index + 1 }} : {{ $errorFor($index) }}</div>@endif
        @endforeach
    </div>
    <button type="button" class="btn btn-outline-primary btn-sm mt-1" data-row-add><i class='bx bx-plus'></i> {{ $addLabel ?? 'Ajouter une ligne' }}</button>
    @if (! empty($help))<div class="form-text">{{ $help }}</div>@endif
</div>

@once
    @push('scripts')
        <script>
            // Listes de lignes des formulaires : ajout, suppression, déplacement, collage multiligne réparti.
            document.querySelectorAll('[data-rows-editor]').forEach((editor) => {
                const list = editor.querySelector('.rows-editor-list');
                const simple = editor.dataset.simple === '1';
                const name = editor.dataset.name;
                const reindex = () => {
                    if (simple) return;
                    [...list.querySelectorAll('.rows-editor-row')].forEach((row, i) => row.querySelectorAll('input').forEach((input) => input.name = name + '[' + i + '][' + input.dataset.col + ']'));
                };
                const add = (after) => {
                    const model = list.querySelector('.rows-editor-row');
                    const row = model.cloneNode(true);
                    row.querySelectorAll('input').forEach((input) => { input.value = ''; input.classList.remove('is-invalid'); });
                    (after ? after.after(row) : list.appendChild(row));
                    reindex();
                    row.querySelector('input').focus();
                    return row;
                };
                editor.querySelector('[data-row-add]').addEventListener('click', () => add());
                list.addEventListener('click', (e) => {
                    const row = e.target.closest('.rows-editor-row');
                    if (e.target.closest('[data-row-del]')) {
                        if (list.querySelectorAll('.rows-editor-row').length > 1) row.remove(); else row.querySelectorAll('input').forEach((i) => i.value = '');
                        reindex();
                    }
                    if (e.target.closest('[data-row-up]') && row.previousElementSibling) { row.previousElementSibling.before(row); reindex(); }
                });
                list.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') { e.preventDefault(); add(e.target.closest('.rows-editor-row')); }
                });
                list.addEventListener('paste', (e) => {
                    const text = (e.clipboardData || window.clipboardData).getData('text');
                    if (!text.includes('\n')) return;
                    e.preventDefault();
                    let row = e.target.closest('.rows-editor-row');
                    text.replace(/\r/g, '').split('\n').map((l) => l.replace(/^[-•*]\s*/, '').trim()).filter(Boolean).forEach((line, i) => {
                        if (i > 0) row = add(row);
                        const inputs = row.querySelectorAll('input');
                        if (inputs.length === 1) { inputs[0].value = line; return; }
                        const parts = line.includes('\t') ? line.split('\t') : line.split(/\s*:\s*/);
                        inputs[0].value = (parts.shift() || '').trim();
                        inputs[1].value = parts.join(' : ').trim();
                    });
                });
            });
        </script>
    @endpush
@endonce
