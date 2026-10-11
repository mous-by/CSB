<?php

namespace App\Http\Requests\Web\Admin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Base commune des formulaires de contenu (même requête pour la création et la modification, comme
 * ReferentielRequest du gabarit) : saisies nettoyées, champs facultatifs vides → null, cases à cocher → booléens.
 */
abstract class ContentRequest extends FormRequest
{
    /** Nom du paramètre de route qui porte le modèle. */
    abstract protected function routeKey(): string;

    /** @return class-string<Model> */
    abstract protected function modelClass(): string;

    /** Champs texte facultatifs : une saisie vide devient null. @return list<string> */
    protected function nullableFields(): array
    {
        return [];
    }

    /** Cases à cocher (absentes du formulaire = faux). @return list<string> */
    protected function booleanFields(): array
    {
        return ['is_published'];
    }

    public function authorize(): bool
    {
        $model = $this->model();

        return $model !== null
            ? ($this->user()?->can('update', $model) ?? false)
            : ($this->user()?->can('create', $this->modelClass()) ?? false);
    }

    public function model(): ?Model
    {
        $model = $this->route($this->routeKey());

        return $model instanceof Model ? $model : null;
    }

    protected function prepareForValidation(): void
    {
        $data = [];

        foreach ($this->all() as $key => $value) {
            if (is_string($value)) {
                $value = trim(str_replace("\r\n", "\n", $value));
                $data[$key] = $value === '' && in_array($key, $this->nullableFields(), true) ? null : $value;
            }
        }

        foreach ($this->booleanFields() as $field) {
            $data[$field] = $this->boolean($field);
        }

        $this->merge($data);
    }
}
