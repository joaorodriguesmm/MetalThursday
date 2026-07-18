@props(['commentable', 'commentableType'])

<div class="card card-body bg-dark border-secondary">
    <form id="comment-form-{{ $commentableType }}-{{ $commentable->id }}" action="{{ route('comments.store', ['commentableType' => $commentableType, 'commentableId' => $commentable->id]) }}" method="POST" class="comment-form mb-4" data-ajax-form data-success-message="Comentário publicado!" novalidate>
        @csrf
        <div class="d-flex align-items-start">
            <div class="me-3 flex-shrink-0">
                <x-avatar :user="Auth::user()" :size="40" />
            </div>
            <div class="flex-grow-1 form-field-group">
                <textarea name="content" class="form-control bg-secondary text-white border-secondary" rows="2" placeholder="Deixa o teu comentário" required></textarea>
                <div class="invalid-feedback mt-1"></div>
                <div class="text-end mt-2">
                    <button type="submit" class="btn btn-sm btn-primary">Comentar</button>
                </div>
            </div>
        </div>
    </form>

    <div class="comments-list">
        @forelse ($commentable->comments()->whereNull('parent_id')->withCount('likes')->oldest()->get() as $comment)
            <x-comment :comment="$comment" />
        @empty
            <p class="text-muted text-center small no-comments-placeholder">Ainda não existem comentários. Sê o primeiro a comentar!</p>
        @endforelse
    </div>
</div>
