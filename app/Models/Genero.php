<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Blameable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

/**
 * Gere os géneros musicais e as respetivas relações hierárquicas.
 *
 * O nome físico da tabela permanece temporariamente em inglês para garantir
 * compatibilidade com a estrutura atual da base de dados.
 *
 * @property int $id
 * @property string $name
 * @property-read Collection<int, Genero> $pais
 * @property-read Collection<int, Genero> $filhos
 * @property-read Collection<int, Genero> $todosFilhos
 * @property-read Collection<int, Banda> $bandas
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
class Genero extends Model
{
    use Blameable;
    use SoftDeletes;

    /**
     * Nome físico atual da tabela dos géneros.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $table = 'genres';

    /**
     * Nome físico atual da tabela intermédia da hierarquia de géneros.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TABELA_HIERARQUIA = 'genre_parent_genre';

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
            'created_by' => 'integer',
            'updated_by' => 'integer',
        ];
    }

    /**
     * Obtém os géneros pais.
     *
     * @return BelongsToMany<Genero, $this> - Relação com os géneros pais.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function pais(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            self::TABELA_HIERARQUIA,
            'genre_id',
            'parent_genre_id',
        );
    }

    /**
     * Obtém os géneros filhos diretos.
     *
     * @return BelongsToMany<Genero, $this> - Relação com os géneros filhos.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function filhos(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            self::TABELA_HIERARQUIA,
            'parent_genre_id',
            'genre_id',
        );
    }

    /**
     * Obtém as bandas associadas ao género.
     *
     * @return BelongsToMany<Banda, $this> - Relação com as bandas.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function bandas(): BelongsToMany
    {
        return $this->belongsToMany(
            Banda::class,
            self::TABELA_BANDAS_GENEROS,
            'genre_id',
            'band_id',
        );
    }

    /**
     * Obtém todos os géneros filhos de forma recursiva.
     *
     * Esta relação deve ser utilizada quando for necessário obter os modelos
     * completos dos géneros descendentes.
     *
     * Para obter apenas os identificadores deve ser utilizado
     * {@see obterIdentificadoresComDescendentes()}, que evita a criação
     * desnecessária de instâncias Eloquent.
     *
     * @return BelongsToMany<Genero, $this> - Relação recursiva com os géneros
     *                                      filhos.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function todosFilhos(): BelongsToMany
    {
        return $this->filhos()->with('todosFilhos');
    }

    /**
     * Obtém os identificadores do género atual e de todos os seus
     * descendentes.
     *
     * A hierarquia é percorrida por níveis, sendo executada uma consulta por
     * profundidade da árvore em vez de uma consulta por género.
     *
     * O conjunto de identificadores já processados impede ciclos e evita que
     * o mesmo género seja devolvido mais de uma vez.
     *
     * Géneros eliminados logicamente não são incluídos nos resultados.
     *
     * @return array<int, int> - Identificadores do género e dos seus
     *                         descendentes.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function obterIdentificadoresComDescendentes(): array
    {
        $identificadorAtual = (int) $this->getKey();

        if ($identificadorAtual <= 0) {
            return [];
        }

        $identificadores = [
            $identificadorAtual => $identificadorAtual,
        ];

        $identificadoresPorProcessar = [
            $identificadorAtual,
        ];

        while ($identificadoresPorProcessar !== []) {
            $identificadoresFilhos = DB::table(
                self::TABELA_HIERARQUIA.' as hierarquia',
            )
                ->join(
                    $this->getTable().' as generos',
                    'generos.id',
                    '=',
                    'hierarquia.genre_id',
                )
                ->whereIn(
                    'hierarquia.parent_genre_id',
                    $identificadoresPorProcessar,
                )
                ->whereNull('generos.deleted_at')
                ->pluck('hierarquia.genre_id')
                ->map(
                    static fn (mixed $identificador): int => (int) $identificador,
                )
                ->unique()
                ->values()
                ->all();

            $identificadoresPorProcessar = [];

            foreach ($identificadoresFilhos as $identificadorFilho) {
                if (isset($identificadores[$identificadorFilho])) {
                    continue;
                }

                $identificadores[$identificadorFilho] =
                    $identificadorFilho;

                $identificadoresPorProcessar[] =
                    $identificadorFilho;
            }
        }

        return array_values($identificadores);
    }

    /**
     * Obtém os géneros pais.
     *
     * Este método mantém compatibilidade temporária com o nome utilizado na
     * versão 1.0.0.
     *
     * @return BelongsToMany<Genero, $this> - Relação com os géneros pais.
     *
     * @deprecated Utilizar {@see pais()}.
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function parents(): BelongsToMany
    {
        return $this->pais();
    }

    /**
     * Obtém os géneros filhos diretos.
     *
     * Este método mantém compatibilidade temporária com o nome utilizado na
     * versão 1.0.0.
     *
     * @return BelongsToMany<Genero, $this> - Relação com os géneros filhos.
     *
     * @deprecated Utilizar {@see filhos()}.
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function children(): BelongsToMany
    {
        return $this->filhos();
    }

    /**
     * Obtém as bandas associadas ao género.
     *
     * Este método mantém compatibilidade temporária com o nome utilizado na
     * versão 1.0.0.
     *
     * @return BelongsToMany<Banda, $this> - Relação com as bandas.
     *
     * @deprecated Utilizar {@see bandas()}.
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function bands(): BelongsToMany
    {
        return $this->bandas();
    }

    /**
     * Obtém todos os géneros filhos de forma recursiva.
     *
     * Este método mantém compatibilidade temporária com o nome utilizado na
     * versão 1.0.0.
     *
     * @return BelongsToMany<Genero, $this> - Relação recursiva com os géneros
     *                                      filhos.
     *
     * @deprecated Utilizar {@see todosFilhos()}.
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function allChildren(): BelongsToMany
    {
        return $this->todosFilhos();
    }
}
