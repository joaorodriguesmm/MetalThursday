<?php

declare(strict_types=1);

namespace App\Http\Requests\Autenticacao;

use App\Models\Autenticacao\Convite;
use App\Models\Autenticacao\Utilizador;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;
use LogicException;
use Throwable;

/**
 * Valida a criação administrativa de um convite.
 *
 * A autorização é executada através da política antes da validação. O nome e
 * o endereço são confirmados também pelos atributos definitivos do modelo.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class CriarConviteRequest extends FormRequest
{
    /**
     * Saco utilizado para os erros da criação.
     *
     * Esta propriedade não deve ser tipada porque é herdada do
     * {@see FormRequest}.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $errorBag =
        'criacao_convite';

    /**
     * Determina se o pedido pode ser processado.
     *
     * @return bool Verdadeiro quando o utilizador autenticado pode criar
     *              convites.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function authorize(): bool
    {
        $utilizador =
            $this->user(
                'sessao',
            );

        return $utilizador instanceof Utilizador
            && $utilizador->can(
                'create',
                Convite::class,
            );
    }

    /**
     * Normaliza os campos textuais antes da validação.
     *
     * O nome reduz sequências de espaços a um único espaço. Um endereço vazio
     * passa a representar a ausência de destinatário específico.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function prepareForValidation(): void
    {
        $nome =
            $this->input(
                'nome_convidado',
            );

        $email =
            $this->input(
                'email_destino',
            );

        $dados = [];

        if (is_string($nome)) {
            $nomeNormalizado =
                preg_replace(
                    '/\s+/u',
                    ' ',
                    trim(
                        $nome,
                    ),
                );

            if (is_string($nomeNormalizado)) {
                $dados['nome_convidado'] =
                    $nomeNormalizado;
            }
        }

        if (is_string($email)) {
            $emailNormalizado =
                trim(
                    $email,
                );

            $dados['email_destino'] =
                $emailNormalizado !== ''
                ? $emailNormalizado
                : null;
        }

        if ($dados !== []) {
            $this->merge(
                $dados,
            );
        }
    }

    /**
     * Obtém as regras de validação.
     *
     * @return array<string, list<mixed>> Regras de validação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function rules(): array
    {
        return [
            'nome_convidado' => [
                'bail',
                'required',
                'string',
                'max:'.Convite::COMPRIMENTO_MAXIMO_NOME_CONVIDADO,
                $this->criarRegraNomeConvidado(),
            ],

            'email_destino' => [
                'bail',
                'nullable',
                'string',
                'max:255',
                'email:rfc',
                $this->criarRegraEmailDestino(),
            ],

            'expira_em' => [
                'bail',
                'nullable',
                'string',
                'date_format:Y-m-d\TH:i',
                'after:now',
            ],
        ];
    }

    /**
     * Obtém as mensagens de validação.
     *
     * @return array<string, string> Mensagens de validação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function messages(): array
    {
        return [
            'nome_convidado.required' => 'Indica o nome da pessoa convidada.',

            'nome_convidado.string' => 'O nome da pessoa convidada deve ser uma sequência de caracteres.',

            'nome_convidado.max' => sprintf(
                'O nome da pessoa convidada não pode ter mais de %d caracteres.',
                Convite::COMPRIMENTO_MAXIMO_NOME_CONVIDADO,
            ),

            'email_destino.string' => 'O endereço de destino deve ser uma sequência de caracteres.',

            'email_destino.max' => 'O endereço de destino não pode ter mais de 255 caracteres.',

            'email_destino.email' => 'Indica um endereço de e-mail válido.',

            'expira_em.string' => 'A expiração deve ser uma sequência de caracteres.',

            'expira_em.date_format' => 'Indica uma data e hora de expiração válidas.',

            'expira_em.after' => 'A expiração do convite deve estar no futuro.',
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
            'nome_convidado' => 'nome da pessoa convidada',

            'email_destino' => 'endereço de destino',

            'expira_em' => 'expiração do convite',
        ];
    }

    /**
     * Obtém o nome validado e normalizado.
     *
     * @return string Nome da pessoa convidada.
     *
     * @throws LogicException Quando o resultado validado não é textual.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function obterNomeConvidado(): string
    {
        $nome =
            $this->validated(
                'nome_convidado',
            );

        if (! is_string($nome)) {
            throw new LogicException(
                'O pedido validado não contém um nome de convidado textual.',
            );
        }

        return $nome;
    }

    /**
     * Obtém o endereço de destino validado.
     *
     * @return string|null Endereço de destino ou nulo.
     *
     * @throws LogicException Quando o resultado possui um tipo inesperado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function obterEmailDestino(): ?string
    {
        $email =
            $this->validated(
                'email_destino',
            );

        if ($email === null) {
            return null;
        }

        if (! is_string($email)) {
            throw new LogicException(
                'O pedido validado não contém um endereço de destino válido.',
            );
        }

        return $email;
    }

    /**
     * Obtém a expiração validada.
     *
     * @return CarbonInterface|null Expiração normalizada ou nulo.
     *
     * @throws LogicException Quando não é possível reconstruir o momento.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function obterExpiracao(): ?CarbonInterface
    {
        $valor =
            $this->validated(
                'expira_em',
            );

        if ($valor === null) {
            return null;
        }

        if (! is_string($valor)) {
            throw new LogicException(
                'O pedido validado não contém uma expiração textual.',
            );
        }

        try {
            return CarbonImmutable::parse(
                $valor,
                (string) config(
                    'app.timezone',
                    'UTC',
                ),
            )->startOfMinute();
        } catch (Throwable $excecao) {
            throw new LogicException(
                'O pedido validado não contém uma expiração válida.',
                previous: $excecao,
            );
        }
    }

    /**
     * Obtém o superadministrador autenticado.
     *
     * @return Utilizador Utilizador responsável.
     *
     * @throws LogicException Quando o pedido não possui autenticação válida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function obterUtilizadorAutenticado(): Utilizador
    {
        $utilizador =
            $this->user(
                'sessao',
            );

        if (! $utilizador instanceof Utilizador) {
            throw new LogicException(
                'Não existe um utilizador autenticado válido para criar o convite.',
            );
        }

        return $utilizador;
    }

    /**
     * Cria a regra definitiva do nome do convidado.
     *
     * @return Closure(string, mixed, Closure(string): void): void Regra.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarRegraNomeConvidado(): Closure
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
                $convite =
                    new Convite;

                $convite->nome_convidado =
                    $valor;
            } catch (InvalidArgumentException $excecao) {
                $falhar(
                    $excecao->getMessage(),
                );
            }
        };
    }

    /**
     * Cria a regra definitiva do endereço de destino.
     *
     * @return Closure(string, mixed, Closure(string): void): void Regra.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarRegraEmailDestino(): Closure
    {
        return static function (
            string $atributo,
            mixed $valor,
            Closure $falhar,
        ): void {
            if (
                $valor === null
                || ! is_string($valor)
            ) {
                return;
            }

            try {
                $convite =
                    new Convite;

                $convite->email_destino =
                    $valor;
            } catch (InvalidArgumentException $excecao) {
                $falhar(
                    $excecao->getMessage(),
                );
            }
        };
    }
}
