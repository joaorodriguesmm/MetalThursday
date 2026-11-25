import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import Collapse from 'bootstrap/js/dist/collapse';
import axios from '../../../resources/js/modulos/ClienteHttp.js';
import NavegadorComentarios from '../../../resources/js/modulos/NavegadorComentarios.js';

describe('NavegadorComentarios', () => {
    let gestor;
    let deslocamentoOriginal;

    beforeEach(() => {
        window.history.replaceState({}, '', '/metal-thursday/20?comentario=11');
        document.body.innerHTML = `
            <article data-endereco-localizacao-comentario="/comentarios/localizacao"
                data-identificador-metal-thursday="20">
                <button type="button" aria-controls="comentarios-metal-thursday-20"></button>
                <div id="comentarios-metal-thursday-20" class="collapse show"
                    data-comentarios-carregados="true">
                    <article class="comentario" id="comentario-11"></article>
                </div>
            </article>
        `;

        deslocamentoOriginal = HTMLElement.prototype.scrollIntoView;
        HTMLElement.prototype.scrollIntoView = vi.fn();
        gestor = {
            carregarSeccaoComentarios: vi.fn(),
            obterAlternadorRespostas: vi.fn(),
            alternarRespostas: vi.fn(),
        };
    });

    afterEach(() => {
        vi.restoreAllMocks();

        if (deslocamentoOriginal === undefined) {
            delete HTMLElement.prototype.scrollIntoView;
        } else {
            HTMLElement.prototype.scrollIntoView = deslocamentoOriginal;
        }

        window.history.replaceState({}, '', '/');
        document.body.replaceChildren();
    });

    it('destaca o comentário principal sem carregar outros ramos', async () => {
        vi.spyOn(axios, 'get').mockResolvedValue({ data: {
            identificador_metal_thursday: 20,
            tipo_comentavel: 'metal_thursday',
            identificador_comentavel: 20,
            caminho: [11],
        } });

        await new NavegadorComentarios(gestor).navegar();

        expect(axios.get).toHaveBeenCalledWith('/comentarios/localizacao', {
            params: { comentario: '11' },
        });
        expect(document.getElementById('comentario-11').classList.contains('border-warning'))
            .toBe(true);
        expect(gestor.alternarRespostas).not.toHaveBeenCalled();
    });

    it('abre apenas os ancestrais da resposta', async () => {
        window.history.replaceState({}, '', '/metal-thursday/20?comentario=13');
        vi.spyOn(axios, 'get').mockResolvedValue({ data: {
            identificador_metal_thursday: 20,
            tipo_comentavel: 'metal_thursday',
            identificador_comentavel: 20,
            caminho: [11, 12, 13],
        } });

        gestor.obterAlternadorRespostas.mockImplementation((comentario) => {
            const botao = document.createElement('button');
            botao.dataset.pai = comentario.id;
            botao.setAttribute('aria-expanded', 'false');
            return botao;
        });

        gestor.alternarRespostas.mockImplementation(async (alternador) => {
            const id = alternador.dataset.pai === 'comentario-11' ? 12 : 13;
            const artigo = document.createElement('article');
            artigo.id = `comentario-${id}`;
            artigo.className = 'comentario';
            document.getElementById(alternador.dataset.pai).append(artigo);
            alternador.setAttribute('aria-expanded', 'true');
        });

        await new NavegadorComentarios(gestor).navegar();

        expect(gestor.alternarRespostas).toHaveBeenCalledTimes(2);
        expect(document.getElementById('comentario-13').classList.contains('border-warning'))
            .toBe(true);
    });

    it('carrega e abre automaticamente a secção correta', async () => {
        window.history.replaceState({}, '', '/metal-thursday/20?comentario=21');
        document.body.innerHTML = `
            <article data-endereco-localizacao-comentario="/comentarios/localizacao"
                data-identificador-metal-thursday="20">
                <button type="button" aria-controls="comentarios-seccao-30"></button>
                <div id="comentarios-seccao-30" class="collapse"
                    data-comentarios-carregados="false"></div>
            </article>
        `;

        vi.spyOn(axios, 'get').mockResolvedValue({ data: {
            identificador_metal_thursday: 20,
            tipo_comentavel: 'seccao_metal_thursday',
            identificador_comentavel: 30,
            caminho: [21],
        } });

        gestor.carregarSeccaoComentarios.mockImplementation(async () => {
            const contentor = document.getElementById('comentarios-seccao-30');
            contentor.dataset.comentariosCarregados = 'true';
            contentor.innerHTML = '<article id="comentario-21" class="comentario"></article>';
        });

        vi.spyOn(Collapse, 'getOrCreateInstance').mockImplementation((contentor) => ({
            show: () => {
                contentor.classList.add('show');
                contentor.dispatchEvent(new Event('shown.bs.collapse'));
            },
        }));

        await new NavegadorComentarios(gestor).navegar();

        expect(gestor.carregarSeccaoComentarios).toHaveBeenCalledTimes(1);
        expect(document.getElementById('comentario-21').classList.contains('border-warning'))
            .toBe(true);
    });

    it('não apresenta dados de comentários que deixaram de estar disponíveis', async () => {
        vi.spyOn(axios, 'get').mockRejectedValue(new Error('404'));

        await new NavegadorComentarios(gestor).navegar();

        expect(document.querySelector('[role="status"]').textContent)
            .toContain('Não foi possível localizar');
    });

    it('ignora a página quando não existe identificador no endereço', async () => {
        window.history.replaceState({}, '', '/metal-thursday/20');
        const pedido = vi.spyOn(axios, 'get');

        await new NavegadorComentarios(gestor).navegar();

        expect(pedido).not.toHaveBeenCalled();
    });
});
