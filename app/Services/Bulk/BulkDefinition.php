<?php

namespace App\Services\Bulk;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Saisie en masse d'un module (demande de CSB, 2026-10-11 : « ajouter des lignes » et « ajouter un fichier » partout où
 * c'est utile, pour qu'un seul enregistrement en crée beaucoup). Une définition déclare les colonnes une seule fois ;
 * BulkService en tire la grille de saisie, le modèle Excel, l'import de fichier et la validation commune.
 *
 * Colonne : clé => [
 *   'label' => titre (grille, modèle Excel, messages), 'rules' => règles Laravel de la valeur, 'required' => bool,
 *   'type' => text | textarea | number | select | bool | lines, 'options' => valeur => libellé (select ; le fichier
 *   peut contenir l'un ou l'autre), 'help' => aide (mode d'emploi), 'example' => exemple, 'width' => largeur Excel,
 *   'default' => valeur par défaut d'une nouvelle ligne, 'unique' => vrai si deux lignes ne peuvent pas avoir la même valeur
 * ]
 */
abstract class BulkDefinition
{
    /** Clé du module (préfixe des routes et des permissions). */
    abstract public function module(): string;

    /** Nom au pluriel affiché (« services », « produits »…). */
    abstract public function label(): string;

    /** Icône Boxicons. */
    abstract public function icon(): string;

    /** Route de la liste du module (retour après enregistrement). */
    abstract public function indexRoute(): string;

    /** @return array<string, array<string, mixed>> */
    abstract public function columns(): array;

    /** Crée un élément à partir d'une ligne validée (valeurs déjà converties). */
    abstract public function create(array $row, User $user): Model;

    /** Permission exigée (route et contrôle). */
    public function permission(): string
    {
        return $this->module().'.create';
    }

    /** L'utilisateur peut-il créer cet élément (contrôle par ligne, ex. famille de catégorie) ? */
    public function authorizeRow(array $row, User $user): bool
    {
        return true;
    }

    /** Nombre maximal de lignes par envoi. */
    public function maxRows(): int
    {
        return 500;
    }

    /** Règles supplémentaires portant sur plusieurs colonnes d'une même ligne. @return array<string, mixed> */
    public function extraRules(): array
    {
        return [];
    }

    /** Lignes d'explication du mode d'emploi (en plus de l'aide de chaque colonne). @return list<string> */
    public function instructions(): array
    {
        return [];
    }
}
