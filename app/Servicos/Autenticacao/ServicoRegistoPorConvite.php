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
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use SensitiveParameter;
use Throwable;

/**
 * Gere o registo de utilizadores através de convites.
 *
 * O serviço coordena o armazenamento da fotografia, a criação transacional
 * do utilizador, a atribuição das permissões de e-mail e a utilização do
 * convite.
 *
 * @since 2.0.0
 *
 * @version 2.1.0
 */
final class ServicoRegistoPorConvite
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
     * Cria uma nova instância do serviço.
     *
     * @param  ServicoPermissoesEmail  $servicoPermissoesEmail  Serviço
     *                                                          responsável pelas permissões de e-mail.
     * @param  ServicoFotografiasUtilizador  $servicoFotografiasUtilizador
     *                                                                      Serviço responsável pelas fotografias.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function __construct(
        private readonly ServicoPermissoesEmail $servicoPermissoesEmail,
        private readonly ServicoFotografiasUtilizador $servicoFotografiasUtilizador,
    ) {}

    /**
     * Regista um utilizador através de um convite disponível.
     *
     * A fotografia é armazenada antes da abertura da transação. Caso o
     * registo transacional falhe, a fotografia armazenada é eliminada sem
     * substituir a exceção que causou a falha.
     *
     * @param  string  $codigoConvite  Código original do convite.
     * @param  string  $nome  Nome do novo utilizador.
     * @param  string  $email  Endereço de e-mail.
     * @param  string  $palavraPasse  Palavra-passe em texto simples.
     * @param  UploadedFile|null  $fotografia  Fotografia enviada.
     * @param  array<int, int|string>  $identificadoresPermissoesEmail
     *                                                                  Permissões selecionadas.
     * @return Utilizador Utilizador criado.
     *
     * @throws DomainException Quando o convite não está disponível, o
     *                         endereço não corresponde ao convite ou o
     *                         e-mail já existe.
     * @throws InvalidArgumentException Quando algum dado não é válido.
     * @throws Throwable Quando o armazenamento ou o registo falham.
     *
     * @since 2.0.0
     *
     * @version 2.1.0
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
        $codigoNormalizado =
            Convite::normalizarCodigo(
                $codigoConvite,
            );

        $codigoHash =
            Convite::calcularHashCodigo(
                $codigoNormalizado,
            );

        $nomeUtilizador =
            NomeUtilizador::deTexto(
                $nome,
            );

        $enderecoEmail =
            EnderecoEmail::deTexto(
                $email,
            );

        RequisitosPalavraPasse::validar(
            $palavraPasse,
        );

        $caminhoFotografia =
            $fotografia instanceof UploadedFile
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
        } catch (UniqueConstraintViolationException $excecao) {
            $this->eliminarFotografiaAposFalha(
                $caminhoFotografia,
            );

            $this->relancarConflitoRestricaoUnica(
                $excecao,
                $enderecoEmail,
            );
        } catch (Throwable $excecao) {
            $this->eliminarFotografiaAposFalha(
                $caminhoFotografia,
            );

            throw $excecao;
        }
    }

    /**
     * Executa o registo transacional do utilizador.
     *
     * @param  string  $codigoHash  Hash do código do convite.
     * @param  NomeUtilizador  $nome  Nome validado do utilizador.
     * @param  EnderecoEmail  $email  Endereço de e-mail validado.
     * @param  string  $palavraPasse  Palavra-passe em texto simples.
     * @param  string|null  $caminhoFotografia  Caminho da fotografia.
     * @param  array<int, int|string>  $identificadoresPermissoesEmail
     *                                                                  Permissões selecionadas.
     * @return Utilizador Utilizador criado.
     *
     * @throws Throwable Quando a transação não pode ser concluída.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
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

                $convite =
                    Convite::query()
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

                $utilizador =
                    new Utilizador;

                $utilizador->nome =
                    $nome->valor();

                $utilizador->email =
                    $email->valor();

                /*
                 * A hash é aplicada explicitamente pelo serviço. O cast
                 * `hashed` do modelo permanece como proteção adicional.
                 */
                $utilizador->password =
                    Hash::make(
                        $palavraPasse,
                    );

                /*
                 * O mutator do modelo realiza a validação final de segurança
                 * do caminho relativo da fotografia.
                 */
                $utilizador->fotografia =
                    $caminhoFotografia;

                $utilizador->papel =
                    PapelUtilizador::Utilizador;

                $utilizador->saveOrFail();

                $this->servicoPermissoesEmail->sincronizar(
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
     * @param  Convite  $convite  Convite bloqueado.
     * @param  EnderecoEmail  $email  Endereço do novo utilizador.
     *
     * @throws DomainException Quando os endereços não coincidem.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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

        $emailDestino =
            EnderecoEmail::deTexto(
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
     * Relança uma violação de restrição única com uma mensagem de domínio
     * quando o conflito pertence ao endereço de e-mail.
     *
     * @param  UniqueConstraintViolationException  $excecao  Exceção original.
     * @param  EnderecoEmail  $email  Endereço utilizado no registo.
     *
     * @throws DomainException Quando o endereço já está registado.
     * @throws UniqueConstraintViolationException Quando a restrição violada
     *                                            não pertence ao endereço.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function relancarConflitoRestricaoUnica(
        UniqueConstraintViolationException $excecao,
        EnderecoEmail $email,
    ): never {
        $emailJaExiste =
            Utilizador::query()
                ->where(
                    'email',
                    $email->valor(),
                )
                ->exists();

        if ($emailJaExiste) {
            throw new DomainException(
                'O endereço de e-mail já está associado a outro utilizador.',
                previous: $excecao,
            );
        }

        throw $excecao;
    }

    /**
     * Elimina a fotografia armazenada após uma falha do registo.
     *
     * Uma eventual falha durante a limpeza é reportada, mas não substitui a
     * exceção original do registo.
     *
     * @param  string|null  $caminhoFotografia  Caminho da fotografia.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function eliminarFotografiaAposFalha(
        ?string $caminhoFotografia,
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
            report(
                $excecaoLimpeza,
            );
        }
    }
}
