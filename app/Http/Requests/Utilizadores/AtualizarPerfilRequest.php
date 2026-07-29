<?php

declare(strict_types=1);

namespace App\Http\Requests\Utilizadores;

use App\Models\Autenticacao\Utilizador;
use App\ObjetosValor\Utilizadores\EnderecoEmail;
use App\ObjetosValor\Utilizadores\NomeUtilizador;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use InvalidArgumentException;
use LogicException;

/**
 * Valida os dados necessários para atualizar o perfil do utilizador
 * autenticado.
 *
 * O nome e o endereço de e-mail são validados através dos respetivos objetos
 * de valor. A persistência dos dados, a substituição da fotografia e as
 * restantes regras de domínio pertencem ao serviço de atualização do perfil.
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
final class AtualizarPerfilRequest extends FormRequest
{
    /**
     * Tamanho máximo permitido para a fotografia, em kilobytes.
     *
     * Dez megabytes correspondem a 10 240 kilobytes.
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
     * Esta propriedade não deve ser tipada, porque é herdada do
     * {@see FormRequest}.
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
     * @return bool Verdadeiro quando existe um utilizador autenticado válido.
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
     * Normaliza preliminarmente os valores textuais antes da validação.
     *
     * Valores que não sejam strings são preservados para que as regras de
     * validação os possam rejeitar corretamente.
     *
     * A normalização definitiva é aplicada pelos objetos de valor
     * {@see NomeUtilizador} e {@see EnderecoEmail}.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
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
     * permitindo conservar o endereço de e-mail atual.
     *
     * A proteção definitiva contra conflitos concorrentes permanece no
     * serviço responsável pela atualização.
     *
     * @return array<string, list<mixed>> Regras de validação.
     *
     * @throws LogicException Quando não existe um utilizador autenticado
     *                        válido.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
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
                $this->criarRegraNome(),
            ],

            'email' => [
                'bail',
                'required',
                'string',
                $this->criarRegraEnderecoEmail(),

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
     * @version 3.0.0
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

            'email.required' => 'Por favor, insere o teu endereço de e-mail.',

            'email.string' => 'O endereço de e-mail deve ser uma sequência de caracteres.',

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
     * Obtém o nome validado e normalizado.
     *
     * @return string Nome normalizado.
     *
     * @throws LogicException Quando o resultado validado deixa de cumprir o
     *                        contrato do objeto de valor.
     *
     * @since 2.1.0
     *
     * @version 2.0.0
     */
    public function obterNome(): string
    {
        $nome =
            $this->obterTextoValidado(
                'nome',
            );

        try {
            return NomeUtilizador::deTexto(
                $nome,
            )->valor();
        } catch (InvalidArgumentException $excecao) {
            throw new LogicException(
                'O pedido validado não contém um nome de utilizador válido.',
                previous: $excecao,
            );
        }
    }

    /**
     * Obtém o endereço de e-mail validado e normalizado.
     *
     * @return string Endereço de e-mail normalizado.
     *
     * @throws LogicException Quando o resultado validado deixa de cumprir o
     *                        contrato do objeto de valor.
     *
     * @since 2.1.0
     *
     * @version 2.0.0
     */
    public function obterEmail(): string
    {
        $email =
            $this->obterTextoValidado(
                'email',
            );

        try {
            return EnderecoEmail::deTexto(
                $email,
            )->valor();
        } catch (InvalidArgumentException $excecao) {
            throw new LogicException(
                'O pedido validado não contém um endereço de e-mail válido.',
                previous: $excecao,
            );
        }
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
     * @version 2.0.0
     */
    public function obterFotografia(): ?UploadedFile
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
     * Cria a regra de validação do nome do utilizador.
     *
     * A normalização, os limites e os caracteres permitidos pertencem ao
     * objeto de valor {@see NomeUtilizador}.
     *
     * @return Closure(string, mixed, Closure(string): void): void Regra.
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    private function criarRegraNome(): Closure
    {
        return static function (
            string $atributo,
            mixed $valor,
            Closure $falhar,
        ): void {
            if (! is_string($valor)) {
                return;
            }

            try {
                NomeUtilizador::deTexto(
                    $valor,
                );
            } catch (InvalidArgumentException) {
                $falhar(
                    'Por favor, insere um nome válido.',
                );
            }
        };
    }

    /**
     * Cria a regra de validação do endereço de e-mail.
     *
     * A sintaxe, o comprimento e a normalização definitiva pertencem ao
     * objeto de valor {@see EnderecoEmail}.
     *
     * @return Closure(string, mixed, Closure(string): void): void Regra.
     *
     * @since 2.1.0
     *
     * @version 1.0.0
     */
    private function criarRegraEnderecoEmail(): Closure
    {
        return static function (
            string $atributo,
            mixed $valor,
            Closure $falhar,
        ): void {
            if (! is_string($valor)) {
                return;
            }

            try {
                EnderecoEmail::deTexto(
                    $valor,
                );
            } catch (InvalidArgumentException) {
                $falhar(
                    'Por favor, insere um endereço de e-mail válido.',
                );
            }
        };
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
     * @since 2.1.0
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
     * Normaliza preliminarmente o nome recebido.
     *
     * Quando o texto não é UTF-8 válido, o valor original é preservado para
     * que o objeto de valor o rejeite durante a validação.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Nome normalizado ou valor original.
     *
     * @since 2.1.0
     *
     * @version 2.0.0
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
            : $valor;
    }

    /**
     * Normaliza preliminarmente o endereço de e-mail.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Endereço normalizado ou valor original.
     *
     * @since 2.1.0
     *
     * @version 2.0.0
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
