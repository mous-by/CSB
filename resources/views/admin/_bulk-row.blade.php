{{-- Une ligne de la grille de saisie en lot. $index (numéro de ligne envoyé), $row (valeurs reçues), $columns. --}}
@php
    $fresh = $row === [];
@endphp
<tr>
    <td class="row-num"></td>
    @foreach ($columns as $key => $column)
        @php
            $type = $column['type'] ?? 'text';
            $name = "rows[{$index}][{$key}]";
            $value = $fresh ? ($column['default'] ?? null) : ($row[$key] ?? null);
            $error = ($template ?? false) ? null : ($errors->first("rows.{$index}.{$key}") ?: $errors->first("rows.{$index}.{$key}.*"));
            $invalid = $error ? 'is-invalid' : '';
        @endphp
        <td>
            @if ($type === 'select')
                <select class="form-select form-select-sm {{ $invalid }}" name="{{ $name }}" data-key="{{ $key }}" aria-label="{{ $column['label'] }}">
                    <option value="">—</option>
                    @foreach ($column['options'] ?? [] as $optionValue => $optionLabel)
                        <option value="{{ $optionValue }}" @selected((string) $value === (string) $optionValue)>{{ $optionLabel }}</option>
                    @endforeach
                </select>
            @elseif ($type === 'bool')
                <input type="hidden" name="{{ $name }}" value="0">
                <input type="checkbox" class="form-check-input mt-2" name="{{ $name }}" value="1" data-key="{{ $key }}" aria-label="{{ $column['label'] }}" @checked(filter_var($value, FILTER_VALIDATE_BOOLEAN))>
            @elseif (in_array($type, ['textarea', 'lines'], true))
                <textarea class="form-control form-control-sm {{ $invalid }}" name="{{ $name }}" rows="1" data-key="{{ $key }}" aria-label="{{ $column['label'] }}" placeholder="{{ $type === 'lines' ? 'Une par ligne ou « ; »' : '' }}">{{ is_array($value) ? implode("\n", $value) : $value }}</textarea>
            @else
                <input type="{{ $type === 'number' ? 'text' : 'text' }}" @if ($type === 'number') inputmode="decimal" @endif class="form-control form-control-sm {{ $invalid }}" name="{{ $name }}" value="{{ $value }}" data-key="{{ $key }}" aria-label="{{ $column['label'] }}">
            @endif
            @if ($error)<div class="invalid-feedback d-block small">{{ $error }}</div>@endif
        </td>
    @endforeach
    <td class="text-nowrap">
        <button type="button" class="btn btn-sm btn-light" data-row-copy title="Dupliquer la ligne"><i class='bx bx-duplicate'></i></button>
        <button type="button" class="btn btn-sm btn-light text-danger" data-row-remove title="Supprimer la ligne"><i class='bx bx-x'></i></button>
    </td>
</tr>
