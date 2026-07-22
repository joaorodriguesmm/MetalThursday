<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Gere as bandas musicais e as respetivas relações.
 *
 * O nome físico da tabela permanece temporariamente em inglês para garantir
 * compatibilidade com a estrutura atual da base de dados.
 *
 * @property int $id
 * @property string $name
 * @property int $country_id
 * @property-read Country $pais
 * @property-read Collection<int, Genero> $generos
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
class Banda extends Model
{
    use Blameable;
    use SoftDeletes;

    /**
     * Nome físico atual da tabela das bandas.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $table = 'bands';

    /**
     * Nome físico atual da tabela intermédia entre bandas e géneros.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TABELA_BANDAS_GENEROS = 'band_genre';

    /**
     * Atributos que podem ser preenchidos em massa.
     *
     * Os nomes físicos permanecem temporariamente em inglês para garantir
     * compatibilidade com a estrutura atual da base de dados.
     *
     * @var array<int, string>
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected $fillable = [
        'name',
        'country_id',
        'created_by',
        'updated_by',
    ];

    /**
     * Define as conversões automáticas dos atributos.
     *
     * @return array<string, string> - Conversões dos atributos do modelo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function casts(): array
    {
        return [
            'country_id' => 'integer',
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    /**
     * Obtém o país da banda.
     *
     * A classe `Country` será substituída por `Pais` quando o respetivo
     * modelo for analisado.
     *
     * @return BelongsTo<Country, $this> - Relação com o país.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function pais(): BelongsTo
    {
        return $this->belongsTo(
            Country::class,
            'country_id',
        );
    }

    /**
     * Obtém os géneros associados à banda.
     *
     * A tabela intermédia e as respetivas chaves são indicadas explicitamente
     * para que a relação não dependa das convenções associadas aos nomes
     * portugueses dos modelos.
     *
     * @return BelongsToMany<Genero, $this> - Relação com os géneros.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function generos(): BelongsToMany
    {
        return $this->belongsToMany(
            Genero::class,
            self::TABELA_BANDAS_GENEROS,
            'band_id',
            'genre_id',
        );
    }

    /**
     * Obtém o país da banda.
     *
     * Este método mantém compatibilidade temporária com o nome utilizado na
     * versão 1.0.0.
     *
     * @return BelongsTo<Country, $this> - Relação com o país.
     *
     * @deprecated Utilizar {@see pais()}.
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function country(): BelongsTo
    {
        return $this->pais();
    }

    /**
     * Obtém os géneros associados à banda.
     *
     * Este método mantém compatibilidade temporária com o nome utilizado na
     * versão 1.0.0.
     *
     * @return BelongsToMany<Genero, $this> - Relação com os géneros.
     *
     * @deprecated Utilizar {@see generos()}.
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function genres(): BelongsToMany
    {
        return $this->generos();
    }
}
