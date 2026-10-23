import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import axios from '../../../resources/js/modulos/ClienteHttp.js';

import InicializadorComentarios
    from '../../../resources/js/modulos/InicializadorComentarios.js';

import TratadorFormularioAjax
    from '../../../resources/js/modulos/TratadorFormularioAjax.js';

function criarFormulario() {
    const formulario = document.createElement('form');

    formulario.id = 'formulario-b25-20';
    formulario.className = 'formulario-comentario';
    formulario.action = '/comentarios/publicar';

    formulario.innerHTML = `
        <textarea name="conteudo">Comentario de teste</textarea>
        <button type="submit">Publicar</button>
    `;

    return formulario;
}

function submeter(formulario) {
    const evento = new SubmitEvent(
        'submit',
        {
            bubbles: true,
            cancelable: true,
        },
    );

    formulario.dispatchEvent(evento);

    return evento;
}

describe('B25.20 — concorrencia do inicializador de comentarios', () => {
    let contentor;

    beforeEach(() => {
        document.body.innerHTML = `
            <section id="comentarios-b25-20"></section>
        `;

        contentor = document.getElementById(
            'comentarios-b25-20',
        );

        vi.spyOn(
            InicializadorComentarios.prototype,
            'atualizarInterfaceAposPublicacao',
        ).mockResolvedValue();

        vi.spyOn(
            TratadorFormularioAjax.prototype,
            'mostrarMensagemSucesso',
        ).mockImplementation(() => {});

        vi.spyOn(
            TratadorFormularioAjax.prototype,
            'mostrarAvisoAtualizacaoInterface',
        ).mockImplementation(() => {});
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('nao duplica a submissao com dois inicializadores', async () => {
        const formulario = criarFormulario();

        contentor.append(formulario);

        const pedido = vi.spyOn(
            axios,
            'post',
        ).mockResolvedValue({
            data: {
                mensagem: 'OK',
            },
        });

        new InicializadorComentarios(contentor);
        new InicializadorComentarios(contentor);

        const evento = submeter(formulario);

        expect(evento.defaultPrevented).toBe(true);

        await vi.waitFor(() => {
            expect(
                formulario.querySelector('button').disabled,
            ).toBe(false);
        });

        expect(pedido).toHaveBeenCalledTimes(1);
    });

    it('nao duplica o carregamento com dois inicializadores', async () => {
        contentor.innerHTML = `
            <button
                type="button"
                data-acao-comentarios="alternar-seccao"
                data-endereco-comentarios="/comentarios/seccao"
                aria-controls="secao-b25-20"
            >
                Comentarios
                <span data-quantidade-comentarios>0</span>
            </button>

            <div
                id="secao-b25-20"
                data-comentarios-carregados="false"
            ></div>
        `;

        const pedido = vi.spyOn(
            axios,
            'get',
        ).mockResolvedValue({
            data: {
                numero_comentarios: 0,
                comentarios_html:
                    '<div class="lista-comentarios"></div>',
            },
        });

        new InicializadorComentarios(contentor);
        new InicializadorComentarios(contentor);

        const botao = contentor.querySelector(
            'button[data-acao-comentarios]',
        );

        botao.click();

        await vi.waitFor(() => {
            expect(
                contentor.querySelector(
                    '#secao-b25-20',
                ).dataset.comentariosCarregados,
            ).toBe('true');
        });

        expect(pedido).toHaveBeenCalledTimes(1);
    });

    it('nao reinicializa formularios na mesma instancia', async () => {
        const formulario = criarFormulario();

        contentor.append(formulario);

        const pedido = vi.spyOn(
            axios,
            'post',
        ).mockResolvedValue({
            data: {
                mensagem: 'OK',
            },
        });

        const inicializador =
            new InicializadorComentarios(contentor);

        inicializador.inicializarFormularios(contentor);
        inicializador.inicializarFormularios(contentor);

        const evento = submeter(formulario);

        expect(evento.defaultPrevented).toBe(true);

        await vi.waitFor(() => {
            expect(
                formulario.querySelector('button').disabled,
            ).toBe(false);
        });

        expect(pedido).toHaveBeenCalledTimes(1);
    });

    it('inicializa um novo formulario com o mesmo identificador', async () => {
        const formularioOriginal = criarFormulario();

        contentor.append(formularioOriginal);

        const pedido = vi.spyOn(
            axios,
            'post',
        ).mockResolvedValue({
            data: {
                mensagem: 'OK',
            },
        });

        const inicializador =
            new InicializadorComentarios(contentor);

        const novoFormulario = criarFormulario();

        formularioOriginal.replaceWith(
            novoFormulario,
        );

        inicializador.inicializarFormularios(
            contentor,
        );

        const evento = submeter(
            novoFormulario,
        );

        expect(evento.defaultPrevented).toBe(true);

        await vi.waitFor(() => {
            expect(
                novoFormulario.querySelector('button').disabled,
            ).toBe(false);
        });

        expect(pedido).toHaveBeenCalledTimes(1);
    });

    it('nao duplica formularios em contentores sobrepostos', async () => {
        const formulario = criarFormulario();

        contentor.append(formulario);

        const pedido = vi.spyOn(
            axios,
            'post',
        ).mockResolvedValue({
            data: {
                mensagem: 'OK',
            },
        });

        new InicializadorComentarios(document);
        new InicializadorComentarios(contentor);

        const evento = submeter(formulario);

        expect(evento.defaultPrevented).toBe(true);

        await vi.waitFor(() => {
            expect(
                formulario.querySelector('button').disabled,
            ).toBe(false);
        });

        expect(pedido).toHaveBeenCalledTimes(1);
    });

    it('nao duplica cliques em contentores sobrepostos', async () => {
        contentor.innerHTML = `
            <button
                type="button"
                data-acao-comentarios="alternar-seccao"
                data-endereco-comentarios="/comentarios/seccao"
                aria-controls="secao-b25-20"
            >
                Comentarios
                <span data-quantidade-comentarios>0</span>
            </button>

            <div
                id="secao-b25-20"
                data-comentarios-carregados="false"
            ></div>
        `;

        const pedido = vi.spyOn(
            axios,
            'get',
        ).mockResolvedValue({
            data: {
                numero_comentarios: 0,
                comentarios_html:
                    '<div class="lista-comentarios"></div>',
            },
        });

        new InicializadorComentarios(document);
        new InicializadorComentarios(contentor);

        contentor.querySelector(
            'button[data-acao-comentarios]',
        ).click();

        await vi.waitFor(() => {
            expect(
                contentor.querySelector(
                    '#secao-b25-20',
                ).dataset.comentariosCarregados,
            ).toBe('true');
        });

        expect(pedido).toHaveBeenCalledTimes(1);
    });

    it('reutiliza a instancia e inicializa formularios novos', async () => {
        const inicializador =
            new InicializadorComentarios(contentor);

        const novoFormulario = criarFormulario();

        contentor.append(novoFormulario);

        const pedido = vi.spyOn(
            axios,
            'post',
        ).mockResolvedValue({
            data: {
                mensagem: 'OK',
            },
        });

        const reutilizado =
            new InicializadorComentarios(contentor);

        expect(reutilizado).toBe(inicializador);

        const evento = submeter(novoFormulario);

        expect(evento.defaultPrevented).toBe(true);

        await vi.waitFor(() => {
            expect(
                novoFormulario.querySelector('button').disabled,
            ).toBe(false);
        });

        expect(pedido).toHaveBeenCalledTimes(1);
    });
});
