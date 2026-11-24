import {
    beforeEach,
    describe,
    expect,
    it,
} from 'vitest';

import GestorSeccoes
    from '../../../resources/js/modulos/GestorSeccoes.js';

/**
 * Testa a recolha de secções sem interferir na submissão, ordenação
 * ou validação nativa do formulário.
 *
 * @since 2.0.0
 */
describe('Secções recolhíveis da MetalThursday', () => {
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
                        <span data-resumo-seccao></span>
                        <button type="button" class="botao-alternar-seccao"
                            aria-expanded="true" aria-controls="conteudo-seccao-0">
                            <span data-rotulo-alternar-seccao>Recolher</span>
                        </button>
                        <button type="button" class="botao-subir-seccao">Subir</button>
                        <button type="button" class="botao-descer-seccao">Descer</button>
                        <div id="conteudo-seccao-0" data-conteudo-seccao>
                            <select class="seletor-tipo-seccao" name="seccoes[0][tipo_seccao_id]">
                                <option value="1" data-exige-detalhes="true" selected>Álbum</option>
                            </select>
                            <select class="tom-select-artistas" name="seccoes[0][artista_id]">
                                <option value="7" selected>Anciients</option>
                            </select>
                            <input data-campo-titulo-seccao name="seccoes[0][titulo]"
                                value="Beyond the Reach of the Sun" required>
                            <textarea name="seccoes[0][descricao]" required>Descrição original</textarea>
                            <button type="button" class="botao-remover-seccao">Remover</button>
                        </div>
                    </section>
                    <section class="item-seccao" data-indice-seccao="1">
                        <span data-posicao-seccao></span>
                        <span data-resumo-seccao></span>
                        <button type="button" class="botao-alternar-seccao"
                            aria-expanded="true" aria-controls="conteudo-seccao-1">
                            <span data-rotulo-alternar-seccao>Recolher</span>
                        </button>
                        <button type="button" class="botao-subir-seccao">Subir</button>
                        <button type="button" class="botao-descer-seccao">Descer</button>
                        <div id="conteudo-seccao-1" data-conteudo-seccao>
                            <input data-campo-titulo-seccao name="seccoes[1][titulo]" value="Outra secção">
                            <button type="button" class="botao-remover-seccao">Remover</button>
                        </div>
                    </section>
                </div>
                <button id="botao-adicionar-seccao" type="button">Adicionar</button>
                <span data-estado-seccoes></span>
            </form>
            <template id="modelo-item-seccao">
                <section class="item-seccao" data-indice-seccao="__INDICE_SECCAO__">
                    <span data-posicao-seccao></span>
                    <span data-resumo-seccao></span>
                    <button type="button" class="botao-alternar-seccao" aria-expanded="true"
                        aria-controls="conteudo-seccao-__INDICE_SECCAO__">Recolher</button>
                    <div id="conteudo-seccao-__INDICE_SECCAO__" data-conteudo-seccao>
                        <select class="seletor-tipo-seccao">
                            <option value="1" data-exige-detalhes="false" selected>Texto</option>
                        </select>
                        <input name="seccoes[__INDICE_SECCAO__][descricao]">
                        <button type="button" class="botao-remover-seccao">Remover</button>
                    </div>
                </section>
            </template>
        `;
    });

    it('recolhe sem perder valores nem remover os campos da submissão', () => {
        criarGestor();

        const seccao = document.querySelector('.item-seccao');
        const botao = seccao.querySelector('.botao-alternar-seccao');
        const conteudo = seccao.querySelector('[data-conteudo-seccao]');

        expect(seccao.querySelector('[data-resumo-seccao]').textContent)
            .toContain('Anciients');
        expect(seccao.querySelector('[data-resumo-seccao]').textContent)
            .toContain('Beyond the Reach of the Sun');

        botao.click();

        expect(conteudo.hidden).toBe(true);
        expect(seccao.classList.contains('item-seccao--recolhida')).toBe(true);
        expect(botao.getAttribute('aria-expanded')).toBe('false');
        expect(botao.querySelector('[data-rotulo-alternar-seccao]').textContent)
            .toBe('Expandir');

        const dados = new FormData(document.getElementById('formulario-metal-thursday'));
        expect(dados.get('seccoes[0][titulo]')).toBe('Beyond the Reach of the Sun');
        expect(dados.get('seccoes[0][descricao]')).toBe('Descrição original');

        botao.click();
        expect(conteudo.hidden).toBe(false);
        expect(seccao.classList.contains('item-seccao--recolhida')).toBe(false);
        expect(botao.getAttribute('aria-expanded')).toBe('true');
    });

    it('expande um campo inválido antes de o navegador tentar focá-lo', () => {
        criarGestor();

        const seccao = document.querySelector('.item-seccao');
        seccao.querySelector('.botao-alternar-seccao').click();
        expect(seccao.querySelector('[data-conteudo-seccao]').hidden).toBe(true);

        const descricao = seccao.querySelector('textarea');
        descricao.dispatchEvent(new Event('invalid', { bubbles: false }));

        expect(seccao.querySelector('[data-conteudo-seccao]').hidden).toBe(false);
        expect(seccao.querySelector('.botao-alternar-seccao')
            .getAttribute('aria-expanded')).toBe('true');
    });

    it('mantém secções recolhidas após reordenar e atualiza os resumos', () => {
        const gestor = criarGestor();
        const primeira = document.querySelector('.item-seccao');
        primeira.querySelector('.botao-alternar-seccao').click();
        primeira.querySelector('.botao-descer-seccao').click();

        expect(document.querySelectorAll('.item-seccao')[1]).toBe(primeira);
        expect(primeira.querySelector('[data-conteudo-seccao]').hidden).toBe(true);
        expect(primeira.querySelector('[data-posicao-seccao]').textContent)
            .toBe('Secção 2 de 2');

        const nova = gestor.adicionar();
        expect(nova.querySelector('[data-conteudo-seccao]').hidden).toBe(false);
        expect(nova.querySelector('[data-posicao-seccao]').textContent)
            .toBe('Secção 3 de 3');
    });
});
