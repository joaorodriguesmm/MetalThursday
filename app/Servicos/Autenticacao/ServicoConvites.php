<?php

declare(strict_types=1);

namespace App\Servicos\Autenticacao;

use App\Models\Autenticacao\Convite;
use App\Models\Autenticacao\Utilizador;
use App\ObjetosValor\Utilizadores\EnderecoEmail;
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
 * @since 2.0.0
 *
 * @version 1.1.0
 */
final class ServicoConvites
{
    /**
     * Prefixo identificativo dos códigos de convite.
     *
     * @var string
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
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const BYTES_ALEATORIOS = 32;

    /**
     * Número máximo de tentativas perante uma colisão do hash.
     *
     * @var int
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
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TENTATIVAS_TRANSACAO = 3;

    /**
     * Comprimento máximo do nome do convidado.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_NOME = 255;

    /**
     * Comprimento máximo aceite para um código recebido.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_CODIGO = 255;

    /**
     * Cria um novo convite.
     *
     * O código original nunca é guardado. Apenas o resultado devolvido contém
     * temporariamente o código necessário para construir a ligação que será
     * apresentada ou enviada ao destinatário.
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
     * @version 1.1.0
     */
    public function criar(
        string $nomeConvidado,
        ?string $emailDestino = null,
        ?Utilizador $criador = null,
        ?CarbonInterface $expiraEm = null,
    ): ConviteCriado {
        $nomeNormalizado =
            $this->normalizarNome(
                $nomeConvidado,
            );

        $emailNormalizado =
            $this->normalizarEmail(
                $emailDestino,
            );

        $identificadorCriador =
            $this->obterIdentificadorCriador(
                $criador,
            );

        $expiracaoNormalizada =
            $this->normalizarExpiracao(
                $expiraEm,
            );

        for (
            $tentativa = 1;
            $tentativa <= self::MAXIMO_TENTATIVAS_GERACAO;
            $tentativa++
        ) {
            $codigo =
                $this->gerarCodigo();

            try {
                $convite =
                    new Convite;

                $convite->nome_convidado =
                    $nomeNormalizado;

                $convite->email_destino =
                    $emailNormalizado;

                $convite->criado_por_id =
                    $identificadorCriador;

                $convite->expira_em =
                    $expiracaoNormalizada;

                $convite->definirCodigo(
                    $codigo,
                );

                $convite->saveOrFail();

                if ($criador instanceof Utilizador) {
                    $convite->setRelation(
                        'criador',
                        $criador,
                    );
                }

                return new ConviteCriado(
                    $convite,
                    $codigo,
                );
            } catch (UniqueConstraintViolationException $excecao) {
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
     * @param  string  $codigo  Código original do convite.
     * @return Convite|null Convite disponível ou nulo.
     *
     * @throws InvalidArgumentException Quando o código não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function encontrarDisponivelPorCodigo(
        #[SensitiveParameter]
        string $codigo,
    ): ?Convite {
        $codigoNormalizado =
            $this->normalizarCodigoRecebido(
                $codigo,
            );

        return Convite::query()
            ->disponiveis()
            ->comCodigo(
                $codigoNormalizado,
            )
            ->first();
    }

    /**
     * Revoga um convite de forma concorrencialmente segura.
     *
     * O registo é bloqueado durante a transação para impedir que seja
     * utilizado ou revogado simultaneamente por outro processo.
     *
     * @param  Convite  $convite  Convite a revogar.
     * @param  CarbonInterface|null  $momento  Momento da revogação.
     * @return Convite Convite revogado.
     *
     * @throws InvalidArgumentException Quando o convite não está persistido.
     * @throws ModelNotFoundException Quando o convite deixou de existir.
     * @throws DomainException Quando o convite já foi utilizado.
     * @throws Throwable Quando ocorre outro erro durante a transação.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    public function revogar(
        Convite $convite,
        ?CarbonInterface $momento = null,
    ): Convite {
        $identificadorConvite =
            $this->obterIdentificadorConvite(
                $convite,
            );

        $momentoNormalizado =
            $momento !== null
            ? CarbonImmutable::instance(
                $momento,
            )
            : null;

        return DB::transaction(
            static function () use (
                $identificadorConvite,
                $momentoNormalizado,
            ): Convite {
                $conviteBloqueado =
                    Convite::query()
                        ->whereKey(
                            $identificadorConvite,
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $conviteBloqueado->revogar(
                    $momentoNormalizado,
                );

                $conviteBloqueado->saveOrFail();

                return $conviteBloqueado;
            },
            self::TENTATIVAS_TRANSACAO,
        );
    }

    /**
     * Gera um código criptograficamente seguro e adequado para URLs.
     *
     * A codificação Base64 URL-safe evita caracteres que precisariam de ser
     * escapados nos caminhos das rotas.
     *
     * @return string Código original do convite.
     *
     * @throws Throwable Quando não é possível obter bytes aleatórios.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function gerarCodigo(): string
    {
        $bytes =
            random_bytes(
                self::BYTES_ALEATORIOS,
            );

        $codigoCodificado =
            rtrim(
                strtr(
                    base64_encode(
                        $bytes,
                    ),
                    '+/',
                    '-_',
                ),
                '=',
            );

        return self::PREFIXO_CODIGO
            .$codigoCodificado;
    }

    /**
     * Normaliza e valida o nome do convidado.
     *
     * Espaços consecutivos, tabulações e quebras de linha são convertidos num
     * único espaço.
     *
     * @param  string  $nome  Nome recebido.
     * @return string Nome normalizado.
     *
     * @throws InvalidArgumentException Quando o nome não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function normalizarNome(
        string $nome,
    ): string {
        if (
            preg_match(
                '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
                $nome,
            ) === 1
        ) {
            throw new InvalidArgumentException(
                'O nome do convidado contém caracteres inválidos.',
            );
        }

        $nomeNormalizado =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    $nome,
                ),
            );

        if (
            ! is_string($nomeNormalizado)
            || $nomeNormalizado === ''
        ) {
            throw new InvalidArgumentException(
                'O nome do convidado é obrigatório.',
            );
        }

        if (
            mb_strlen(
                $nomeNormalizado,
            ) > self::COMPRIMENTO_MAXIMO_NOME
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    'O nome do convidado não pode ter mais de %d caracteres.',
                    self::COMPRIMENTO_MAXIMO_NOME,
                ),
            );
        }

        return $nomeNormalizado;
    }

    /**
     * Normaliza e valida o endereço de e-mail.
     *
     * Uma string vazia é interpretada como ausência de endereço.
     *
     * @param  string|null  $email  Endereço recebido.
     * @return string|null Endereço normalizado ou nulo.
     *
     * @throws InvalidArgumentException Quando o endereço não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.1.0
     */
    private function normalizarEmail(
        ?string $email,
    ): ?string {
        if (
            $email === null
            || trim($email) === ''
        ) {
            return null;
        }

        return EnderecoEmail::deTexto(
            $email,
        )->valor();
    }

    /**
     * Obtém o identificador do utilizador responsável pela criação.
     *
     * @param  Utilizador|null  $criador  Utilizador recebido.
     * @return int|null Identificador do criador ou nulo.
     *
     * @throws InvalidArgumentException Quando o utilizador não está
     *                                  persistido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterIdentificadorCriador(
        ?Utilizador $criador,
    ): ?int {
        if ($criador === null) {
            return null;
        }

        $identificador =
            $criador->getKey();

        if (
            ! $criador->exists
            || ! is_numeric($identificador)
            || (int) $identificador < 1
        ) {
            throw new InvalidArgumentException(
                'O criador do convite deve estar persistido.',
            );
        }

        return (int) $identificador;
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
     * @version 1.0.0
     */
    private function obterIdentificadorConvite(
        Convite $convite,
    ): int {
        $identificador =
            $convite->getKey();

        if (
            ! $convite->exists
            || ! is_numeric($identificador)
            || (int) $identificador < 1
        ) {
            throw new InvalidArgumentException(
                'O convite deve estar persistido antes de ser revogado.',
            );
        }

        return (int) $identificador;
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
     * @version 1.1.0
     */
    private function normalizarExpiracao(
        ?CarbonInterface $expiraEm,
    ): ?CarbonImmutable {
        if ($expiraEm === null) {
            return null;
        }

        $expiracao =
            CarbonImmutable::instance(
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

    /**
     * Normaliza um código recebido para pesquisa.
     *
     * O código continua sensível a maiúsculas e minúsculas. Apenas os espaços
     * exteriores são removidos.
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
    private function normalizarCodigoRecebido(
        #[SensitiveParameter]
        string $codigo,
    ): string {
        $codigoNormalizado =
            trim(
                $codigo,
            );

        if ($codigoNormalizado === '') {
            throw new InvalidArgumentException(
                'O código do convite não pode estar vazio.',
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
}
