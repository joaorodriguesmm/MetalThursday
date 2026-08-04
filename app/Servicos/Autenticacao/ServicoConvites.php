<?php

declare(strict_types=1);

namespace App\Servicos\Autenticacao;

use App\Models\Autenticacao\Convite;
use App\Models\Autenticacao\Utilizador;
use App\Resultados\Autenticacao\ConviteCriado;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use SensitiveParameter;
use Throwable;

/**
 * Gere a criação, localização e revogação dos convites.
 *
 * Os códigos são gerados através de um gerador criptograficamente seguro. O
 * código original é devolvido apenas no momento da criação, sendo persistido
 * exclusivamente o respetivo hash SHA-256.
 *
 * As revogações conservam o superadministrador ativo responsável e são
 * executadas com bloqueios pessimistas.
 *
 * @since 2.0.0
 *
 * @version 3.0.0
 */
final class ServicoConvites
{
    /**
     * Prefixo identificativo dos códigos de convite.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const PREFIXO_CODIGO = 'MT-';

    /**
     * Quantidade de bytes aleatórios utilizada na geração do código.
     *
     * Trinta e dois bytes correspondem a 256 bits de entropia antes da
     * codificação Base64 URL-safe.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const BYTES_ALEATORIOS = 32;

    /**
     * Número máximo de tentativas perante uma colisão do hash.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const MAXIMO_TENTATIVAS_GERACAO = 3;

    /**
     * Número máximo de tentativas perante conflitos transitórios numa
     * transação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TENTATIVAS_TRANSACAO = 3;

    /**
     * Cria um novo convite.
     *
     * O código original nunca é guardado. Apenas o resultado devolvido contém
     * temporariamente o código necessário para construir a ligação que será
     * apresentada ou enviada ao destinatário.
     *
     * O nome e o endereço de destino são normalizados e validados pelos
     * atributos definitivos do modelo {@see Convite}.
     *
     * @param  string  $nomeConvidado  Nome da pessoa convidada.
     * @param  string|null  $emailDestino  Endereço ao qual o convite fica
     *                                     limitado.
     * @param  Utilizador|null  $criador  Utilizador responsável pela criação.
     * @param  CarbonInterface|null  $expiraEm  Momento de expiração.
     * @return ConviteCriado Convite persistido e código original.
     *
     * @throws InvalidArgumentException Quando os dados recebidos não são
     *                                  válidos.
     * @throws RuntimeException Quando não é possível gerar um código único.
     * @throws Throwable Quando ocorre outro erro durante a persistência.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function criar(
        string $nomeConvidado,
        ?string $emailDestino = null,
        ?Utilizador $criador = null,
        ?CarbonInterface $expiraEm = null,
    ): ConviteCriado {
        $this->garantirCriadorPersistido(
            $criador,
        );

        $expiracao = $this->normalizarExpiracao(
            $expiraEm,
        );

        for (
            $tentativa = 1;
            $tentativa <= self::MAXIMO_TENTATIVAS_GERACAO;
            $tentativa++
        ) {
            $codigo = $this->gerarCodigo();

            try {
                $convite =
                    new Convite;

                $convite->nome_convidado =
                    $nomeConvidado;

                $convite->email_destino =
                    $emailDestino;

                $convite->expira_em =
                    $expiracao;

                if ($criador !== null) {
                    $convite
                        ->criador()
                        ->associate(
                            $criador,
                        );
                }

                $convite->definirCodigo(
                    $codigo,
                );

                $convite->saveOrFail();

                return new ConviteCriado(
                    $convite,
                    $codigo,
                );
            } catch (
                UniqueConstraintViolationException $excecao
            ) {
                if (
                    $tentativa
                    < self::MAXIMO_TENTATIVAS_GERACAO
                ) {
                    continue;
                }

                throw new RuntimeException(
                    'Não foi possível gerar um código de convite único.',
                    previous: $excecao,
                );
            }
        }

        throw new RuntimeException(
            'Não foi possível criar o convite.',
        );
    }

    /**
     * Procura um convite disponível através do código original.
     *
     * Convites utilizados, revogados ou expirados não são devolvidos.
     *
     * A normalização e a validação do código são efetuadas pelo modelo
     * {@see Convite}.
     *
     * @param  string  $codigo  Código original do convite.
     * @return Convite|null Convite disponível ou nulo.
     *
     * @throws InvalidArgumentException Quando o código não é válido.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    public function encontrarDisponivelPorCodigo(
        #[SensitiveParameter]
        string $codigo,
    ): ?Convite {
        return Convite::query()
            ->disponiveis()
            ->comCodigo(
                $codigo,
            )
            ->first();
    }

    /**
     * Revoga um convite de forma concorrencialmente segura.
     *
     * O convite e o responsável são novamente obtidos com bloqueio exclusivo.
     * Apenas um superadministrador com acesso ativo pode concluir a operação.
     *
     * Uma revogação repetida preserva o primeiro momento e o primeiro
     * responsável, não alterando sequer a data de atualização do convite.
     *
     * @param  Convite  $convite  Convite a revogar.
     * @param  Utilizador  $responsavel  Superadministrador responsável.
     * @param  CarbonInterface|null  $momento  Momento da revogação.
     * @return Convite Convite revogado.
     *
     * @throws InvalidArgumentException Quando o convite ou o responsável não
     *                                  estão persistidos.
     * @throws ModelNotFoundException Quando algum registo deixou de existir.
     * @throws DomainException Quando o responsável não está autorizado ou o
     *                         convite já foi utilizado.
     * @throws Throwable Quando ocorre outro erro durante a transação.
     *
     * @since 2.0.0
     *
     * @version 3.0.0
     */
    public function revogar(
        Convite $convite,
        Utilizador $responsavel,
        ?CarbonInterface $momento = null,
    ): Convite {
        $identificadorConvite =
            $this->obterIdentificadorConvite(
                $convite,
            );

        $identificadorResponsavel =
            $this->obterIdentificadorUtilizador(
                $responsavel,
            );

        $momentoRevogacao = $momento !== null
            ? CarbonImmutable::instance(
                $momento,
            )
            : null;

        return DB::transaction(
            function () use (
                $identificadorConvite,
                $identificadorResponsavel,
                $momentoRevogacao,
            ): Convite {
                $responsavelBloqueado =
                    Utilizador::query()
                        ->whereKey(
                            $identificadorResponsavel,
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $this->garantirResponsavelAutorizado(
                    $responsavelBloqueado,
                );

                $conviteBloqueado =
                    Convite::query()
                        ->whereKey(
                            $identificadorConvite,
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $conviteBloqueado->revogar(
                    $responsavelBloqueado,
                    $momentoRevogacao,
                );

                if (
                    $conviteBloqueado->isDirty([
                        'revogado_em',
                        'revogado_por_id',
                    ])
                ) {
                    $conviteBloqueado->saveOrFail();
                }

                return $conviteBloqueado;
            },
            self::TENTATIVAS_TRANSACAO,
        );
    }

    /**
     * Gera um código criptograficamente seguro e adequado para URLs.
     *
     * A codificação Base64 URL-safe evita caracteres que precisariam de ser
     * escapados nos caminhos das rotas. O código produzido respeita o padrão
     * e o comprimento definidos pelo modelo {@see Convite}.
     *
     * @return string Código original do convite.
     *
     * @throws Throwable Quando não é possível obter bytes aleatórios.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function gerarCodigo(): string
    {
        $bytes = random_bytes(
            self::BYTES_ALEATORIOS,
        );

        $codigoCodificado = rtrim(
            strtr(
                base64_encode(
                    $bytes,
                ),
                '+/',
                '-_',
            ),
            '=',
        );

        return Convite::normalizarCodigo(
            self::PREFIXO_CODIGO
                .$codigoCodificado,
        );
    }

    /**
     * Confirma que o criador opcional está persistido.
     *
     * @param  Utilizador|null  $criador  Utilizador recebido.
     *
     * @throws InvalidArgumentException Quando o utilizador não está
     *                                  persistido ou não possui um
     *                                  identificador válido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function garantirCriadorPersistido(
        ?Utilizador $criador,
    ): void {
        if ($criador === null) {
            return;
        }

        $identificador = $criador->getKey();

        if (
            ! $criador->exists
            || (
                ! is_int($identificador)
                && ! is_string($identificador)
            )
        ) {
            throw new InvalidArgumentException(
                'O criador do convite deve estar persistido.',
            );
        }

        if (
            is_int($identificador)
            && $identificador > 0
        ) {
            return;
        }

        if (
            is_string($identificador)
            && ctype_digit(
                trim(
                    $identificador,
                ),
            )
            && (int) trim(
                $identificador,
            ) > 0
        ) {
            return;
        }

        throw new InvalidArgumentException(
            'O criador do convite deve possuir um identificador válido.',
        );
    }

    /**
     * Confirma que o responsável pode revogar convites.
     *
     * @param  Utilizador  $responsavel  Utilizador responsável.
     *
     * @throws DomainException Quando o responsável não é um
     *                         superadministrador com acesso ativo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function garantirResponsavelAutorizado(
        Utilizador $responsavel,
    ): void {
        if (
            $responsavel->eSuperAdministrador()
            && $responsavel->temAcessoAtivo()
        ) {
            return;
        }

        throw new DomainException(
            'A revogação de convites exige um superadministrador com acesso ativo.',
        );
    }

    /**
     * Obtém o identificador de um convite persistido.
     *
     * @param  Convite  $convite  Convite recebido.
     * @return int Identificador do convite.
     *
     * @throws InvalidArgumentException Quando o convite não está persistido.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function obterIdentificadorConvite(
        Convite $convite,
    ): int {
        if (! $convite->exists) {
            throw new InvalidArgumentException(
                'O convite deve estar persistido antes de ser revogado.',
            );
        }

        $identificador = $convite->getKey();

        if (
            is_int($identificador)
            && $identificador > 0
        ) {
            return $identificador;
        }

        if (! is_string($identificador)) {
            throw new InvalidArgumentException(
                'O convite deve possuir um identificador válido.',
            );
        }

        $identificadorNormalizado = trim(
            $identificador,
        );

        if (
            $identificadorNormalizado === ''
            || ! ctype_digit(
                $identificadorNormalizado,
            )
            || (int) $identificadorNormalizado < 1
        ) {
            throw new InvalidArgumentException(
                'O convite deve possuir um identificador válido.',
            );
        }

        return (int) $identificadorNormalizado;
    }

    /**
     * Obtém o identificador de um responsável persistido.
     *
     * @param  Utilizador  $responsavel  Utilizador recebido.
     * @return int Identificador do responsável.
     *
     * @throws InvalidArgumentException Quando o responsável não está
     *                                  persistido ou não possui um
     *                                  identificador válido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterIdentificadorUtilizador(
        Utilizador $responsavel,
    ): int {
        if (! $responsavel->exists) {
            throw new InvalidArgumentException(
                'O responsável pela revogação deve estar persistido.',
            );
        }

        $identificador =
            $responsavel->getKey();

        if (
            is_int($identificador)
            && $identificador > 0
        ) {
            return $identificador;
        }

        if (is_string($identificador)) {
            $identificadorNormalizado = trim(
                $identificador,
            );

            if (
                $identificadorNormalizado !== ''
                && ctype_digit(
                    $identificadorNormalizado,
                )
                && (int) $identificadorNormalizado > 0
            ) {
                return (int) $identificadorNormalizado;
            }
        }

        throw new InvalidArgumentException(
            'O responsável pela revogação deve possuir um identificador válido.',
        );
    }

    /**
     * Normaliza e valida a data de expiração.
     *
     * @param  CarbonInterface|null  $expiraEm  Momento recebido.
     * @return CarbonImmutable|null Momento normalizado ou nulo.
     *
     * @throws InvalidArgumentException Quando a data não está no futuro.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function normalizarExpiracao(
        ?CarbonInterface $expiraEm,
    ): ?CarbonImmutable {
        if ($expiraEm === null) {
            return null;
        }

        $expiracao = CarbonImmutable::instance(
            $expiraEm,
        );

        if (
            $expiracao->lessThanOrEqualTo(
                CarbonImmutable::now(),
            )
        ) {
            throw new InvalidArgumentException(
                'A expiração do convite deve estar no futuro.',
            );
        }

        return $expiracao;
    }
}
