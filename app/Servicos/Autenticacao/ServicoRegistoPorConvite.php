<?php

declare(strict_types=1);

namespace App\Servicos\Autenticacao;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Convite;
use App\Models\Autenticacao\Utilizador;
use App\ObjetosValor\Utilizadores\EnderecoEmail;
use App\ObjetosValor\Utilizadores\NomeUtilizador;
use App\Regras\Autenticacao\RequisitosPalavraPasse;
use App\Servicos\Comunicacoes\ServicoPermissoesEmail;
use App\Servicos\Utilizadores\ServicoFotografiasUtilizador;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use SensitiveParameter;
use Throwable;

/**
 * Gere o registo de utilizadores através de convites.
 *
 * O serviço coordena a validação dos dados, o armazenamento da fotografia, a
 * criação transacional do utilizador, a atribuição das permissões de e-mail e
 * a utilização definitiva do convite.
 *
 * Como o sistema de ficheiros não participa na transação SQL, uma fotografia
 * guardada antes de uma falha é eliminada através de uma operação
 * compensatória.
 *
 * @since 2.0.0
 *
 * @version 3.0.0
 */
final class ServicoRegistoPorConvite
{
    /**
     * Número máximo de tentativas perante conflitos transitórios.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TENTATIVAS_TRANSACAO = 3;

    /**
     * Cria o serviço.
     *
     * @param  ServicoPermissoesEmail  $servicoPermissoesEmail  Serviço
     *                                                          responsável
     *                                                          pelas
     *                                                          permissões de
     *                                                          e-mail.
     * @param  ServicoFotografiasUtilizador  $servicoFotografiasUtilizador
     *                                                                      Serviço
     *                                                                      responsável
     *                                                                      pelas
     *                                                                      fotografias.
     *
     * @since 2.0.0
     *
     * @version 3.0.0
     */
    public function __construct(
        private readonly ServicoPermissoesEmail $servicoPermissoesEmail,
        private readonly ServicoFotografiasUtilizador $servicoFotografiasUtilizador,
    ) {}

    /**
     * Regista um utilizador através de um convite disponível.
     *
     * Todos os dados textuais e a palavra-passe são validados antes do
     * armazenamento da fotografia. O código original nunca é persistido nem
     * enviado para a transação; apenas o respetivo hash é utilizado para
     * bloquear e localizar o convite.
     *
     * A fotografia é armazenada antes da abertura da transação para evitar
     * manter bloqueios SQL durante uma operação no sistema de ficheiros.
     *
     * Quando o registo falha, a fotografia nova é eliminada sem substituir a
     * exceção que provocou a falha.
     *
     * @param  string  $codigoConvite  Código original do convite.
     * @param  string  $nome  Nome do novo utilizador.
     * @param  string  $email  Endereço de e-mail.
     * @param  string  $palavraPasse  Palavra-passe em texto simples.
     * @param  UploadedFile|null  $fotografia  Fotografia opcional.
     * @param  array<int, int|string>  $identificadoresPermissoesEmail
     *                                                                  Identificadores das permissões
     *                                                                  selecionadas.
     * @return Utilizador Utilizador criado.
     *
     * @throws DomainException Quando o convite não está disponível, o
     *                         endereço não corresponde ao convite ou o e-mail
     *                         já pertence a outro utilizador.
     * @throws InvalidArgumentException Quando algum dado não é válido.
     * @throws Throwable Quando o armazenamento ou o registo falham.
     *
     * @since 2.0.0
     *
     * @version 3.0.0
     */
    public function registar(
        #[SensitiveParameter]
        string $codigoConvite,
        string $nome,
        string $email,
        #[SensitiveParameter]
        string $palavraPasse,
        ?UploadedFile $fotografia = null,
        array $identificadoresPermissoesEmail = [],
    ): Utilizador {
        $codigoHash = Convite::calcularHashCodigo(
            $codigoConvite,
        );

        $nomeUtilizador = NomeUtilizador::deTexto(
            $nome,
        );

        $enderecoEmail = EnderecoEmail::deTexto(
            $email,
        );

        RequisitosPalavraPasse::validar(
            $palavraPasse,
        );

        $caminhoFotografia = $fotografia !== null
            ? $this
                ->servicoFotografiasUtilizador
                ->guardar(
                    $fotografia,
                )
            : null;

        try {
            return $this->registarTransacionalmente(
                codigoHash: $codigoHash,
                nome: $nomeUtilizador,
                email: $enderecoEmail,
                palavraPasse: $palavraPasse,
                caminhoFotografia: $caminhoFotografia,
                identificadoresPermissoesEmail: $identificadoresPermissoesEmail,
            );
        } catch (
            UniqueConstraintViolationException $excecao
        ) {
            $this->eliminarFotografiaAposFalha(
                $caminhoFotografia,
                $excecao,
            );

            $this->relancarConflitoRestricaoUnica(
                $excecao,
                $enderecoEmail,
            );
        } catch (Throwable $excecao) {
            $this->eliminarFotografiaAposFalha(
                $caminhoFotografia,
                $excecao,
            );

            throw $excecao;
        }
    }

    /**
     * Executa o registo transacional do utilizador.
     *
     * O convite é bloqueado antes da validação do respetivo estado para
     * impedir utilizações simultâneas. A criação do utilizador, a
     * sincronização das permissões e a utilização do convite pertencem à
     * mesma transação.
     *
     * @param  string  $codigoHash  Hash SHA-256 do código.
     * @param  NomeUtilizador  $nome  Nome validado.
     * @param  EnderecoEmail  $email  Endereço validado.
     * @param  string  $palavraPasse  Palavra-passe em texto simples.
     * @param  string|null  $caminhoFotografia  Caminho da fotografia.
     * @param  array<int, int|string>  $identificadoresPermissoesEmail
     *                                                                  Identificadores das permissões.
     * @return Utilizador Utilizador criado.
     *
     * @throws DomainException Quando o convite não está disponível ou o
     *                         endereço não corresponde ao destinatário.
     * @throws Throwable Quando a transação não pode ser concluída.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function registarTransacionalmente(
        string $codigoHash,
        NomeUtilizador $nome,
        EnderecoEmail $email,
        #[SensitiveParameter]
        string $palavraPasse,
        ?string $caminhoFotografia,
        array $identificadoresPermissoesEmail,
    ): Utilizador {
        return DB::transaction(
            function () use (
                $codigoHash,
                $nome,
                $email,
                $palavraPasse,
                $caminhoFotografia,
                $identificadoresPermissoesEmail,
            ): Utilizador {
                $momentoUtilizacao =
                    CarbonImmutable::now();

                $convite = Convite::query()
                    ->where(
                        'codigo_hash',
                        $codigoHash,
                    )
                    ->lockForUpdate()
                    ->first();

                if (
                    ! $convite instanceof Convite
                    || ! $convite->estaDisponivel(
                        $momentoUtilizacao,
                    )
                ) {
                    throw new DomainException(
                        'O convite não está disponível para utilização.',
                    );
                }

                $this->validarEmailDestino(
                    $convite,
                    $email,
                );

                $utilizador = new Utilizador;

                $utilizador->nome =
                    $nome->valor();

                $utilizador->email =
                    $email->valor();

                /*
                 * O cast `hashed` do modelo aplica o hash antes da
                 * persistência.
                 */
                $utilizador->password =
                    $palavraPasse;

                /*
                 * O mutator do modelo valida o caminho relativo.
                 */
                $utilizador->fotografia =
                    $caminhoFotografia;

                /*
                 * O papel é atribuído explicitamente porque não pertence a
                 * `$fillable` e nunca deve ser controlado por dados externos.
                 */
                $utilizador->papel =
                    PapelUtilizador::Utilizador;

                $utilizador->saveOrFail();

                $this
                    ->servicoPermissoesEmail
                    ->sincronizar(
                        $utilizador,
                        $identificadoresPermissoesEmail,
                    );

                $convite->utilizar(
                    $utilizador,
                    $momentoUtilizacao,
                );

                $convite->saveOrFail();

                return $utilizador;
            },
            self::TENTATIVAS_TRANSACAO,
        );
    }

    /**
     * Confirma que o endereço corresponde ao destinatário do convite.
     *
     * Um convite sem endereço de destino pode ser utilizado com qualquer
     * endereço válido. Quando existe um destinatário, a comparação utiliza os
     * objetos de valor normalizados.
     *
     * @param  Convite  $convite  Convite bloqueado.
     * @param  EnderecoEmail  $email  Endereço do novo utilizador.
     *
     * @throws DomainException Quando os endereços não coincidem.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function validarEmailDestino(
        Convite $convite,
        EnderecoEmail $email,
    ): void {
        $emailDestinoPersistido =
            $convite->email_destino;

        if (
            ! is_string($emailDestinoPersistido)
            || trim($emailDestinoPersistido) === ''
        ) {
            return;
        }

        $emailDestino = EnderecoEmail::deTexto(
            $emailDestinoPersistido,
        );

        if ($emailDestino->igualA($email)) {
            return;
        }

        throw new DomainException(
            'O endereço de e-mail não corresponde ao destinatário do convite.',
        );
    }

    /**
     * Relança uma violação de restrição única com uma exceção de domínio
     * quando o conflito pertence ao endereço de e-mail.
     *
     * A consulta é executada depois do rollback da transação. Quando o
     * endereço não pertence a outro utilizador, a exceção original é
     * preservada para não esconder outro conflito de integridade.
     *
     * @param  UniqueConstraintViolationException  $excecao  Exceção original.
     * @param  EnderecoEmail  $email  Endereço utilizado.
     *
     * @throws DomainException Quando o endereço já existe.
     * @throws UniqueConstraintViolationException Quando a restrição violada
     *                                            não pertence ao endereço.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function relancarConflitoRestricaoUnica(
        UniqueConstraintViolationException $excecao,
        EnderecoEmail $email,
    ): never {
        if (
            Utilizador::query()
                ->where(
                    'email',
                    $email->valor(),
                )
                ->exists()
        ) {
            throw new DomainException(
                'O endereço de e-mail já está associado a outro utilizador.',
                previous: $excecao,
            );
        }

        throw $excecao;
    }

    /**
     * Elimina a fotografia armazenada quando o registo falha.
     *
     * Uma falha na operação compensatória é registada, mas nunca substitui a
     * exceção original do registo.
     *
     * @param  string|null  $caminhoFotografia  Caminho da fotografia.
     * @param  Throwable  $excecaoOriginal  Erro que provocou a compensação.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function eliminarFotografiaAposFalha(
        ?string $caminhoFotografia,
        Throwable $excecaoOriginal,
    ): void {
        if ($caminhoFotografia === null) {
            return;
        }

        try {
            $this
                ->servicoFotografiasUtilizador
                ->eliminar(
                    $caminhoFotografia,
                );
        } catch (Throwable $excecaoLimpeza) {
            Log::error(
                'Ocorreu um erro ao eliminar a fotografia após uma falha no registo por convite.',
                [
                    'caminho' => $caminhoFotografia,

                    'excecao_original' => $excecaoOriginal::class,

                    'excecao_limpeza' => $excecaoLimpeza::class,

                    'mensagem_limpeza' => $excecaoLimpeza->getMessage(),
                ],
            );
        }
    }
}
