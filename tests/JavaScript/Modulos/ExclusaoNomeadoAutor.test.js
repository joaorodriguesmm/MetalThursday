import {
    afterEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

import SeletorNomeados
    from '../../../resources/js/modulos/SeletorNomeados.js';

function criarTomSelect(campo) {
    const opcoes = Object.fromEntries(
        Array.from(campo.options).map((opcao) => [
            opcao.value,
            { value: opcao.value, text: opcao.textContent },
        ]),
    );

    const ouvintes = new Map();

    const tomSelect = {
        options: opcoes,
        input: campo,
        getValue: vi.fn(() => campo.value),
        setValue: vi.fn((valor) => {
            campo.value = valor;
            tomSelect.emitir('change');
        }),
        clear: vi.fn(() => {
            campo.value = '';
            tomSelect.emitir('change');
        }),
        addOption: vi.fn((opcao) => {
            opcoes[opcao.value] = opcao;
        }),
        removeOption: vi.fn((valor) => {
            delete opcoes[valor];
        }),
        refreshOptions: vi.fn(),
        lock: vi.fn(),
        unlock: vi.fn(),
        on: vi.fn((evento, callback) => {
            const anteriores = ouvintes.get(evento) ?? [];
            anteriores.push(callback);
            ouvintes.set(evento, anteriores);
        }),
        emitir: (evento) => {
            ouvintes.get(evento)?.forEach((callback) => callback());
            campo.dispatchEvent(new Event(evento, { bubbles: true }));
        },
    };

    campo.tomselect = tomSelect;
    return tomSelect;
}

function preparar() {
    document.body.innerHTML = `
        <form>
            <select name="autor_id">
                <option value="">Seleciona o autor</option>
                <option value="18">Utilizador 18</option>
                <option value="19" selected>Utilizador 19</option>
                <option value="20">Utilizador 20</option>
            </select>
            <select name="proximo_nomeado_id">
                <option value="">Seleciona o nomeado</option>
                <option value="18">Utilizador 18</option>
                <option value="19">Utilizador 19</option>
                <option value="20" selected>Utilizador 20</option>
            </select>
        </form>
    `;

    const campoAutor = document.querySelector('[name="autor_id"]');
    const campoNomeado = document.querySelector('[name="proximo_nomeado_id"]');
    const autor = criarTomSelect(campoAutor);
    const nomeado = criarTomSelect(campoNomeado);

    new SeletorNomeados({
        instanciaTomSelect: nomeado,
        campoAutor,
        obterValorExcluido: () => campoAutor.value,
    });

    return {
        autor,
        nomeado,
        campoAutor,
        campoNomeado,
    };
}

describe('Exclusão bidirecional de autor e próximo nomeado', () => {
    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('oculta o nomeado da lista de autores na inicialização', () => {
        const { autor, nomeado } = preparar();

        expect(Object.hasOwn(autor.options, '20')).toBe(false);
        expect(Object.hasOwn(autor.options, '19')).toBe(true);
        expect(Object.hasOwn(nomeado.options, '19')).toBe(false);
    });

    it('repõe o nomeado anterior ao selecionar outro', () => {
        const { autor, nomeado } = preparar();

        nomeado.setValue('18');

        expect(Object.hasOwn(autor.options, '20')).toBe(true);
        expect(Object.hasOwn(autor.options, '18')).toBe(false);
        expect(Object.hasOwn(nomeado.options, '19')).toBe(false);
    });

    it('repõe o nomeado anterior quando a nomeação é limpa', () => {
        const { autor, nomeado } = preparar();

        nomeado.clear();

        expect(Object.hasOwn(autor.options, '20')).toBe(true);
        expect(Object.hasOwn(autor.options, '18')).toBe(true);
    });

    it('limpa o autor em caso de coincidência programática', () => {
        const { autor, nomeado, campoAutor } = preparar();

        // O valor excluído não é selecionável manualmente, mas pode
        // chegar através de uma atualização programática.
        nomeado.setValue('19');

        expect(campoAutor.value).toBe('');
        expect(nomeado.getValue()).toBe('19');
        expect(Object.hasOwn(autor.options, '19')).toBe(false);
    });

    it('não limpa a nomeação quando o autor muda para outro válido', () => {
        const { autor, nomeado } = preparar();

        autor.setValue('18');

        expect(nomeado.getValue()).toBe('20');
        expect(Object.hasOwn(nomeado.options, '18')).toBe(false);
        expect(Object.hasOwn(nomeado.options, '19')).toBe(true);
        expect(Object.hasOwn(autor.options, '20')).toBe(false);
    });
});
