<?php

declare(strict_types=1);

namespace App\Http\Controllers\Autenticacao;

use App\Http\Controllers\Controller;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SensitiveParameter;

/**
 * Gere a verificação do endereço de e-mail de um utilizador.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class ControladorVerificacaoEmail extends Controller
{
    /**
     * Resultado utilizado quando a ligação não é válida.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const RESULTADO_INVALIDO = 'invalido';

    /**
     * Resultado utilizado quando o e-mail já estava verificado.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const RESULTADO_JA_VERIFICADO =
        'ja_verificado';

    /**
     * Resultado utilizado quando o e-mail é verificado.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const RESULTADO_VERIFICADO = 'verificado';

    /**
     * Verifica o endereço de e-mail indicado pela ligação assinada.
     *
     * Os parâmetros `id` e `hash` mantêm estes nomes porque fazem parte do
     * contrato utilizado pelo sistema de verificação de e-mail do Laravel.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  string  $id  Identificador recebido na ligação.
     * @param  string  $hash  Hash recebido na ligação.
     * @return RedirectResponse Redirecionamento para a autenticação.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function __invoke(
        Request $pedido,
        string $id,
        #[SensitiveParameter]
        string $hash,
    ): RedirectResponse {
        if (! $pedido->hasValidSignature()) {
            return $this->redirecionarLigacaoInvalida();
        }

        $identificadorUtilizador =
            $this->converterParaIdentificador(
                $id,
            );

        if ($identificadorUtilizador === null) {
            return $this->redirecionarLigacaoInvalida();
        }

        $resultado = DB::transaction(
            function () use (
                $identificadorUtilizador,
                $hash,
            ): string {
                $utilizador = Utilizador::query()
                    ->lockForUpdate()
                    ->find(
                        $identificadorUtilizador,
                    );

                if (
                    $utilizador === null
                    || ! $this->hashCorresponde(
                        $utilizador,
                        $hash,
                    )
                ) {
                    return self::RESULTADO_INVALIDO;
                }

                if ($utilizador->hasVerifiedEmail()) {
                    return self::RESULTADO_JA_VERIFICADO;
                }

                if (! $utilizador->markEmailAsVerified()) {
                    throw new RuntimeException(
                        'Não foi possível guardar a verificação do e-mail.',
                    );
                }

                DB::afterCommit(
                    static fn () => event(
                        new Verified(
                            $utilizador,
                        ),
                    ),
                );

                return self::RESULTADO_VERIFICADO;
            },
        );

        return match ($resultado) {
            self::RESULTADO_JA_VERIFICADO => redirect()
                ->route('login')
                ->with(
                    'estado',
                    'O teu e-mail já estava verificado. Podes iniciar sessão.',
                ),

            self::RESULTADO_VERIFICADO => redirect()
                ->route('login')
                ->with(
                    'estado',
                    'E-mail verificado com sucesso. Já podes iniciar sessão.',
                ),

            default => $this->redirecionarLigacaoInvalida(),
        };
    }

    /**
     * Confirma que o hash recebido pertence ao utilizador.
     *
     * @param  Utilizador  $utilizador  Utilizador encontrado.
     * @param  string  $hash  Hash recebido.
     * @return bool Verdadeiro quando o hash corresponde.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function hashCorresponde(
        Utilizador $utilizador,
        #[SensitiveParameter]
        string $hash,
    ): bool {
        $hashEsperado = sha1(
            (string) $utilizador
                ->getEmailForVerification(),
        );

        return hash_equals(
            $hashEsperado,
            $hash,
        );
    }

    /**
     * Converte o identificador recebido num inteiro positivo.
     *
     * @param  string  $valor  Valor recebido.
     * @return int|null Identificador válido ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function converterParaIdentificador(
        string $valor,
    ): ?int {
        $identificador = filter_var(
            $valor,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ],
        );

        return $identificador === false
            ? null
            : (int) $identificador;
    }

    /**
     * Redireciona uma ligação inválida para a autenticação.
     *
     * @return RedirectResponse Redirecionamento com mensagem de erro.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function redirecionarLigacaoInvalida(): RedirectResponse
    {
        return redirect()
            ->route('login')
            ->withErrors([
                'email' => 'A ligação de verificação é inválida ou expirou.',
            ]);
    }
}
