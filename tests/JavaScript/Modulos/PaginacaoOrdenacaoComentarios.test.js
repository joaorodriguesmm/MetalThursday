import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import axios from '../../../resources/js/modulos/ClienteHttp.js';
import InicializadorComentarios from '../../../resources/js/modulos/InicializadorComentarios.js';

describe('Paginação e ordenação dos comentários', () => {
    let gestor;
    let alternador;
    let contentor;

    const html = (numeros) => `
        <section aria-label="Comentários">
            <select data-ordenacao-comentarios>
                <option value="recentes">Recentes</option>
                <option value="antigos">Antigos</option>
                <option value="populares">Populares</option>
            </select>
            <div class="lista-comentarios">
                ${numeros.map((numero) => `<article class="comentario" id="comentario-${numero}"></article>`).join('')}
            </div>
        </section>
    `;

    beforeEach(() => {
        document.body.innerHTML = `
            <button id="alternador" type="button" data-acao-comentarios="alternar-seccao"
                data-endereco-comentarios="/metal-thursday/1/comentarios"
                aria-controls="comentarios-metal-thursday-1">
                <span data-quantidade-comentarios>25</span>
            </button>
            <div id="comentarios-metal-thursday-1" data-comentarios-carregados="false"></div>
        `;
        gestor = new InicializadorComentarios();
        alternador = document.getElementById('alternador');
        contentor = document.getElementById('comentarios-metal-thursday-1');
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('carrega a segunda página sem repetir os comentários existentes', async () => {
        const pedido = vi.spyOn(axios, 'get')
            .mockResolvedValueOnce({ data: {
                numero_comentarios: 25, pagina: 1, ordenacao: 'recentes',
                proxima_pagina: 2, comentarios_html: html([25, 24]),
            } })
            .mockResolvedValueOnce({ data: {
                numero_comentarios: 25, pagina: 2, ordenacao: 'recentes',
                proxima_pagina: null, comentarios_html: html([23, 22]),
            } });

        await gestor.carregarSeccaoComentarios(alternador);
        const botaoMais = contentor.querySelector('button[data-acao-comentarios="mais-principais"]');
        expect(botaoMais).not.toBeNull();
        await gestor.carregarMaisPrincipais(botaoMais);

        expect(pedido).toHaveBeenNthCalledWith(2, '/metal-thursday/1/comentarios', {
            params: { pagina: 2, ordenacao: 'recentes' },
        });
        expect(contentor.querySelectorAll('.lista-comentarios > .comentario')).toHaveLength(4);
        expect(contentor.querySelector('button[data-acao-comentarios="mais-principais"]')).toBeNull();
    });

    it('substitui a lista ao mudar a ordenação sem duplicar comentários', async () => {
        vi.spyOn(axios, 'get')
            .mockResolvedValueOnce({ data: {
                numero_comentarios: 2, pagina: 1, ordenacao: 'recentes',
                proxima_pagina: null, comentarios_html: html([2, 1]),
            } })
            .mockResolvedValueOnce({ data: {
                numero_comentarios: 2, pagina: 1, ordenacao: 'antigos',
                proxima_pagina: null, comentarios_html: html([1, 2]),
            } });
        await gestor.carregarSeccaoComentarios(alternador);
        const seletor = contentor.querySelector('select[data-ordenacao-comentarios]');
        seletor.value = 'antigos';
        await gestor.tratarAlteracaoOrdenacao({ target: seletor });

        expect(axios.get).toHaveBeenLastCalledWith('/metal-thursday/1/comentarios', {
            params: { ordenacao: 'antigos' },
        });
        expect(Array.from(contentor.querySelectorAll('.lista-comentarios > .comentario'))
            .map((elemento) => elemento.id)).toEqual(['comentario-1', 'comentario-2']);
    });
});

describe('Paginação de respostas', () => {
    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('rejeita uma página parcial sem continuação válida', async () => {
        document.body.innerHTML = `
            <article class="comentario" id="comentario-10" data-nivel-visual="1">
                <button type="button" data-acao-comentarios="alternar-respostas"
                    data-endereco-respostas="/comentarios/10/respostas"
                    data-quantidade-respostas="25" aria-expanded="false">
                    <span data-texto-alternador-respostas>Ver 25 respostas</span>
                </button>
                <div data-respostas-comentario data-respostas-carregadas="false" hidden></div>
            </article>
        `;

        vi.spyOn(axios, 'get').mockResolvedValue({ data: {
            numero_respostas: 25,
            proxima_pagina: 2,
            respostas: [{
                comentario_html: '<article class="comentario" id="comentario-11"></article>',
            }],
        } });

        const gestor = new InicializadorComentarios();
        const alternador = document.querySelector('button[data-acao-comentarios="alternar-respostas"]');
        const contentor = document.querySelector('[data-respostas-comentario]');

        await gestor.alternarRespostas(alternador);

        expect(contentor.dataset.respostasCarregadas).toBe('false');
        expect(contentor.hidden).toBe(true);
        expect(contentor.children).toHaveLength(0);
        expect(alternador.getAttribute('aria-expanded')).toBe('false');
        expect(alternador.disabled).toBe(false);
    });

    it('carrega mais respostas sem perder as anteriores', async () => {
        document.body.innerHTML = `
            <article class="comentario" id="comentario-10" data-nivel-visual="1">
                <button type="button" data-acao-comentarios="alternar-respostas"
                    data-endereco-respostas="/comentarios/10/respostas"
                    data-quantidade-respostas="25" aria-expanded="false">
                    <span data-texto-alternador-respostas>Ver 25 respostas</span>
                </button>
                <div data-respostas-comentario data-respostas-carregadas="false" hidden></div>
            </article>
        `;

        const respostas = (inicio, fim) => Array.from({ length: fim - inicio + 1 }, (_, indice) => ({
            comentario_html: `<article class="comentario" id="comentario-${inicio + indice}"></article>`,
        }));
        vi.spyOn(axios, 'get')
            .mockResolvedValueOnce({ data: {
                numero_respostas: 25, proxima_pagina: 2,
                respostas: respostas(11, 30),
            } })
            .mockResolvedValueOnce({ data: {
                numero_respostas: 25, proxima_pagina: null,
                respostas: respostas(31, 35),
            } });

        const gestor = new InicializadorComentarios();
        const alternador = document.querySelector('button[data-acao-comentarios="alternar-respostas"]');
        await gestor.alternarRespostas(alternador);
        const botaoMais = document.querySelector('button[data-acao-comentarios="mais-respostas"]');
        expect(botaoMais).not.toBeNull();
        await gestor.carregarMaisRespostas(botaoMais);

        expect(axios.get).toHaveBeenLastCalledWith('/comentarios/10/respostas', {
            params: { pagina: 2 },
        });
        expect(document.querySelectorAll('[data-respostas-comentario] > .comentario')).toHaveLength(25);
        expect(document.querySelector('button[data-acao-comentarios="mais-respostas"]')).toBeNull();
    });
});
