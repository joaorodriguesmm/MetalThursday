import {
    afterEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import {
    configurarValidacaoTempoRealSeccoes,
} from '../../../resources/js/modulos/InicializadorFormularioMetalThursday.js';

function criarSecao(indice) {
    const seccao = document.createElement('section');
    seccao.className = 'item-seccao';
    seccao.innerHTML = `
        <select class="seletor-tipo-seccao"
            name="seccoes[${indice}][tipo_seccao_id]">
            <option value="">Selecionar</option>
            <option value="1" data-identificador-tipo-seccao="texto">
                Texto
            </option>
        </select>
        <textarea name="seccoes[${indice}][descricao]"></textarea>
    `;
    return seccao;
}

function alterarTipo(seccao) {
    const campo = seccao.querySelector('.seletor-tipo-seccao');
    campo.value = '1';
    campo.dispatchEvent(new Event('change', { bubbles: true }));
}

function submeter(formulario) {
    formulario.dispatchEvent(new Event('submit', {
        bubbles: true,
        cancelable: true,
    }));
}

describe('Validação de secções acrescentadas após tentativa de submissão', () => {
    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('não apresenta erro de descrição numa secção ainda não submetida', () => {
        document.body.innerHTML = '<form id="formulario"></form>';
        const formulario = document.getElementById('formulario');
        const validarCampoComRegras = vi.fn().mockReturnValue(true);
        configurarValidacaoTempoRealSeccoes(formulario, {
            validarCampoComRegras,
        });

        submeter(formulario);
        const seccao = criarSecao(0);
        formulario.append(seccao);
        alterarTipo(seccao);

        expect(validarCampoComRegras).not.toHaveBeenCalled();

        submeter(formulario);
        alterarTipo(seccao);
        expect(validarCampoComRegras).toHaveBeenCalledTimes(2);
    });

    it('mantém a revalidação das secções anteriormente submetidas', () => {
        document.body.innerHTML = '<form id="formulario"></form>';
        const formulario = document.getElementById('formulario');
        const primeira = criarSecao(0);
        formulario.append(primeira);
        const validarCampoComRegras = vi.fn().mockReturnValue(true);
        configurarValidacaoTempoRealSeccoes(formulario, {
            validarCampoComRegras,
        });

        submeter(formulario);
        alterarTipo(primeira);
        expect(validarCampoComRegras).toHaveBeenCalledTimes(2);

        const segunda = criarSecao(1);
        formulario.append(segunda);
        alterarTipo(segunda);
        expect(validarCampoComRegras).toHaveBeenCalledTimes(2);
    });

    it('limpa o estado de validação após a reposição', () => {
        document.body.innerHTML = '<form id="formulario"></form>';
        const formulario = document.getElementById('formulario');
        const seccao = criarSecao(0);
        formulario.append(seccao);
        const validarCampoComRegras = vi.fn().mockReturnValue(true);
        configurarValidacaoTempoRealSeccoes(formulario, {
            validarCampoComRegras,
        });

        submeter(formulario);
        formulario.dispatchEvent(new Event('reset'));
        alterarTipo(seccao);
        expect(validarCampoComRegras).not.toHaveBeenCalled();
    });
});
