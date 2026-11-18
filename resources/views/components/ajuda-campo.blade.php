{{--
    Botão de ajuda contextual reutilizável e acessível por teclado.
--}}

@props([
    'campo',
    'texto',
])

<button
    class="btn border-0 bg-transparent text-muted p-0 align-baseline"
    type="button"
    data-bs-toggle="tooltip"
    data-bs-title="{{ $texto }}"
    aria-label="Ajuda sobre {{ $campo }}"
>
    <i class="bi bi-info-circle" aria-hidden="true"></i>
</button>
