import {
    afterEach,
    expect,
    it,
    vi,
} from 'vitest';

import GestorAlertas
    from '../../../resources/js/modulos/GestorAlertas.js';
import TratadorFormularioAjax
    from '../../../resources/js/modulos/TratadorFormularioAjax.js';

afterEach(() => {
    vi.restoreAllMocks();
    document.body.replaceChildren();
});

it('apresenta os dois erros de identificadores ocultos ao utilizador', () => {
    document.body.innerHTML = `
        <form id="formulario-teste-artista">
            <section data-importacao-artista>
                <input type="hidden" name="musicbrainz_id">
                <input type="hidden" name="discogs_id">
                <div
                    class="invalid-feedback"
                    data-erro-campo="musicbrainz_id"
                ></div>
                <div
                    class="invalid-feedback"
                    data-erro-campo="discogs_id"
                ></div>
            </section>
            <button type="submit">Criar artista</button>
        </form>
    `;

    const alerta = vi.spyOn(
        GestorAlertas,
        'mostrarErro',
    ).mockResolvedValue(undefined);

    const tratador = new TratadorFormularioAjax(
        'formulario-teste-artista',
        '/artistas',
    );

    tratador.tratarErrosValidacao({
        musicbrainz_id: ['MusicBrainz já associado.'],
        discogs_id: ['Discogs já associado.'],
    });

    const erros = document.querySelectorAll('[data-erro-campo]');

    expect(erros[0].textContent).toBe('MusicBrainz já associado.');
    expect(erros[1].textContent).toBe('Discogs já associado.');
    expect(erros[0].classList.contains('d-block')).toBe(true);
    expect(erros[1].classList.contains('d-block')).toBe(true);
    expect(alerta).toHaveBeenCalledWith(
        'MusicBrainz já associado. Discogs já associado.',
        'Dados inválidos',
    );
});
