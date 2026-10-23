import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vitest';

vi.mock('tom-select/base', () => {
    class TomSelectSimulado {
        static define() {}

        constructor(elemento, configuracao) {
            this.input = elemento;
            this.configuracao = configuracao;
            this.eventos = new Map();
            this.destruido = false;

            elemento.tomselect = this;
        }

        on(nomeEvento, callback) {
            const callbacks =
                this.eventos.get(nomeEvento) ?? [];

            callbacks.push(callback);

            this.eventos.set(
                nomeEvento,
                callbacks,
            );
        }

        destroy() {
            if (this.destruido) {
                return;
            }

            this.destruido = true;

            delete this.input.tomselect;

            (this.eventos.get('destroy') ?? [])
                .forEach((callback) => {
                    callback();
                });
        }
    }

    return {
        default: TomSelectSimulado,
    };
});

vi.mock('tom-select/plugins/clear_button/plugin.js', () => ({
    default: () => {},
}));

vi.mock('tom-select/plugins/remove_button/plugin.js', () => ({
    default: () => {},
}));

import InicializadorTomSelect
    from '../../../resources/js/modulos/InicializadorTomSelect.js';

function criarSelecao(
    identificador = 'selecao-teste',
) {
    const elemento = document.createElement('select');

    elemento.id = identificador;
    elemento.className = 'tom-select-unico';

    elemento.innerHTML = `
        <option value="1">Primeira</option>
        <option value="2">Segunda</option>
    `;

    return elemento;
}

describe('B25.21 — ciclo de vida do inicializador Tom Select', () => {
    beforeEach(() => {
        document.body.replaceChildren();
    });

    afterEach(() => {
        vi.restoreAllMocks();
        document.body.replaceChildren();
    });

    it('reutiliza a mesma instancia entre inicializadores', () => {
        const campo = criarSelecao();

        document.body.append(campo);

        const primeiro = new InicializadorTomSelect();

        const instancia = campo.tomselect;

        const segundo = new InicializadorTomSelect();

        expect(campo.tomselect).toBe(instancia);

        expect(
            segundo.obterInstancia('selecao-teste'),
        ).toBe(instancia);

        expect(
            primeiro.instancias.has(instancia),
        ).toBe(true);

        expect(
            segundo.instancias.has(instancia),
        ).toBe(true);
    });

    it('inclui a propria raiz quando ela e um select correspondente', () => {
        const inicializador = new InicializadorTomSelect();

        const campo = criarSelecao();

        document.body.append(campo);

        const instancias = inicializador.iniciarTodos(
            campo,
        );

        expect(instancias).toHaveLength(1);

        expect(instancias[0]).toBe(
            campo.tomselect,
        );

        expect(campo.tomselect).toBeDefined();
    });

    it('inicializa um campo substituido com o mesmo identificador', () => {
        const campoOriginal = criarSelecao();

        document.body.append(campoOriginal);

        const inicializador = new InicializadorTomSelect();

        const instanciaOriginal =
            campoOriginal.tomselect;

        const campoNovo = criarSelecao();

        campoOriginal.replaceWith(campoNovo);

        const instanciaNova =
            inicializador.obterInstancia('selecao-teste');

        expect(instanciaNova).not.toBeNull();

        expect(instanciaNova).not.toBe(
            instanciaOriginal,
        );

        expect(campoNovo.tomselect).toBe(
            instanciaNova,
        );
    });

    it('permite reinicializar um campo apos destruicao', () => {
        const campo = criarSelecao();

        document.body.append(campo);

        const inicializador = new InicializadorTomSelect();

        const primeira = campo.tomselect;

        primeira.destroy();

        expect(
            inicializador.instancias.has(primeira),
        ).toBe(false);

        expect(campo.tomselect).toBeUndefined();

        const segunda = inicializador.iniciar(campo);

        expect(segunda).not.toBe(primeira);

        expect(
            inicializador.instancias.has(segunda),
        ).toBe(true);
    });

    it('atualiza os registos de outros inicializadores ao destruir', () => {
        const campo = criarSelecao();

        document.body.append(campo);

        const primeiro = new InicializadorTomSelect();
        const segundo = new InicializadorTomSelect();

        const instancia = campo.tomselect;

        primeiro.destruirTodas();

        expect(instancia.destruido).toBe(true);

        expect(primeiro.instancias.size).toBe(0);
        expect(segundo.instancias.size).toBe(0);

        expect(campo.tomselect).toBeUndefined();
    });

    it('normaliza o texto das opcoes ordenadas', () => {
        const campo = criarSelecao();

        campo.setAttribute(
            'data-ordenar-alfabeticamente',
            '',
        );

        campo.options[0].textContent =
            '   Primeira   ';

        document.body.append(campo);

        new InicializadorTomSelect();

        expect(campo.options[0].textContent).toBe(
            'Primeira',
        );

        expect(
            campo.tomselect.configuracao.sortField,
        ).toEqual({
            field: 'text',
            direction: 'asc',
        });
    });

    it('ignora uma raiz select sem a classe configurada', () => {
        const inicializador = new InicializadorTomSelect();

        const campo = criarSelecao();

        campo.className = 'select-normal';

        document.body.append(campo);

        const instancias = inicializador.iniciarTodos(
            campo,
        );

        expect(instancias).toEqual([]);

        expect(campo.tomselect).toBeUndefined();
    });

    it('inicializa os selects descendentes de um contentor', () => {
        const inicializador = new InicializadorTomSelect();

        const contentor = document.createElement('div');

        const primeiro = criarSelecao('select-primeiro');
        const segundo = criarSelecao('select-segundo');

        const ignorado = document.createElement('select');

        ignorado.id = 'select-normal';

        contentor.append(
            primeiro,
            ignorado,
            segundo,
        );

        document.body.append(contentor);

        const instancias = inicializador.iniciarTodos(
            contentor,
        );

        expect(instancias).toHaveLength(2);

        expect(instancias).toEqual([
            primeiro.tomselect,
            segundo.tomselect,
        ]);

        expect(ignorado.tomselect).toBeUndefined();
    });
});
