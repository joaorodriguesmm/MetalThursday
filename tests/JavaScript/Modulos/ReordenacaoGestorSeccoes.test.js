import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import GestorSeccoes
    from '../../../resources/js/modulos/GestorSeccoes.js';

/**
 * Confirma que a ordem visual é também a ordem submetida, sem destruir
 * os componentes já inicializados nem alterar os índices técnicos.
 *
 * @since 2.0.0
 */
describe('Reordenação das secções da MetalThursday', () => {
    const ordem = () => Array.from(
        document.querySelectorAll('#contentor-seccoes > .item-seccao'),
        (seccao) => seccao.dataset.indiceSeccao,
    );

    const criarGestor = () => new GestorSeccoes(
        '#contentor-seccoes',
        '#botao-adicionar-seccao',
        '#modelo-item-seccao',
    );

    beforeEach(() => {
        document.body.innerHTML = `
            <form id="formulario-metal-thursday">
                <div id="contentor-seccoes">
                    <section class="item-seccao" data-indice-seccao="0">
                        <span data-posicao-seccao></span>
                        <button type="button" class="botao-subir-seccao"><i></i></button>
                        <button type="button" class="botao-descer-seccao"><i></i></button>
                        <input type="hidden" name="seccoes[0][id]" value="101">
                        <input name="seccoes[0][descricao]" value="Primeira">
                        <button type="button" class="botao-remover-seccao">Remover</button>
                    </section>
                    <section class="item-seccao" data-indice-seccao="2">
                        <span data-posicao-seccao></span>
                        <button type="button" class="botao-subir-seccao"><i></i></button>
                        <button type="button" class="botao-descer-seccao"><i></i></button>
                        <input type="hidden" name="seccoes[2][id]" value="102">
                        <input name="seccoes[2][descricao]" value="Segunda">
                        <input name="seccoes[2][ligacoes][0][url]" value="https://exemplo.pt/segunda">
                        <select name="seccoes[2][artista_id]">
                            <option value="5" selected>Artista</option>
                        </select>
                        <button type="button" class="botao-remover-seccao">Remover</button>
                    </section>
                    <section class="item-seccao" data-indice-seccao="4">
                        <span data-posicao-seccao></span>
                        <button type="button" class="botao-subir-seccao"><i></i></button>
                        <button type="button" class="botao-descer-seccao"><i></i></button>
                        <input type="hidden" name="seccoes[4][id]" value="103">
                        <input name="seccoes[4][descricao]" value="Terceira">
                        <button type="button" class="botao-remover-seccao">Remover</button>
                    </section>
                </div>

                <button id="botao-adicionar-seccao" type="button">Adicionar</button>
                <p data-estado-seccoes aria-live="polite"></p>
            </form>

            <template id="modelo-item-seccao">
                <section class="item-seccao" data-indice-seccao="__INDICE_SECCAO__">
                    <span data-posicao-seccao></span>
                    <button type="button" class="botao-subir-seccao">Subir</button>
                    <button type="button" class="botao-descer-seccao">Descer</button>
                    <input name="seccoes[__INDICE_SECCAO__][descricao]">
                    <select class="seletor-tipo-seccao" name="seccoes[__INDICE_SECCAO__][tipo_seccao_id]">
                        <option value="1" data-exige-detalhes="false" selected>Texto</option>
                    </select>
                    <button type="button" class="botao-remover-seccao">Remover</button>
                </section>
            </template>
        `;
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('submete as secções na ordem visual sem alterar campos ou instâncias', () => {
        criarGestor();

        const segunda = document.querySelector('[data-indice-seccao="2"]');
        const selecao = segunda.querySelector('select');
        const instancia = { destroy: vi.fn() };
        selecao.tomselect = instancia;

        segunda.querySelector('.botao-subir-seccao i').click();

        expect(ordem()).toEqual(['2', '0', '4']);
        expect(selecao.tomselect).toBe(instancia);
        expect(instancia.destroy).not.toHaveBeenCalled();
        expect(segunda.querySelector('input[name$="[descricao]"]').value)
            .toBe('Segunda');
        expect(segunda.querySelector('input[name$="[url]"]').value)
            .toBe('https://exemplo.pt/segunda');
        expect(selecao.value).toBe('5');

        const formulario = document.getElementById('formulario-metal-thursday');
        const idsSubmetidos = Array.from(new FormData(formulario))
            .filter(([nome]) => nome.endsWith('[id]'))
            .map(([, valor]) => valor);

        expect(idsSubmetidos).toEqual(['102', '101', '103']);
        expect(segunda.querySelector('input[name$="[id]"]').name)
            .toBe('seccoes[2][id]');
        expect(document.querySelector('[data-estado-seccoes]').textContent)
            .toBe('Secção movida para a posição 1 de 3.');
    });

    it('atualiza limites e posições após mover, adicionar e remover', () => {
        const gestor = criarGestor();
        const primeira = document.querySelector('[data-indice-seccao="0"]');
        const ultima = document.querySelector('[data-indice-seccao="4"]');

        expect(primeira.querySelector('.botao-subir-seccao').disabled).toBe(true);
        expect(ultima.querySelector('.botao-descer-seccao').disabled).toBe(true);
        expect(gestor.mover(primeira.querySelector('.botao-subir-seccao'), -1))
            .toBe(false);

        ultima.querySelector('.botao-subir-seccao').click();
        expect(ordem()).toEqual(['0', '4', '2']);
        expect(ultima.querySelector('[data-posicao-seccao]').textContent)
            .toBe('Secção 2 de 3');

        const nova = gestor.adicionar();
        expect(nova.dataset.indiceSeccao).toBe('5');
        expect(nova.querySelector('.botao-descer-seccao').disabled).toBe(true);
        expect(gestor.remover(nova.querySelector('.botao-remover-seccao')))
            .toBe(true);
        expect(document.querySelectorAll('[data-posicao-seccao]'))
            .toHaveLength(3);
        expect(document.querySelector('[data-indice-seccao="2"] .botao-descer-seccao').disabled)
            .toBe(true);
    });

    it('regista apenas um gestor e permite inverter movimentos com o teclado', () => {
        const primeiroGestor = criarGestor();
        const segundoGestor = criarGestor();
        expect(primeiroGestor).toBe(segundoGestor);

        const segunda = document.querySelector('[data-indice-seccao="2"]');
        segunda.querySelector('.botao-subir-seccao').click();
        expect(ordem()).toEqual(['2', '0', '4']);
        expect(segunda.querySelector('.botao-subir-seccao').disabled).toBe(true);
        expect(document.activeElement).toBe(
            segunda.querySelector('.botao-descer-seccao'),
        );

        segunda.querySelector('.botao-descer-seccao').click();
        expect(ordem()).toEqual(['0', '2', '4']);
    });
});
