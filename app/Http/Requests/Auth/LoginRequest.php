<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Gere os pedidos de autenticação.
 *
 * @since 1.0
 * @version 1.0
 */
class LoginRequest extends FormRequest
{
    /**
     * Determina se o utilizador é autorizado a executar o pedido.
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
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Obtém as mensagens de erro personalizadas para as regras de validação.
     *
     * @return array - Mensagens de erro personalizadas.
     *
     * @since 1.0
     * @version 1.0
     */
    public function messages(): array
    {
        return [
            'email.required'    => 'Por favor, insere o teu e-mail.',
            'email.string'      => 'O e-mail deve ser uma sequência de caracteres.',
            'email.email'       => 'Por favor, insere um e-mail válido.',
            'password.required' => 'Por favor, insere a palavra-passe.',
            'password.string'   => 'A palavra-passe deve ser uma sequência de caracteres.',
        ];
    }

    /**
     * Autentica o utilizador.
     *
     * @return void
     * @throws ValidationException - Exceção de validação.
     *
     * @since 1.0
     * @version 1.0
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (!Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Garante que o pedido de autenticação não está sujeito a limites de taxa.
     *
     * @return void
     * @throws ValidationException - Exceção de validação
     *
     * @since 1.0
     * @version 1.0
     */
    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Obtém a chave de limitação de taxa para o pedido.
     *
     * @return string - Chave de limitação de taxa.
     *
     * @since 1.0
     * @version 1.0
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')) . '|' . $this->ip());
    }
}
