{{--
    Disponibiliza a importação assistida de dados externos de um artista.

    O MusicBrainz é a fonte principal de identificação. O TheAudioDB pode
    complementar os dados devolvidos. Quando o MusicBrainz conhece o perfil
    Discogs, apenas o respetivo identificador é associado.

    Os géneros nunca são alterados pela importação.

    @since 2.0.0
--}}

@php
    $identificadorMusicBrainzImportacao =
        isset($identificadorMusicBrainzImportacao)
        && is_string($identificadorMusicBrainzImportacao)
            ? trim($identificadorMusicBrainzImportacao)
            : '';

    $identificadorDiscogsImportacao =
        isset($identificadorDiscogsImportacao)
            ? trim((string) $identificadorDiscogsImportacao)
            : '';

    $mbidModelo =
        '00000000-0000-0000-0000-000000000000';
@endphp

<section
    class="rounded-3 border border-secondary bg-black bg-opacity-25 p-3 mb-3"
    data-importacao-artista
    data-endereco-pesquisa-importacao="{{ route('artistas.importacao.pesquisar') }}"
    data-endereco-obter-importacao="{{
        route(
            'artistas.importacao.obter',
            [
                'mbid' => $mbidModelo,
            ],
        )
    }}"
    data-mbid-modelo="{{ $mbidModelo }}"
>
    <div class="d-flex flex-column flex-sm-row justify-content-between gap-3">
        <div>
            <h3 class="h6 mb-1">
                Importar dados do artista
            </h3>

            <p class="small text-muted mb-0">
                Pesquisa primeiro no MusicBrainz. O TheAudioDB pode complementar
                automaticamente a ficha selecionada.
            </p>

            <p class="small text-muted mb-0 mt-1">
                Os dados são copiados para os campos abaixo para revisão.
                Os géneros nunca são alterados.
            </p>
        </div>

        <div class="d-flex align-items-start gap-2 flex-shrink-0">
            <button
                class="btn btn-sm btn-primary"
                type="button"
                data-acao-pesquisar-importacao
            >
                <i class="bi bi-search me-1" aria-hidden="true"></i>
                Pesquisar dados
            </button>

            <button
                class="btn btn-sm btn-outline-danger"
                type="button"
                data-acao-remover-importacao
                @if (
                    $identificadorMusicBrainzImportacao === ''
                    && $identificadorDiscogsImportacao === ''
                )
                    hidden
                @endif
            >
                Desassociar
            </button>
        </div>
    </div>

    <input
        type="hidden"
        name="musicbrainz_id"
        value="{{ $identificadorMusicBrainzImportacao }}"
        data-musicbrainz-id
    >

    <input
        type="hidden"
        name="discogs_id"
        value="{{ $identificadorDiscogsImportacao }}"
        data-discogs-id
    >

    <div
        class="small mt-3"
        data-estado-importacao
        aria-live="polite"
    ></div>

    <div
        class="mt-3"
        data-resultados-importacao
    ></div>

    <div
        class="mt-3"
        data-associacao-importacao
    ></div>

    @error('musicbrainz_id')
        <div class="invalid-feedback d-block">
            {{ $message }}
        </div>
    @enderror

    @error('discogs_id')
        <div class="invalid-feedback d-block">
            {{ $message }}
        </div>
    @enderror
</section>
