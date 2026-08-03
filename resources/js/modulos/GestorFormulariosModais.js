import TratadorFormularioAjax from './TratadorFormularioAjax';
import ValidadorFormulario from './ValidadorFormulario';

/**
 * Gere os formulários existentes nas janelas modais.
 *
 * Associa a validação no navegador à submissão assíncrona de cada formulário
 * configurado.
 *
 * @since 1.0.0
 * @version 3.0.0
 */
class GestorFormulariosModais {
    /**
     * Cria um gestor de formulários de janelas modais.
     *
     * Cada configuração deve possuir:
     *
     * - `idFormulario`: identificador HTML do formulário;
     * - `url`: endereço utilizado na submissão;
     * - `regrasValidacao`: regras opcionais de validação;
     * - `mensagensValidacao`: mensagens opcionais de validação;
     * - `aoSucesso`: função opcional executada após o sucesso.
     *
     * @param {Array<object>} configuracoesModais
     *     Configurações dos formulários.
     * @throws {TypeError} Quando as configurações não são uma lista.
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    constructor(configuracoesModais) {
        if (!Array.isArray(configuracoesModais)) {
            throw new TypeError(
                'As configurações dos formulários devem ser uma lista.',
            );
        }

        this.configuracoesModais =
            configuracoesModais;

        this.registosFormularios =
            new Map();

        this.iniciado =
            false;

        this.iniciar();
    }

    /**
     * Inicia os formulários configurados.
     *
     * @returns {void}
     *
     * @since 1.0.0
     * @version 2.0.0
     */
    iniciar() {
        if (this.iniciado) {
            return;
        }

        this.configuracoesModais.forEach(
            (configuracao) => {
                this.inicializarFormulario(
                    configuracao,
                );
            },
        );

        this.iniciado =
            true;
    }

    /**
     * Inicializa um formulário configurado.
     *
     * @param {object} configuracao Configuração do formulário.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    inicializarFormulario(
        configuracao,
    ) {
        if (!this.eConfiguracaoValida(configuracao)) {
            throw new TypeError(
                'Foi recebida uma configuração de formulário modal inválida.',
            );
        }

        const formulario =
            document.getElementById(
                configuracao.idFormulario,
            );

        if (!(formulario instanceof HTMLFormElement)) {
            return;
        }

        if (
            this.registosFormularios.has(
                formulario,
            )
        ) {
            return;
        }

        const tratadorAjax =
            new TratadorFormularioAjax(
                configuracao.idFormulario,
                configuracao.url,
                async (dadosResposta) => {
                    await this.executarAcaoSucesso(
                        configuracao,
                        dadosResposta,
                    );
                },
            );

        const validador =
            new ValidadorFormulario(
                formulario,
                {
                    regras:
                        configuracao.regrasValidacao
                        ?? {},

                    mensagens:
                        configuracao.mensagensValidacao
                        ?? {},

                    aoSucesso: () => {
                        /*
                         * A promessa não é devolvida porque o validador exige
                         * uma função de sucesso síncrona.
                         */
                        tratadorAjax.submeter();
                    },
                },
            );

        this.registosFormularios.set(
            formulario,
            validador,
        );
    }

    /**
     * Executa a função configurada para uma submissão bem-sucedida.
     *
     * @param {object} configuracao Configuração do formulário.
     * @param {unknown} dadosResposta Dados devolvidos pelo servidor.
     *
     * @returns {Promise<void>}
     *
     * @since 2.0.0
     * @version 1.0.0
     */
    async executarAcaoSucesso(
        configuracao,
        dadosResposta,
    ) {
        if (
            typeof configuracao.aoSucesso
            !== 'function'
        ) {
            return;
        }

        await configuracao.aoSucesso(
            dadosResposta,
        );
    }

    /**
     * Verifica se uma configuração pode ser utilizada.
     *
     * @param {unknown} configuracao Configuração a verificar.
     *
     * @returns {boolean} Verdadeiro quando a configuração é válida.
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    eConfiguracaoValida(
        configuracao,
    ) {
        if (
            typeof configuracao !== 'object'
            || configuracao === null
            || Array.isArray(configuracao)
        ) {
            return false;
        }

        if (
            typeof configuracao.idFormulario !== 'string'
            || configuracao.idFormulario.trim() === ''
        ) {
            return false;
        }

        if (
            typeof configuracao.url !== 'string'
            || configuracao.url.trim() === ''
        ) {
            return false;
        }

        if (
            configuracao.aoSucesso !== undefined
            && configuracao.aoSucesso !== null
            && typeof configuracao.aoSucesso !== 'function'
        ) {
            return false;
        }

        return this.eObjetoOpcional(
            configuracao.regrasValidacao,
        )
            && this.eObjetoOpcional(
                configuracao.mensagensValidacao,
            );
    }

    /**
     * Verifica se um valor opcional é um objeto válido.
     *
     * @param {unknown} valor Valor recebido.
     *
     * @returns {boolean} Verdadeiro quando o valor é omitido ou é um objeto.
     *
     * @since 3.0.0
     * @version 1.0.0
     */
    eObjetoOpcional(valor) {
        return valor === undefined
            || valor === null
            || (
                typeof valor === 'object'
                && !Array.isArray(valor)
            );
    }

    /**
     * Remove os eventos associados aos formulários.
     *
     * @returns {void}
     *
     * @since 2.0.0
     * @version 2.0.0
     */
    destruir() {
        this.registosFormularios.forEach(
            (validador) => {
                validador.destruir();
            },
        );

        this.registosFormularios.clear();
        this.iniciado =
            false;
    }
}

export default GestorFormulariosModais;
