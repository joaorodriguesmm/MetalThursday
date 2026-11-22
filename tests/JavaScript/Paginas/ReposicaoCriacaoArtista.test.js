import {
    afterEach,
    expect,
    it,
    vi,
} from 'vitest';

afterEach(() => {
    vi.restoreAllMocks();
    document.body.replaceChildren();
});

it('limpa ligacoes dinamicas e associações numa nova abertura do modal', async () => {
    document.body.innerHTML = `
        <button id="abrir-artista">Novo artista</button>
        <div class="modal" id="modal-criar-artista">
            <form data-formulario-perfil-artista data-formulario-criar-artista>
                <input name="nome" value="">
                <input type="hidden" name="musicbrainz_id" data-musicbrainz-id>
                <input type="hidden" name="discogs_id" data-discogs-id>
                <div data-importacao-artista>
                    <button type="button" data-acao-remover-importacao hidden>
                        Desassociar
                    </button>
                    <div data-estado-importacao></div>
                    <div data-resultados-importacao></div>
                    <div data-associacao-importacao></div>
                </div>
                <div data-lista-ligacoes></div>
                <template data-modelo-ligacao>
                    <div data-ligacao>
                        <input data-campo-ligacao="titulo">
                        <input data-campo-ligacao="url">
                    </div>
                </template>
            </form>
        </div>
    `;

    vi.resetModules();
    await import('../../../resources/js/paginas/perfilArtista.js');
    document.dispatchEvent(new Event('DOMContentLoaded'));

    const modal = document.getElementById('modal-criar-artista');
    const formulario = modal.querySelector('form');
    const musicBrainz = formulario.querySelector('[data-musicbrainz-id]');
    const discogs = formulario.querySelector('[data-discogs-id]');
    const lista = formulario.querySelector('[data-lista-ligacoes]');
    const modelo = formulario.querySelector('template');

    musicBrainz.value = 'mbid-anterior';
    discogs.value = '123';

    for (let indice = 0; indice < 3; indice += 1) {
        const linha = modelo.content.firstElementChild.cloneNode(true);
        linha.querySelector('[data-campo-ligacao="titulo"]').value = `Ligação ${indice}`;
        linha.querySelector('[data-campo-ligacao="url"]').value = `https://exemplo.pt/${indice}`;
        lista.append(linha);
    }

    // O retorno de uma janela de criação de géneros preserva a edição.
    modal.dispatchEvent(new Event('show.bs.modal'));
    await Promise.resolve();

    expect(musicBrainz.value).toBe('mbid-anterior');
    expect(lista.querySelectorAll('[data-ligacao]')).toHaveLength(3);

    const abertura = new Event('show.bs.modal', {cancelable: true});
    Object.defineProperty(abertura, 'relatedTarget', {
        value: document.getElementById('abrir-artista'),
    });
    modal.dispatchEvent(abertura);

    await vi.waitFor(() => {
        expect(musicBrainz.value).toBe('');
        expect(discogs.value).toBe('');
        expect(lista.querySelectorAll('[data-ligacao]')).toHaveLength(1);
    });

    expect(musicBrainz.defaultValue).toBe('');
    expect(discogs.defaultValue).toBe('');
    expect(lista.querySelector('[data-campo-ligacao="titulo"]').value)
        .toBe('');
    expect(lista.querySelector('[data-campo-ligacao="url"]').value)
        .toBe('');
});
