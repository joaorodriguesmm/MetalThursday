import {
    afterEach,
    describe,
    expect,
    it,
} from 'vitest';

import InicializadorComentarios
    from '../../../resources/js/modulos/InicializadorComentarios.js';

describe('idempotência na interface de comentários', () => {
    function preparar() {
        document.body.innerHTML = `
            <button type="button" aria-controls="painel-comentarios">
                <span data-quantidade-comentarios>1</span>
            </button>

            <div id="painel-comentarios" class="collapse">
                <section aria-label="Comentários">
                    <div class="lista-comentarios">
                        <article
                            id="comentario-10"
                            class="comentario"
                            data-nivel-visual="1"
                        >
                            <button
                                type="button"
                                data-acao-comentarios="alternar-respostas"
                                data-quantidade-respostas="0"
                                aria-expanded="false"
                                hidden
                            >
                                <span data-texto-alternador-respostas>
                                    Ver 0 respostas
                                </span>
                            </button>

                            <div
                                data-respostas-comentario
                                data-respostas-carregadas="false"
                                hidden
                            ></div>

                            <div class="contentor-formulario-resposta">
                                <form class="formulario-resposta-comentario"
                                    data-identificador-comentario-pai="10">
                                </form>
                            </div>
                        </article>
                    </div>
                </section>
            </div>
        `;

        // Evita inicializar formulários incompletos da fixture.
        const gestor = new InicializadorComentarios(
            document.createElement('div'),
        );

        const seccao = document.querySelector(
            'section[aria-label="Comentários"]',
        );

        const contador = document.querySelector(
            '[data-quantidade-comentarios]',
        );

        return {
            gestor,
            seccao,
            contador,
        };
    }

    function dados(id, repetida = true) {
        return {
            submissao_repetida: repetida,
            comentario: {id},
            comentario_html:
                `<article id="comentario-${id}" class="comentario"></article>`,
        };
    }

    afterEach(() => {
        document.body.replaceChildren();
    });

    it('não volta a inserir um comentário já apresentado', async () => {
        const {gestor, seccao, contador} = preparar();

        const formulario = document.createElement('form');
        formulario.className = 'formulario-comentario';
        seccao.prepend(formulario);

        await gestor.atualizarInterfaceAposPublicacao(
            formulario,
            dados(10),
        );

        expect(seccao.querySelectorAll('#comentario-10'))
            .toHaveLength(1);

        expect(contador.textContent).toBe('1');
    });

    it('apresenta comentário quando a primeira resposta HTTP se perdeu', async () => {
        const {gestor, seccao, contador} = preparar();

        const formulario = document.createElement('form');
        formulario.className = 'formulario-comentario';
        seccao.prepend(formulario);

        await gestor.atualizarInterfaceAposPublicacao(
            formulario,
            dados(11),
        );

        expect(seccao.querySelectorAll('#comentario-11'))
            .toHaveLength(1);

        expect(contador.textContent).toBe('2');

        await gestor.atualizarInterfaceAposPublicacao(
            formulario,
            dados(11),
        );

        expect(seccao.querySelectorAll('#comentario-11'))
            .toHaveLength(1);

        expect(contador.textContent).toBe('2');
    });

    it('não duplica uma resposta já apresentada', async () => {
        const {gestor, seccao, contador} = preparar();

        const formulario = seccao.querySelector(
            '.formulario-resposta-comentario',
        );

        const ramo = seccao.querySelector(
            '[data-respostas-comentario]',
        );

        ramo.innerHTML =
            '<article id="comentario-20" class="comentario"></article>';

        contador.textContent = '2';

        await gestor.atualizarInterfaceAposPublicacao(
            formulario,
            dados(20),
        );

        expect(ramo.querySelectorAll('#comentario-20'))
            .toHaveLength(1);

        expect(contador.textContent).toBe('2');

        expect(formulario.closest(
            '.contentor-formulario-resposta',
        ).hidden).toBe(true);
    });

    it('acrescenta uma resposta cuja confirmação HTTP se perdeu', async () => {
        const {gestor, seccao, contador} = preparar();

        const formulario = seccao.querySelector(
            '.formulario-resposta-comentario',
        );

        const pai = seccao.querySelector('#comentario-10');

        const botao = pai.querySelector(
            '[data-acao-comentarios="alternar-respostas"]',
        );

        const ramo = pai.querySelector(
            '[data-respostas-comentario]',
        );

        await gestor.atualizarInterfaceAposPublicacao(
            formulario,
            dados(20),
        );

        expect(ramo.querySelectorAll('#comentario-20'))
            .toHaveLength(1);

        expect(botao.dataset.quantidadeRespostas).toBe('1');
        expect(contador.textContent).toBe('2');

        await gestor.atualizarInterfaceAposPublicacao(
            formulario,
            dados(20),
        );

        expect(ramo.querySelectorAll('#comentario-20'))
            .toHaveLength(1);

        expect(botao.dataset.quantidadeRespostas).toBe('1');
        expect(contador.textContent).toBe('2');
    });
});
