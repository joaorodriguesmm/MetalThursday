<?php

declare(strict_types=1);

namespace App\Servicos\Autenticacao;

use App\Models\Autenticacao\Convite;
use App\Models\User;
use App\Resultados\Autenticacao\ConviteCriado;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use SensitiveParameter;

/**
 * Gere a criação, localização e revogação dos convites.
 *
 * Os códigos são gerados através de um gerador criptograficamente seguro. O
 * código original é devolvido apenas no momento da criação, sendo persistido
 * exclusivamente o respetivo hash SHA-256.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
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
     * Uma colisão é extremamente improvável, mas a restrição única da base de
     * dados continua a ser tratada explicitamente.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const MAXIMO_TENTATIVAS = 3;

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
     * Comprimento máximo do endereço de e-mail.
     *
     * @var int
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const COMPRIMENTO_MAXIMO_EMAIL = 255;

    /**
     * Cria um novo convite.
     *
     * O código original nunca é guardado. Apenas o resultado devolvido contém
     * temporariamente o código necessário para construir a ligação que será
     * apresentada ou enviada ao destinatário.
     *
     * @param  string  $nomeConvidado  - Nome da pessoa convidada.
     * @param  string|null  $emailDestino  - E-mail ao qual o convite fica
     *                                     limitado.
     * @param  User|null  $criador  - Utilizador responsável pela criação.
     * @param  CarbonInterface|null  $expiraEm  - Momento de expiração.
     * @return ConviteCriado - Convite persistido e respetivo código original.
     *
     * @throws InvalidArgumentException Quando os dados recebidos são
     *                                  inválidos.
     * @throws RuntimeException Quando não é possível gerar um código único.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function criar(
        string $nomeConvidado,
        ?string $emailDestino = null,
        ?User $criador = null,
        ?CarbonInterface $expiraEm = null,
    ): ConviteCriado {
        $nomeNormalizado = $this->normalizarNome($nomeConvidado);
        $emailNormalizado = $this->normalizarEmail($emailDestino);

        $this->validarCriador($criador);
        $this->validarExpiracao($expiraEm);

        for (
            $tentativa = 1;
            $tentativa <= self::MAXIMO_TENTATIVAS;
            $tentativa++
        ) {
            $codigo = $this->gerarCodigo();

            try {
                $convite = new Convite;

                $convite->nome_convidado = $nomeNormalizado;
                $convite->email_destino = $emailNormalizado;
                $convite->criado_por = $criador?->getKey();
                $convite->expira_em = $expiraEm;
                $convite->definirCodigo($codigo);

                $convite->saveOrFail();

                return new ConviteCriado(
                    $convite,
                    $codigo,
                );
            } catch (UniqueConstraintViolationException $excecao) {
                if ($tentativa === self::MAXIMO_TENTATIVAS) {
                    throw new RuntimeException(
                        'Não foi possível gerar um código de convite único.',
                        previous: $excecao,
                    );
                }
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
     * @param  string  $codigo  - Código original do convite.
     * @return Convite|null - Convite disponível ou nulo quando não existe.
     *
     * @throws InvalidArgumentException Quando o código está vazio.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function encontrarDisponivelPorCodigo(
        #[SensitiveParameter]
        string $codigo,
    ): ?Convite {
        return Convite::query()
            ->disponiveis()
            ->comCodigo($codigo)
            ->first();
    }

    /**
     * Revoga um convite de forma concorrencialmente segura.
     *
     * O registo é bloqueado durante a transação para impedir que o mesmo
     * convite seja utilizado ou revogado simultaneamente por outro processo.
     *
     * @param  Convite  $convite  - Convite que deverá ser revogado.
     * @param  CarbonInterface|null  $momento  - Momento da revogação.
     * @return Convite - Convite revogado e atualizado.
     *
     * @throws ModelNotFoundException Quando o convite já não existe.
     * @throws \DomainException Quando o convite já foi utilizado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function revogar(
        Convite $convite,
        ?CarbonInterface $momento = null,
    ): Convite {
        return DB::transaction(
            function () use ($convite, $momento): Convite {
                /** @var Convite $conviteBloqueado */
                $conviteBloqueado = Convite::query()
                    ->lockForUpdate()
                    ->findOrFail($convite->getKey());

                $conviteBloqueado->revogar($momento);
                $conviteBloqueado->saveOrFail();

                return $conviteBloqueado;
            },
        );
    }

    /**
     * Gera um código criptograficamente seguro e adequado para URLs.
     *
     * A codificação Base64 URL-safe evita caracteres que precisariam de ser
     * escapados nos caminhos das rotas.
     *
     * @return string - Código original do convite.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function gerarCodigo(): string
    {
        $codigoAleatorio = rtrim(
            strtr(
                base64_encode(
                    random_bytes(self::BYTES_ALEATORIOS),
                ),
                '+/',
                '-_',
            ),
            '=',
        );

        return self::PREFIXO_CODIGO.$codigoAleatorio;
    }

    /**
     * Normaliza e valida o nome do convidado.
     *
     * Espaços consecutivos e quebras de linha são convertidos num único
     * espaço.
     *
     * @param  string  $nome  - Nome recebido.
     * @return string - Nome normalizado.
     *
     * @throws InvalidArgumentException Quando o nome é vazio ou demasiado
     *                                  longo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarNome(string $nome): string
    {
        $nomeNormalizado = preg_replace(
            '/\s+/u',
            ' ',
            trim($nome),
        );

        if (
            $nomeNormalizado === null
            || $nomeNormalizado === ''
        ) {
            throw new InvalidArgumentException(
                'O nome do convidado é obrigatório.',
            );
        }

        if (
            mb_strlen($nomeNormalizado)
            > self::COMPRIMENTO_MAXIMO_NOME
        ) {
            throw new InvalidArgumentException(
                'O nome do convidado é demasiado longo.',
            );
        }

        return $nomeNormalizado;
    }

    /**
     * Normaliza e valida o endereço de e-mail.
     *
     * Uma string vazia é interpretada como ausência de e-mail.
     *
     * @param  string|null  $email  - Endereço recebido.
     * @return string|null - Endereço normalizado ou nulo.
     *
     * @throws InvalidArgumentException Quando o endereço não é válido ou é
     *                                  demasiado longo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarEmail(
        ?string $email,
    ): ?string {
        if ($email === null || trim($email) === '') {
            return null;
        }

        $emailNormalizado = mb_strtolower(
            trim($email),
        );

        if (
            mb_strlen($emailNormalizado)
            > self::COMPRIMENTO_MAXIMO_EMAIL
        ) {
            throw new InvalidArgumentException(
                'O endereço de e-mail é demasiado longo.',
            );
        }

        if (
            filter_var(
                $emailNormalizado,
                FILTER_VALIDATE_EMAIL,
            ) === false
        ) {
            throw new InvalidArgumentException(
                'O endereço de e-mail não é válido.',
            );
        }

        return $emailNormalizado;
    }

    /**
     * Valida o utilizador responsável pela criação.
     *
     * @param  User|null  $criador  - Utilizador recebido.
     *
     * @throws InvalidArgumentException Quando o utilizador ainda não foi
     *                                  persistido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function validarCriador(
        ?User $criador,
    ): void {
        if ($criador === null) {
            return;
        }

        if (
            ! $criador->exists
            || $criador->getKey() === null
        ) {
            throw new InvalidArgumentException(
                'O criador do convite deve estar persistido.',
            );
        }
    }

    /**
     * Valida a data de expiração.
     *
     * @param  CarbonInterface|null  $expiraEm  - Momento de expiração.
     *
     * @throws InvalidArgumentException Quando a data não está no futuro.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function validarExpiracao(
        ?CarbonInterface $expiraEm,
    ): void {
        if ($expiraEm === null) {
            return;
        }

        if ($expiraEm->lessThanOrEqualTo(now())) {
            throw new InvalidArgumentException(
                'A expiração do convite deve estar no futuro.',
            );
        }
    }
}
