<?php

declare(strict_types=1);

namespace App\Http\Requests\Utilizadores;

use App\Models\Autenticacao\Utilizador;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use LogicException;

/**
 * Valida os pedidos de atualização do perfil do utilizador autenticado.
 *
 * Este pedido trata apenas das regras associadas ao transporte HTTP. A
 * normalização e validação canónicas do nome e do endereço de e-mail
 * permanecem asseguradas pelos respetivos objetos de valor no serviço
 * responsável pela atualização do perfil.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class AtualizarPerfilRequest extends FormRequest
{
    /**
     * Saco utilizado para os erros da atualização do perfil.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $errorBag = 'perfil';

    /**
     * Determina se o pedido pode ser executado.
     *
     * Apenas um utilizador autenticado através do modelo principal da
     * aplicação pode atualizar o próprio perfil.
     *
     * @return bool - Verdadeiro quando existe um utilizador autenticado.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function authorize(): bool
    {
        return $this->user() instanceof Utilizador;
    }

    /**
     * Prepara os valores recebidos para validação.
     *
     * Esta normalização melhora a experiência de validação HTTP. As regras
     * definitivas continuam protegidas pelos objetos de valor utilizados na
     * camada de aplicação.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function prepareForValidation(): void
    {
        $nomeNormalizado = preg_replace(
            '/\s+/u',
            ' ',
            trim((string) $this->input('nome')),
        );

        $this->merge([
            'nome' => $nomeNormalizado ?? '',
            'email' => mb_strtolower(
                trim((string) $this->input('email')),
            ),
        ]);
    }

    /**
     * Obtém as regras de validação aplicáveis ao pedido.
     *
     * A verificação de unicidade ignora o próprio utilizador autenticado,
     * permitindo manter o endereço atual sem provocar um falso conflito.
     *
     * @return array<string, array<int, mixed>> - Regras de validação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function rules(): array
    {
        $utilizador = $this->obterUtilizadorAutenticado();

        return [
            'fotografia' => [
                'bail',
                'nullable',
                File::image()
                    ->max('10mb'),
                'mimes:jpg,jpeg,png,webp',
            ],

            'nome' => [
                'bail',
                'required',
                'string',
                'min:3',
                'max:255',
            ],

            'email' => [
                'bail',
                'required',
                'string',
                'email:rfc',
                'max:255',
                Rule::unique(
                    Utilizador::class,
                    'email',
                )->ignore($utilizador),
            ],
        ];
    }

    /**
     * Obtém as mensagens de erro específicas do pedido.
     *
     * @return array<string, string> - Mensagens de validação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function messages(): array
    {
        return [
            'fotografia.image' => 'A fotografia deve ser uma imagem válida.',

            'fotografia.mimes' => 'A fotografia deve estar no formato JPG, JPEG, PNG ou WEBP.',

            'fotografia.max' => 'A fotografia não pode ter mais de 10 MB.',

            'nome.required' => 'Por favor, insere o teu nome.',

            'nome.string' => 'O nome deve ser uma sequência de caracteres.',

            'nome.min' => 'O nome deve ter, pelo menos, 3 caracteres.',

            'nome.max' => 'O nome não pode ter mais de 255 caracteres.',

            'email.required' => 'Por favor, insere o teu endereço de e-mail.',

            'email.string' => 'O endereço de e-mail deve ser uma sequência de caracteres.',

            'email.email' => 'Por favor, insere um endereço de e-mail válido.',

            'email.max' => 'O endereço de e-mail não pode ter mais de 255 caracteres.',

            'email.unique' => 'O endereço de e-mail já está associado a outro utilizador.',
        ];
    }

    /**
     * Obtém os nomes legíveis dos atributos validados.
     *
     * @return array<string, string> - Nomes dos atributos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function attributes(): array
    {
        return [
            'fotografia' => 'fotografia',
            'nome' => 'nome',
            'email' => 'endereço de e-mail',
        ];
    }

    /**
     * Obtém o utilizador autenticado.
     *
     * Este método centraliza o refinamento do tipo devolvido pelo mecanismo
     * de autenticação do Laravel.
     *
     * @return Utilizador - Utilizador autenticado.
     *
     * @throws LogicException Quando o pedido é utilizado sem autenticação
     *                        válida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterUtilizadorAutenticado(): Utilizador
    {
        $utilizador = $this->user();

        if (! $utilizador instanceof Utilizador) {
            throw new LogicException(
                'Não existe um utilizador autenticado válido para atualizar o perfil.',
            );
        }

        return $utilizador;
    }
}
