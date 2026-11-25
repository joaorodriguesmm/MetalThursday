import Collapse from 'bootstrap/js/dist/collapse';

import axios from './ClienteHttp';

/**
 * Abre o comentário indicado por uma ligação de notificação.
 *
 * Consulta a cadeia de ancestrais no servidor e expande cada nível
 * sequencialmente, sem carregar as respostas de outros ramos.
 *
 * @since 2.0.0
 */
class NavegadorComentarios {
    constructor(gestorComentarios) {
        this.gestorComentarios = gestorComentarios;
    }

    async navegar() {
        const identificador = new URL(window.location.href)
            .searchParams.get('comentario');

        if (!identificador || !/^[1-9]\d{0,17}$/.test(identificador)) {
            return;
        }

        const cartao = document.querySelector(
            '[data-endereco-localizacao-comentario]',
        );

        if (!(cartao instanceof HTMLElement)) {
            return;
        }

        try {
            const resposta = await axios.get(
                cartao.dataset.enderecoLocalizacaoComentario,
                { params: { comentario: identificador } },
            );

            const dados = resposta.data;

            if (
                !dados
                || !Array.isArray(dados.caminho)
                || dados.caminho.length === 0
                || dados.caminho.some((valor) => !Number.isSafeInteger(valor) || valor <= 0)
                || String(dados.caminho[dados.caminho.length - 1]) !== identificador
                || String(dados.identificador_metal_thursday)
                    !== cartao.dataset.identificadorMetalThursday
            ) {
                throw new Error('Localização de comentário inválida.');
            }

            const paginas = Array.isArray(dados.paginas)
                && dados.paginas.length === dados.caminho.length
                && dados.paginas.every((pagina) => Number.isSafeInteger(pagina) && pagina >= 1)
                ? dados.paginas
                : dados.caminho.map(() => 1);

            const identificadorContentor = dados.tipo_comentavel
                === 'seccao_metal_thursday'
                ? `comentarios-seccao-${dados.identificador_comentavel}`
                : dados.tipo_comentavel === 'metal_thursday'
                    ? `comentarios-metal-thursday-${dados.identificador_comentavel}`
                    : null;

            if (identificadorContentor === null) {
                throw new Error('Entidade do comentário inválida.');
            }

            const contentor = document.getElementById(identificadorContentor);
            const botao = Array.from(cartao.querySelectorAll('button[aria-controls]'))
                .find((elemento) => elemento.getAttribute('aria-controls') === identificadorContentor);

            if (
                !(contentor instanceof HTMLElement)
                || !(botao instanceof HTMLButtonElement)
                || !cartao.contains(contentor)
            ) {
                throw new Error('Secção de comentários indisponível.');
            }

            if (paginas[0] > 1) {
                await this.gestorComentarios.carregarPaginaPrincipalParaNavegacao(botao, paginas[0]);
            } else if (contentor.dataset.comentariosCarregados === 'false') {
                await this.gestorComentarios.carregarSeccaoComentarios(botao);
            }

            await this.abrirSeccao(contentor);

            let comentario = null;

            for (const [indice, id] of dados.caminho.entries()) {
                comentario = document.getElementById(`comentario-${id}`);

                if (!(comentario instanceof HTMLElement) || !contentor.contains(comentario)) {
                    throw new Error('Comentário indisponível.');
                }

                if (indice < dados.caminho.length - 1) {
                    const alternador = this.gestorComentarios.obterAlternadorRespostas(comentario);

                    if (!(alternador instanceof HTMLButtonElement)) {
                        throw new Error('Respostas indisponíveis.');
                    }

                    if (paginas[indice + 1] > 1) {
                        await this.gestorComentarios.carregarPaginaRespostasParaNavegacao(
                            alternador,
                            paginas[indice + 1],
                        );
                    } else if (alternador.getAttribute('aria-expanded') !== 'true') {
                        await this.gestorComentarios.alternarRespostas(alternador);
                    }
                }
            }

            comentario.classList.add('border', 'border-warning', 'rounded', 'p-2');
            comentario.setAttribute('tabindex', '-1');
            comentario.focus({ preventScroll: true });
            comentario.scrollIntoView({ behavior: 'smooth', block: 'center' });
        } catch {
            this.apresentarAviso(cartao);
        }
    }

    abrirSeccao(contentor) {
        if (contentor.classList.contains('show')) {
            return Promise.resolve();
        }

        return new Promise((resolver, rejeitar) => {
            const aoAbrir = () => {
                window.clearTimeout(tempoLimite);
                resolver();
            };

            const tempoLimite = window.setTimeout(() => {
                contentor.removeEventListener('shown.bs.collapse', aoAbrir);
                rejeitar(new Error('Não foi possível abrir os comentários.'));
            }, 1500);

            contentor.addEventListener('shown.bs.collapse', aoAbrir, { once: true });
            Collapse.getOrCreateInstance(contentor, { toggle: false }).show();
        });
    }

    apresentarAviso(cartao) {
        const aviso = document.createElement('p');
        aviso.className = 'alert alert-warning mt-3';
        aviso.setAttribute('role', 'status');
        aviso.textContent = 'Não foi possível localizar o comentário. Pode ter sido eliminado ou estar indisponível.';
        cartao.after(aviso);
    }
}

export default NavegadorComentarios;
