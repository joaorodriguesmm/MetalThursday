/**
 * Script especifico para a pagina de criação de MetalThursday.
 *
 * @since 1.0
 * @version 1.0
 */
import EmbedTester from '../modules/EmbedTester';
import FormValidator from '../modules/FormValidator';
import TooltipInitializer from '../modules/TooltipInitializer';
import SectionManager from '../modules/SectionManager';
import ModalManager from '../modules/ModalManager';
import NomineeSelector from '../modules/NomineeSelector';
import TomSelectInitializer from '../modules/TomSelectInitializer';

/**
 * Define os comportamentos a executar após o carregamento da página.
 *
 * @since 1.0
 * @version 1.0
 */
document.addEventListener('DOMContentLoaded', () => {
    /**
     * Inicia os TomSelects.
     *
     * @since 1.0
     * @version 1.0
     */
    const tomSelects = new TomSelectInitializer();

    /**
     * Inicia os tooltips.
     *
     * @since 1.0
     * @version 1.0
     */
    new TooltipInitializer();

    /**
     * Inicializa os componentes de uma secção.
     *
     * @since 1.0
     * @version 1.0
     */
    const initComponentsForSection = (sectionElement) => {
        sectionElement.querySelectorAll('select').forEach(el => tomSelects.init(el));
        new EmbedTester(sectionElement);
        new TooltipInitializer();
    };

    /**
     * Inicia o gerenciador de secções.
     *
     * @since 1.0
     * @version 1.0
     */
    new SectionManager(
        '#sections-container', '#add-section-btn', '#section-template',
        (newSectionElement) => initComponentsForSection(newSectionElement)
    );

    /**
     * Inicia o selecionador de nomeados.
     *
     * @since 1.0
     * @version 1.0
     */
    new NomineeSelector({
        randomBtnSelector: '#select-random-nominee',
        oldestBtnSelector: '#select-oldest-nominee',
        tomSelectInstance: tomSelects.getInstance('next_nominee_id'),
        oldestNomineeUrl : window.longestNotNominatedUrl
    });

    /**
     * Valida o formulário principal.
     *
     * @since 1.0
     * @version 1.0
     */
    const mainFormValidator = new FormValidator(
        '#create-metalthursday-form',
        {
            'edition_id'     : ['required'],
            'date'           : ['required', 'date'],
            'name'           : ['max:255'],
            'author_id'      : ['required'],
            'next_nominee_id': ['required'],
        },
        {
            'edition_id'     : { required: 'Por favor, seleciona a edição.' },
            'date'           : {
                required: 'Por favor, seleciona a data.',
                date    : 'A data deve ser uma data válida.'
            },
            'name'           : { max: 'O nome deve ter no máximo 255 caracteres.' },
            'author_id'      : { required: 'Por favor, seleciona o autor.' },
            'next_nominee_id': { required: 'Por favor, seleciona o nomeado.' },
        },
        null,
        (validator) => {
            let isAllSectionsValid = true;
            const sectionsContainer = document.getElementById('sections-container');
            const sections = sectionsContainer.querySelectorAll('.section-item');
            const feedbackElement = document.getElementById('sections-validation-feedback');

            if (sections.length === 0) {
                feedbackElement.textContent = 'É necessário adicionar no mínimo uma secção.';
                feedbackElement.style.display = 'block';
                return false;
            } else {
                feedbackElement.textContent = '';
                feedbackElement.style.display = 'none';
            }

            sections.forEach(section => {
                const typeSelect = section.querySelector('.section-type-select');
                const description = section.querySelector('textarea[name*="[description]"]');

                if (!validator.validateFieldWithRules(typeSelect, ['required'], { required: 'Por favor, seleciona o tipo de secção.' })) isAllSectionsValid = false;
                if (!validator.validateFieldWithRules(description, ['required'], { required: 'Por favor, insere a descrição.' })) isAllSectionsValid = false;

                const selectedOption = typeSelect.options[typeSelect.selectedIndex];
                const hasDetails = selectedOption && selectedOption.dataset.hasDetails === 'true';

                if (hasDetails) {
                    const band = section.querySelector('select[name*="[band_id]"]');
                    const title = section.querySelector('input[name*="[title]"]');
                    const link = section.querySelector('input[name*="[link]"]');
                    const year = section.querySelector('input[name*="[year]"]');

                    if (!validator.validateFieldWithRules(band, ['required'], { required: 'Por favor, seleciona a banda.' })) isAllSectionsValid = false;
                    if (!validator.validateFieldWithRules(title, ['required'], { required: 'Por favor, insere o título.' })) isAllSectionsValid = false;
                    if (!validator.validateFieldWithRules(link, ['required'], { required: 'Por favor, insere o link.' })) isAllSectionsValid = false;
                    if (!validator.validateFieldWithRules(year, ['required'], { required: 'Por favor, insere o ano.' })) isAllSectionsValid = false;
                }
            });

            return isAllSectionsValid;
        }
    );

    /**
     * Configura os modais de criação.
     *
     * @since 1.0
     * @version 1.0
     */
    const modalConfigs = [
        {
            formId: 'create-edition-form',
            url: window.editionStoreUrl,
            validationRules: {
                'name': ['required', 'max:255'],
                'start_date': ['required', 'date'],
                'end_date': ['date', 'after_or_equal:start_date'],
            },
            validationMessages: {
                'name': {
                    required: 'Por favor, insere o nome.',
                    max: 'O nome não deve ter no máximo 255 caracteres.'
                },
                'start_date': {
                    required: 'Por favor, seleciona a data de início.',
                    date: 'A data de início deve ser uma data válida.'
                },
                'end_date': {
                    date: 'A data de fim deve ser uma data válida.',
                    after_or_equal: 'A data de fim não deve ser anterior à data de início.'
                }
            },
            onSuccess: (responseData) => {
                const editionSelect = tomSelects.getInstance('edition_id');
                if (editionSelect) {
                    editionSelect.addOption({ value: responseData.id, text: responseData.display_text });
                    editionSelect.setValue(responseData.id);
                }
            }
        },
        {
            formId: 'create-band-form',
            url: window.bandStoreUrl,
            validationRules: {
                'name': ['required', 'max:255'],
                'country_id': ['required'],
                'genres[]': ['required'],
            },
            validationMessages: {
                'name': {
                    required: 'Por favor, insere o nome.',
                    max: 'O nome deve ter no máximo 255 caracteres.'
                },
                'country_id': {
                    required: 'Por favor, seleciona o país.'
                },
                'genres[]': {
                    required: 'Por favor, seleciona pelo menos um género.'
                }
            },
            onSuccess: (responseData) => {
                document.querySelectorAll('.tom-select-bands').forEach(select => {
                    if (select.tomselect) select.tomselect.addOption({ value: responseData.id, text: responseData.name });
                });
            }
        },
        {
            formId: 'create-genre-form',
            url: window.genreStoreUrl,
            validationRules: {
                'name': ['required', 'max:255'],
            },
            validationMessages: {
                'name': {
                    required: 'Por favor, insere o nome.',
                    max: 'O nome deve ter no máximo 255 caracteres.'
                }
            },
            onSuccess: (responseData) => {
                document.querySelectorAll('.tom-select-multiple, #new_genre_parent_ids').forEach(select => {
                    if (select.tomselect) select.tomselect.addOption({ value: responseData.id, text: responseData.name });
                });
            }
        }
    ];

    new ModalManager(modalConfigs, tomSelects);
});
