<?php

declare(strict_types=1);

namespace App\Models\Geografia;

use App\Models\Musica\Banda;
use Carbon\CarbonInterface;
use Database\Factories\Geografia\PaisFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

/**
 * Representa um país disponível na aplicação.
 *
 * Os países constituem dados geográficos de referência e podem ser
 * utilizados por diferentes domínios da aplicação.
 *
 * @property int $id
 * @property string $nome
 * @property string $codigo_iso
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Collection<int, Banda> $bandas
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
class Pais extends Model
{
    /** @use HasFactory<PaisFactory> */
    use HasFactory;

    /**
     * Nome físico da tabela associada ao modelo.
     *
     * @var string
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $table = 'paises';

    /**
     * Atributos permitidos em operações de atribuição em massa.
     *
     * @var array<int, string>
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $fillable = [
        'nome',
        'codigo_iso',
    ];

    /**
     * Cria a factory associada ao modelo.
     *
     * A associação é explícita porque o modelo e a factory se encontram
     * em namespaces próprios.
     *
     * @return PaisFactory Factory dos países.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected static function newFactory(): PaisFactory
    {
        return PaisFactory::new();
    }

    /**
     * Normaliza o nome do país antes da persistência.
     *
     * @return Attribute<string, string> Atributo do nome.
     *
     * @throws InvalidArgumentException Quando o nome está vazio.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function nome(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): string {
                $nomeNormalizado =
                    trim(
                        (string) $valor,
                    );

                if ($nomeNormalizado === '') {
                    throw new InvalidArgumentException(
                        'O nome do país não pode estar vazio.',
                    );
                }

                return $nomeNormalizado;
            },
        );
    }

    /**
     * Normaliza e valida o código ISO do país.
     *
     * O código é guardado em maiúsculas e deve conter exatamente duas
     * letras, conforme o formato ISO 3166-1 alfa-2 utilizado pela aplicação.
     *
     * @return Attribute<string, string> Atributo do código ISO.
     *
     * @throws InvalidArgumentException Quando o código não é válido.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function codigoIso(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): string {
                $codigoNormalizado =
                    mb_strtoupper(
                        trim(
                            (string) $valor,
                        ),
                    );

                if (
                    preg_match(
                        '/^[A-Z]{2}$/',
                        $codigoNormalizado,
                    ) !== 1
                ) {
                    throw new InvalidArgumentException(
                        'O código ISO do país deve conter exatamente duas letras.',
                    );
                }

                return $codigoNormalizado;
            },
        );
    }

    /**
     * Obtém as bandas originárias do país.
     *
     * As bandas são ordenadas alfabeticamente pelo nome.
     *
     * @return HasMany<Banda, $this> Relação com as bandas.
     *
     * @since 1.0.0
     *
     * @version 2.1.0
     */
    public function bandas(): HasMany
    {
        return $this
            ->hasMany(
                Banda::class,
                'pais_id',
            )
            ->orderBy('nome')
            ->orderBy('id');
    }
}
