{{-- Bouton de validation / publication d'une liste : en attente → bouton vert avec son texte (« Valider », « Publier ») ;
     déjà en ligne → icône « Dépublier » / « Masquer ». $action (URL du POST), $published, $label, $hideLabel (facultatif).
     Le texte est dans un <span> : sinon le thème le prend pour un bouton « icône seule » (34 px, fond pâle) ; icône pleine « bxs-check-circle » : le thème rend pâles les « bx-check » des tableaux. --}}
<form method="POST" action="{{ $action }}">
    @csrf
    @if ($published)
        <button type="submit" class="btn btn-primary btn-sm" title="{{ $hideLabel ?? 'Dépublier' }}"><i class='bx bx-hide'></i></button>
    @else
        <button type="submit" class="btn btn-success btn-sm text-nowrap" title="{{ $label }} : mettre en ligne sur le site"><i class='bx bxs-check-circle me-1'></i><span>{{ $label }}</span></button>
    @endif
</form>
