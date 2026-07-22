<?php

namespace App\Http\Controllers\MetalThursday;

use App\Filters\MetalThursdayFilters;
use App\Http\Controllers\Controller;
use App\Http\Middleware\TranslateUrlParameters;
use App\Http\Requests\MetalThursday\StoreMetalThursdayRequest;
use App\Models\Band;
use App\Models\Country;
use App\Models\Genre;
use App\Models\MetalThursday;
use App\Models\MtEdition;
use App\Models\MtSection;
use App\Models\MtSectionType;
use App\Models\Autenticacao\Utilizador;
use App\Notifications\NewMetalThursdayCreated;
use App\Notifications\UserNominated;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

/**
 * Gere as MetalThursdays.
 *
 * @since 1.0
 *
 * @version 1.0
 */
class MetalThursdayController extends Controller
{
    use AuthorizesRequests;

    /**
     * Apresenta a página inicial (Listagem de MetalThursdays).
     *
     * @param  Request  $request  - Pedido HTTP.
     * @param  MetalThursdayFilters  $filters  - Filtros de MetalThursdays.
     * @return View - Página inicial.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function index(Request $request, MetalThursdayFilters $filters): View
    {
        $perPageOptions = [5, 10, 20, 50];
        $perPage = in_array($request->input('per_page', 10), $perPageOptions) ? $request->input('per_page', 10) : 10;
        $viewType = $request->input('view', 'full');
        $metalThursdays = null;
        $simplifiedSections = null;

        if ($viewType === 'simplified') {
            $query = MtSection::query()
                ->with(['metalThursday.author', 'band.country', 'band.genres', 'sectionType', 'ratings.user', 'listens.user'])
                ->withCount(['ratings', 'listens'])
                ->withAvg('ratings', 'rating')
                ->whereHas('sectionType', fn($q) => $q->where('has_details', true));

            $query->addSelect([
                'parent_date' => MetalThursday::select('date')
                    ->whereColumn('id', 'mt_sections.metal_thursday_id')
                    ->limit(1),
            ]);
        } else {
            $query = MetalThursday::query()
                ->withCount(['comments', 'ratings', 'listens'])->withAvg('ratings', 'rating')
                ->with([
                    'edition',
                    'author',
                    'nextNominee',
                    'ratings.user',
                    'listens.user',
                    'userRatingRelation',
                    'userListenRelation',
                    'sections' => function ($query) {
                        $query->withCount(['comments', 'ratings', 'listens'])
                            ->withAvg('ratings', 'rating')
                            ->with(['sectionType', 'band', 'ratings.user', 'listens.user', 'userRatingRelation', 'userListenRelation'])
                            ->orderBy('id');
                    },
                ]);
        }

        $query = $filters->apply($query);

        $paginationParams = [];
        if ($request->query()) {
            $reverseParamMap = TranslateUrlParameters::getReverseParamMap();
            foreach ($request->query() as $key => $value) {
                $translatedKey = $reverseParamMap[$key] ?? $key;
                $paginationParams[$translatedKey] = $value;
            }
        }

        if ($viewType === 'simplified') {
            $simplifiedSections = $query->paginate($perPage)->appends($paginationParams);
        } else {
            $metalThursdays = $query->paginate($perPage)->appends($paginationParams);
        }

        $filterConfig = config('filters.params');
        $currentSortBy = request('sort_by', 'date');
        $currentSortDir = request('sort_direction', 'desc');
        $sortByOptions = collect($filterConfig['sort_by']['options']);
        $sortDirOptions = collect($filterConfig['sort_direction']['options']);
        $currentSortByValue = $sortByOptions->firstWhere('key', $currentSortBy)['value'] ?? $sortByOptions->first()['value'];
        $currentSortDirValue = $sortDirOptions->firstWhere('key', $currentSortDir)['value'] ?? $sortDirOptions->first()['value'];

        $viewParams = [
            'view' => [
                'name' => $filterConfig['view']['param'],
                'simplified' => $filterConfig['view']['values']['simplified'],
                'full' => $filterConfig['view']['values']['full'],
            ],
            'per_page' => ['name' => $filterConfig['per_page']['param']],
            'sort_by' => [
                'name' => $filterConfig['sort_by']['param'],
                'options' => $sortByOptions,
                'current' => $currentSortByValue,
            ],
            'sort_direction' => [
                'name' => $filterConfig['sort_direction']['param'],
                'options' => $sortDirOptions,
                'current' => $currentSortDirValue,
            ],
        ];

        return view('metalthursday.index', [
            'metalThursdays' => $metalThursdays,
            'simplifiedSections' => $simplifiedSections,
            'editions' => MtEdition::orderBy('name')->get(),
            'users' => Utilizador::selectable()->get(),
            'bands' => Band::orderBy('name')->get(),
            'genres' => Genre::select('genres.*')->distinct()->orderBy('name')->get(),
            'perPageOptions' => $perPageOptions,
            'perPage' => $perPage,
            'viewType' => $viewType,
            'availableFilters' => config('filters.metalthursday', []),
            'viewParams' => $viewParams,
        ]);
    }

    /**
     * Apresenta a página de criação de MetalThursday.
     *
     * @return View - Página de criação de MetalThursday.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function create(): View
    {
        $editions = MtEdition::orderBy('start_date', 'desc')->get();
        $users = Utilizador::selectable()->get();
        $sectionTypes = MtSectionType::orderBy('name')->get();
        $bands = Band::orderBy('name')->get();
        $countries = Country::orderBy('name')->get();
        $genres = Genre::select('genres.*')->distinct()->orderBy('name')->get();

        return view('metalthursday.create', compact('editions', 'users', 'sectionTypes', 'bands', 'countries', 'genres'));
    }

    /**
     * Processa o formulário de criação de MetalThursday.
     *
     * @param  StoreMetalThursdayRequest  $request  - Pedido de criação de MetalThursday.
     * @return RedirectResponse - Redirecionamento para a página de listagem de MetalThursdays ou para a página de criação de MetalThursday.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function store(StoreMetalThursdayRequest $request): RedirectResponse
    {
        $validatedData = $request->validated();
        $creator = $request->user();

        try {
            $metalThursday = DB::transaction(function () use ($validatedData) {
                $mt = MetalThursday::create([
                    'edition_id' => $validatedData['edition_id'],
                    'date' => $validatedData['date'],
                    'name' => $validatedData['name'] ?? null,
                    'author_id' => $validatedData['author_id'],
                    'next_nominee_id' => $validatedData['next_nominee_id'],
                ]);

                foreach ($validatedData['sections'] as $sectionData) {
                    $sectionType = MtSectionType::find($sectionData['type_id']);
                    $mt->sections()->create([
                        'section_type_id' => $sectionData['type_id'],
                        'band_id' => $sectionType->has_details ? ($sectionData['band_id'] ?? null) : null,
                        'title' => $sectionType->has_details ? ($sectionData['title'] ?? null) : null,
                        'link' => $sectionType->has_details ? ($sectionData['link'] ?? null) : null,
                        'embed_type' => $sectionType->has_details ? ($sectionData['embed_type'] ?? 'link') : null,
                        'year' => $sectionType->has_details ? ($sectionData['year'] ?? null) : null,
                        'description' => $sectionData['description'],
                    ]);
                }

                return $mt;
            });

            if ($metalThursday) {
                $metalThursday->load('author', 'nextNominee');

                $nominee = $metalThursday->nextNominee;
                if ($nominee) {
                    $nominee->notify(new UserNominated($metalThursday));
                }

                $recipients = Utilizador::selectable()
                    ->where('id', '!=', $creator->id)
                    ->where('id', '!=', $nominee?->id)
                    ->get();

                Notification::send($recipients, new NewMetalThursdayCreated($metalThursday));
            }

            return redirect()
                ->route('home')
                ->with('success', 'MetalThursday criada com sucesso!');
        } catch (\Exception $e) {
            Log::error('Falha ao criar MetalThursday: ' . $e->getMessage());

            return back()
                ->withInput()
                ->with('error', 'Ocorreu um erro inesperado ao guardar a MetalThursday.');
        }
    }

    /**
     * Apresenta a página de edição de MetalThursday.
     *
     * @param  int  $metalThursdayId  - Id da MetalThursday a editar.
     * @return View - Página de edição de MetalThursday.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function edit(int $metalThursdayId): View
    {
        $metalThursday = MetalThursday::findOrFail($metalThursdayId);
        $this->authorize('update', $metalThursday);

        $editions = MtEdition::orderBy('start_date', 'desc')->get();
        $users = Utilizador::selectable()->get();
        $sectionTypes = MtSectionType::orderBy('name')->get();
        $bands = Band::orderBy('name')->get();
        $countries = Country::orderBy('name')->get();
        $genres = Genre::select('genres.*')->distinct()->orderBy('name')->get();

        return view('metalthursday.edit', compact('metalThursday', 'editions', 'users', 'sectionTypes', 'bands', 'countries', 'genres'));
    }

    /**
     * Processa o formulário de edição de MetalThursday.
     *
     * @param  StoreMetalThursdayRequest  $request  - Pedido de edição de MetalThursday.
     * @param  int  $metalThursdayId  - Id da MetalThursday a editar.
     * @return RedirectResponse - Redirecionamento para a página de listagem de MetalThursdays.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function update(StoreMetalThursdayRequest $request, int $metalThursdayId): RedirectResponse
    {
        $metalThursday = MetalThursday::findOrFail($metalThursdayId);
        $this->authorize('update', $metalThursday);

        $validatedData = $request->validated();

        DB::transaction(function () use ($validatedData, $metalThursday) {
            $metalThursday->update([
                'edition_id' => $validatedData['edition_id'],
                'date' => $validatedData['date'],
                'name' => $validatedData['name'] ?? null,
                'author_id' => $validatedData['author_id'],
                'next_nominee_id' => $validatedData['next_nominee_id'],
            ]);

            $sectionIdsFromRequest = collect($validatedData['sections'])->pluck('id')->filter();
            $metalThursday->sections()->whereNotIn('id', $sectionIdsFromRequest)->delete();

            foreach ($validatedData['sections'] as $sectionData) {
                $sectionType = MtSectionType::find($sectionData['type_id']);
                $metalThursday->sections()->updateOrCreate(
                    ['id' => $sectionData['id'] ?? null],
                    [
                        'section_type_id' => $sectionData['type_id'],
                        'band_id' => $sectionType->has_details ? ($sectionData['band_id'] ?? null) : null,
                        'title' => $sectionType->has_details ? ($sectionData['title'] ?? null) : null,
                        'link' => $sectionType->has_details ? ($sectionData['link'] ?? null) : null,
                        'embed_type' => $sectionType->has_details ? ($sectionData['embed_type'] ?? 'link') : null,
                        'year' => $sectionType->has_details ? ($sectionData['year'] ?? null) : null,
                        'description' => $sectionData['description'],
                    ]
                );
            }
        });

        return redirect()
            ->route('home')
            ->with('success', 'MetalThursday atualizada com sucesso!');
    }

    /**
     * Elimina uma MetalThursday.
     *
     * @param  int  $metalThursdayId  - Id da MetalThursday a eliminar.
     * @return JsonResponse - Mensagem de sucesso.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function destroy(int $metalThursdayId): JsonResponse
    {
        $metalThursday = MetalThursday::findOrFail($metalThursdayId);
        $this->authorize('delete', $metalThursday);
        $metalThursday->delete();

        return response()->json([
            'success' => true,
            'message' => 'MetalThursday eliminada com sucesso!',
        ]);
    }

    /**
     * Obtém o utilizador que não é nomeado há mais tempo na MetalThursday.
     *
     * @return JsonResponse - Utilizador que não é nomeado há mais tempo.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function getLongestNotNominatedUser(): JsonResponse
    {
        $eligibleUsers = Utilizador::selectable()->pluck('id');
        $lastNominated = MetalThursday::select('next_nominee_id', DB::raw('MAX(created_at) as last_nominated_at'))
            ->whereIn('next_nominee_id', $eligibleUsers)
            ->groupBy('next_nominee_id');
        $usersWithNominationDate = Utilizador::leftJoinSub($lastNominated, 'last_nominations', function ($join) {
            $join->on('users.id', '=', 'last_nominations.next_nominee_id');
        })
            ->whereIn('users.id', $eligibleUsers)
            ->orderBy('last_nominations.last_nominated_at', 'asc')
            ->select('users.id')
            ->first();

        return response()->json($usersWithNominationDate);
    }

    /**
     * Apresenta a página de detalhes de uma MetalThursday.
     *
     * @param  MetalThursday  $metalThursday  - MetalThursday a apresentar.
     * @return View - Página de detalhes de MetalThursday.
     *
     * @since 1.0
     *
     * @version 1.0
     */
    public function show(MetalThursday $metalThursday): View
    {
        $metalThursday->loadCount(['comments', 'ratings', 'listens'])
            ->loadAvg('ratings', 'rating')
            ->load([
                'edition',
                'author',
                'nextNominee',
                'creator',
                'ratings.user',
                'listens.user',
                'sections' => function ($query) {
                    $query->withCount(['comments', 'ratings', 'listens'])
                        ->withAvg('ratings', 'rating')
                        ->with('ratings.user', 'listens.user', 'sectionType', 'band')
                        ->orderBy('id');
                },
            ]);

        return view('metalthursday.show', ['mt' => $metalThursday]);
    }
}
