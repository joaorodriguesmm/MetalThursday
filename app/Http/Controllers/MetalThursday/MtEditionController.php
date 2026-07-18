<?php

namespace App\Http\Controllers\MetalThursday;

use App\Http\Controllers\Controller;
use App\Http\Requests\MetalThursday\StoreEditionRequest;
use App\Http\Requests\MetalThursday\UpdateEditionRequest;
use App\Models\EditionRanking;
use App\Models\MtEdition;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MtEditionController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('viewAny', MtEdition::class);
        $editions = MtEdition::latest()->paginate(20);
        return view('entities.editions.index', compact('editions'));
    }

    public function create(): View
    {
        $this->authorize('create', MtEdition::class);
        return view('entities.editions.create');
    }

    public function store(StoreEditionRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorize('create', MtEdition::class);
        $validated = $request->validated();
        $edition = MtEdition::create($validated);

        if ($request->wantsJson()) {
            return response()->json($edition->append('display_text'));
        }
        return redirect()->route('editions.index')->with('success', 'Edição criada com sucesso!');
    }

    public function edit(MtEdition $edition): View
    {
        $this->authorize('update', $edition);
        return view('entities.editions.edit', compact('edition'));
    }

    public function update(UpdateEditionRequest $request, MtEdition $edition): RedirectResponse
    {
        $this->authorize('update', $edition);
        $validated = $request->validated();
        $edition->update($validated);
        return redirect()->route('editions.index')->with('success', 'Edição atualizada com sucesso!');
    }

    public function destroy(MtEdition $edition): RedirectResponse
    {
        $this->authorize('delete', $edition);
        $edition->delete();
        return redirect()->route('editions.index')->with('success', 'Edição eliminada com sucesso!');
    }

    public function show(MtEdition $edition): View
    {
        $this->authorize('view', $edition);

        $rankings = $edition->rankings->groupBy('user_id');
        $users = User::selectable()->get();

        $totalEntries = $rankings->flatten()->count();
        $isLocked = $totalEntries >= ($users->count() * 3);

        return view('entities.editions.show', compact('edition', 'rankings', 'users', 'isLocked'));
    }

    public function storeRanking(Request $request, MtEdition $edition)
    {
        $this->authorize('update', $edition);

        $validated = $request->validate([
            'rankings.*' => 'present|array|size:3',
            'rankings.*.*' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($validated, $edition) {
            foreach ($validated['rankings'] as $userId => $entries) {
                EditionRanking::where('edition_id', $edition->id)->where('user_id', $userId)->delete();

                foreach ($entries as $entryText) {
                    if (!empty($entryText)) {
                        EditionRanking::create([
                            'edition_id' => $edition->id,
                            'user_id' => $userId,
                            'entry_text' => $entryText,
                            'submitted_by' => auth()->id(),
                        ]);
                    }
                }
            }
        });

        return back()->with('success', 'Rankings guardados com sucesso!');
    }

    public function updateLink(Request $request, MtEdition $edition)
    {
        $this->authorize('update', $edition);

        $validated = $request->validate([
            'compilation_link' => ['nullable', 'url', 'max:2048'],
        ]);

        $edition->update($validated);

        return back()->with('success', 'Link da compilação atualizado com sucesso!');
    }
}
