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
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use SensitiveParameter;
use Throwable;

/**
 * Gere o registo de utilizadores através de convites.
 *
 * A criação do utilizador, a atribuição das permissões de e-mail e a
 * utilização do convite são executadas na mesma transação.
 *
 * @since 2.0.0
 *
 * @version 1.1.0
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
     * Comprimento máximo aceite para um código de convite.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_CODIGO = 255;

    /**
     * Cria o serviço.
     *
     * @param  ServicoPermissoesEmail  $servicoPermissoesEmail  Serviço
     *                                                          responsável
     *                                                          pelas permissões
     *                                                          de e-mail.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function __construct(
        private readonly ServicoPermissoesEmail $servicoPermissoesEmail,
    ) {}

    /**
     * Regista um utilizador através de um convite disponível.
     *
     * @param  string  $codigoConvite  Código original do convite.
     * @param  string  $nome  Nome do novo utilizador.
     * @param  string  $email  Endereço de e-mail.
     * @param  string  $palavraPasse  Palavra-passe em texto simples.
     * @param  string|null  $caminhoFotografia  Caminho relativo da fotografia.
     * @param  array<int, int|string>  $identificadoresPermissoesEmail
     *                                                                  Permissões
     *                                                                  selecionadas.
     * @return Utilizador Utilizador criado.
     *
     * @throws DomainException Quando o convite não está disponível, o endereço
     *                         não corresponde ao convite ou o e-mail já existe.
     * @throws InvalidArgumentException Quando algum dado não é válido.
     * @throws Throwable Quando ocorre outro erro durante a transação.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
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
        $codigoNormalizado =
            $this->normalizarCodigoConvite(
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
                        $enderecoEmail,
                    );

                    $utilizador =
                        new Utilizador;

                    $utilizador->nome =
                        $nomeUtilizador->valor();

                    $utilizador->email =
                        $enderecoEmail->valor();

                    /*
                     * O cast `hashed` do modelo aplica a hash antes da
                     * persistência.
                     */
                    $utilizador->password =
                        $palavraPasse;

                    /*
                     * O mutator do modelo Utilizador realiza a validação final
                     * de segurança do caminho.
                     */
                    $utilizador->fotografia =
                        $caminhoNormalizado;

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
        } catch (
            UniqueConstraintViolationException $excecao
        ) {
            if (
                Utilizador::query()
                    ->where(
                        'email',
                        $enderecoEmail->valor(),
                    )
                    ->exists()
            ) {
                throw new DomainException(
                    'O endereço de e-mail já está associado a outro utilizador.',
                    previous: $excecao,
                );
            }

            /*
             * A restrição violada não pertence ao endereço de e-mail.
             * Preservamos a exceção original para não esconder o erro real.
             */
            throw $excecao;
        }
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
     * Normaliza e valida o código do convite.
     *
     * Os espaços exteriores são removidos por se tratar de um valor recebido
     * através de um formulário. O restante conteúdo mantém-se sensível a
     * maiúsculas e minúsculas.
     *
     * @param  string  $codigo  Código recebido.
     * @return string Código normalizado.
     *
     * @throws InvalidArgumentException Quando o código não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarCodigoConvite(
        #[SensitiveParameter]
        string $codigo,
    ): string {
        $codigoNormalizado =
            trim(
                $codigo,
            );

        if ($codigoNormalizado === '') {
            throw new InvalidArgumentException(
                'O código do convite é obrigatório.',
            );
        }

        if (
            mb_strlen(
                $codigoNormalizado,
            ) > self::COMPRIMENTO_MAXIMO_CODIGO
        ) {
            throw new InvalidArgumentException(
                'O código do convite é demasiado longo.',
            );
        }

        if (
            preg_match(
                '/[\x00-\x1F\x7F]/',
                $codigoNormalizado,
            ) === 1
        ) {
            throw new InvalidArgumentException(
                'O código do convite contém caracteres inválidos.',
            );
        }

        return $codigoNormalizado;
    }

    /**
     * Normaliza o caminho da fotografia.
     *
     * A validação definitiva de segurança é efetuada pelo mutator
     * `fotografia` do modelo Utilizador.
     *
     * @param  string|null  $caminho  Caminho recebido.
     * @return string|null Caminho normalizado ou nulo.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function normalizarCaminhoFotografia(
        ?string $caminho,
    ): ?string {
        if ($caminho === null) {
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
}
