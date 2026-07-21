<?php

declare(strict_types=1);

namespace App\Http\Requests\Autenticacao;

use App\Models\Autenticacao\Utilizador;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Valida os dados necessários para aceitar um convite e criar um utilizador.
 *
 * Este pedido valida apenas a estrutura e o formato dos dados recebidos. A
 * existência, disponibilidade, expiração, revogação e utilização do convite
 * são verificadas pelo serviço dos convites dentro de uma transação.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class AceitarConviteRequest extends FormRequest
{
    /**
     * Determina se o pedido pode ser processado.
     *
     * A autorização de acesso ao formulário é controlada pelas rotas e pelos
     * respetivos middlewares. A validade do convite é uma regra de domínio e
     * será verificada pelo serviço responsável.
     *
     * @return bool - Verdadeiro para permitir a validação do pedido.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza os valores recebidos antes da validação.
     *
     * O código mantém a capitalização porque os códigos dos convites são
     * sensíveis a maiúsculas e minúsculas. O endereço de e-mail é convertido
     * para minúsculas para garantir comparações consistentes.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function prepareForValidation(): void
    {
        $codigoConvite = $this->input('codigo_convite');
        $nome = $this->input('nome');
        $email = $this->input('email');

        $this->merge([
            'codigo_convite' => is_string($codigoConvite)
                ? trim($codigoConvite)
                : $codigoConvite,

            'nome' => is_string($nome)
                ? preg_replace('/\s+/u', ' ', trim($nome))
                : $nome,

            'email' => is_string($email)
                ? mb_strtolower(trim($email))
                : $email,
        ]);
    }

    /**
     * Obtém as regras de validação do pedido.
     *
     * O código do convite não utiliza uma regra `exists`, porque o seu valor
     * original não é guardado na base de dados. A pesquisa pelo respetivo hash
     * será realizada posteriormente pelo serviço dos convites.
     *
     * @return array<string, array<int, mixed>> - Regras de validação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function rules(): array
    {
        return [
            'codigo_convite' => [
                'bail',
                'required',
                'string',
                'max:128',
            ],

            'fotografia' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:10240',
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
                Rule::unique(Utilizador::class, 'email'),
            ],

            'palavra_passe' => [
                'bail',
                'required',
                'string',
                'confirmed:confirmacao_palavra_passe',
                Password::defaults(),
            ],

            'confirmacao_palavra_passe' => [
                'required',
                'string',
            ],

            'permissoes_email' => [
                'nullable',
                'array',
            ],

            'permissoes_email.*' => [
                'bail',
                'integer',
                'distinct',
                Rule::exists('email_permissions', 'id'),
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
            'codigo_convite.required' => 'Não foi recebido um código de convite.',

            'codigo_convite.string' => 'O código do convite não é válido.',

            'codigo_convite.max' => 'O código do convite não é válido.',

            'fotografia.image' => 'A fotografia deve ser uma imagem válida.',

            'fotografia.mimes' => 'A fotografia deve estar no formato JPG, PNG ou WebP.',

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

            'palavra_passe.required' => 'Por favor, insere uma palavra-passe.',

            'palavra_passe.string' => 'A palavra-passe deve ser uma sequência de caracteres.',

            'palavra_passe.confirmed' => 'A palavra-passe e a confirmação não coincidem.',

            'confirmacao_palavra_passe.required' => 'Por favor, confirma a palavra-passe.',

            'confirmacao_palavra_passe.string' => 'A confirmação da palavra-passe não é válida.',

            'permissoes_email.array' => 'As permissões de e-mail recebidas não são válidas.',

            'permissoes_email.*.integer' => 'Uma das permissões de e-mail não é válida.',

            'permissoes_email.*.distinct' => 'A mesma permissão de e-mail foi selecionada mais do que uma vez.',

            'permissoes_email.*.exists' => 'Uma das permissões de e-mail selecionadas não existe.',
        ];
    }

    /**
     * Obtém os nomes apresentados para os atributos validados.
     *
     * @return array<string, string> - Nomes legíveis dos atributos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function attributes(): array
    {
        return [
            'codigo_convite' => 'código do convite',
            'fotografia' => 'fotografia',
            'nome' => 'nome',
            'email' => 'endereço de e-mail',
            'palavra_passe' => 'palavra-passe',
            'confirmacao_palavra_passe' => 'confirmação da palavra-passe',
            'permissoes_email' => 'permissões de e-mail',
            'permissoes_email.*' => 'permissão de e-mail',
        ];
    }
}
