<?php

namespace App\Http\Controllers\Interactions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entities\StoreRatingRequest;
use App\Models\MetalThursday;
use App\Models\MtSection;
use App\Traits\NotifiesUsers;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Gere as avaliações.
 *
 * @since 1.0
 * @version 1.0
 */
class RatingController extends Controller
{
    use NotifiesUsers;

    /**
     * Guarda uma avaliação.
     *
     * @param StoreRatingRequest $request - Pedido de submissão de avaliação.
     * @param string $rateableType - Tipo de destino da avaliação.
     * @param int $rateableId - Id do tipo de destino.
     * @return JsonResponse - Resposta JSON.
     *
     * @since 1.0
     * @version 1.0
     */
    public function store(StoreRatingRequest $request, string $rateableType, int $rateableId): JsonResponse
    {
        $validated = $request->validated();
        $modelClass = $rateableType === 'section' ? MtSection::class : MetalThursday::class;
        $rateable = $modelClass::findOrFail($rateableId);

        $rateable->ratings()->updateOrCreate(
            ['user_id' => $request->user()->id],
            ['rating'  => $validated['rating']]
        );

        if ($rateable instanceof MtSection) {
            $rateable->load('metalThursday.author');
        } else {
            $rateable->load('author');
        }
        $this->notifyOtherUsers($rateable, 'avaliou');

        $rateable->load('ratings.user');
        $tooltipContent = $rateable->ratings->map(fn($r) => e($r->user->name) . ': ' . number_format($r->rating, 1))->join('<br>');

        return response()->json([
            'average_rating' => number_format($rateable->ratings->avg('rating'), 1),
            'ratings_count'  => $rateable->ratings->count(),
            'user_rating'    => (float) $validated['rating'],
            'tooltip_html'   => $tooltipContent ?: 'Ainda sem avaliações.',
        ]);
    }
}
