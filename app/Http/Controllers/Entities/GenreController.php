<?php

namespace App\Http\Controllers\Entities;

use App\Http\Controllers\Controller;
use App\Http\Requests\Entities\StoreGenreRequest;
use App\Http\Requests\Entities\UpdateGenreRequest;
use App\Models\Genre;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GenreController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('viewAny', Genre::class);
        $query = Genre::with('parents')->orderBy('name', 'asc');
        if (request('search')) {
            $query->where('name', 'like', '%' . request('search') . '%');
        }
        $genres = $query->paginate(20)->withQueryString();

        return view('entities.genres.index', compact('genres'));
    }

    public function create(): View
    {
        $this->authorize('create', Genre::class);
        return view('entities.genres.create', [
            'genres' => Genre::orderBy('name')->get(),
        ]);
    }

    public function store(StoreGenreRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorize('create', Genre::class);

        $validated = $request->validated();
        $genre = DB::transaction(function () use ($validated) {
            $genre = Genre::create(['name' => $validated['name']]);
            if (!empty($validated['parent_genres'])) {
                $genre->parents()->sync($validated['parent_genres']);
            }
            return $genre;
        });

        if ($request->wantsJson()) {
            return response()->json($genre);
        }
        return redirect()->route('genres.index')->with('success', 'Género criado com sucesso!');
    }

    public function edit(Genre $genre): View
    {
        $this->authorize('update', $genre);
        return view('entities.genres.edit', [
            'genre' => $genre,
            'genres' => Genre::where('id', '!=', $genre->id)->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateGenreRequest $request, Genre $genre): RedirectResponse
    {
        $this->authorize('update', $genre);

        $validated = $request->validated();
        DB::transaction(function () use ($validated, $genre) {
            $genre->update(['name' => $validated['name']]);
            $genre->parents()->sync($validated['parent_genres'] ?? []);
        });

        return redirect()->route('genres.index')->with('success', 'Género atualizado com sucesso!');
    }

    public function destroy(Genre $genre): RedirectResponse
    {
        $this->authorize('delete', $genre);
        $genre->delete();
        return redirect()->route('genres.index')->with('success', 'Género eliminado com sucesso!');
    }

    public function show(Genre $genre): View
    {
        $this->authorize('view', $genre);
        $bands = $genre->bands()->with('country', 'genres')->orderBy('name')->paginate(20);
        return view('entities.genres.show', compact('genre', 'bands'));
    }
}
