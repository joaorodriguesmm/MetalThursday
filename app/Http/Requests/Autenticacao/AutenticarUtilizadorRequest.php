<?php

declare(strict_types=1);

namespace App\Http\Requests\Autenticacao;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Valida e processa um pedido de autenticação.
 *
 * A limitação de tentativas combina o endereço de e-mail normalizado com o
 * endereço IP do pedido, sem guardar esses valores diretamente na chave da
 * cache.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
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
     * A palavra-passe não é alterada, porque espaços podem fazer parte do seu
     * valor.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function prepareForValidation(): void
    {
        $email = $this->input('email');

        if (! is_string($email)) {
            return;
        }

        $this->merge([
            'email' => mb_strtolower(
                trim($email),
            ),
        ]);
    }

    /**
     * Obtém as regras de validação.
     *
     * Os nomes `email`, `password` e `remember` são mantidos por fazerem parte
     * do contrato atual do formulário e do sistema de autenticação.
     *
     * @return array<string, array<int, string>> Regras de validação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function rules(): array
    {
        return [
            'email' => [
                'bail',
                'required',
                'string',
                'email:rfc',
                'max:255',
            ],

            'password' => [
                'bail',
                'required',
                'string',
                'max:4096',
            ],

            'remember' => [
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
     * @version 2.0.0
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Por favor, insere o teu endereço de e-mail.',

            'email.string' => 'O endereço de e-mail deve ser uma sequência de caracteres.',

            'email.email' => 'Por favor, insere um endereço de e-mail válido.',

            'email.max' => 'O endereço de e-mail não pode ter mais de 255 caracteres.',

            'password.required' => 'Por favor, insere a palavra-passe.',

            'password.string' => 'A palavra-passe deve ser uma sequência de caracteres.',

            'password.max' => 'A palavra-passe recebida é demasiado extensa.',

            'remember.boolean' => 'A opção de manter a sessão iniciada não é válida.',
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
            'email' => 'endereço de e-mail',

            'password' => 'palavra-passe',

            'remember' => 'manter a sessão iniciada',
        ];
    }

    /**
     * Autentica o utilizador através do guard web.
     *
     * @throws ValidationException Quando as credenciais são inválidas ou o
     *                             limite de tentativas foi excedido.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function autenticar(): void
    {
        $this->garantirAusenciaDeLimitacao();

        $dados = $this->validated();

        /** @var string $email */
        $email = $dados['email'];

        /** @var string $palavraPasse */
        $palavraPasse = $dados['password'];

        $autenticado = Auth::guard('web')->attempt(
            [
                'email' => $email,
                'password' => $palavraPasse,
            ],
            $this->boolean('remember'),
        );

        if (! $autenticado) {
            RateLimiter::hit(
                $this->obterChaveLimitacao(),
                self::DURACAO_BLOQUEIO_SEGUNDOS,
            );

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear(
            $this->obterChaveLimitacao(),
        );
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
        $chave = $this->obterChaveLimitacao();

        if (
            ! RateLimiter::tooManyAttempts(
                $chave,
                self::MAXIMO_TENTATIVAS,
            )
        ) {
            return;
        }

        event(
            new Lockout($this),
        );

        $segundos = RateLimiter::availableIn(
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
     * O endereço de e-mail e o IP são transformados num hash para não serem
     * armazenados diretamente como identificador de cache.
     *
     * @return string Chave da limitação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private function obterChaveLimitacao(): string
    {
        $email = $this->input(
            'email',
            '',
        );

        $emailNormalizado = is_string($email)
            ? mb_strtolower(trim($email))
            : '';

        $enderecoIp = $this->ip()
            ?? 'desconhecido';

        return 'autenticacao:'.hash(
            'sha256',
            $emailNormalizado.'|'.$enderecoIp,
        );
    }
}
