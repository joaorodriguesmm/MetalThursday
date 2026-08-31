import TratadorFormularioAjax from './TratadorFormularioAjax';
import ValidadorFormulario from './ValidadorFormulario';

/**
 * Gere os formulários existentes nas janelas modais.
 *
 * Associa a validação no navegador à submissão assíncrona de cada formulário
 * configurado.
 *
 * @since 1.0.0
 */
class GestorFormulariosModais {
    /**
     * Cria e inicializa os formulários de janelas modais.
     *
     * Cada configuração deve possuir:
     *
     * - `idFormulario`: identificador HTML do formulário;
     * - `url`: endereço utilizado na submissão;
     * - `regrasValidacao`: regras opcionais de validação;
     * - `mensagensValidacao`: mensagens opcionais de validação;
     * - `aoSucesso`: função opcional executada após o sucesso.
     * - `aoErro`: função opcional executada perante um erro de submissão.
     *
     * @param {Array<object>} configuracoesModais
     *     Configurações dos formulários.
     *
     * @throws {TypeError} Quando as configurações são inválidas.
     *
     * @since 1.0.0
     */
    constructor(configuracoesModais) {
        if (!Array.isArray(configuracoesModais)) {
            throw new TypeError(
                'As configurações dos formulários devem ser uma lista.',
            );
        }

        const formulariosConfigurados = new Set();

        configuracoesModais.forEach((configuracao) => {
            const configuracaoNormalizada =
                this.normalizarConfiguracao(
                    configuracao,
                );

            if (
                formulariosConfigurados.has(
                    configuracaoNormalizada.idFormulario,
                )
            ) {
                throw new TypeError(
                    `O formulário "${configuracaoNormalizada.idFormulario}" está configurado mais do que uma vez.`,
                );
            }

            formulariosConfigurados.add(
                configuracaoNormalizada.idFormulario,
            );

            this.inicializarFormulario(
                configuracaoNormalizada,
            );
        });
    }

    /**
     * Inicializa um formulário configurado quando este existe no documento.
     *
     * @param {object} configuracao Configuração normalizada.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    inicializarFormulario(configuracao) {
        const formulario = document.getElementById(
            configuracao.idFormulario,
        );

        if (!(formulario instanceof HTMLFormElement)) {
            return;
        }

        const tratadorAjax = new TratadorFormularioAjax(
            configuracao.idFormulario,
            configuracao.url,
            configuracao.aoSucesso,
            configuracao.aoErro,
        );

        new ValidadorFormulario(
            formulario,
            {
                regras: configuracao.regrasValidacao,
                mensagens:
                    configuracao.mensagensValidacao,

                aoSucesso: () => {
                    void tratadorAjax.submeter();
                },
            },
        );
    }

    /**
     * Valida e normaliza a configuração de um formulário modal.
     *
     * @param {unknown} configuracao Configuração recebida.
     *
     * @returns {{
     *     idFormulario: string,
     *     url: string,
     *     regrasValidacao: object,
     *     mensagensValidacao: object,
     *     aoSucesso: Function|null,
     *     aoErro: Function|null
     * }} Configuração normalizada.
     *
     * @throws {TypeError} Quando a configuração é inválida.
     *
     * @since 2.0.0
     */
    normalizarConfiguracao(configuracao) {
        if (!this.eObjeto(configuracao)) {
            throw new TypeError(
                'Foi recebida uma configuração de formulário modal inválida.',
            );
        }

        if (
            typeof configuracao.idFormulario !== 'string'
            || configuracao.idFormulario.trim() === ''
        ) {
            throw new TypeError(
                'Cada formulário modal deve possuir um identificador válido.',
            );
        }

        if (
            typeof configuracao.url !== 'string'
            || configuracao.url.trim() === ''
        ) {
            throw new TypeError(
                'Cada formulário modal deve possuir um endereço de submissão válido.',
            );
        }

        const regrasValidacao =
            configuracao.regrasValidacao ?? {};

        const mensagensValidacao =
            configuracao.mensagensValidacao ?? {};

        if (
            !this.eObjeto(regrasValidacao)
            || !this.eObjeto(mensagensValidacao)
        ) {
            throw new TypeError(
                'As regras e mensagens de validação dos formulários modais devem ser objetos.',
            );
        }

        const aoSucesso =
            configuracao.aoSucesso ?? null;

        if (
            aoSucesso !== null
            && typeof aoSucesso !== 'function'
        ) {
            throw new TypeError(
                'A função de sucesso do formulário modal é inválida.',
            );
        }

        const aoErro =
            configuracao.aoErro ?? null;

        if (
            aoErro !== null
            && typeof aoErro !== 'function'
        ) {
            throw new TypeError(
                'A função de erro do formulário modal é inválida.',
            );
        }

        return {
            idFormulario:
                configuracao.idFormulario.trim(),

            url: configuracao.url.trim(),

            regrasValidacao,
            mensagensValidacao,
            aoSucesso,
            aoErro,
        };
    }

    /**
     * Verifica se um valor é um objeto não nulo.
     *
     * @param {unknown} valor Valor recebido.
     *
     * @returns {boolean} Verdadeiro quando o valor é um objeto simples.
     *
     * @since 2.0.0
     */
    eObjeto(valor) {
        return typeof valor === 'object'
            && valor !== null
            && !Array.isArray(valor);
    }
}

export default GestorFormulariosModais;
