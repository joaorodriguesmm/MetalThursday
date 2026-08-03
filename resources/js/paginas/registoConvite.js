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
 * Configura os comportamentos da página de registo por convite.
 *
 * @since 1.0.0
 * @version 3.0.0
 */

/**
 * Tamanho máximo permitido para a fotografia, em bytes.
 *
 * O valor corresponde aos 10 240 KiB aceites pelo servidor.
 *
 * @type {number}
 *
 * @since 2.0.0
 * @version 2.0.0
 */
const TAMANHO_MAXIMO_FOTOGRAFIA =
    10 * (1024 ** 2);

/**
 * Seletores utilizados na página.
 *
 * @type {Readonly<Record<string, string>>}
 *
 * @since 2.0.0
 * @version 2.0.0
 */
const SELETORES = Object.freeze({
    formulario:
        '#formulario-aceitar-convite',

    fotografia:
        '#fotografia-perfil',

    previsualizacaoFotografia:
        '#previsualizacao-fotografia-perfil',

    iniciaisAvatar:
        '#iniciais-avatar-registo',

    erroFotografia:
        '#erro-fotografia',

    permissaoTodas:
        '[data-permissao-email-todas]',

    permissaoIndividual:
        '[data-permissao-email-individual]',

    alternadorPalavraPasse:
        '[data-alvo-palavra-passe]',

    tooltip:
        '[data-bs-toggle="tooltip"]',
});

/**
 * Obtém um campo de texto através do respetivo nome.
 *
 * @param {HTMLFormElement} formulario Formulário pesquisado.
 * @param {string} nomeCampo Nome HTML do campo.
 *
 * @returns {HTMLInputElement|null} Campo encontrado ou nulo.
 *
 * @since 3.0.0
 * @version 1.0.0
 */
function obterCampo(
    formulario,
    nomeCampo,
) {
    const campo =
        formulario.elements.namedItem(
            nomeCampo,
        );

    return campo instanceof HTMLInputElement
        ? campo
        : null;
}

/**
 * Obtém o comprimento mínimo declarado num campo.
 *
 * @param {HTMLInputElement|null} campo Campo recebido.
 * @param {number} valorPredefinido Valor utilizado quando não existe limite.
 *
 * @returns {number} Comprimento mínimo positivo.
 *
 * @since 3.0.0
 * @version 1.0.0
 */
function obterComprimentoMinimo(
    campo,
    valorPredefinido,
) {
    if (
        campo instanceof HTMLInputElement
        && Number.isInteger(campo.minLength)
        && campo.minLength > 0
    ) {
        return campo.minLength;
    }

    return valorPredefinido;
}

/**
 * Obtém o comprimento máximo declarado num campo.
 *
 * @param {HTMLInputElement|null} campo Campo recebido.
 * @param {number} valorPredefinido Valor utilizado quando não existe limite.
 *
 * @returns {number} Comprimento máximo positivo.
 *
 * @since 3.0.0
 * @version 1.0.0
 */
function obterComprimentoMaximo(
    campo,
    valorPredefinido,
) {
    if (
        campo instanceof HTMLInputElement
        && Number.isInteger(campo.maxLength)
        && campo.maxLength > 0
    ) {
        return campo.maxLength;
    }

    return valorPredefinido;
}

/**
 * Obtém os tipos MIME declarados no campo da fotografia.
 *
 * @param {HTMLInputElement} campoFotografia Campo da fotografia.
 *
 * @returns {Array<string>} Tipos MIME permitidos.
 *
 * @throws {TypeError} Quando o campo não declara tipos MIME válidos.
 *
 * @since 3.0.0
 * @version 1.0.0
 */
function obterTiposFotografiaPermitidos(
    campoFotografia,
) {
    const tipos =
        campoFotografia.accept
            .split(',')
            .map(
                (tipo) =>
                    tipo.trim().toLowerCase(),
            )
            .filter(
                (tipo) =>
                    tipo.includes('/'),
            );

    if (tipos.length === 0) {
        throw new TypeError(
            'O campo da fotografia deve declarar os tipos MIME permitidos.',
        );
    }

    return Array.from(
        new Set(tipos),
    );
}

/**
 * Obtém os contentores das permissões individuais.
 *
 * @returns {Array<HTMLElement>} Contentores encontrados.
 *
 * @throws {TypeError} Quando uma permissão não pertence a um contentor.
 *
 * @since 3.0.0
 * @version 1.0.0
 */
function obterItensPermissoesIndividuais() {
    return Array.from(
        document.querySelectorAll(
            SELETORES.permissaoIndividual,
        ),
    ).map((campo) => {
        if (!(campo instanceof HTMLInputElement)) {
            throw new TypeError(
                'Uma das permissões de e-mail não é representada por uma checkbox válida.',
            );
        }

        const item =
            campo.closest(
                '.form-check',
            );

        if (!(item instanceof HTMLElement)) {
            throw new TypeError(
                'Uma das permissões de e-mail não possui um contentor válido.',
            );
        }

        return item;
    });
}

/**
 * Inicia a validação e a pré-visualização da fotografia.
 *
 * @returns {void}
 *
 * @since 2.0.0
 * @version 3.0.0
 */
function iniciarFotografiaPerfil() {
    const gestorFotografia =
        new GestorFotografiaPerfil(
            SELETORES.fotografia,
            SELETORES.previsualizacaoFotografia,
            SELETORES.iniciaisAvatar,
        );

    if (!gestorFotografia.estaDisponivel()) {
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
                obterTiposFotografiaPermitidos(
                    campoFotografia,
                ),

            tamanhoMaximo:
                TAMANHO_MAXIMO_FOTOGRAFIA,

            seletorMensagemErro:
                SELETORES.erroFotografia,

            aoFicheiroValido: (ficheiro) => {
                gestorFotografia.previsualizarImagem(
                    ficheiro,
                );
            },

            aoFicheiroInvalido: () => {
                gestorFotografia
                    .restaurarPrevisualizacao();
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
 * @returns {void}
 *
 * @since 1.0.0
 * @version 3.0.0
 */
function iniciarValidacaoFormulario() {
    const formulario =
        document.querySelector(
            SELETORES.formulario,
        );

    if (!(formulario instanceof HTMLFormElement)) {
        return;
    }

    const campoNome =
        obterCampo(
            formulario,
            'nome',
        );

    const campoEmail =
        obterCampo(
            formulario,
            'email',
        );

    const campoPalavraPasse =
        obterCampo(
            formulario,
            'palavra_passe',
        );

    const campoConfirmacao =
        obterCampo(
            formulario,
            'confirmacao_palavra_passe',
        );

    const comprimentoMinimoNome =
        obterComprimentoMinimo(
            campoNome,
            3,
        );

    const comprimentoMaximoNome =
        obterComprimentoMaximo(
            campoNome,
            255,
        );

    const comprimentoMaximoEmail =
        obterComprimentoMaximo(
            campoEmail,
            255,
        );

    const comprimentoMinimoPalavraPasse =
        obterComprimentoMinimo(
            campoPalavraPasse,
            12,
        );

    const comprimentoMaximoPalavraPasse =
        obterComprimentoMaximo(
            campoPalavraPasse,
            4096,
        );

    const comprimentoMaximoConfirmacao =
        obterComprimentoMaximo(
            campoConfirmacao,
            comprimentoMaximoPalavraPasse,
        );

    new ValidadorFormulario(
        formulario,
        {
            regras: {
                codigo_convite: [
                    'obrigatorio',
                ],

                nome: [
                    'obrigatorio',
                    `minimo:${comprimentoMinimoNome}`,
                    `maximo:${comprimentoMaximoNome}`,
                ],

                email: [
                    'obrigatorio',
                    'email',
                    `maximo:${comprimentoMaximoEmail}`,
                ],

                palavra_passe: [
                    'obrigatorio',
                    `minimo:${comprimentoMinimoPalavraPasse}`,
                    `maximo:${comprimentoMaximoPalavraPasse}`,
                    'maiuscula',
                    'minuscula',
                    'numero',
                    'simbolo',
                ],

                confirmacao_palavra_passe: [
                    'obrigatorio',
                    `maximo:${comprimentoMaximoConfirmacao}`,
                    'confirmado:palavra_passe',
                ],
            },

            mensagens: {
                codigo_convite: {
                    obrigatorio:
                        'O código do convite não foi recebido. Recarrega a página e tenta novamente.',
                },

                nome: {
                    obrigatorio:
                        'Por favor, insere o teu nome.',

                    minimo:
                        `O nome deve ter, pelo menos, ${comprimentoMinimoNome} caracteres.`,

                    maximo:
                        `O nome não pode ter mais de ${comprimentoMaximoNome} caracteres.`,
                },

                email: {
                    obrigatorio:
                        'Por favor, insere o teu endereço de e-mail.',

                    email:
                        'Por favor, insere um endereço de e-mail válido.',

                    maximo:
                        `O endereço de e-mail não pode ter mais de ${comprimentoMaximoEmail} caracteres.`,
                },

                palavra_passe: {
                    obrigatorio:
                        'Por favor, insere uma palavra-passe.',

                    minimo:
                        `A palavra-passe deve ter, pelo menos, ${comprimentoMinimoPalavraPasse} caracteres.`,

                    maximo:
                        'A palavra-passe é demasiado longa.',

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

                    maximo:
                        'A confirmação da palavra-passe é demasiado longa.',

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
 * @returns {void}
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
 * @returns {void}
 *
 * @since 1.0.0
 * @version 3.0.0
 */
function iniciarPermissoesEmail() {
    const campoTodasPermissoes =
        document.querySelector(
            SELETORES.permissaoTodas,
        );

    if (
        !(
            campoTodasPermissoes
            instanceof HTMLInputElement
        )
    ) {
        return;
    }

    const itensPermissoes =
        obterItensPermissoesIndividuais();

    if (itensPermissoes.length === 0) {
        return;
    }

    new SeletorPermissoes(
        campoTodasPermissoes,
        itensPermissoes,
    );
}

/**
 * Inicia os tooltips existentes na página.
 *
 * @returns {void}
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
 * @returns {void}
 *
 * @since 1.0.0
 * @version 3.0.0
 */
function iniciarPaginaRegistoConvite() {
    iniciarFotografiaPerfil();
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
