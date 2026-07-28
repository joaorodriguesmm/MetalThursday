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
 * @version 1.2.0
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
     * Cria o serviço.
     *
     * @param  ServicoFotografiasUtilizador  $servicoFotografias  Serviço
     *                                                            responsável
     *                                                            pelas
     *                                                            fotografias.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function __construct(
        private readonly ServicoFotografiasUtilizador $servicoFotografias,
    ) {}

    /**
     * Atualiza os dados gerais do perfil.
     *
     * A fotografia nova é guardada antes da transação para evitar manter um
     * bloqueio da base de dados durante uma operação de armazenamento.
     *
     * Quando a transação falha, a fotografia nova é eliminada. Quando a
     * transação termina com sucesso, a fotografia anterior é eliminada.
     *
     * @param  Utilizador  $utilizador  Utilizador autenticado.
     * @param  string  $nome  Novo nome.
     * @param  string  $email  Novo endereço de e-mail.
     * @param  UploadedFile|null  $fotografia  Nova fotografia opcional.
     * @return PerfilAtualizado Resultado da atualização.
     *
     * @throws DomainException Quando o endereço de e-mail já está em uso.
     * @throws InvalidArgumentException Quando o utilizador ou os dados não são
     *                                  válidos.
     * @throws ModelNotFoundException Quando o utilizador deixou de existir.
     * @throws Throwable Quando ocorre outro erro.
     *
     * @since 2.0.0
     *
     * @version 1.2.0
     */
    public function atualizar(
        Utilizador $utilizador,
        string $nome,
        string $email,
        ?UploadedFile $fotografia = null,
    ): PerfilAtualizado {
        $identificadorUtilizador =
            $this->obterIdentificadorUtilizador(
                $utilizador,
            );

        $nomeUtilizador =
            NomeUtilizador::deTexto(
                $nome,
            );

        $enderecoEmail =
            EnderecoEmail::deTexto(
                $email,
            );

        $caminhoFotografiaNova =
            $fotografia instanceof UploadedFile
            ? $this
                ->servicoFotografias
                ->guardar(
                    $fotografia,
                )
            : null;

        $caminhoFotografiaAnterior =
            null;

        try {
            $resultado =
                DB::transaction(
                    function () use (
                        $identificadorUtilizador,
                        $nomeUtilizador,
                        $enderecoEmail,
                        $caminhoFotografiaNova,
                        &$caminhoFotografiaAnterior,
                    ): PerfilAtualizado {
                        $utilizadorBloqueado =
                            Utilizador::query()
                                ->whereKey(
                                    $identificadorUtilizador,
                                )
                                ->lockForUpdate()
                                ->firstOrFail();

                        $caminhoFotografiaAnterior =
                            $this->normalizarCaminhoPersistido(
                                $utilizadorBloqueado->fotografia,
                            );

                        $emailAlterado =
                            $this->emailFoiAlterado(
                                $utilizadorBloqueado,
                                $enderecoEmail,
                            );

                        if ($emailAlterado) {
                            $this->garantirEmailDisponivel(
                                $enderecoEmail,
                                $identificadorUtilizador,
                            );
                        }

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
        } catch (UniqueConstraintViolationException $excecao) {
            $this->eliminarFotografiaNovaAposFalha(
                $caminhoFotografiaNova,
                $excecao,
            );

            if (
                $this->emailPertenceAOutroUtilizador(
                    $enderecoEmail,
                    $identificadorUtilizador,
                )
            ) {
                throw new DomainException(
                    'O endereço de e-mail já está associado a outro utilizador.',
                    previous: $excecao,
                );
            }

            throw $excecao;
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
            && $caminhoFotografiaAnterior !== $caminhoFotografiaNova
        ) {
            $this->eliminarFotografiaAnterior(
                $caminhoFotografiaAnterior,
            );
        }

        return $resultado;
    }

    /**
     * Obtém o identificador de um utilizador persistido.
     *
     * @param  Utilizador  $utilizador  Utilizador recebido.
     * @return int Identificador do utilizador.
     *
     * @throws InvalidArgumentException Quando o utilizador não está
     *                                  persistido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterIdentificadorUtilizador(
        Utilizador $utilizador,
    ): int {
        $identificador =
            $utilizador->getKey();

        if (
            ! $utilizador->exists
            || ! is_numeric($identificador)
            || (int) $identificador < 1
        ) {
            throw new InvalidArgumentException(
                'O utilizador deve estar persistido para atualizar o perfil.',
            );
        }

        return (int) $identificador;
    }

    /**
     * Determina se o endereço de e-mail foi alterado.
     *
     * @param  Utilizador  $utilizador  Utilizador bloqueado.
     * @param  EnderecoEmail  $novoEmail  Novo endereço normalizado.
     * @return bool Verdadeiro quando o endereço foi alterado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function emailFoiAlterado(
        Utilizador $utilizador,
        EnderecoEmail $novoEmail,
    ): bool {
        $emailAtual =
            $utilizador->email;

        if (
            ! is_string($emailAtual)
            || trim($emailAtual) === ''
        ) {
            return true;
        }

        return ! EnderecoEmail::deTexto(
            $emailAtual,
        )->igualA(
            $novoEmail,
        );
    }

    /**
     * Confirma que o endereço não pertence a outro utilizador.
     *
     * Esta validação melhora a mensagem apresentada, mas a restrição única da
     * base de dados continua a ser a proteção definitiva contra concorrência.
     *
     * @param  EnderecoEmail  $email  Endereço pretendido.
     * @param  int  $identificadorUtilizador  Utilizador atualmente editado.
     *
     * @throws DomainException Quando o endereço já está em utilização.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function garantirEmailDisponivel(
        EnderecoEmail $email,
        int $identificadorUtilizador,
    ): void {
        if (
            ! $this->emailPertenceAOutroUtilizador(
                $email,
                $identificadorUtilizador,
            )
        ) {
            return;
        }

        throw new DomainException(
            'O endereço de e-mail já está associado a outro utilizador.',
        );
    }

    /**
     * Determina se o endereço pertence a outro utilizador.
     *
     * @param  EnderecoEmail  $email  Endereço pesquisado.
     * @param  int  $identificadorUtilizador  Utilizador atualmente editado.
     * @return bool Verdadeiro quando existe outro utilizador com o endereço.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function emailPertenceAOutroUtilizador(
        EnderecoEmail $email,
        int $identificadorUtilizador,
    ): bool {
        return Utilizador::query()
            ->where(
                'email',
                $email->valor(),
            )
            ->where(
                'id',
                '!=',
                $identificadorUtilizador,
            )
            ->exists();
    }

    /**
     * Normaliza um caminho de fotografia persistido.
     *
     * @param  mixed  $caminho  Valor persistido.
     * @return string|null Caminho normalizado ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarCaminhoPersistido(
        mixed $caminho,
    ): ?string {
        if (! is_string($caminho)) {
            return null;
        }

        $caminhoNormalizado =
            trim(
                $caminho,
            );

        return $caminhoNormalizado !== ''
            ? $caminhoNormalizado
            : null;
    }

    /**
     * Elimina a fotografia nova quando a atualização falha.
     *
     * A exceção original nunca é substituída por uma eventual falha da
     * operação compensatória.
     *
     * @param  string|null  $caminho  Caminho da fotografia nova.
     * @param  Throwable  $excecaoOriginal  Erro que provocou a compensação.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function eliminarFotografiaNovaAposFalha(
        ?string $caminho,
        Throwable $excecaoOriginal,
    ): void {
        if ($caminho === null) {
            return;
        }

        try {
            $this
                ->servicoFotografias
                ->eliminar(
                    $caminho,
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
     * @param  string  $caminho  Caminho da fotografia anterior.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function eliminarFotografiaAnterior(
        string $caminho,
    ): void {
        try {
            $this
                ->servicoFotografias
                ->eliminar(
                    $caminho,
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
