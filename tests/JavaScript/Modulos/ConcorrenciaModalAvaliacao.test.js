import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import axios from '../../../resources/js/modulos/ClienteHttp.js';
import GestorAlertas from '../../../resources/js/modulos/GestorAlertas.js';
import GestorModalAvaliacao
    from '../../../resources/js/modulos/GestorModalAvaliacao.js';

describe(
    'GestorModalAvaliacao — concorrencia durante confirmacao de limpeza',
    () => {
        beforeEach(() => {
            document.body.innerHTML = criarEstrutura();
        });

        afterEach(() => {
            vi.restoreAllMocks();
            document.body.replaceChildren();
        });

        it(
            'nao abre uma segunda confirmacao enquanto a primeira esta pendente',
            async () => {
                const { promessa, resolver } = criarPromessaPendente();
                const confirmar = vi.spyOn(
                    GestorAlertas,
                    'confirmar',
                ).mockReturnValue(promessa);
                const gestor = prepararGestor();

                const primeiraLimpeza = gestor.limparAvaliacao();
                const segundaLimpeza = gestor.limparAvaliacao();

                resolver(false);
                await Promise.all([primeiraLimpeza, segundaLimpeza]);

                expect(confirmar).toHaveBeenCalledTimes(1);
            },
        );

        it(
            'nao permite guardar outra avaliacao durante a confirmacao de limpeza',
            async () => {
                const { promessa, resolver } = criarPromessaPendente();
                vi.spyOn(
                    GestorAlertas,
                    'confirmar',
                ).mockReturnValue(promessa);
                const pedido = vi.spyOn(
                    axios,
                    'post',
                ).mockRejectedValue(new Error('Falha simulada.'));
                const gestor = prepararGestor();
                vi.spyOn(
                    gestor,
                    'tratarErroSubmissao',
                ).mockImplementation(() => {});

                const limpeza = gestor.limparAvaliacao();
                const submissao = gestor.submeterFormulario({
                    preventDefault() {},
                });

                resolver(false);
                await Promise.all([limpeza, submissao]);

                expect(pedido).not.toHaveBeenCalled();
            },
        );

        it(
            'impede fechar a modal enquanto a confirmacao de limpeza esta pendente',
            async () => {
                const { promessa, resolver } = criarPromessaPendente();
                vi.spyOn(
                    GestorAlertas,
                    'confirmar',
                ).mockReturnValue(promessa);
                const gestor = prepararGestor();
                const impedirFecho = vi.fn();

                const limpeza = gestor.limparAvaliacao();
                gestor.tratarFechoModal({
                    preventDefault: impedirFecho,
                });

                resolver(false);
                await limpeza;

                expect(impedirFecho).toHaveBeenCalledTimes(1);
            },
        );
        it(
            'liberta o bloqueio quando a confirmacao de limpeza e cancelada',
            async () => {
                const confirmar = vi.spyOn(
                    GestorAlertas,
                    'confirmar',
                ).mockResolvedValue(false);

                const gestor = prepararGestor();

                await gestor.limparAvaliacao();

                expect(gestor.emSubmissao).toBe(false);

                await gestor.limparAvaliacao();

                expect(confirmar).toHaveBeenCalledTimes(2);
                expect(gestor.emSubmissao).toBe(false);
            },
        );

        it(
            'liberta o bloqueio quando a confirmacao de limpeza falha',
            async () => {
                const confirmar = vi.spyOn(
                    GestorAlertas,
                    'confirmar',
                )
                    .mockRejectedValueOnce(
                        new Error('Falha simulada na confirmação.'),
                    )
                    .mockResolvedValueOnce(false);

                const gestor = prepararGestor();

                await expect(
                    gestor.limparAvaliacao(),
                ).rejects.toThrow('Falha simulada na confirmação.');

                expect(gestor.emSubmissao).toBe(false);

                await gestor.limparAvaliacao();

                expect(confirmar).toHaveBeenCalledTimes(2);
                expect(gestor.emSubmissao).toBe(false);
            },
        );

        it(
            'liberta o bloqueio se a preparacao da limpeza falhar',
            async () => {
                vi.spyOn(
                    GestorAlertas,
                    'confirmar',
                ).mockResolvedValue(true);

                const gestor = prepararGestor();

                vi.spyOn(
                    gestor,
                    'iniciarEstadoOperacao',
                ).mockImplementation(() => {
                    throw new Error('Falha simulada na preparação.');
                });

                await expect(
                    gestor.limparAvaliacao(),
                ).rejects.toThrow('Falha simulada na preparação.');

                expect(gestor.emSubmissao).toBe(false);
            },
        );

        it(
            'liberta o bloqueio se o restauro da limpeza falhar',
            async () => {
                vi.spyOn(
                    GestorAlertas,
                    'confirmar',
                ).mockResolvedValue(true);
                vi.spyOn(
                    GestorAlertas,
                    'mostrarErro',
                ).mockResolvedValue(undefined);
                vi.spyOn(
                    axios,
                    'delete',
                ).mockRejectedValue(new Error('Falha HTTP simulada.'));

                const gestor = prepararGestor();
                vi.spyOn(
                    gestor,
                    'restaurarEstadoOperacao',
                ).mockImplementation(() => {
                    throw new Error('Falha simulada no restauro.');
                });

                await expect(gestor.limparAvaliacao())
                    .rejects.toThrow('Falha simulada no restauro.');

                expect(gestor.emSubmissao).toBe(false);
            },
        );
    },
);

/**
 * Configura uma avaliacao existente com a modal de teste.
 *
 * @returns {GestorModalAvaliacao} Gestor configurado.
 */
function prepararGestor() {
    const gestor = new GestorModalAvaliacao();
    const acionador = document.getElementById('botao-avaliar');

    gestor.configurarModal({
        relatedTarget: acionador,
    });

    return gestor;
}

/**
 * Controla explicitamente o fim da confirmacao assincrona.
 *
 * @returns {{ promessa: Promise<boolean>, resolver: (valor: boolean) => void }}
 */
function criarPromessaPendente() {
    let resolver;
    const promessa = new Promise((resolve) => {
        resolver = resolve;
    });

    return { promessa, resolver };
}

/**
 * Cria o DOM minimo da modal e de uma avaliacao existente.
 *
 * @returns {string} HTML de teste.
 */
function criarEstrutura() {
    return `
        <div data-contentor-interacoes>
            <button
                id="botao-avaliar"
                type="button"
                data-tipo-avaliavel="lancamento"
                data-identificador-avaliavel="77"
                data-nome-avaliavel="Master of Puppets"
                data-endereco-avaliacao="/avaliacoes/77"
                data-pontuacao-utilizador="3.5"
                data-texto-sem-avaliacao="Avaliar"
            >
                <span data-texto-avaliacao>A tua avaliação: 3,5</span>
            </button>
            <button type="button" class="apresentacao-avaliacoes">
                <span class="media-avaliacoes">3,8</span>
                <span class="quantidade-avaliacoes">8</span>
            </button>
        </div>
        <div id="modal-avaliacao" class="modal">
            <form id="formulario-avaliacao" data-formulario-avaliacao>
                <span data-nome-avaliavel>elemento selecionado</span>
                <div data-estrelas-avaliacao>
                    <button type="button" data-valor="1" aria-label="1 de 5"></button>
                    <button type="button" data-valor="2" aria-label="2 de 5"></button>
                    <button type="button" data-valor="3" aria-label="3 de 5"></button>
                    <button type="button" data-valor="4" aria-label="4 de 5"></button>
                    <button type="button" data-valor="5" aria-label="5 de 5"></button>
                </div>
                <input type="hidden" name="pontuacao" value="0">
                <p data-feedback-avaliacao></p>
                <p id="erro-pontuacao-avaliacao"></p>
                <button type="submit">Guardar</button>
                <button type="button" data-limpar-avaliacao hidden>Limpar</button>
            </form>
        </div>
    `;
}
