@props(['comment'])

<div class="comment-container mt-3" id="comment-{{ $comment->id }}">
    <div class="d-flex align-items-start">
        <div class="me-3 flex-shrink-0">
            <x-avatar :user="$comment->user" :size="40" />
        </div>
        <div class="flex-grow-1">
            <div class="bg-secondary p-3 rounded">
                <div class="d-flex justify-content-between">
                    <h6 class="mb-0 text-white">{{ $comment->user->name }}</h6>
                    <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
                </div>
                <div class="comment-content mt-2">
                    <p class="text-white-50 mb-0">{!! nl2br(e($comment->content)) !!}</p>
                </div>
                <div class="comment-edit-form mt-2" style="display: none;">
                    <form id="edit-form-{{ $comment->id }}" data-url="{{ route('comments.update', $comment) }}">
                        <div class="form-field-group">
                            <textarea name="content" class="form-control form-control-sm bg-dark text-white border-secondary" rows="3" required>{{ $comment->content }}</textarea>
                            <div class="invalid-feedback mt-1"></div>
                        </div>
                        <div class="text-end mt-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-interaction-type="edit-cancel">Cancelar</button>
                            <button type="submit" class="btn btn-sm btn-primary">Guardar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="comment-actions small mt-2 d-flex align-items-center">
                <button class="btn btn-sm btn-link text-muted text-decoration-none p-0" data-interaction-type="like" data-url="{{ route('likes.toggle', $comment) }}">
                    <span class="likes-count-wrapper"
                        data-bs-toggle="tooltip"
                        data-bs-html="true"
                        data-comment-id="{{ $comment->id }}"
                        title="A carregar...">

                        <i class="bi {{ $comment->is_liked_by_user ? 'bi-heart-fill text-danger' : 'bi-heart' }}"></i>
                        Gosto
                        (<span class="current-count">{{ $comment->likes_count }}</span>)
                    </span>
                </button>
                <span class="text-muted mx-1">&middot;</span>
                <button class="btn btn-sm btn-link text-muted text-decoration-none" data-interaction-type="toggle-reply">Responder</button>
                @can('update', $comment)
                    <span class="text-muted mx-1">&middot;</span>
                    <button class="btn btn-sm btn-link text-muted text-decoration-none" data-interaction-type="edit-start">Editar</button>
                @endcan
                @can('delete', $comment)
                    <span class="text-muted mx-1">&middot;</span>
                    <button class="btn btn-sm btn-link text-danger text-decoration-none" data-interaction-type="delete" data-url="{{ route('comments.destroy', $comment) }}" data-removable-parent=".comment-container">Eliminar</button>
                @endcan
            </div>

            <div class="reply-form-container mt-3" style="display: none;">
                <form id="reply-form-{{ $comment->id }}" action="{{ route('comments.reply.store', $comment) }}" method="POST" class="comment-form" data-ajax-form data-success-message="Resposta publicada!" novalidate>
                    @csrf
                    <div class="d-flex align-items-start">
                        <div class="me-3 flex-shrink-0">
                            <x-avatar :user="Auth::user()" :size="30" />
                        </div>
                        <div class="flex-grow-1 form-field-group">
                            <textarea name="content" class="form-control form-control-sm bg-secondary text-white border-secondary" rows="1" placeholder="Deixa a tua resposta" required></textarea>
                            <div class="invalid-feedback mt-1"></div>
                            <div class="text-end mt-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-interaction-type="cancel-reply">Cancelar</button>
                                <button type="submit" class="btn btn-sm btn-primary">Responder</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="replies-container ms-4 border-start ps-3 mt-3">
                @foreach ($comment->replies()->withCount('likes')->oldest()->get() as $reply)
                    <x-comment :comment="$reply" />
                @endforeach
            </div>
        </div>
    </div>
</div>
