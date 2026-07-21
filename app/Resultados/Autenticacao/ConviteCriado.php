<?php

declare(strict_types=1);

namespace App\Resultados\Autenticacao;

use App\Models\Autenticacao\Convite;
use InvalidArgumentException;
use SensitiveParameter;

/**
 * Transporta o resultado da criação de um convite.
 *
 * O objeto mantém o convite persistido e o respetivo código original. O
 * código existe apenas em memória e deve ser apresentado ou enviado ao
 * destinatário imediatamente após a criação.
 *
 * O código nunca deve ser guardado em registos, sessões, filas ou tabelas da
 * base de dados.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final readonly class ConviteCriado
{
    /**
     * Convite persistido na base de dados.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private Convite $convite;

    /**
     * Código original do convite.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private string $codigo;

    /**
     * Cria o resultado de um convite acabado de gerar.
     *
     * @param  Convite  $convite  - Convite persistido.
     * @param  string  $codigo  - Código original não persistido.
     * @return void
     *
     * @throws InvalidArgumentException Quando o código está vazio.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function __construct(
        Convite $convite,
        #[SensitiveParameter]
        string $codigo,
    ) {
        $codigoNormalizado = trim($codigo);

        if ($codigoNormalizado === '') {
            throw new InvalidArgumentException(
                'O código do convite não pode estar vazio.',
            );
        }

        $this->convite = $convite;
        $this->codigo = $codigoNormalizado;
    }

    /**
     * Obtém o convite persistido.
     *
     * @return Convite - Convite criado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
     * @return string - Código original do convite.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function obterCodigo(): string
    {
        return $this->codigo;
    }

    /**
     * Impede que o código original apareça em resultados de depuração.
     *
     * @return array<string, mixed> - Representação segura para depuração.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function __debugInfo(): array
    {
        return [
            'convite' => $this->convite,
            'codigo' => '[OCULTO]',
        ];
    }
}
