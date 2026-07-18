import FormValidator from '../modules/FormValidator';
import TomSelectInitializer from '../modules/TomSelectInitializer';

document.addEventListener('DOMContentLoaded', () => {
    new TomSelectInitializer();

    // Validação para o formulário de Bandas
    if (document.getElementById('band-form')) {
        new FormValidator(
            '#band-form',
            {
                'name': ['required', 'max:255'],
                'country_id': ['required'],
                'genres[]': ['required'],
            },
            {
                'name': {
                    required: 'Por favor, insere o nome.',
                    max: 'O nome não deve ter no máximo 255 caracteres.'
                },
                'country_id': {
                    required: 'Por favor, seleciona o país.'
                },
                'genres[]': {
                    required: 'Por favor, seleciona pelo menos um género.'
                }
            }
        );
    }

    // Validação para o formulário de Géneros (quando o criares)
    if (document.getElementById('genre-form')) {
        new FormValidator(
            '#genre-form',
            {
                'name': ['required', 'max:255'],
            },
            {
                'name': {
                    required: 'Por favor, insere o nome.',
                    max: 'O nome deve ter no máximo 255 caracteres.'
                }
            }
        );
    }

    // Validação para o formulário de Edições (quando o criares)
    if (document.getElementById('edition-form')) {
        new FormValidator(
            '#edition-form',
            {
                'name': ['required', 'max:255'],
                'start_date': ['required', 'date'],
                'end_date': ['date', 'after_or_equal:start_date'],
            },
            {
                'name': {
                    required: 'Por favor, insere o nome.',
                    max: 'O nome deve ter no máximo 255 caracteres.'
                },
                'start_date': {
                    required: 'A data de início é obrigatória.',
                    date: 'A data de início deve ser uma data válida.'
                },
                'end_date': {
                    date: 'A data de fim deve ser uma data válida.',
                    after_or_equal: 'A data de fim não deve ser anterior à de início.'
                }
            }
        );
    }
});
