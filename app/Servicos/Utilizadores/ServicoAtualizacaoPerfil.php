<?php

declare(strict_types=1);

namespace App\Servicos\Utilizadores;

use App\Models\Autenticacao\Utilizador;
use App\ObjetosValor\Utilizadores\EnderecoEmail;
use App\ObjetosValor\Utilizadores\NomeUtilizador;
use App\Resultados\Utilizadores\PerfilAtualizado;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

/**
 * Gere a atualização dos dados gerais do perfil.
 *
 * A atualização da base de dados é transacional. O armazenamento da
 * fotografia é coordenado com a transação através de operações compensatórias,
 * uma vez que o sistema de ficheiros não participa nas transações SQL.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class ServicoAtualizacaoPerfil
{
    /**
     * Número máximo de tentativas perante conflitos transitórios.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TENTATIVAS_TRANSACAO = 3;

    /**
     * Serviço das fotografias.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private readonly ServicoFotografiasUtilizador $servicoFotografias;

    /**
     * Cria o serviço.
     *
     * @param  ServicoFotografiasUtilizador  $servicoFotografias  - Serviço das
     *                                                            fotografias.
     * @return void
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function __construct(
        ServicoFotografiasUtilizador $servicoFotografias,
    ) {
        $this->servicoFotografias = $servicoFotografias;
    }

    /**
     * Atualiza os dados gerais do perfil.
     *
     * A fotografia nova é guardada antes da transação para evitar manter um
     * bloqueio da base de dados durante uma operação de armazenamento.
     *
     * Quando a transação falha, a fotografia nova é eliminada. Quando a
     * transação termina com sucesso, a fotografia anterior é eliminada.
     *
     * @param  Utilizador  $utilizador  - Utilizador autenticado.
     * @param  string  $nome  - Novo nome.
     * @param  string  $email  - Novo endereço de e-mail.
     * @param  UploadedFile|null  $fotografia  - Nova fotografia opcional.
     * @return PerfilAtualizado - Resultado da atualização.
     *
     * @throws DomainException Quando o endereço de e-mail já está em uso.
     * @throws InvalidArgumentException Quando o utilizador ou os dados são
     *                                  inválidos.
     * @throws ModelNotFoundException Quando o utilizador deixou de existir.
     * @throws Throwable Quando ocorre outro erro.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function atualizar(
        Utilizador $utilizador,
        string $nome,
        string $email,
        ?UploadedFile $fotografia = null,
    ): PerfilAtualizado {
        if (
            ! $utilizador->exists
            || $utilizador->getKey() === null
        ) {
            throw new InvalidArgumentException(
                'O utilizador deve estar persistido para atualizar o perfil.',
            );
        }

        $nomeUtilizador = NomeUtilizador::deTexto($nome);
        $enderecoEmail = EnderecoEmail::deTexto($email);

        $caminhoFotografiaNova = $fotografia !== null
            ? $this->servicoFotografias->guardar($fotografia)
            : null;

        $caminhoFotografiaAnterior = null;

        try {
            $resultado = DB::transaction(
                function () use (
                    $utilizador,
                    $nomeUtilizador,
                    $enderecoEmail,
                    $caminhoFotografiaNova,
                    &$caminhoFotografiaAnterior,
                ): PerfilAtualizado {
                    $utilizadorBloqueado = Utilizador::query()
                        ->whereKey($utilizador->getKey())
                        ->lockForUpdate()
                        ->firstOrFail();

                    $caminhoFotografiaAnterior =
                        $utilizadorBloqueado->fotografia;

                    $emailAtual = $utilizadorBloqueado->email;

                    $emailAlterado = $emailAtual === null
                        || ! EnderecoEmail::deTexto(
                            $emailAtual,
                        )->igualA($enderecoEmail);

                    $utilizadorBloqueado->nome =
                        $nomeUtilizador->valor();

                    $utilizadorBloqueado->email =
                        $enderecoEmail->valor();

                    if ($caminhoFotografiaNova !== null) {
                        $utilizadorBloqueado->fotografia =
                            $caminhoFotografiaNova;
                    }

                    if ($emailAlterado) {
                        $utilizadorBloqueado->email_verified_at =
                            null;
                    }

                    $utilizadorBloqueado->saveOrFail();

                    return new PerfilAtualizado(
                        utilizador: $utilizadorBloqueado,
                        emailAlterado: $emailAlterado,
                    );
                },
                self::TENTATIVAS_TRANSACAO,
            );
        } catch (
            UniqueConstraintViolationException $excecao
        ) {
            $this->eliminarFotografiaNovaAposFalha(
                $caminhoFotografiaNova,
                $excecao,
            );

            throw new DomainException(
                'O endereço de e-mail já está associado a outro utilizador.',
                previous: $excecao,
            );
        } catch (Throwable $excecao) {
            $this->eliminarFotografiaNovaAposFalha(
                $caminhoFotografiaNova,
                $excecao,
            );

            throw $excecao;
        }

        if (
            $caminhoFotografiaNova !== null
            && $caminhoFotografiaAnterior !== null
            && $caminhoFotografiaAnterior
            !== $caminhoFotografiaNova
        ) {
            $this->eliminarFotografiaAnterior(
                $caminhoFotografiaAnterior,
            );
        }

        return $resultado;
    }

    /**
     * Elimina a fotografia nova quando a atualização falha.
     *
     * A exceção original nunca é substituída por uma eventual falha da
     * operação compensatória.
     *
     * @param  string|null  $caminho  - Caminho da fotografia nova.
     * @param  Throwable  $excecaoOriginal  - Erro que provocou a compensação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function eliminarFotografiaNovaAposFalha(
        ?string $caminho,
        Throwable $excecaoOriginal,
    ): void {
        if ($caminho === null) {
            return;
        }

        try {
            $eliminada = $this->servicoFotografias
                ->eliminar($caminho);

            if ($eliminada) {
                return;
            }

            Log::error(
                'Não foi possível eliminar a fotografia nova após uma falha na atualização do perfil.',
                [
                    'caminho' => $caminho,
                    'excecao_original' => $excecaoOriginal::class,
                ],
            );
        } catch (Throwable $excecaoLimpeza) {
            Log::error(
                'Ocorreu um erro ao eliminar a fotografia nova após uma falha na atualização do perfil.',
                [
                    'caminho' => $caminho,
                    'excecao_original' => $excecaoOriginal::class,

                    'excecao_limpeza' => $excecaoLimpeza::class,

                    'mensagem_limpeza' => $excecaoLimpeza->getMessage(),
                ],
            );
        }
    }

    /**
     * Elimina a fotografia anterior depois da atualização.
     *
     * Uma falha nesta limpeza é registada, mas não invalida a atualização já
     * confirmada na base de dados.
     *
     * @param  string  $caminho  - Caminho da fotografia anterior.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function eliminarFotografiaAnterior(
        string $caminho,
    ): void {
        try {
            $eliminada = $this->servicoFotografias
                ->eliminar($caminho);

            if ($eliminada) {
                return;
            }

            Log::warning(
                'Não foi possível eliminar a fotografia anterior do utilizador.',
                [
                    'caminho' => $caminho,
                ],
            );
        } catch (Throwable $excecao) {
            Log::warning(
                'Ocorreu um erro ao eliminar a fotografia anterior do utilizador.',
                [
                    'caminho' => $caminho,
                    'excecao' => $excecao::class,
                    'mensagem' => $excecao->getMessage(),
                ],
            );
        }
    }
}
