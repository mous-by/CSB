{{-- Familles de catégories accessibles à l'utilisateur (CategoryType::permission()). --}}
@php
    $categoryTabs = collect(\App\Enums\CategoryType::cases())->filter(fn ($t) => auth()->user()->can('viewAny', [\App\Models\Category::class, $t]));
@endphp
@if ($categoryTabs->count() > 1)
    <nav class="nv-tabs mb-3" aria-label="Familles de catégories">
        @foreach ($categoryTabs as $tab)
            <a href="{{ route('categories.index', $tab->value) }}" class="nv-tab {{ $tab === $type ? 'active' : '' }}"><i class='{{ $tab->icon() }}'></i>{{ $tab->label() }}</a>
        @endforeach
    </nav>
@endif
