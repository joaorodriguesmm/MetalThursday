<?php

namespace App\Http\Controllers\Entities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entities\StoreBandRequest;
use App\Http\Requests\Entities\UpdateBandRequest;
use App\Models\Band;
use App\Models\Country;
use App\Models\Genre;
use App\Models\MtSection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BandController extends Controller
{
    use AuthorizesRequests;

    /**
     * Apresenta uma lista do recurso.
     */
    public function index(): View
    {
        $this->authorize('viewAny', Band::class);
        $query = Band::with('country', 'genres')->orderBy('name', 'asc');

        if (request('search')) {
            $query->where('name', 'like', '%' . request('search') . '%');
        }

        $bands = $query->paginate(20)->withQueryString();
        return view('entities.bands.index', compact('bands'));
    }

    /**
     * Apresenta o formulário para criar um novo recurso.
     */
    public function create(): View
    {
        $this->authorize('create', Band::class);
        return view('entities.bands.create', [
            'countries' => Country::orderBy('name')->get(),
            'genres' => Genre::orderBy('name')->get(),
        ]);
    }

    /**
     * Guarda um novo recurso na base de dados.
     */
    public function store(StoreBandRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorize('create', Band::class);

        $validated = $request->validated();
        $band = DB::transaction(function () use ($validated) {
            $band = Band::create([
                'name' => $validated['name'],
                'country_id' => $validated['country_id'],
            ]);
            $band->genres()->attach($validated['genres']);
            return $band;
        });

        if ($request->wantsJson()) {
            return response()->json($band->load('country'));
        }
        return redirect()->route('bands.index')->with('success', 'Banda criada com sucesso!');
    }

    /**
     * Apresenta o formulário para editar o recurso especificado.
     */
    public function edit(Band $band): View
    {
        $this->authorize('update', $band);
        return view('entities.bands.edit', [
            'band' => $band,
            'countries' => Country::orderBy('name')->get(),
            'genres' => Genre::orderBy('name')->get(),
        ]);
    }

    /**
     * Atualiza o recurso especificado na base de dados.
     */
    public function update(UpdateBandRequest $request, Band $band): RedirectResponse
    {
        $this->authorize('update', $band);

        $validated = $request->validated();
        DB::transaction(function () use ($validated, $band) {
            $band->update([
                'name' => $validated['name'],
                'country_id' => $validated['country_id'],
            ]);
            $band->genres()->sync($validated['genres']);
        });

        return redirect()->route('bands.index')->with('success', 'Banda atualizada com sucesso!');
    }

    /**
     * Remove o recurso especificado da base de dados.
     */
    public function destroy(Band $band): RedirectResponse
    {
        $this->authorize('delete', $band);

        $band->delete();
        return redirect()->route('bands.index')->with('success', 'Banda eliminada com sucesso!');
    }

    public function show(Band $band): View
    {
        $this->authorize('view', $band);

        $sections = MtSection::where('band_id', $band->id)
            ->with('metalThursday.author', 'sectionType')
            ->get()
            ->sortByDesc('metalThursday.date');

        return view('entities.bands.show', compact('band', 'sections'));
    }
}
