<?php

declare(strict_types=1);

namespace App\Models\MetalThursday;

use Carbon\CarbonInterface;
use Database\Factories\MetalThursday\TipoSeccaoFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

/**
 * Representa um tipo de secção de uma MetalThursday.
 *
 * O tipo de secção identifica a natureza das secções e determina se estas
 * necessitam de informação detalhada adicional.
 *
 * @property int $id
 * @property string $nome
 * @property string $descricao
 * @property bool $tem_detalhes
 * @property CarbonInterface|null $created_at
 * @property CarbonInterface|null $updated_at
 * @property-read Collection<int, SeccaoMetalThursday> $seccoes
 *
 * @since 1.0.0
 *
 * @version 2.1.0
 */
class TipoSeccao extends Model
{
    /** @use HasFactory<TipoSeccaoFactory> */
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
    protected $table = 'tipos_seccao';

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
        'descricao',
        'tem_detalhes',
    ];

    /**
     * Define as conversões automáticas dos atributos.
     *
     * @return array<string, string> Conversões dos atributos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function casts(): array
    {
        return [
            'tem_detalhes' => 'boolean',
        ];
    }

    /**
     * Cria a factory associada ao modelo.
     *
     * A associação é explícita porque o modelo e a factory se encontram em
     * namespaces próprios.
     *
     * @return TipoSeccaoFactory Factory dos tipos de secção.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected static function newFactory(): TipoSeccaoFactory
    {
        return TipoSeccaoFactory::new();
    }

    /**
     * Normaliza o nome do tipo de secção antes da persistência.
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
                $nomeNormalizado = trim(
                    (string) $valor,
                );

                if ($nomeNormalizado === '') {
                    throw new InvalidArgumentException(
                        'O nome do tipo de secção não pode estar vazio.',
                    );
                }

                return $nomeNormalizado;
            },
        );
    }

    /**
     * Normaliza a descrição do tipo de secção antes da persistência.
     *
     * A descrição é obrigatória porque a respetiva coluna não aceita valores
     * nulos.
     *
     * @return Attribute<string, string> Atributo da descrição.
     *
     * @throws InvalidArgumentException Quando a descrição está vazia.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function descricao(): Attribute
    {
        return Attribute::make(
            set: static function (
                mixed $valor,
            ): string {
                $descricaoNormalizada = trim(
                    (string) $valor,
                );

                if ($descricaoNormalizada === '') {
                    throw new InvalidArgumentException(
                        'A descrição do tipo de secção não pode estar vazia.',
                    );
                }

                return $descricaoNormalizada;
            },
        );
    }

    /**
     * Obtém as secções que utilizam este tipo.
     *
     * @return HasMany<SeccaoMetalThursday, $this> Relação com as secções.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function seccoes(): HasMany
    {
        return $this->hasMany(
            SeccaoMetalThursday::class,
            'tipo_seccao_id',
        );
    }
}
