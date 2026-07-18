<?php

namespace App\Filters;

use App\Models\Genre;
use App\Models\MetalThursday;
use App\Models\MtSection;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Filtra as MetalThursdays de acordo com os parâmetros da URL.
 *
 * @since 1.0
 * @version 1.0
 */
class MetalThursdayFilters
{
    protected $request;
    protected $builder;
    protected $modelType;

    /**
     * Instancia a classe.
     *
     * @param Request $request - Pedido HTTP.
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Aplica os filtros.
     *
     * @param Builder $builder - Query Builder.
     * @return Builder - Query Builder.
     *
     * @since 1.0
     * @version 1.0
     */
    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;
        $this->modelType = get_class($builder->getModel());

        foreach ($this->request->query() as $filter => $value) {
            if (str_starts_with($filter, 'filter_') && $value) {
                $method = substr($filter, 7);
                if (method_exists($this, $method)) {
                    $this->$method($value);
                }
            }
        }

        $this->sort();

        return $this->builder;
    }

    /**
     * Aplica a ordenação à query.
     *
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    protected function sort(): void
    {
        $sortBy = $this->request->query('sort_by', 'date');
        $direction = $this->request->query('sort_direction', 'desc');

        if (!in_array($direction, ['asc', 'desc'])) {
            $direction = 'desc';
        }

        switch ($sortBy) {
            case 'rating':
                $this->builder->orderBy('ratings_avg_rating', $direction);
                break;
            case 'my_rating':
                $this->myRatingSort($direction);
                break;
            case 'date':
            default:
                $column = ($this->modelType === MtSection::class) ? 'parent_date' : 'date';
                $this->builder->orderBy($column, $direction);
                break;
        }
    }

    /**
     * Ordena pela avaliação do utilizador autenticado.
     *
     * @param string $direction - Direção da ordenação.
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    protected function myRatingSort(string $direction): void
    {
        if (!Auth::check()) {
            return;
        }

        $userId = Auth::id();
        $rateableType = addslashes($this->modelType);
        $tableName = $this->builder->getModel()->getTable();
        $tableKey = $tableName . '.id';

        $subQuery = "(SELECT rating FROM ratings WHERE rateable_id = {$tableKey} AND rateable_type = '{$rateableType}' AND user_id = {$userId})";

        $nullsOrder = $direction === 'asc' ? 'DESC' : 'ASC';

        $this->builder->orderByRaw("{$subQuery} IS NULL {$nullsOrder}, {$subQuery} {$direction}");
    }

    /**
     * Filtra as MetalThursdays ou secções por autor específico.
     *
     * @param int $value - Id do autor.
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    protected function author(int $value): void
    {
        if ($this->modelType === MetalThursday::class) {
            $this->builder->where('author_id', $value);
        } elseif ($this->modelType === MtSection::class) {
            $this->builder->whereHas('metalThursday', fn($q) => $q->where('author_id', $value));
        }
    }

    /**
     * Filtra as MetalThursdays ou secções por banda específica.
     *
     * @param int $value - Id da banda.
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    protected function band(int $value): void
    {
        if ($this->modelType === MetalThursday::class) {
            $this->builder->whereHas('sections.band', fn($q) => $q->where('bands.id', $value));
        } elseif ($this->modelType === MtSection::class) {
            $this->builder->where('band_id', $value);
        }
    }

    /**
     * Filtra as MetalThursdays ou secções por se o utilizador foi o autor.
     *
     * @param string $value - Sim ou nao.
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    protected function authored_by_me(string $value): void
    {
        if ($value === 'yes') {
            if ($this->modelType === MetalThursday::class) {
                $this->builder->where('author_id', Auth::id());
            } elseif ($this->modelType === MtSection::class) {
                $this->builder->whereHas('metalThursday', fn($q) => $q->where('author_id', Auth::id()));
            }
        } elseif ($value === 'no') {
            if ($this->modelType === MetalThursday::class) {
                $this->builder->where('author_id', '!=', Auth::id());
            } elseif ($this->modelType === MtSection::class) {
                $this->builder->whereHas('metalThursday', fn($q) => $q->where('author_id', '!=', Auth::id()));
            }
        }
    }

    /**
     * Filtra as MetalThursdays ou secções por data até data específica.
     *
     * @param string $value - Data.
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    protected function date_to(string $value): void
    {
        try {
            $dateTo = Carbon::parse($value)->endOfDay();
            if ($this->modelType === MetalThursday::class) {
                $this->builder->where('date', '<=', $dateTo);
            } elseif ($this->modelType === MtSection::class) {
                $this->builder->whereHas('metalThursday', fn($q) => $q->where('date', '<=', $dateTo));
            }
        } catch (\Exception $e) {
            Log::warning("Falha ao processar o filtro de data 'date_to'. Valor recebido: {$value}. Erro: " . $e->getMessage());
        }
    }

    /**
     * Filtra as MetalThursdays ou secções por data desde data específica.
     *
     * @param string $value - Data.
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    protected function date_from(string $value): void
    {
        try {
            $dateFrom = Carbon::parse($value)->startOfDay();
            if ($this->modelType === MetalThursday::class) {
                $this->builder->where('date', '>=', $dateFrom);
            } elseif ($this->modelType === MtSection::class) {
                $this->builder->whereHas('metalThursday', fn($q) => $q->where('date', '>=', $dateFrom));
            }
        } catch (\Exception $e) {
            Log::warning("Falha ao processar o filtro de data 'date_from'. Valor recebido: {$value}. Erro: " . $e->getMessage());
        }
    }

    /**
     * Filtra as MetalThursdays ou secções por data especifica.
     *
     * @param string $value - Data.
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    protected function date(string $value): void
    {
        try {
            $date = Carbon::parse($value)->toDateString();
            if ($this->modelType === MetalThursday::class) {
                $this->builder->whereDate('date', $date);
            } elseif ($this->modelType === MtSection::class) {
                $this->builder->whereHas('metalThursday', fn($q) => $q->whereDate('date', $date));
            }
        } catch (\Exception $e) {
            Log::warning("Falha ao processar o filtro de data 'date'. Valor recebido: {$value}. Erro: " . $e->getMessage());
        }
    }

    /**
     * Filtra as MetalThursdays ou secções por edição especifica.
     *
     * @param int $value - Id da edição.
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    protected function edition(int $value): void
    {
        if ($this->modelType === MetalThursday::class) {
            $this->builder->where('edition_id', $value);
        } elseif ($this->modelType === MtSection::class) {
            $this->builder->whereHas('metalThursday', fn($q) => $q->where('edition_id', $value));
        }
    }

    /**
     * Filtra as MetalThursdays ou secções por se o utilizador foi nomeado.
     *
     * @param string $value - Sim ou nao.
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    protected function nominated(string $value): void
    {
        if ($value === 'yes') {
            if ($this->modelType === MetalThursday::class) {
                $this->builder->where('next_nominee_id', Auth::id());
            } elseif ($this->modelType === MtSection::class) {
                $this->builder->whereHas('metalThursday', fn($q) => $q->where('next_nominee_id', Auth::id()));
            }
        } elseif ($value === 'no') {
            if ($this->modelType === MetalThursday::class) {
                $this->builder->where('next_nominee_id', '!=', Auth::id());
            } elseif ($this->modelType === MtSection::class) {
                $this->builder->whereHas('metalThursday', fn($q) => $q->where('next_nominee_id', '!=', Auth::id()));
            }
        }
    }

    /**
     * Filtra as MetalThursdays ou secções por género especifico.
     *
     * @param int $value - Id do genero.
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    protected function genre(int $value): void
    {
        $genre = Genre::with('allChildren')->find($value);

        if (!$genre) {
            return;
        }

        $genreIds = $this->getGenreIdsIncludingChildren($genre);

        if ($this->modelType === MetalThursday::class) {
            $this->builder->whereHas('sections.band.genres', function ($q) use ($genreIds) {
                $q->whereIn('genres.id', $genreIds);
            });
        } elseif ($this->modelType === MtSection::class) {
            $this->builder->whereHas('band.genres', function ($q) use ($genreIds) {
                $q->whereIn('genres.id', $genreIds);
            });
        }
    }

    /**
     * Obtém uma lista de Ids de um género e todos os seus descendentes.
     *
     * @param Genre $genre - Género.
     * @return array - Lista de Ids.
     *
     * @since 1.0
     * @version 1.0
     */
    private function getGenreIdsIncludingChildren(Genre $genre): array
    {
        $ids = [$genre->id];

        $collectIds = function ($genres) use (&$ids, &$collectIds) {
            foreach ($genres as $child) {
                $ids[] = $child->id;
                if ($child->allChildren->isNotEmpty()) {
                    $collectIds($child->allChildren);
                }
            }
        };

        $collectIds($genre->allChildren);

        return array_unique($ids);
    }

    /**
     * Filtra as MetalThursdays ou secções por se o utilizador avaliou.
     *
     * @param string $value - Sim ou nao.
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    protected function rated(string $value): void
    {
        if ($value === 'yes') {
            $this->builder->whereHas('ratings', fn($q) => $q->where('user_id', Auth::id()));
        } elseif ($value === 'no') {
            $this->builder->whereDoesntHave('ratings', fn($q) => $q->where('user_id', Auth::id()));
        }
    }

    /**
     * Filtra as MetalThursdays ou secções por se o utilizador ouviu.
     *
     * @param string $value - Sim ou nao.
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    protected function listened(string $value): void
    {
        if ($value === 'yes') {
            $this->builder->whereHas('listens', fn($q) => $q->where('user_id', Auth::id()));
        } elseif ($value === 'no') {
            $this->builder->whereDoesntHave('listens', fn($q) => $q->where('user_id', Auth::id()));
        }
    }
}
