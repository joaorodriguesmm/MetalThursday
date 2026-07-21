<?php

declare(strict_types=1);

namespace App\Servicos\Autenticacao;

use App\Enumeracoes\PapelUtilizador;
use App\Models\Autenticacao\Convite;
use App\Models\Autenticacao\Utilizador;
use App\ObjetosValor\Utilizadores\EnderecoEmail;
use App\ObjetosValor\Utilizadores\NomeUtilizador;
use App\Regras\Autenticacao\PoliticaPalavraPasse;
use App\Servicos\Comunicacoes\ServicoPermissoesEmail;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use SensitiveParameter;
use Throwable;

/**
 * Gere o registo de utilizadores através de convites.
 *
 * A criação do utilizador, a atribuição das permissões e a utilização do
 * convite são executadas na mesma transação.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
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
     * Serviço responsável pelas permissões de e-mail.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private readonly ServicoPermissoesEmail $servicoPermissoesEmail;

    /**
     * Cria o serviço.
     *
     * @param  ServicoPermissoesEmail  $servicoPermissoesEmail  - Serviço das
     *                                                          permissões de e-mail.
     * @return void
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function __construct(
        ServicoPermissoesEmail $servicoPermissoesEmail,
    ) {
        $this->servicoPermissoesEmail =
            $servicoPermissoesEmail;
    }

    /**
     * Regista um utilizador através de um convite disponível.
     *
     * @param  string  $codigoConvite  - Código original do convite.
     * @param  string  $nome  - Nome do novo utilizador.
     * @param  string  $email  - Endereço de e-mail.
     * @param  string  $palavraPasse  - Palavra-passe em texto simples.
     * @param  string|null  $caminhoFotografia  - Caminho da fotografia.
     * @param  array<int, int|string>  $identificadoresPermissoesEmail  -
     *                                                                  Permissões selecionadas.
     * @return Utilizador - Utilizador criado.
     *
     * @throws DomainException Quando o convite não está disponível ou o
     *                         endereço de e-mail já existe.
     * @throws \InvalidArgumentException Quando algum dado não é válido.
     * @throws Throwable Quando ocorre outro erro durante a transação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function registar(
        #[SensitiveParameter]
        string $codigoConvite,
        string $nome,
        string $email,
        #[SensitiveParameter]
        string $palavraPasse,
        ?string $caminhoFotografia = null,
        array $identificadoresPermissoesEmail = [],
    ): Utilizador {
        $codigoHash = Convite::calcularHashCodigo(
            $codigoConvite,
        );

        $nomeUtilizador = NomeUtilizador::deTexto($nome);
        $enderecoEmail = EnderecoEmail::deTexto($email);

        PoliticaPalavraPasse::validar(
            $palavraPasse,
        );

        $caminhoNormalizado =
            $this->normalizarCaminhoFotografia(
                $caminhoFotografia,
            );

        try {
            return DB::transaction(
                function () use (
                    $codigoHash,
                    $nomeUtilizador,
                    $enderecoEmail,
                    $palavraPasse,
                    $caminhoNormalizado,
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
                        $convite === null
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
                        $enderecoEmail,
                    );

                    $utilizador = new Utilizador;

                    $utilizador->nome =
                        $nomeUtilizador->valor();

                    $utilizador->email =
                        $enderecoEmail->valor();

                    $utilizador->password =
                        $palavraPasse;

                    $utilizador->fotografia =
                        $caminhoNormalizado;

                    $utilizador->papel =
                        PapelUtilizador::Utilizador;

                    $utilizador->saveOrFail();

                    $this->servicoPermissoesEmail
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
        } catch (
            UniqueConstraintViolationException $excecao
        ) {
            throw new DomainException(
                'O endereço de e-mail já está associado a outro utilizador.',
                previous: $excecao,
            );
        }
    }

    /**
     * Confirma que o e-mail corresponde ao destinatário do convite.
     *
     * @param  Convite  $convite  - Convite bloqueado.
     * @param  EnderecoEmail  $email  - Endereço do novo utilizador.
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
        if ($convite->email_destino === null) {
            return;
        }

        $emailDestino = EnderecoEmail::deTexto(
            $convite->email_destino,
        );

        if ($emailDestino->igualA($email)) {
            return;
        }

        throw new DomainException(
            'O endereço de e-mail não corresponde ao destinatário do convite.',
        );
    }

    /**
     * Normaliza o caminho da fotografia.
     *
     * O armazenamento e substituição das fotografias serão posteriormente
     * movidos para um serviço próprio.
     *
     * @param  string|null  $caminho  - Caminho recebido.
     * @return string|null - Caminho normalizado ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarCaminhoFotografia(
        ?string $caminho,
    ): ?string {
        if ($caminho === null) {
            return null;
        }

        $caminhoNormalizado = trim($caminho);

        return $caminhoNormalizado !== ''
            ? $caminhoNormalizado
            : null;
    }
}
