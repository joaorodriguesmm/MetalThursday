import ValidadorFormulario
    from '../modulos/ValidadorFormulario';

/**
 * Valida a alteração administrativa do papel antes da submissão.
 * A autorização e a validação definitiva permanecem no servidor.
 *
 * @since 2.0.0
 */
function iniciarValidacaoAlteracaoPapel() {
    const formulario = document.querySelector(
        '#formulario-alterar-papel',
    );

    if (!(formulario instanceof HTMLFormElement)) {
        return;
    }

    const campoPapel = formulario.elements.namedItem('papel');

    if (!(campoPapel instanceof HTMLSelectElement)) {
        throw new TypeError(
            'O formulário de alteração do papel exige um seletor de papel.',
        );
    }

    const papelAtual = campoPapel.dataset.papelAtual;

    if (typeof papelAtual !== 'string' || papelAtual === '') {
        throw new TypeError(
            'O seletor deve declarar o papel atual do utilizador.',
        );
    }

    new ValidadorFormulario(formulario, {
        regras: {
            papel: [
                'obrigatorio',
                ({ valor }) => valor !== papelAtual
                    || 'O utilizador já possui o papel selecionado.',
            ],
            confirmar_alteracao_papel: [
                'obrigatorio',
            ],
        },
        mensagens: {
            papel: {
                obrigatorio: 'Seleciona o novo papel do utilizador.',
            },
            confirmar_alteracao_papel: {
                obrigatorio: 'Confirma explicitamente a alteração do papel.',
            },
        },
    });
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        iniciarValidacaoAlteracaoPapel,
        { once: true },
    );
} else {
    iniciarValidacaoAlteracaoPapel();
}

/**
 * Valida a confirmação administrativa da reativação do acesso.
 * A autorização e a validação definitiva permanecem no servidor.
 *
 * @since 2.0.0
 */
function iniciarValidacaoReativacao() {
    const formulario = document.querySelector(
        '#formulario-reativar-acesso',
    );

    if (!(formulario instanceof HTMLFormElement)) {
        return;
    }

    new ValidadorFormulario(formulario, {
        regras: {
            confirmar_reativacao: [
                'obrigatorio',
            ],
        },
        mensagens: {
            confirmar_reativacao: {
                obrigatorio:
                    'Confirma explicitamente a reativação do acesso.',
            },
        },
    });
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        iniciarValidacaoReativacao,
        { once: true },
    );
} else {
    iniciarValidacaoReativacao();
}

/**
 * Valida o motivo da suspensão antes da submissão.
 * A validação definitiva permanece no servidor.
 *
 * @since 2.0.0
 */
function iniciarValidacaoSuspensao() {
    const formulario = document.querySelector(
        '#formulario-suspender-acesso',
    );

    if (!(formulario instanceof HTMLFormElement)) {
        return;
    }

    const campo = formulario.elements.namedItem('motivo');

    if (
        !(campo instanceof HTMLTextAreaElement)
        || !Number.isInteger(campo.maxLength)
        || campo.maxLength <= 0
    ) {
        throw new TypeError(
            'O motivo da suspensão deve declarar um comprimento máximo válido.',
        );
    }

    const maximo = campo.maxLength;

    new ValidadorFormulario(formulario, {
        regras: {
            motivo: [
                'obrigatorio',
                `maximo:${maximo}`,
                ({ valor }) =>
                    !Array.from(valor).some((caractere) => {
                        const codigo = caractere.charCodeAt(0);
                        return (codigo < 32 && ![9, 10, 13].includes(codigo))
                            || codigo === 127;
                    })
                    || 'O motivo da suspensão contém caracteres inválidos.',
            ],
        },
        mensagens: {
            motivo: {
                obrigatorio:
                    'Por favor, indica o motivo da suspensão.',
                maximo:
                    `O motivo da suspensão não pode ter mais de ${maximo} caracteres.`,
            },
        },
    });
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        iniciarValidacaoSuspensao,
        { once: true },
    );
} else {
    iniciarValidacaoSuspensao();
}

/**
 * Valida a confirmação do encerramento administrativo de sessões.
 * A autorização e a validação definitiva permanecem no servidor.
 *
 * @since 2.0.0
 */
function iniciarValidacaoEncerramentoSessoes() {
    const campo = document.querySelector(
        '#confirmar-encerramento-sessoes',
    );

    const formulario = campo?.form;

    if (!(formulario instanceof HTMLFormElement)) {
        return;
    }

    new ValidadorFormulario(formulario, {
        regras: {
            confirmar_encerramento_sessoes: ['obrigatorio'],
        },
        mensagens: {
            confirmar_encerramento_sessoes: {
                obrigatorio:
                    'Confirma explicitamente o encerramento das sessões.',
            },
        },
    });
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        iniciarValidacaoEncerramentoSessoes,
        { once: true },
    );
} else {
    iniciarValidacaoEncerramentoSessoes();
}
