<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\CategoryType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Catégories d'une famille (logiciels, matériel, actualités) : liste, création et modification en modale. Une catégorie
 * utilisée ne se supprime pas, elle se désactive (règle dans le contrôleur : Gate::before ne la contourne pas).
 */
class CategoryController extends Controller
{
    public function index(string $type): View
    {
        $type = CategoryType::from($type);
        Gate::authorize('viewAny', [Category::class, $type]);

        $categories = Category::ofType($type)->ordered()->get();

        return view('categories.index', [
            'type' => $type,
            'categories' => $categories,
            'usages' => $categories->mapWithKeys(fn (Category $c) => [$c->id => $c->usageCount()]),
        ]);
    }

    public function store(CategoryRequest $request, string $type): RedirectResponse
    {
        $category = Category::create($request->validated() + ['type' => $request->type()]);

        return redirect()->route('categories.index', $category->type->value)->with('status', "Catégorie « {$category->name} » créée.");
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('categories.index', $category->type->value)->with('status', "Catégorie « {$category->name} » modifiée.");
    }

    public function destroy(Category $category): RedirectResponse
    {
        Gate::authorize('delete', $category);

        if ($category->usageCount() > 0) {
            return redirect()->route('categories.index', $category->type->value)->with('error', 'Cette catégorie est utilisée : désactivez-la plutôt que de la supprimer.');
        }

        $category->delete();

        return redirect()->route('categories.index', $category->type->value)->with('status', "Catégorie « {$category->name} » supprimée.");
    }
}
