import TratadorFormularioAjax from './TratadorFormularioAjax';
import ValidadorFormulario from './ValidadorFormulario';

/**
 * Inicializa a validação e a submissão assíncrona dos formulários de
 * comentários e respostas existentes num contentor.
 *
 * Após uma publicação bem-sucedida, a página é recarregada para que o
 * servidor apresente o comentário através do componente Blade e aplique as
 * autorizações correspondentes ao utilizador autenticado.
 *
 * @since 2.0.0
 */
class InicializadorComentarios {
    /**
     * Seletor dos formulários suportados.
     *
     * @type {string}
     *
     * @since 2.0.0
     */
    static SELETOR_FORMULARIOS = [
        'form.formulario-comentario',
        'form.formulario-resposta-comentario',
    ].join(', ');

    /**
     * Comprimento máximo utilizado quando o campo não declara `maxlength`.
     *
     * @type {number}
     *
     * @since 2.0.0
     */
    static COMPRIMENTO_MAXIMO_PREDEFINIDO = 2000;

    /**
     * Cria e inicializa os formulários de comentários encontrados.
     *
     * @param {Document|Element} contentor Contentor utilizado na pesquisa.
     *
     * @throws {TypeError} Quando o contentor não é válido.
     * @throws {Error} Quando algum formulário não respeita o contrato.
     *
     * @since 2.0.0
     */
    constructor(contentor = document) {
        if (
            !(contentor instanceof Document)
            && !(contentor instanceof Element)
        ) {
            throw new TypeError(
                'O contentor dos formulários de comentário é inválido.',
            );
        }

        contentor.querySelectorAll(
            InicializadorComentarios.SELETOR_FORMULARIOS,
        ).forEach((formulario) => {
            if (formulario instanceof HTMLFormElement) {
                this.inicializarFormulario(formulario);
            }
        });
    }

    /**
     * Inicializa um formulário de comentário ou resposta.
     *
     * @param {HTMLFormElement} formulario Formulário recebido.
     *
     * @returns {void}
     *
     * @throws {Error} Quando faltam contratos obrigatórios no formulário.
     *
     * @since 2.0.0
     */
    inicializarFormulario(formulario) {
        const identificador = formulario.id.trim();

        const endereco = formulario.action.trim();

        const campoConteudo =
            formulario.elements.namedItem(
                'conteudo',
            );

        if (
            identificador === ''
            || endereco === ''
            || !(campoConteudo instanceof HTMLTextAreaElement)
        ) {
            throw new Error(
                'Cada formulário de comentário deve possuir identificador, endereço e campo de conteúdo.',
            );
        }

        const comprimentoMaximo =
            Number.isInteger(campoConteudo.maxLength)
            && campoConteudo.maxLength > 0
                ? campoConteudo.maxLength
                : InicializadorComentarios
                    .COMPRIMENTO_MAXIMO_PREDEFINIDO;

        const tratadorAjax =
            new TratadorFormularioAjax(
                identificador,
                endereco,
                () => {
                    window.location.reload();
                },
            );

        new ValidadorFormulario(
            formulario,
            {
                regras: {
                    conteudo: [
                        'obrigatorio',
                        `maximo:${comprimentoMaximo}`,
                    ],
                },

                mensagens: {
                    conteudo: {
                        obrigatorio:
                            'Por favor, insere o texto do comentário.',

                        maximo:
                            `O comentário não pode ter mais de ${comprimentoMaximo} caracteres.`,
                    },
                },

                eventosTempoReal: [
                    'input',
                ],

                aoSucesso: () => {
                    /*
                     * O validador exige uma função síncrona. A submissão AJAX
                     * gere internamente o respetivo ciclo assíncrono.
                     */
                    void tratadorAjax.submeter();
                },
            },
        );
    }
}

export default InicializadorComentarios;
