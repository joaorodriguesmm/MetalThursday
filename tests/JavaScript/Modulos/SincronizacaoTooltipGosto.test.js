import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import axios from '../../../resources/js/modulos/ClienteHttp.js';
import GestorInteracoes
    from '../../../resources/js/modulos/GestorInteracoes.js';

describe(
    'GestorInteracoes — respostas antigas dos tooltips de gostos',
    () => {
        beforeEach(
            () => {
                document.body.innerHTML = `
                    <main id="interacoes">
                        <button
                            id="gosto"
                            type="button"
                            data-tipo-interacao="alternar-gosto"
                            data-endereco-utilizadores-gosto="/comentarios/50/utilizadores-gosto"
                            data-bs-toggle="tooltip"
                            data-bs-title="A carregar..."
                            aria-pressed="false"
                        >
                            <span data-icone-gosto class="bi bi-heart"></span>
                            <span data-quantidade-gostos>0</span>
                        </button>
                    </main>
                `;
            },
        );

        afterEach(
            () => {
                vi.restoreAllMocks();
                document.body.replaceChildren();
            },
        );

        it(
            'ignora o resultado de uma consulta anterior a um gosto atualizado',
            async () => {
                let resolverConsulta;
                const consultaPendente = new Promise(
                    (resolver) => {
                        resolverConsulta = resolver;
                    },
                );
                const consulta = vi.spyOn(axios, 'get')
                    .mockReturnValue(consultaPendente);

                const botao = document.getElementById('gosto');
                const gestor = new GestorInteracoes('#interacoes');
                const atualizarTooltip = vi.spyOn(gestor, 'atualizarTooltip')
                    .mockImplementation((elemento, conteudo) => {
                        elemento.setAttribute('data-bs-title', conteudo);
                    });

                const carregamento = gestor.carregarTooltipGostos(botao);
                expect(consulta).toHaveBeenCalledTimes(1);
                expect(botao.dataset.estadoTooltipGostos).toBe('a-carregar');

                gestor.atualizarGosto(botao, {
                    adicionado: true,
                    numero_gostos: 1,
                    conteudo_indicador_html: 'Lista atualizada',
                });
                expect(botao.dataset.estadoTooltipGostos).toBe('carregado');

                resolverConsulta({
                    data: { conteudo_indicador_html: 'Lista desatualizada' },
                });
                await carregamento;

                expect(atualizarTooltip).toHaveBeenCalledTimes(1);
                expect(botao.getAttribute('data-bs-title'))
                    .toBe('Lista atualizada');
                expect(botao.dataset.estadoTooltipGostos).toBe('carregado');
                expect(botao.getAttribute('aria-pressed')).toBe('true');
            },
        );

        it(
            'preserva o tooltip novo quando uma consulta anterior falha',
            async () => {
                let rejeitarConsulta;
                const consultaPendente = new Promise(
                    (_resolver, rejeitar) => {
                        rejeitarConsulta = rejeitar;
                    },
                );
                vi.spyOn(axios, 'get')
                    .mockReturnValue(consultaPendente);

                const botao = document.getElementById('gosto');
                const gestor = new GestorInteracoes('#interacoes');
                vi.spyOn(gestor, 'atualizarTooltip')
                    .mockImplementation((elemento, conteudo) => {
                        elemento.setAttribute('data-bs-title', conteudo);
                    });

                const carregamento = gestor.carregarTooltipGostos(botao);

                gestor.atualizarGosto(botao, {
                    adicionado: true,
                    numero_gostos: 1,
                    conteudo_indicador_html: 'Lista atualizada',
                });
                rejeitarConsulta(new Error('Erro da consulta antiga'));
                await carregamento;

                expect(botao.dataset.estadoTooltipGostos).toBe('carregado');
                expect(botao.getAttribute('data-bs-title'))
                    .toBe('Lista atualizada');
                expect(botao.getAttribute('aria-pressed')).toBe('true');
            },
        );
    },
);
