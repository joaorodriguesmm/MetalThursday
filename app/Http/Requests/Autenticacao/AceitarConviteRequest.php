<?php

declare(strict_types=1);

namespace App\Http\Requests\Autenticacao;

use App\Models\Autenticacao\Convite;
use App\Models\Autenticacao\Utilizador;
use App\Models\Comunicacao\PermissaoEmail;
use App\Regras\Autenticacao\RequisitosPalavraPasse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use LogicException;

/**
 * Valida e normaliza os dados necessários para aceitar um convite.
 *
 * A disponibilidade, expiração e utilização do convite são verificadas pelo
 * serviço de registo dentro da respetiva transação.
 *
 * @since 1.0.0
 *
 * @version 3.1.0
 */
final class AceitarConviteRequest extends FormRequest
{
    /**
     * Tamanho máximo permitido para a fotografia, em kilobytes.
     *
     * @var int
     *
     * @since 3.1.0
     *
     * @version 1.0.0
     */
    private const TAMANHO_MAXIMO_FOTOGRAFIA_KILOBYTES =
        10 * 1024;

    /**
     * Determina se o pedido pode ser processado.
     *
     * A validade do convite é uma regra de domínio e não uma regra de
     * autorização do pedido.
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
     * O código do convite mantém a capitalização por ser sensível a
     * maiúsculas e minúsculas.
     *
     * @since 2.0.0
     *
     * @version 2.2.0
     */
    protected function prepareForValidation(): void
    {
        $codigoConvite =
            $this->input(
                'codigo_convite',
            );

        $this->merge([
            'codigo_convite' => is_string($codigoConvite)
                ? trim(
                    $codigoConvite,
                )
                : $codigoConvite,

            'nome' => $this->normalizarNome(
                $this->input(
                    'nome',
                ),
            ),

            'email' => $this->normalizarEmail(
                $this->input(
                    'email',
                ),
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
     * O código do convite não utiliza a regra `exists`, porque apenas o hash
     * do código é guardado na base de dados.
     *
     * @return array<string, array<int, mixed>> Regras de validação.
     *
     * @since 1.0.0
     *
     * @version 3.1.0
     */
    public function rules(): array
    {
        return [
            'codigo_convite' => [
                'bail',
                'required',
                'string',
                'min:'.Convite::COMPRIMENTO_MINIMO_CODIGO,
                'max:'.Convite::COMPRIMENTO_MAXIMO_CODIGO,
                'regex:/\A'.Convite::PADRAO_CODIGO.'\z/',
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

            'fotografia' => [
                'bail',
                'nullable',

                File::image()
                    ->types([
                        'jpg',
                        'jpeg',
                        'png',
                        'webp',
                    ])
                    ->max(
                        self::TAMANHO_MAXIMO_FOTOGRAFIA_KILOBYTES,
                    ),
            ],

            'palavra_passe' => RequisitosPalavraPasse::regrasObrigatorias(),

            'confirmacao_palavra_passe' => [
                'bail',
                'required',
                'string',
                'max:'.RequisitosPalavraPasse::comprimentoMaximo(),
                'same:palavra_passe',
            ],

            'permissoes_email' => [
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
     * @version 3.1.0
     */
    public function messages(): array
    {
        return [
            'codigo_convite.required' => 'Não foi recebido um código de convite.',

            'codigo_convite.string' => 'O código do convite não é válido.',

            'codigo_convite.min' => 'O código do convite não é válido.',

            'codigo_convite.max' => 'O código do convite não é válido.',

            'codigo_convite.regex' => 'O código do convite não é válido.',

            'nome.required' => 'Por favor, insere o teu nome.',

            'nome.string' => 'O nome deve ser uma sequência de caracteres.',

            'nome.min' => 'O nome deve ter, pelo menos, 3 caracteres.',

            'nome.max' => 'O nome não pode ter mais de 255 caracteres.',

            'email.required' => 'Por favor, insere o teu endereço de e-mail.',

            'email.string' => 'O endereço de e-mail deve ser uma sequência de caracteres.',

            'email.email' => 'Por favor, insere um endereço de e-mail válido.',

            'email.max' => 'O endereço de e-mail não pode ter mais de 255 caracteres.',

            'email.unique' => 'O endereço de e-mail já está associado a outro utilizador.',

            'fotografia.image' => 'A fotografia deve ser uma imagem válida.',

            'fotografia.mimes' => 'A fotografia deve estar no formato JPG, PNG ou WebP.',

            'fotografia.max' => 'A fotografia não pode ter mais de 10 MiB.',

            'palavra_passe.required' => 'Por favor, insere uma palavra-passe.',

            'palavra_passe.string' => 'A palavra-passe deve ser uma sequência de caracteres.',

            'palavra_passe.max' => 'A palavra-passe é demasiado longa.',

            'confirmacao_palavra_passe.required' => 'Por favor, confirma a palavra-passe.',

            'confirmacao_palavra_passe.string' => 'A confirmação da palavra-passe não é válida.',

            'confirmacao_palavra_passe.max' => 'A confirmação da palavra-passe é demasiado longa.',

            'confirmacao_palavra_passe.same' => 'A palavra-passe e a confirmação não coincidem.',

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

            'nome' => 'nome',

            'email' => 'endereço de e-mail',

            'fotografia' => 'fotografia',

            'palavra_passe' => 'palavra-passe',

            'confirmacao_palavra_passe' => 'confirmação da palavra-passe',

            'permissoes_email' => 'permissões de e-mail',

            'permissoes_email.*' => 'permissão de e-mail',
        ];
    }

    /**
     * Obtém o código do convite validado.
     *
     * @return string Código do convite.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public function codigoConvite(): string
    {
        return $this->obterTextoValidado(
            'codigo_convite',
        );
    }

    /**
     * Obtém o nome validado.
     *
     * @return string Nome do utilizador.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public function nome(): string
    {
        return $this->obterTextoValidado(
            'nome',
        );
    }

    /**
     * Obtém o endereço de e-mail validado.
     *
     * @return string Endereço de e-mail.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public function email(): string
    {
        return $this->obterTextoValidado(
            'email',
        );
    }

    /**
     * Obtém a palavra-passe validada.
     *
     * @return string Palavra-passe em texto simples.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public function palavraPasse(): string
    {
        return $this->obterTextoValidado(
            'palavra_passe',
        );
    }

    /**
     * Obtém a fotografia validada.
     *
     * @return UploadedFile|null Fotografia enviada ou nulo.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public function fotografia(): ?UploadedFile
    {
        $fotografia =
            $this->file(
                'fotografia',
            );

        if ($fotografia === null) {
            return null;
        }

        if (! $fotografia instanceof UploadedFile) {
            throw new LogicException(
                'A fotografia validada possui um tipo inesperado.',
            );
        }

        return $fotografia;
    }

    /**
     * Obtém os identificadores validados das permissões de e-mail.
     *
     * @return array<int, int> Identificadores das permissões.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public function identificadoresPermissoesEmail(): array
    {
        $identificadores =
            $this->validated(
                'permissoes_email',
                [],
            );

        if (! is_array($identificadores)) {
            throw new LogicException(
                'As permissões de e-mail validadas possuem um tipo inesperado.',
            );
        }

        foreach ($identificadores as $identificador) {
            if (! is_int($identificador)) {
                throw new LogicException(
                    'Uma permissão de e-mail validada possui um tipo inesperado.',
                );
            }
        }

        /** @var array<int, int> $identificadores */
        return array_values(
            $identificadores,
        );
    }

    /**
     * Obtém um texto validado.
     *
     * @param  string  $campo  Nome do campo validado.
     * @return string Texto validado.
     *
     * @throws LogicException Quando o valor validado possui um tipo
     *                        inesperado.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function obterTextoValidado(
        string $campo,
    ): string {
        $valor =
            $this->validated(
                $campo,
            );

        if (! is_string($valor)) {
            throw new LogicException(
                sprintf(
                    'O campo validado "%s" possui um tipo inesperado.',
                    $campo,
                ),
            );
        }

        return $valor;
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

        $nome =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    $valor,
                ),
            );

        return is_string($nome)
            ? $nome
            : trim(
                $valor,
            );
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
                trim(
                    $valor,
                ),
            )
            : $valor;
    }

    /**
     * Normaliza os identificadores das permissões de e-mail.
     *
     * Os valores numéricos são convertidos para inteiros. Os restantes são
     * preservados para que a validação os rejeite.
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
                    && ctype_digit(
                        $identificador,
                    )
                ) {
                    return (int) $identificador;
                }

                return $identificador;
            },
            $valor,
        );
    }
}
