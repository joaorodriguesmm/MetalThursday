import AlternadorPalavraPasse
    from '../modulos/AlternadorPalavraPasse';

import GestorFotografiaPerfil
    from '../modulos/GestorFotografiaPerfil';

import InicializadorTooltips
    from '../modulos/InicializadorTooltips';

import SeletorPermissoes
    from '../modulos/SeletorPermissoes';

import ValidadorFicheiro
    from '../modulos/ValidadorFicheiro';

import ValidadorFormulario
    from '../modulos/ValidadorFormulario';

/**
 * Tipos MIME permitidos para a fotografia do utilizador.
 *
 * @type {ReadonlyArray<string>}
 *
 * @since 2.0.0
 * @version 1.0.0
 */
const TIPOS_FOTOGRAFIA_PERMITIDOS = Object.freeze([
    'image/jpeg',
    'image/png',
    'image/webp',
]);

/**
 * Tamanho máximo permitido para a fotografia.
 *
 * Corresponde a 10 MiB.
 *
 * @type {number}
 *
 * @since 2.0.0
 * @version 1.0.0
 */
const TAMANHO_MAXIMO_FOTOGRAFIA =
    10 * 1024 * 1024;

/**
 * Seletores utilizados na página de registo por convite.
 *
 * @type {Readonly<Object>}
 *
 * @since 2.0.0
 * @version 1.0.0
 */
const SELETORES = Object.freeze({
    formulario:
        '#formulario-registo-convite',

    fotografia:
        '#fotografia',

    previsualizacaoFotografia:
        '#previsualizacao-fotografia',

    iniciaisAvatar:
        '#iniciais-avatar',

    erroFotografia:
        '#erro-fotografia',

    textoFotografia:
        '#texto-fotografia',

    permissaoTodas:
        '[data-permissao-todas="true"]',

    itemPermissaoEmail:
        '[data-item-permissao-email]',

    alternadorPalavraPasse:
        '[data-alvo-palavra-passe]',

    tooltip:
        '[data-bs-toggle="tooltip"]',
});

/**
 * Inicia o gestor da fotografia do utilizador.
 *
 * @return {GestorFotografiaPerfil|null} Gestor iniciado ou nulo quando os
 * elementos necessários não existem.
 *
 * @since 2.0.0
 * @version 1.0.0
 */
function iniciarGestorFotografia() {
    const gestorFotografia =
        new GestorFotografiaPerfil(
            SELETORES.fotografia,
            SELETORES.previsualizacaoFotografia,
            SELETORES.iniciaisAvatar,
        );

    if (!gestorFotografia.estaDisponivel()) {
        return null;
    }

    return gestorFotografia;
}

/**
 * Inicia a validação e a pré-visualização da fotografia.
 *
 * @param {GestorFotografiaPerfil|null}
 * gestorFotografia - Gestor da fotografia.
 *
 * @return {void}
 *
 * @since 2.0.0
 * @version 1.0.0
 */
function iniciarValidacaoFotografia(
    gestorFotografia,
) {
    if (
        !(gestorFotografia
            instanceof GestorFotografiaPerfil)
    ) {
        return;
    }

    const campoFotografia =
        gestorFotografia.obterCampoFicheiro();

    if (!(campoFotografia instanceof HTMLInputElement)) {
        return;
    }

    new ValidadorFicheiro(
        campoFotografia,
        {
            tiposPermitidos:
                TIPOS_FOTOGRAFIA_PERMITIDOS,

            tamanhoMaximo:
                TAMANHO_MAXIMO_FOTOGRAFIA,

            seletorMensagemErro:
                SELETORES.erroFotografia,

            seletorTextoFicheiro:
                SELETORES.textoFotografia,

            textoPadrao:
                'Selecionar fotografia',

            textoSelecionado:
                'Alterar fotografia',

            aoFicheiroInvalido: () => {
                gestorFotografia
                    .restaurarPrevisualizacao();
            },

            aoFicheiroValido: (ficheiro) => {
                gestorFotografia
                    .previsualizarImagem(
                        ficheiro,
                    );
            },

            aoLimparSelecao: () => {
                gestorFotografia
                    .restaurarPrevisualizacao();
            },
        },
    );
}

/**
 * Inicia a validação do formulário de registo.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function iniciarValidacaoFormulario() {
    const formulario = document.querySelector(
        SELETORES.formulario,
    );

    if (!(formulario instanceof HTMLFormElement)) {
        return;
    }

    new ValidadorFormulario(
        formulario,
        {
            regras: {
                codigo_convite: [
                    'obrigatorio',
                ],

                nome: [
                    'obrigatorio',
                    'minimo:3',
                    'maximo:255',
                ],

                email: [
                    'obrigatorio',
                    'email',
                    'maximo:255',
                ],

                palavra_passe: [
                    'obrigatorio',
                    'minimo:12',
                    'maiuscula',
                    'minuscula',
                    'numero',
                    'simbolo',
                ],

                confirmacao_palavra_passe: [
                    'obrigatorio',
                    'confirmado:palavra_passe',
                ],
            },

            mensagens: {
                codigo_convite: {
                    obrigatorio:
                        'Ocorreu um erro ao validar a integridade do convite. Recarrega a página e tenta novamente.',
                },

                nome: {
                    obrigatorio:
                        'Por favor, insere o teu nome.',

                    minimo:
                        'O nome deve ter, no mínimo, 3 caracteres.',

                    maximo:
                        'O nome deve ter, no máximo, 255 caracteres.',
                },

                email: {
                    obrigatorio:
                        'Por favor, insere o teu endereço de e-mail.',

                    email:
                        'Por favor, insere um endereço de e-mail válido.',

                    maximo:
                        'O endereço de e-mail deve ter, no máximo, 255 caracteres.',
                },

                palavra_passe: {
                    obrigatorio:
                        'Por favor, insere uma palavra-passe.',

                    minimo:
                        'A palavra-passe deve ter, no mínimo, 12 caracteres.',

                    maiuscula:
                        'A palavra-passe deve incluir, pelo menos, uma letra maiúscula.',

                    minuscula:
                        'A palavra-passe deve incluir, pelo menos, uma letra minúscula.',

                    numero:
                        'A palavra-passe deve incluir, pelo menos, um número.',

                    simbolo:
                        'A palavra-passe deve incluir, pelo menos, um símbolo.',
                },

                confirmacao_palavra_passe: {
                    obrigatorio:
                        'Por favor, confirma a palavra-passe.',

                    confirmado:
                        'A confirmação não corresponde à palavra-passe.',
                },
            },
        },
    );
}

/**
 * Inicia os alternadores de visibilidade das palavras-passe.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function iniciarAlternadoresPalavraPasse() {
    const alternadores =
        document.querySelectorAll(
            SELETORES.alternadorPalavraPasse,
        );

    if (alternadores.length === 0) {
        return;
    }

    new AlternadorPalavraPasse(
        alternadores,
    );
}

/**
 * Inicia o seletor das permissões de e-mail.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function iniciarPermissoesEmail() {
    const campoTodas = document.querySelector(
        SELETORES.permissaoTodas,
    );

    if (!(campoTodas instanceof HTMLInputElement)) {
        return;
    }

    const itensPermissoes =
        document.querySelectorAll(
            SELETORES.itemPermissaoEmail,
        );

    if (itensPermissoes.length === 0) {
        return;
    }

    new SeletorPermissoes(
        campoTodas,
        itensPermissoes,
    );
}

/**
 * Inicia os tooltips existentes na página.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function iniciarTooltips() {
    new InicializadorTooltips(
        SELETORES.tooltip,
    );
}

/**
 * Inicia os comportamentos da página de registo por convite.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function iniciarPaginaRegistoConvite() {
    const gestorFotografia =
        iniciarGestorFotografia();

    iniciarValidacaoFotografia(
        gestorFotografia,
    );

    iniciarValidacaoFormulario();
    iniciarAlternadoresPalavraPasse();
    iniciarPermissoesEmail();
    iniciarTooltips();
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        iniciarPaginaRegistoConvite,
        {
            once: true,
        },
    );
} else {
    iniciarPaginaRegistoConvite();
}
