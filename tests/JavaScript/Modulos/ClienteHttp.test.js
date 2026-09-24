import {
    describe,
    expect,
    it,
} from 'vitest';

import axios from '../../../resources/js/modulos/ClienteHttp.js';

describe(
    'ClienteHttp',
    () => {
        it(
            'configura os cabeçalhos predefinidos dos pedidos HTTP',
            () => {
                expect(
                    axios.defaults.headers.common['X-Requested-With'],
                ).toBe('XMLHttpRequest');

                expect(
                    axios.defaults.headers.common.Accept,
                ).toBe('application/json');
            },
        );
    },
);
