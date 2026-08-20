/**
 * Disponibiliza operações partilhadas sobre opções utilizadas pelo
 * Tom Select e pelas respostas AJAX que as originam.
 *
 * @since 2.0.0
 */

/**
 * Verifica se um valor é um objeto não nulo.
 *
 * @param {unknown} valor Valor recebido.
 *
 * @returns {boolean} Verdadeiro quando o valor é um objeto simples.
 *
 * @since 2.0.0
 */
function eObjeto(valor) {
    return typeof valor === 'object'
        && valor !== null
        && !Array.isArray(valor);
}

/**
 * Adiciona ou atualiza uma opção numa instância Tom Select.
 *
 * Quando solicitado, a opção é acrescentada à seleção existente. Nos campos
 * múltiplos, os valores anteriormente selecionados são preservados.
 *
 * @param {object|null} instancia Instância Tom Select.
 * @param {unknown} identificador Identificador da opção.
 * @param {unknown} nome Texto apresentado.
 * @param {boolean} selecionar Indica se a opção deve ficar selecionada.
 *
 * @returns {boolean} Indica se a opção foi adicionada ou atualizada.
 *
 * @since 2.0.0
 */
function adicionarOpcaoTomSelect(
    instancia,
    identificador,
    nome,
    selecionar = false,
) {
    if (
        !instancia
        || typeof instancia.addOption !== 'function'
        || !Number.isInteger(
            identificador,
        )
        || identificador < 1
        || typeof nome !== 'string'
        || nome.trim() === ''
    ) {
        return false;
    }

    const valor = String(
        identificador,
    );

    const opcao = {
        value:
            valor,

        text:
            nome.trim(),
    };

    if (
        eObjeto(
            instancia.options,
        )
        && Object.hasOwn(
            instancia.options,
            valor,
        )
        && typeof instancia.updateOption
            === 'function'
    ) {
        instancia.updateOption(
            valor,
            opcao,
        );
    } else {
        instancia.addOption(
            opcao,
        );
    }

    if (selecionar) {
        if (
            typeof instancia.addItem
            === 'function'
        ) {
            instancia.addItem(
                valor,
            );
        } else if (
            typeof instancia.setValue
            === 'function'
        ) {
            const valoresAtuais =
                Array.isArray(
                    instancia.items,
                )
                    ? instancia.items.map(
                        String,
                    )
                    : [];

            const selecaoMultipla =
                instancia.input
                instanceof HTMLSelectElement
                && instancia.input.multiple;

            instancia.setValue(
                selecaoMultipla
                    ? Array.from(
                        new Set([
                            ...valoresAtuais,
                            valor,
                        ]),
                    )
                    : valor,
            );
        }
    }

    if (
        typeof instancia.refreshOptions
            === 'function'
    ) {
        instancia.refreshOptions(
            false,
        );
    }

    return true;
}

/**
 * Obtém uma entidade criada a partir de uma resposta AJAX.
 *
 * @param {unknown} dadosResposta Resposta recebida.
 * @param {string} chave Chave da entidade.
 * @param {string} chaveNome Chave do texto apresentado.
 *
 * @returns {{identificador: number, nome: string}|null} Opção criada.
 *
 * @since 2.0.0
 */
function obterOpcaoResposta(
    dadosResposta,
    chave,
    chaveNome,
) {
    if (!eObjeto(dadosResposta)) {
        return null;
    }

    const entidade =
        dadosResposta[chave];

    if (!eObjeto(entidade)) {
        return null;
    }

    const identificador =
        entidade.id;

    const nome =
        entidade[chaveNome];

    if (
        !Number.isInteger(
            identificador,
        )
        || identificador < 1
        || typeof nome !== 'string'
        || nome.trim() === ''
    ) {
        return null;
    }

    return {
        identificador,

        nome:
            nome.trim(),
    };
}

export {
    adicionarOpcaoTomSelect,
    obterOpcaoResposta,
};
