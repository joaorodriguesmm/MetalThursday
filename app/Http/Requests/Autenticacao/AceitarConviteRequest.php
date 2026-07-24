<?php

declare(strict_types=1);

namespace App\Http\Requests\Autenticacao;

use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Valida os dados necessários para aceitar um convite e criar um utilizador.
 *
 * A disponibilidade, validade, expiração e utilização do convite são
 * verificadas pelo serviço responsável dentro de uma transação.
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
final class AceitarConviteRequest extends FormRequest
{
    /**
     * Determina se o pedido pode ser processado.
     *
     * A validade do convite é uma regra de domínio e não uma regra de
     * autorização deste pedido.
     *
     * @return bool Verdadeiro para permitir a validação.
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
     * O código do convite mantém a capitalização, porque é sensível a
     * maiúsculas e minúsculas.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'codigo_convite' => $this->normalizarTexto(
                $this->input('codigo_convite'),
            ),

            'nome' => $this->normalizarNome(
                $this->input('nome'),
            ),

            'email' => $this->normalizarEmail(
                $this->input('email'),
            ),

            'permissoes_email' => $this->normalizarPermissoesEmail(
                $this->input(
                    'permissoes_email',
                    [],
                ),
            ),
        ]);
    }

    /**
     * Obtém as regras de validação.
     *
     * O código do convite não utiliza `exists`, porque apenas o respetivo hash
     * é guardado na base de dados.
     *
     * @return array<string, array<int, mixed>> Regras de validação.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
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
                'file',
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
                Rule::unique(
                    Utilizador::class,
                    'email',
                ),
            ],

            'palavra_passe' => [
                'bail',
                'required',
                'string',
                Password::defaults(),
            ],

            'confirmacao_palavra_passe' => [
                'bail',
                'required',
                'string',
                'same:palavra_passe',
            ],

            'permissoes_email' => [
                'present',
                'array',
            ],

            'permissoes_email.*' => [
                'bail',
                'integer',
                'distinct:strict',
                Rule::exists(
                    PermissaoEmail::class,
                    'id',
                ),
            ],
        ];
    }

    /**
     * Obtém as mensagens de erro específicas.
     *
     * @return array<string, string> Mensagens de validação.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function messages(): array
    {
        return [
            'codigo_convite.required' => 'Não foi recebido um código de convite.',

            'codigo_convite.string' => 'O código do convite não é válido.',

            'codigo_convite.max' => 'O código do convite não é válido.',

            'fotografia.file' => 'A fotografia recebida não é um ficheiro válido.',

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

            'confirmacao_palavra_passe.required' => 'Por favor, confirma a palavra-passe.',

            'confirmacao_palavra_passe.string' => 'A confirmação da palavra-passe não é válida.',

            'confirmacao_palavra_passe.same' => 'A palavra-passe e a confirmação não coincidem.',

            'permissoes_email.present' => 'Não foram recebidas as permissões de e-mail.',

            'permissoes_email.array' => 'As permissões de e-mail recebidas não são válidas.',

            'permissoes_email.*.integer' => 'Uma das permissões de e-mail não é válida.',

            'permissoes_email.*.distinct' => 'A mesma permissão de e-mail foi selecionada mais do que uma vez.',

            'permissoes_email.*.exists' => 'Uma das permissões de e-mail selecionadas não existe.',
        ];
    }

    /**
     * Obtém os nomes apresentados para os atributos validados.
     *
     * @return array<string, string> Nomes legíveis dos atributos.
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

    /**
     * Normaliza um texto recebido.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Texto normalizado ou valor original.
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    private function normalizarTexto(
        mixed $valor,
    ): mixed {
        return is_string($valor)
            ? trim($valor)
            : $valor;
    }

    /**
     * Normaliza o nome recebido.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Nome normalizado ou valor original.
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    private function normalizarNome(
        mixed $valor,
    ): mixed {
        if (! is_string($valor)) {
            return $valor;
        }

        $nome = preg_replace(
            '/\s+/u',
            ' ',
            trim($valor),
        );

        return is_string($nome)
            ? $nome
            : trim($valor);
    }

    /**
     * Normaliza o endereço de e-mail.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Endereço normalizado ou valor original.
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    private function normalizarEmail(
        mixed $valor,
    ): mixed {
        return is_string($valor)
            ? mb_strtolower(
                trim($valor),
            )
            : $valor;
    }

    /**
     * Normaliza os identificadores das permissões de e-mail.
     *
     * Valores numéricos são convertidos para inteiros. Os restantes valores
     * são preservados para que a validação os possa rejeitar.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Lista normalizada ou valor original.
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    private function normalizarPermissoesEmail(
        mixed $valor,
    ): mixed {
        if ($valor === null) {
            return [];
        }

        if (! is_array($valor)) {
            return $valor;
        }

        return array_map(
            static function (
                mixed $identificador,
            ): mixed {
                if (
                    is_string($identificador)
                    && ctype_digit($identificador)
                ) {
                    return (int) $identificador;
                }

                return $identificador;
            },
            $valor,
        );
    }
}
