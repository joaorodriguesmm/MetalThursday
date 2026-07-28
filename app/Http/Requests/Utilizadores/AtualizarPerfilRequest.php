<?php

declare(strict_types=1);

namespace App\Http\Requests\Utilizadores;

use App\Models\Autenticacao\Utilizador;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use LogicException;

/**
 * Valida os dados necessários para atualizar o perfil do utilizador
 * autenticado.
 *
 * O pedido normaliza apenas os dados textuais recebidos. A atualização da
 * fotografia e as restantes regras de domínio são executadas pelo serviço
 * responsável pelo perfil.
 *
 * @since 1.0.0
 *
 * @version 2.2.0
 */
final class AtualizarPerfilRequest extends FormRequest
{
    /**
     * Tamanho máximo permitido para a fotografia, em kilobytes.
     *
     * @var int
     *
     * @since 2.2.0
     *
     * @version 1.0.0
     */
    private const TAMANHO_MAXIMO_FOTOGRAFIA_KILOBYTES =
        10 * 1024;

    /**
     * Saco utilizado para os erros da atualização do perfil.
     *
     * Esta propriedade não deve ser tipada, porque é herdada do FormRequest.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    protected $errorBag = 'perfil';

    /**
     * Determina se o pedido pode ser processado.
     *
     * Apenas o modelo principal de utilizador autenticado pode atualizar o
     * respetivo perfil.
     *
     * @return bool Verdadeiro quando existe um utilizador autenticado.
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
     * Normaliza os valores textuais antes da validação.
     *
     * Valores que não sejam strings são preservados para que as regras de
     * validação os possam rejeitar corretamente.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
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
        ]);
    }

    /**
     * Obtém as regras de validação.
     *
     * A verificação de unicidade ignora o próprio utilizador autenticado,
     * permitindo manter o endereço de e-mail atual.
     *
     * @return array<string, array<int, mixed>> Regras de validação.
     *
     * @throws LogicException Quando não existe um utilizador autenticado
     *                        válido.
     *
     * @since 1.0.0
     *
     * @version 2.2.0
     */
    public function rules(): array
    {
        $utilizador =
            $this->obterUtilizadorAutenticado();

        return [
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
                )->ignore(
                    $utilizador,
                ),
            ],
        ];
    }

    /**
     * Obtém as mensagens de validação.
     *
     * @return array<string, string> Mensagens de validação.
     *
     * @since 1.0.0
     *
     * @version 2.2.0
     */
    public function messages(): array
    {
        return [
            'fotografia.image' => 'A fotografia deve ser uma imagem válida.',

            'fotografia.extensions' => 'A fotografia deve estar no formato JPG, PNG ou WebP.',

            'fotografia.mimetypes' => 'A fotografia deve estar no formato JPG, PNG ou WebP.',

            'fotografia.max' => 'A fotografia não pode ter mais de 10 MiB.',

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
     * Obtém os nomes apresentados para os atributos.
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
            'fotografia' => 'fotografia',

            'nome' => 'nome',

            'email' => 'endereço de e-mail',
        ];
    }

    /**
     * Obtém o nome validado.
     *
     * @return string Nome normalizado.
     *
     * @throws LogicException Quando o pedido validado não contém um nome.
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    public function obterNome(): string
    {
        $nome =
            $this->validated(
                'nome',
            );

        if (! is_string($nome)) {
            throw new LogicException(
                'O pedido validado não contém um nome válido.',
            );
        }

        return $nome;
    }

    /**
     * Obtém o endereço de e-mail validado.
     *
     * @return string Endereço de e-mail normalizado.
     *
     * @throws LogicException Quando o pedido validado não contém um endereço
     *                        de e-mail.
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    public function obterEmail(): string
    {
        $email =
            $this->validated(
                'email',
            );

        if (! is_string($email)) {
            throw new LogicException(
                'O pedido validado não contém um endereço de e-mail válido.',
            );
        }

        return $email;
    }

    /**
     * Obtém a fotografia enviada.
     *
     * @return UploadedFile|null Fotografia validada ou nulo.
     *
     * @throws LogicException Quando o valor recebido não corresponde a um
     *                        ficheiro carregado válido.
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    public function obterFotografia(): ?UploadedFile
    {
        if (! $this->hasFile('fotografia')) {
            return null;
        }

        $fotografia =
            $this->file(
                'fotografia',
            );

        if (! $fotografia instanceof UploadedFile) {
            throw new LogicException(
                'O pedido validado não contém uma fotografia válida.',
            );
        }

        return $fotografia;
    }

    /**
     * Obtém o utilizador autenticado.
     *
     * @return Utilizador Utilizador autenticado.
     *
     * @throws LogicException Quando o pedido é utilizado sem autenticação
     *                        válida.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function obterUtilizadorAutenticado(): Utilizador
    {
        $utilizador =
            $this->user();

        if (! $utilizador instanceof Utilizador) {
            throw new LogicException(
                'Não existe um utilizador autenticado válido para atualizar o perfil.',
            );
        }

        return $utilizador;
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
}
