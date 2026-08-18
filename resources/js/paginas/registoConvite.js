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
 */

/**
 * Tamanho máximo permitido para a fotografia, em bytes.
 *
 * Corresponde ao limite de 10 MiB aplicado pelo servidor e comunicado na
 * interface.
 *
 * @type {number}
 *
 * @since 2.0.0
 */
const TAMANHO_MAXIMO_FOTOGRAFIA =
    10 * (1024 ** 2);

/**
 * Seletores utilizados na página.
 *
 * @type {Readonly<Record<string, string>>}
 *
 * @since 2.0.0
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

    textoFotografia:
        '#texto-fotografia-registo',

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
 * Obtém obrigatoriamente um campo de entrada através do respetivo nome e tipo.
 *
 * @param {HTMLFormElement} formulario Formulário pesquisado.
 * @param {string} nomeCampo Nome HTML do campo.
 * @param {string} tipoCampo Tipo esperado do campo.
 *
 * @returns {HTMLInputElement} Campo encontrado.
 *
 * @throws {TypeError} Quando o campo esperado não existe ou tem outro tipo.
 *
 * @since 2.0.0
 */
function obterCampoEntrada(
    formulario,
    nomeCampo,
    tipoCampo,
) {
    const campo =
        formulario.elements.namedItem(
            nomeCampo,
        );

    if (
        !(campo instanceof HTMLInputElement)
        || campo.type !== tipoCampo
    ) {
        throw new TypeError(
            `O formulário "${formulario.id}" deve possuir o campo "${nomeCampo}" do tipo "${tipoCampo}".`,
        );
    }

    return campo;
}

/**
 * Obtém obrigatoriamente o comprimento mínimo declarado num campo.
 *
 * @param {HTMLInputElement} campo Campo pesquisado.
 *
 * @returns {number} Comprimento mínimo positivo.
 *
 * @throws {TypeError} Quando o limite não é válido.
 *
 * @since 2.0.0
 */
function obterComprimentoMinimo(
    campo,
) {
    if (
        !Number.isInteger(
            campo.minLength,
        )
        || campo.minLength <= 0
    ) {
        throw new TypeError(
            `O campo "${campo.name}" deve possuir um comprimento mínimo válido.`,
        );
    }

    return campo.minLength;
}

/**
 * Obtém obrigatoriamente o comprimento máximo declarado num campo.
 *
 * @param {HTMLInputElement} campo Campo pesquisado.
 *
 * @returns {number} Comprimento máximo positivo.
 *
 * @throws {TypeError} Quando o limite não é válido.
 *
 * @since 2.0.0
 */
function obterComprimentoMaximo(
    campo,
) {
    if (
        !Number.isInteger(
            campo.maxLength,
        )
        || campo.maxLength <= 0
    ) {
        throw new TypeError(
            `O campo "${campo.name}" deve possuir um comprimento máximo válido.`,
        );
    }

    return campo.maxLength;
}

/**
 * Obtém os tipos declarados no atributo `accept` do campo da fotografia.
 *
 * A validação estrutural dos MIME é efetuada pelo ValidadorFicheiro.
 *
 * @param {HTMLInputElement} campoFotografia Campo da fotografia.
 *
 * @returns {Array<string>} Tipos declarados.
 *
 * @throws {TypeError} Quando o campo não declara tipos permitidos.
 *
 * @since 2.0.0
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
                    tipo !== '',
            );

    if (tipos.length === 0) {
        throw new TypeError(
            'O campo da fotografia deve declarar os tipos MIME permitidos.',
        );
    }

    return Array.from(
        new Set(
            tipos,
        ),
    );
}

/**
 * Obtém os contentores das permissões individuais.
 *
 * @returns {Array<HTMLElement>} Contentores encontrados.
 *
 * @throws {TypeError} Quando uma permissão não pertence a um contentor.
 *
 * @since 2.0.0
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
        throw new TypeError(
            'Não foi encontrado um campo válido para a fotografia de perfil.',
        );
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

            seletorTextoFicheiro:
                SELETORES.textoFotografia,

            textoPadrao:
                'Selecionar fotografia',

            textoSelecionado:
                'Alterar fotografia',

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
 * O código do convite é validado apenas pelo servidor. É um valor oculto que
 * o utilizador não pode corrigir e, por isso, não deve bloquear a submissão
 * através da validação de apoio do navegador.
 *
 * @returns {void}
 *
 * @since 1.0.0
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
        obterCampoEntrada(
            formulario,
            'nome',
            'text',
        );

    const campoEmail =
        obterCampoEntrada(
            formulario,
            'email',
            'email',
        );

    const campoPalavraPasse =
        obterCampoEntrada(
            formulario,
            'palavra_passe',
            'password',
        );

    const campoConfirmacao =
        obterCampoEntrada(
            formulario,
            'confirmacao_palavra_passe',
            'password',
        );

    const comprimentoMinimoNome =
        obterComprimentoMinimo(
            campoNome,
        );

    const comprimentoMaximoNome =
        obterComprimentoMaximo(
            campoNome,
        );

    const comprimentoMaximoEmail =
        obterComprimentoMaximo(
            campoEmail,
        );

    const comprimentoMinimoPalavraPasse =
        obterComprimentoMinimo(
            campoPalavraPasse,
        );

    const comprimentoMaximoPalavraPasse =
        obterComprimentoMaximo(
            campoPalavraPasse,
        );

    const comprimentoMaximoConfirmacao =
        obterComprimentoMaximo(
            campoConfirmacao,
        );

    new ValidadorFormulario(
        formulario,
        {
            regras: {
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
 */
function iniciarPermissoesEmail() {
    const campoTodasPermissoes =
        document.querySelector(
            SELETORES.permissaoTodas,
        );

    if (!(campoTodasPermissoes instanceof HTMLInputElement)) {
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
