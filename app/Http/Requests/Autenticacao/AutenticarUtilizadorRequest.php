<?php

declare(strict_types=1);

namespace App\Http\Requests\Autenticacao;

use App\Models\Autenticacao\Utilizador;
use App\ObjetosValor\Utilizadores\EnderecoEmail;
use App\Regras\Autenticacao\RequisitosPalavraPasse;
use Closure;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LogicException;

/**
 * Valida e processa um pedido de autenticação.
 *
 * A limitação de tentativas combina o endereço de e-mail normalizado com o
 * endereço IP do pedido. Esses valores são transformados num hash antes de
 * serem utilizados como chave da cache.
 *
 * O nome `password` utilizado nas credenciais pertence ao contrato técnico
 * de autenticação do Laravel. O campo recebido pelo formulário mantém o nome
 * português `palavra_passe`.
 *
 * Uma conta suspensa só é identificada depois de o Laravel confirmar as
 * credenciais. Desta forma, o estado da conta não é revelado a quem não
 * conhece a palavra-passe correta.
 *
 * @since 1.0.0
 *
 * @version 5.0.1
 */
final class AutenticarUtilizadorRequest extends FormRequest
{
    /**
     * Número máximo de tentativas antes do bloqueio temporário.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const MAXIMO_TENTATIVAS = 5;

    /**
     * Duração do bloqueio temporário, em segundos.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const DURACAO_BLOQUEIO_SEGUNDOS = 60;

    /**
     * Determina se o pedido pode ser processado.
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
     * Normaliza o endereço de e-mail antes da validação.
     *
     * A palavra-passe não é alterada, porque os espaços podem fazer parte do
     * respetivo valor.
     *
     * A normalização definitiva e a validação do endereço são posteriormente
     * realizadas pelo objeto de valor {@see EnderecoEmail}.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    protected function prepareForValidation(): void
    {
        $email =
            $this->input(
                'email',
            );

        if (! is_string($email)) {
            return;
        }

        $this->merge([
            'email' => mb_strtolower(
                trim(
                    $email,
                ),
            ),
        ]);
    }

    /**
     * Obtém as regras de validação.
     *
     * Não são aplicados à autenticação os requisitos mínimos atuais de
     * complexidade, porque uma conta existente pode utilizar uma
     * palavra-passe criada sob regras anteriores.
     *
     * O comprimento máximo continua a ser aplicado para limitar o custo do
     * processamento de valores maliciosamente extensos.
     *
     * @return array<string, list<string|Closure>> Regras de validação.
     *
     * @since 1.0.0
     *
     * @version 4.0.0
     */
    public function rules(): array
    {
        return [
            'email' => [
                'bail',
                'required',
                'string',

                /**
                 * Valida o endereço através do objeto de valor do domínio.
                 *
                 * @param  string  $atributo  Nome do atributo.
                 * @param  mixed  $valor  Valor recebido.
                 * @param  Closure(string): void  $falhar  Função de erro.
                 *
                 * @since 2.0.0
                 *
                 * @version 1.0.0
                 */
                static function (
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
                },
            ],

            'palavra_passe' => [
                'bail',
                'required',
                'string',
                'max:'.RequisitosPalavraPasse::comprimentoMaximo(),
            ],

            'manter_sessao_iniciada' => [
                'nullable',
                'boolean',
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
     * @version 4.0.0
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Por favor, insere o teu endereço de e-mail.',

            'email.string' => 'O endereço de e-mail deve ser uma sequência de caracteres.',

            'palavra_passe.required' => 'Por favor, insere a palavra-passe.',

            'palavra_passe.string' => 'A palavra-passe deve ser uma sequência de caracteres.',

            'palavra_passe.max' => 'A palavra-passe recebida é demasiado longa.',

            'manter_sessao_iniciada.boolean' => 'A opção de manter a sessão iniciada não é válida.',
        ];
    }

    /**
     * Obtém os nomes apresentados para os atributos validados.
     *
     * @return array<string, string> Nomes legíveis dos atributos.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function attributes(): array
    {
        return [
            'email' => 'endereço de e-mail',

            'palavra_passe' => 'palavra-passe',

            'manter_sessao_iniciada' => 'manter a sessão iniciada',
        ];
    }

    /**
     * Autentica um utilizador com acesso ativo através do guard da sessão.
     *
     * O callback de `attemptWhen` é executado apenas depois de as credenciais
     * serem confirmadas. Uma conta suspensa recebe uma mensagem específica,
     * mas uma palavra-passe incorreta continua a produzir apenas o erro
     * genérico de autenticação.
     *
     * Tentativas com credenciais corretas de uma conta suspensa não aumentam
     * o limitador de tentativas falhadas.
     *
     * @throws ValidationException Quando as credenciais são inválidas, a
     *                             conta está suspensa ou o limite de
     *                             tentativas foi excedido.
     * @throws LogicException Quando o guard `sessao` não utiliza o driver de
     *                        sessões configurado pela aplicação.
     *
     * @since 1.0.0
     *
     * @version 5.0.1
     */
    public function autenticar(): void
    {
        $this->garantirAusenciaDeLimitacao();

        $chaveLimitacao =
            $this->obterChaveLimitacao();

        $utilizadorSuspenso =
            false;

        $guardaSessao =
            $this->obterGuardaSessao();

        $autenticado =
            $guardaSessao->attemptWhen(
                $this->obterCredenciais(),

                static function (
                    Authenticatable $utilizador,
                ) use (
                    &$utilizadorSuspenso,
                ): bool {
                    if (! $utilizador instanceof Utilizador) {
                        return false;
                    }

                    $utilizadorSuspenso =
                        $utilizador->estaSuspenso();

                    return ! $utilizadorSuspenso;
                },

                $this->manterSessaoIniciada(),
            );

        if (! $autenticado) {
            if ($utilizadorSuspenso) {
                RateLimiter::clear(
                    $chaveLimitacao,
                );

                throw ValidationException::withMessages([
                    'email' => 'A tua conta encontra-se suspensa.',
                ]);
            }

            RateLimiter::hit(
                $chaveLimitacao,
                self::DURACAO_BLOQUEIO_SEGUNDOS,
            );

            throw ValidationException::withMessages([
                'email' => trans(
                    'auth.failed',
                ),
            ]);
        }

        RateLimiter::clear(
            $chaveLimitacao,
        );
    }

    /**
     * Obtém o endereço de e-mail validado e normalizado.
     *
     * @return string Endereço de e-mail.
     *
     * @throws LogicException Quando o resultado validado deixa de cumprir o
     *                        contrato do objeto de valor.
     *
     * @since 3.0.0
     *
     * @version 2.0.0
     */
    public function email(): string
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
     * Determina se a sessão deve permanecer iniciada.
     *
     * @return bool Verdadeiro quando a opção foi selecionada.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    public function manterSessaoIniciada(): bool
    {
        return $this->boolean(
            'manter_sessao_iniciada',
        );
    }

    /**
     * Obtém o guard de sessões utilizado pela aplicação.
     *
     * A facade de autenticação declara um tipo genérico para os guards. A
     * verificação explícita restringe esse contrato à implementação
     * `SessionGuard`, exigida pela configuração do guard `sessao` e necessária
     * para utilizar `attemptWhen`.
     *
     * @return SessionGuard Guard de autenticação baseado em sessões.
     *
     * @throws LogicException Quando o guard utiliza uma implementação
     *                        diferente da configurada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterGuardaSessao(): SessionGuard
    {
        $guarda =
            Auth::guard(
                'sessao',
            );

        if ($guarda instanceof SessionGuard) {
            return $guarda;
        }

        throw new LogicException(
            'O guard "sessao" deve utilizar a implementação de autenticação por sessão.',
        );
    }

    /**
     * Obtém as credenciais no formato técnico exigido pelo Laravel.
     *
     * A chave `password` pertence ao contrato interno do sistema de
     * autenticação e não corresponde ao nome do campo recebido pelo
     * formulário.
     *
     * @return array{
     *     email: string,
     *     password: string
     * } Credenciais de autenticação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterCredenciais(): array
    {
        return [
            'email' => $this->email(),

            'password' => $this->palavraPasse(),
        ];
    }

    /**
     * Impede uma nova tentativa quando o limite foi excedido.
     *
     * @throws ValidationException Quando o pedido está temporariamente
     *                             bloqueado.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private function garantirAusenciaDeLimitacao(): void
    {
        $chave =
            $this->obterChaveLimitacao();

        if (
            ! RateLimiter::tooManyAttempts(
                $chave,
                self::MAXIMO_TENTATIVAS,
            )
        ) {
            return;
        }

        event(
            new Lockout(
                $this,
            ),
        );

        $segundos =
            RateLimiter::availableIn(
                $chave,
            );

        throw ValidationException::withMessages([
            'email' => trans(
                'auth.throttle',
                [
                    'seconds' => $segundos,

                    'minutes' => (int) ceil(
                        $segundos / 60,
                    ),
                ],
            ),
        ]);
    }

    /**
     * Obtém a chave utilizada na limitação de tentativas.
     *
     * O endereço de e-mail normalizado e o endereço IP são transformados num
     * hash para não serem armazenados diretamente como identificador da
     * cache.
     *
     * Este método é executado apenas depois da validação bem-sucedida do
     * pedido.
     *
     * @return string Chave da limitação.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    private function obterChaveLimitacao(): string
    {
        $enderecoIp =
            $this->ip()
            ?? 'desconhecido';

        return 'autenticacao:'.hash(
            'sha256',
            $this->email()
                .'|'
                .$enderecoIp,
        );
    }

    /**
     * Obtém um texto validado.
     *
     * @param  string  $campo  Nome do campo.
     * @return string Valor validado.
     *
     * @throws LogicException Quando o valor possui um tipo inesperado.
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
}
