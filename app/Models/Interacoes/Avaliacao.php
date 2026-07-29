<?php

declare(strict_types=1);

namespace App\Models\Interacoes;

use App\Models\Autenticacao\Utilizador;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use InvalidArgumentException;

/**
 * Representa uma avaliação atribuída por um utilizador.
 *
 * Uma avaliação pode pertencer a uma MetalThursday ou a uma das respetivas
 * secções. Cada utilizador pode avaliar cada entidade apenas uma vez.
 *
 * A pontuação utiliza uma escala entre 0,5 e 10,0, em incrementos de 0,5.
 *
 * @property int $id
 * @property int $utilizador_id
 * @property float $pontuacao
 * @property 'metal_thursday'|'seccao_metal_thursday' $tipo_avaliavel
 * @property int $avaliavel_id
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Utilizador $utilizador
 * @property-read MetalThursday|SeccaoMetalThursday $avaliavel
 *
 * @since 1.0.0
 *
 * @version 3.0.0
 */
class Avaliacao extends Model
{
    /**
     * Pontuação mínima permitida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public const PONTUACAO_MINIMA = 0.5;

    /**
     * Pontuação máxima permitida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public const PONTUACAO_MAXIMA = 10.0;

    /**
     * Incremento permitido entre pontuações.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public const INCREMENTO_PONTUACAO = 0.5;

    /**
     * Número de casas decimais persistidas.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const CASAS_DECIMAIS_PONTUACAO = 1;

    /**
     * Fator utilizado para validar a escala sem depender de operações
     * modulares diretamente sobre números de vírgula flutuante.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const FATOR_ESCALA = 10;

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $table = 'avaliacoes';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * O tipo e o identificador da entidade avaliada são preenchidos pela
     * relação polimórfica e não podem ser atribuídos diretamente através de
     * dados externos.
     *
     * @var list<string>
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    protected $fillable = [
        'utilizador_id',
        'pontuacao',
    ];

    /**
     * Define as conversões automáticas dos identificadores.
     *
     * A pontuação é tratada pelo atributo {@see pontuacao()} para garantir a
     * validação da escala antes da persistência.
     *
     * @return array<string, string> Conversões dos atributos.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    protected function casts(): array
    {
        return [
            'utilizador_id' => 'integer',

            'avaliavel_id' => 'integer',
        ];
    }

    /**
     * Normaliza e valida a pontuação.
     *
     * São aceites inteiros, números de vírgula flutuante e representações
     * numéricas textuais que utilizem ponto como separador decimal.
     *
     * O valor persistido possui sempre uma casa decimal.
     *
     * @return Attribute<float, float|int|string> Atributo da pontuação.
     *
     * @throws InvalidArgumentException Quando a pontuação não pertence à
     *                                  escala permitida.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    protected function pontuacao(): Attribute
    {
        return Attribute::make(
            get: static fn (
                mixed $valor,
            ): float => (float) $valor,

            set: static function (
                mixed $valor,
            ): string {
                if (
                    ! is_int($valor)
                    && ! is_float($valor)
                    && ! is_string($valor)
                ) {
                    throw new InvalidArgumentException(
                        'A pontuação da avaliação deve ser numérica.',
                    );
                }

                if (
                    is_string($valor)
                    && (
                        trim($valor) === ''
                        || ! is_numeric(trim($valor))
                    )
                ) {
                    throw new InvalidArgumentException(
                        'A pontuação da avaliação deve ser numérica.',
                    );
                }

                $pontuacao = (float) (
                    is_string($valor)
                    ? trim($valor)
                    : $valor
                );

                if (! is_finite($pontuacao)) {
                    throw new InvalidArgumentException(
                        'A pontuação da avaliação deve ser um número finito.',
                    );
                }

                $pontuacaoEmUnidades = (int) round(
                    $pontuacao * self::FATOR_ESCALA,
                );

                $pontuacaoNormalizada =
                    $pontuacaoEmUnidades
                    / self::FATOR_ESCALA;

                if (
                    abs(
                        $pontuacaoNormalizada
                            - $pontuacao,
                    ) > PHP_FLOAT_EPSILON
                    || $pontuacaoNormalizada
                    < self::PONTUACAO_MINIMA
                    || $pontuacaoNormalizada
                    > self::PONTUACAO_MAXIMA
                    || $pontuacaoEmUnidades
                    % (
                        (int) (
                            self::INCREMENTO_PONTUACAO
                            * self::FATOR_ESCALA
                        )
                    ) !== 0
                ) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'A pontuação deve estar entre %.1f e %.1f, em incrementos de %.1f.',
                            self::PONTUACAO_MINIMA,
                            self::PONTUACAO_MAXIMA,
                            self::INCREMENTO_PONTUACAO,
                        ),
                    );
                }

                return number_format(
                    $pontuacaoNormalizada,
                    self::CASAS_DECIMAIS_PONTUACAO,
                    '.',
                    '',
                );
            },
        );
    }

    /**
     * Obtém o utilizador que atribuiu a avaliação.
     *
     * @return BelongsTo<Utilizador, $this> Relação com o utilizador.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function utilizador(): BelongsTo
    {
        return $this->belongsTo(
            Utilizador::class,
            'utilizador_id',
        );
    }

    /**
     * Obtém a entidade associada à avaliação.
     *
     * Os aliases polimórficos permitidos são:
     *
     * - `metal_thursday`;
     * - `seccao_metal_thursday`.
     *
     * @return MorphTo<Model, $this> Relação com a entidade avaliada.
     *
     * @since 1.0.0
     *
     * @version 3.0.0
     */
    public function avaliavel(): MorphTo
    {
        return $this->morphTo(
            name: 'avaliavel',
            type: 'tipo_avaliavel',
            id: 'avaliavel_id',
        );
    }
}
