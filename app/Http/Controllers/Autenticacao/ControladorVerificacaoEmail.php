<?php

declare(strict_types=1);

namespace App\Http\Controllers\Autenticacao;

use App\Http\Controllers\Controller;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use LogicException;
use RuntimeException;
use SensitiveParameter;

/**
 * Gere a verificação do endereço de e-mail de um utilizador.
 *
 * A ligação de verificação é pública e assinada, permitindo confirmar o
 * endereço antes de o utilizador poder iniciar uma sessão autenticada.
 *
 * A atualização é executada numa transação com bloqueio pessimista, garantindo
 * que pedidos simultâneos não emitem repetidamente o evento de verificação.
 *
 * @since 1.0.0
 */
final class ControladorVerificacaoEmail extends Controller
{
    /**
     * Resultado utilizado quando a ligação não é válida.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const RESULTADO_INVALIDO =
        'invalido';

    /**
     * Resultado utilizado quando o e-mail já estava verificado.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const RESULTADO_JA_VERIFICADO =
        'ja_verificado';

    /**
     * Resultado utilizado quando o e-mail é verificado.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const RESULTADO_VERIFICADO =
        'verificado';

    /**
     * Padrão hexadecimal do hash SHA-1 utilizado pelo Laravel nas ligações de
     * verificação.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const PADRAO_HASH_VERIFICACAO =
        '/\A[0-9a-f]{40}\z/';

    /**
     * Mensagem apresentada quando a ligação não pode ser utilizada.
     *
     * A mensagem não distingue assinaturas inválidas, utilizadores
     * inexistentes, identificadores incorretos ou endereços alterados.
     *
     * @var string
     *
     * @since 2.0.0
     */
    private const MENSAGEM_LIGACAO_INVALIDA =
        'A ligação de verificação é inválida ou expirou.';

    /**
     * Verifica o endereço de e-mail indicado pela ligação assinada.
     *
     * Os parâmetros `id` e `hash` mantêm estas designações porque pertencem
     * ao contrato técnico utilizado pelo sistema de verificação de e-mail do
     * Laravel.
     *
     * @param  Request  $pedido  Pedido HTTP.
     * @param  string  $id  Identificador recebido na ligação.
     * @param  string  $hash  Hash recebido na ligação.
     * @return RedirectResponse Redirecionamento para a autenticação.
     *
     * @throws LogicException Quando a operação produz um resultado interno
     *                        desconhecido.
     * @throws RuntimeException Quando a verificação não pode ser persistida.
     *
     * @since 1.0.0
     */
    public function __invoke(
        Request $pedido,
        string $id,
        #[SensitiveParameter]
        string $hash,
    ): RedirectResponse {
        if (
            ! $pedido->hasValidSignature()
            || ! $this->hashTemFormatoValido(
                $hash,
            )
        ) {
            return $this->redirecionarLigacaoInvalida();
        }

        $identificadorUtilizador =
            $this->converterParaIdentificador(
                $id,
            );

        if ($identificadorUtilizador === null) {
            return $this->redirecionarLigacaoInvalida();
        }

        $resultado =
            DB::transaction(
                function () use (
                    $identificadorUtilizador,
                    $hash,
                ): string {
                    $utilizador =
                        Utilizador::query()
                            ->lockForUpdate()
                            ->find(
                                $identificadorUtilizador,
                            );

                    if (
                        ! $utilizador instanceof Utilizador
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
                            'Não foi possível guardar a verificação do endereço de e-mail.',
                        );
                    }

                    DB::afterCommit(
                        static function () use ($utilizador): void {
                            event(
                                new Verified(
                                    $utilizador,
                                ),
                            );
                        },
                    );

                    return self::RESULTADO_VERIFICADO;
                },
            );

        return match ($resultado) {
            self::RESULTADO_INVALIDO => $this->redirecionarLigacaoInvalida(),

            self::RESULTADO_JA_VERIFICADO => to_route(
                'login',
            )->with(
                'informacao',
                'O teu e-mail já estava verificado. Podes iniciar sessão.',
            ),

            self::RESULTADO_VERIFICADO => to_route(
                'login',
            )->with(
                'sucesso',
                'E-mail verificado com sucesso. Já podes iniciar sessão.',
            ),

            default => throw new LogicException(
                'A verificação do endereço de e-mail produziu um resultado desconhecido.',
            ),
        };
    }

    /**
     * Confirma que o hash recebido possui o formato esperado.
     *
     * Esta validação complementa a assinatura da ligação e impede que valores
     * com um formato inesperado prossigam para a consulta do utilizador.
     *
     * @param  string  $hash  Hash recebido.
     * @return bool Verdadeiro quando o formato é válido.
     *
     * @since 2.0.0
     */
    private function hashTemFormatoValido(
        #[SensitiveParameter]
        string $hash,
    ): bool {
        return preg_match(
            self::PADRAO_HASH_VERIFICACAO,
            $hash,
        ) === 1;
    }

    /**
     * Confirma que o hash recebido pertence ao utilizador.
     *
     * A comparação utiliza tempo constante para evitar diferenças temporais
     * dependentes do conteúdo do hash.
     *
     * @param  Utilizador  $utilizador  Utilizador encontrado.
     * @param  string  $hash  Hash recebido.
     * @return bool Verdadeiro quando o hash corresponde.
     *
     * @since 2.0.0
     */
    private function hashCorresponde(
        Utilizador $utilizador,
        #[SensitiveParameter]
        string $hash,
    ): bool {
        $hashEsperado =
            sha1(
                (string) $utilizador->getEmailForVerification(),
            );

        return hash_equals(
            $hashEsperado,
            $hash,
        );
    }

    /**
     * Converte o identificador recebido num inteiro positivo.
     *
     * São aceites apenas representações decimais sem sinal ou espaços. O
     * valor também tem de caber no intervalo suportado por um inteiro PHP.
     *
     * @param  string  $valor  Valor recebido.
     * @return int|null Identificador válido ou nulo.
     *
     * @since 2.0.0
     */
    private function converterParaIdentificador(
        string $valor,
    ): ?int {
        if (
            $valor === ''
            || ! ctype_digit(
                $valor,
            )
        ) {
            return null;
        }

        $identificador =
            filter_var(
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
            : $identificador;
    }

    /**
     * Redireciona uma ligação inválida para a autenticação.
     *
     * @return RedirectResponse Redirecionamento com mensagem de erro.
     *
     * @since 2.0.0
     */
    private function redirecionarLigacaoInvalida(): RedirectResponse
    {
        return to_route(
            'login',
        )->withErrors([
            'email' => self::MENSAGEM_LIGACAO_INVALIDA,
        ]);
    }
}
