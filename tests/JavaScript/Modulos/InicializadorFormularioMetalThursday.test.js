import {
    afterEach,
    describe,
    expect,
    it,
} from 'vitest';

import {
    obterConfiguracaoFormulario,
} from '../../../resources/js/modulos/InicializadorFormularioMetalThursday';

/**
 * Endereços válidos utilizados pela configuração de teste.
 *
 * @returns {Record<string, string>} Endereços da configuração.
 */
function criarEnderecos() {
    return {
        guardarEdicao:
            '/edicoes',

        guardarArtista:
            '/artistas',

        guardarGenero:
            '/generos',

        pesquisarLancamentos:
            '/discogs/pesquisar',

        importarLancamento:
            '/discogs/importar',

        obterUtilizadorHaMaisTempoSemNomeacao:
            '/utilizadores/mais-antigo',
    };
}

/**
 * Acrescenta ao documento o bloco JSON de configuração.
 *
 * @param {string} conteudo Conteúdo a inserir no bloco.
 *
 * @returns {HTMLScriptElement} Elemento criado.
 */
function adicionarConfiguracao(
    conteudo,
) {
    const elemento =
        document.createElement(
            'script',
        );

    elemento.id =
        'configuracao-formulario-metal-thursday';

    elemento.type =
        'application/json';

    elemento.textContent =
        conteudo;

    document.body.append(
        elemento,
    );

    return elemento;
}

afterEach(
    () => {
        document.body.replaceChildren();
    },
);

describe(
    'configuração do formulário de MetalThursday',
    () => {
        it(
            'obtém e normaliza os endereços preparados pelo servidor',
            () => {
                adicionarConfiguracao(
                    JSON.stringify({
                        enderecos:
                            criarEnderecos(),
                    }),
                );

                const configuracao =
                    obterConfiguracaoFormulario();

                expect(
                    configuracao.enderecos,
                ).toEqual({
                    guardarEdicao:
                        'http://localhost:3000/edicoes',

                    guardarArtista:
                        'http://localhost:3000/artistas',

                    guardarGenero:
                        'http://localhost:3000/generos',

                    pesquisarLancamentos:
                        'http://localhost:3000/discogs/pesquisar',

                    importarLancamento:
                        'http://localhost:3000/discogs/importar',

                    obterUtilizadorHaMaisTempoSemNomeacao:
                        'http://localhost:3000/utilizadores/mais-antigo',
                });

                expect(
                    Object.isFrozen(
                        configuracao.enderecos,
                    ),
                ).toBe(
                    true,
                );
            },
        );

        it(
            'rejeita a ausência do bloco de configuração',
            () => {
                expect(
                    () =>
                        obterConfiguracaoFormulario(),
                ).toThrow(
                    'Não foi encontrada a configuração do formulário de MetalThursday.',
                );
            },
        );

        it(
            'rejeita um bloco de configuração vazio',
            () => {
                adicionarConfiguracao(
                    '   ',
                );

                expect(
                    () =>
                        obterConfiguracaoFormulario(),
                ).toThrow(
                    'A configuração do formulário de MetalThursday está vazia.',
                );
            },
        );

        it(
            'rejeita JSON inválido',
            () => {
                adicionarConfiguracao(
                    '{invalido',
                );

                expect(
                    () =>
                        obterConfiguracaoFormulario(),
                ).toThrow(
                    'A configuração do formulário de MetalThursday contém JSON inválido.',
                );
            },
        );

        it(
            'rejeita uma estrutura sem endereços',
            () => {
                adicionarConfiguracao(
                    JSON.stringify({}),
                );

                expect(
                    () =>
                        obterConfiguracaoFormulario(),
                ).toThrow(
                    'A configuração do formulário de MetalThursday é inválida.',
                );
            },
        );
    },
);
