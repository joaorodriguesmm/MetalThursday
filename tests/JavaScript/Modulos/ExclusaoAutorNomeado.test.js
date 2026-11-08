import {
    afterEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import SeletorNomeados
    from '../../../resources/js/modulos/SeletorNomeados.js';

function preparar() {
    document.body.innerHTML = `
        <select id="autor">
            <option value="">Selecionar autor</option>
            <option value="1" selected>Utilizador 1</option>
            <option value="2">Utilizador 2</option>
        </select>
    `;
    const campoAutor = document.getElementById('autor');
    const opcoes = {
        '1': { value: '1', text: 'Utilizador 1' },
        '2': { value: '2', text: 'Utilizador 2' },
        '3': { value: '3', text: 'Utilizador 3' },
    };
    let selecionado = '';
    const tomSelect = {
        options: opcoes,
        isLocked: false,
        setValue: vi.fn((valor) => { selecionado = valor; }),
        lock: vi.fn(),
        unlock: vi.fn(),
        getValue: vi.fn(() => selecionado),
        clear: vi.fn(() => { selecionado = ''; }),
        removeOption: vi.fn((valor) => { delete opcoes[valor]; }),
        addOption: vi.fn((opcao) => { opcoes[opcao.value] = opcao; }),
        refreshOptions: vi.fn(),
    };
    return {
        campoAutor,
        tomSelect,
        opcoes,
        selecionar: (valor) => { selecionado = valor; },
        criar: () => new SeletorNomeados({
            instanciaTomSelect: tomSelect,
            obterValorExcluido: () => campoAutor.value,
            campoAutor,
        }),
    };
}

describe('Exclusão dinâmica do autor na nomeação manual', () => {
    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('não disponibiliza o autor na lista inicial de nomeados', () => {
        const { criar, opcoes, tomSelect } = preparar();
        criar();
        expect(Object.hasOwn(opcoes, '1')).toBe(false);
        expect(Object.hasOwn(opcoes, '2')).toBe(true);
        expect(tomSelect.refreshOptions).toHaveBeenCalledWith(false);
    });

    it('repõe o autor anterior e limpa uma nomeação incompatível', () => {
        const { criar, campoAutor, opcoes, tomSelect, selecionar } = preparar();
        criar();
        selecionar('2');
        campoAutor.value = '2';
        campoAutor.dispatchEvent(new Event('change', { bubbles: true }));

        expect(Object.hasOwn(opcoes, '1')).toBe(true);
        expect(Object.hasOwn(opcoes, '2')).toBe(false);
        expect(tomSelect.clear).toHaveBeenCalledTimes(1);
        expect(tomSelect.removeOption).toHaveBeenCalledWith('2');
    });

    it('repõe todas as opções ao limpar o autor', () => {
        const { criar, campoAutor, opcoes, tomSelect } = preparar();
        criar();
        campoAutor.value = '';
        campoAutor.dispatchEvent(new Event('change', { bubbles: true }));

        expect(Object.hasOwn(opcoes, '1')).toBe(true);
        expect(Object.hasOwn(opcoes, '2')).toBe(true);
        expect(tomSelect.addOption).toHaveBeenCalledTimes(1);
    });

    it('remove uma seleção inicial igual ao autor', () => {
        const { criar, opcoes, tomSelect, selecionar } = preparar();
        selecionar('1');
        criar();
        expect(tomSelect.clear).toHaveBeenCalledTimes(1);
        expect(Object.hasOwn(opcoes, '1')).toBe(false);
    });
});
