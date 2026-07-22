<?php

namespace App\Http\Controllers\Interactions;

use App\Http\Controllers\Controller;
use App\Models\MetalThursday;
use App\Models\MtSection;
use App\Traits\NotifiesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Gere os ouvidos.
 *
 * @since 1.0
 *
 * @version 1.0
 */
class ListenController extends Controller
{
    use NotifiesUsers;

    /**
     * Marca ou desmarca o ouvido.
     *
     * @param  Request  $request  - Pedido HTTP.
     * @param  string  $listenableType  - Tipo de destino do ouvido.
     * @param  int  $listenableId  - Id do tipo de destino.
     * @return JsonResponse - Resposta JSON com o estado do ouvido e o contador de ouvidos.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function toggleListen(Request $request, string $listenableType, int $listenableId): JsonResponse
    {
        $modelClass = $listenableType === 'section' ? MtSection::class : MetalThursday::class;
        $listenable = $modelClass::findOrFail($listenableId);
        $listen = $listenable->listens()->where('user_id', Auth::id())->first();

        if ($listen) {
            $listen->delete();
            $hasHeard = false;
        } else {
            $listenable->listens()->create(['user_id' => Auth::id()]);
            $hasHeard = true;

            if ($listenable instanceof MtSection) {
                $listenable->load('metalThursday.author');
            } else {
                $listenable->load('author');
            }

            $this->notifyOtherUsers($listenable, 'marcou como ouvido');
        }

        $listenable->load('listens.user');
        $tooltipContent = $listenable->listens->map(function ($listen) {
            return e($listen->user->name);
        })->join('<br>');

        if (empty($tooltipContent)) {
            $tooltipContent = 'Ninguém marcou como ouvido.';
        }

        return response()->json([
            'has_heard' => $hasHeard,
            'listens_count' => $listenable->listens->count(),
            'tooltip_html' => $tooltipContent,
        ]);
    }
}
