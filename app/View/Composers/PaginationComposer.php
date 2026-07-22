<?php

namespace App\View\Composers;

use App\Http\Middleware\TranslateUrlParameters;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class PaginationComposer
{
    public function compose(View $view)
    {
        // Obtém a instância do paginador que está a ser renderizada
        $paginator = $view->paginator;

        // Se não for o nosso paginador principal, não faz nada
        if (! $paginator instanceof LengthAwarePaginator) {
            return;
        }

        // Obtém os parâmetros da query atual (que estão em inglês)
        $queryParams = request()->query();

        // Obtém o mapa de tradução inverso (inglês -> português)
        $reverseMap = TranslateUrlParameters::getReverseParamMap();

        $translatedParams = [];

        foreach ($queryParams as $key => $value) {
            // Se a chave em inglês existir no mapa, usa a sua versão em português
            if (isset($reverseMap[$key])) {
                $translatedParams[$reverseMap[$key]] = $value;
            } else {
                // Mantém outros parâmetros que não são traduzidos (como 'page')
                if ($key !== 'page') {
                    $translatedParams[$key] = $value;
                }
            }
        }

        // Adiciona os parâmetros traduzidos a todos os links da paginação
        $paginator->appends($translatedParams);
    }
}
