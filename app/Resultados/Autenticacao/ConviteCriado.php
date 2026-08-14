<?php

declare(strict_types=1);

namespace App\Resultados\Autenticacao;

use App\Models\Autenticacao\Convite;
use InvalidArgumentException;
use LogicException;
use SensitiveParameter;

/**
 * Transporta o resultado da criação de um convite.
 *
 * O objeto contém o convite persistido e o respetivo código original. O
 * código existe apenas em memória e deve ser apresentado ou enviado ao
 * destinatário imediatamente após a criação.
 *
 * Este resultado não pode ser serializado, impedindo a colocação acidental do
 * código original em sessões, filas ou outros mecanismos de persistência.
 *
 * @since 2.0.0
 */
final readonly class ConviteCriado
{
    /**
     * Convite persistido na base de dados.
     *
     * @since 2.0.0
     */
    private Convite $convite;

    /**
     * Código original do convite.
     *
     * @since 2.0.0
     */
    private string $codigo;

    /**
     * Cria o resultado de um convite acabado de gerar.
     *
     * @param  Convite  $convite  Convite persistido.
     * @param  string  $codigo  Código original não persistido.
     *
     * @throws InvalidArgumentException Quando o convite ainda não está
     *                                  persistido ou o código não é válido.
     *
     * @since 2.0.0
     */
    public function __construct(
        Convite $convite,
        #[SensitiveParameter]
        string $codigo,
    ) {
        self::validarConvitePersistido(
            $convite,
        );

        self::validarCodigo(
            $codigo,
        );

        $this->convite = $convite;
        $this->codigo = $codigo;
    }

    /**
     * Obtém o convite persistido.
     *
     * @return Convite Convite criado.
     *
     * @since 2.0.0
     */
    public function obterConvite(): Convite
    {
        return $this->convite;
    }

    /**
     * Obtém o código original do convite.
     *
     * Este valor deve ser utilizado apenas para construir a ligação enviada
     * ao destinatário ou apresentada imediatamente ao administrador.
     *
     * @return string Código original do convite.
     *
     * @since 2.0.0
     */
    public function obterCodigo(): string
    {
        return $this->codigo;
    }

    /**
     * Impede que o código original apareça em resultados de depuração.
     *
     * O nome permanece inalterado por corresponder a um método mágico do PHP.
     *
     * @return array{
     *     convite_id: mixed,
     *     codigo: string
     * } Representação segura para depuração.
     *
     * @since 2.0.0
     */
    public function __debugInfo(): array
    {
        return [
            'convite_id' => $this->convite->getKey(),

            'codigo' => '[OCULTO]',
        ];
    }

    /**
     * Impede a serialização do resultado e do código original.
     *
     * O nome permanece inalterado por corresponder a um método mágico do PHP.
     *
     * @return array<never, never>
     *
     * @throws LogicException Sempre que seja tentada a serialização.
     *
     * @since 2.0.0
     */
    public function __serialize(): array
    {
        throw new LogicException(
            'O resultado da criação de um convite não pode ser serializado.',
        );
    }

    /**
     * Confirma que o convite foi persistido.
     *
     * @param  Convite  $convite  Convite recebido.
     *
     * @throws InvalidArgumentException Quando o convite não está persistido.
     *
     * @since 2.0.0
     */
    private static function validarConvitePersistido(
        Convite $convite,
    ): void {
        if (
            $convite->exists
            && $convite->getKey() !== null
        ) {
            return;
        }

        throw new InvalidArgumentException(
            'O convite deve estar persistido antes de criar o resultado.',
        );
    }

    /**
     * Valida o código original do convite.
     *
     * O código não é normalizado para evitar alterações silenciosas a um
     * valor sensível gerado pela aplicação.
     *
     * @param  string  $codigo  Código recebido.
     *
     * @throws InvalidArgumentException Quando o código não é válido.
     *
     * @since 2.0.0
     */
    private static function validarCodigo(
        #[SensitiveParameter]
        string $codigo,
    ): void {
        $codigoSemEspacosExteriores =
            trim(
                $codigo,
            );

        if ($codigoSemEspacosExteriores === '') {
            throw new InvalidArgumentException(
                'O código do convite não pode estar vazio.',
            );
        }

        if ($codigo !== $codigoSemEspacosExteriores) {
            throw new InvalidArgumentException(
                'O código do convite não pode conter espaços exteriores.',
            );
        }

        if (
            preg_match(
                '/[\x00-\x1F\x7F]/',
                $codigo,
            ) === 1
        ) {
            throw new InvalidArgumentException(
                'O código do convite contém caracteres inválidos.',
            );
        }
    }
}
