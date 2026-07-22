<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Traduz os parâmetros da URL para português.
 *
 * @since 1.0
 *
 * @version 1.0
 */
class TranslateUrlParameters
{
    protected array $paramMap;

    protected array $valueMap;

    protected array $filterMap;

    /**
     * Cria um novo middleware de tradução de parâmetros da URL.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function __construct()
    {
        $this->paramMap = $this->buildParamMap();
        $this->valueMap = $this->buildValueMap();
        $this->filterMap = $this->buildFilterMap();
    }

    /**
     * Traduz os parâmetros da URL para português.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function handle(Request $request, Closure $next): Response
    {
        $translatedQuery = [];

        foreach ($request->query() as $param => $value) {
            $translatedKey = $this->paramMap[$param] ?? $this->filterMap[$param] ?? $param;
            $translatedValue = $this->valueMap[$param][$value] ?? $value;

            $translatedQuery[$translatedKey] = $translatedValue;
        }

        $request->query->replace($translatedQuery);

        return $next($request);
    }

    /**
     * Cria o mapa de tradução dos parâmetros da URL.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    private function buildParamMap(): array
    {
        $map = [];
        foreach (config('filters.params', []) as $englishKey => $config) {
            $map[$config['param']] = $englishKey;
        }

        return $map;
    }

    /**
     * Cria o mapa de tradução dos valores dos parâmetros da URL.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    private function buildValueMap(): array
    {
        $map = [];
        foreach (config('filters.params', []) as $config) {
            if (isset($config['values'])) {
                $map[$config['param']] = array_flip($config['values']);
            }

            if (isset($config['options'])) {
                $valueMap = collect($config['options'])->pluck('key', 'value')->all();
                $map[$config['param']] = $valueMap;
            }
        }

        return $map;
    }

    /**
     * Cria o mapa de tradução dos filtros da URL.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    private function buildFilterMap(): array
    {
        $map = [];
        $filters = collect(config('filters.metalthursday', []))->flatMap(fn ($group) => $group);
        foreach ($filters as $filter) {
            $map['filtro_'.$filter['param']] = 'filter_'.$filter['key'];
        }

        return $map;
    }

    /**
     * Cria o mapa de tradução reverso dos parâmetros da URL.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public static function getReverseParamMap(): array
    {
        $reverseMap = [];
        $params = config('filters.params', []);
        $filters = collect(config('filters.metalthursday', []))->flatMap(fn ($group) => $group);

        foreach ($params as $englishKey => $config) {
            $reverseMap[$englishKey] = $config['param'];
        }

        foreach ($filters as $filter) {
            $reverseMap['filter_'.$filter['key']] = 'filtro_'.$filter['param'];
        }

        return $reverseMap;
    }
}
