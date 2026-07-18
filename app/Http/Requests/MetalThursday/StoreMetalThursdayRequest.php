<?php

namespace App\Http\Requests\MetalThursday;

use App\Rules\RequiredWhenSectionHasDetails;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Gere os pedidos de criação/edição de MetalThursday.
 *
 * @since 1.0
 * @version 1.0
 */
class StoreMetalThursdayRequest extends FormRequest
{
    /**
     * Obtém se o utilizador é autorizado a executar o pedido.
     *
     * @return bool - Verdadeiro se o utilizador é autorizado.
     *
     * @since 1.0
     * @version 1.0
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtém as regras de validação para o pedido.
     *
     * @return array - Regras de validação.
     *
     * @since 1.0
     * @version 1.0
     */
    public function rules(): array
    {
        return [
            'edition_id'             => ['required', 'exists:mt_editions,id'],
            'date'                   => ['required', 'date'],
            'name'                   => ['nullable', 'string', 'max:255'],
            'author_id'              => ['required', 'exists:users,id'],
            'next_nominee_id'        => ['required', 'exists:users,id'],
            'sections'               => ['required', 'array', 'min:1'],
            'sections.*.id'          => ['nullable', 'exists:mt_sections,id'],
            'sections.*.type_id'     => ['required', 'exists:mt_section_types,id'],
            'sections.*.description' => ['required', 'string'],
            'sections.*.title'       => ['nullable', 'string', 'max:255', new RequiredWhenSectionHasDetails('Título')],
            'sections.*.band_id'     => ['nullable', 'exists:bands,id', new RequiredWhenSectionHasDetails('Banda')],
            'sections.*.link'        => ['nullable', 'url', 'max:2048', new RequiredWhenSectionHasDetails('Link')],
            'sections.*.embed_type'  => ['nullable', 'string', Rule::in(['youtube_video', 'youtube_playlist', 'link'])],
            'sections.*.year'        => ['nullable', 'integer', 'min:1900', 'max:' . date('Y'), new RequiredWhenSectionHasDetails('Ano')],
        ];
    }

    /**
     * Obtém as mensagens de erro para o pedido.
     *
     * @return array - Mensagens de erro.
     *
     * @since 1.0
     * @version 1.0
     */
    public function messages(): array
    {
        return [
            'edition_id.required'             => 'Por favor, seleciona uma edição.',
            'edition_id.exists'               => 'A edição selecionada é inválida.',
            'date.required'                   => 'Por favor, insere uma data.',
            'date.date'                       => 'A data deve ser uma data válida.',
            'author_id.required'              => 'Por favor, seleciona um autor.',
            'author_id.exists'                => 'O autor selecionado é inválido.',
            'next_nominee_id.required'        => 'Por favor, seleciona o próximo nomeado.',
            'next_nominee_id.exists'          => 'O próximo nomeado selecionado é inválido.',
            'sections.required'               => 'Por favor, insere pelo menos uma secção.',
            'sections.array'                  => 'As secções devem ser uma seleção valida.',
            'sections.min'                    => 'Por favor, insere pelo menos uma secção.',
            'sections.*.id.exists'            => 'A secção é inválida.',
            'sections.*.type_id.required'     => 'Por favor, seleciona o tipo de secção.',
            'sections.*.type_id.exists'       => 'O tipo de secção selecionado é inválido.',
            'sections.*.description.required' => 'Por favor, insere uma descrição.',
            'sections.*.description.string'   => 'A descrição deve ser uma sequência de caracteres.',
            'sections.*.title.string'         => 'O título deve ser uma sequência de caracteres.',
            'sections.*.title.max'            => 'O título deve ter no máximo 255 caracteres.',
            'sections.*.band_id.exists'       => 'A banda selecionada é inválida.',
            'sections.*.link.url'             => 'O link deve ser um URL válido (ex: http://...).',
            'sections.*.link.max'             => 'O link deve ter no máximo 2048 caracteres.',
            'sections.*.year.integer'         => 'O ano deve ser um número inteiro.',
            'sections.*.year.min'             => 'O ano deve ter no mínimo 1900.',
            'sections.*.year.max'             => 'O ano deve ter no máximo ' . date('Y') . '.',
        ];
    }
}
