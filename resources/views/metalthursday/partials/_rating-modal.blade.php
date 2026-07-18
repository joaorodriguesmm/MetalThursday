<div class="modal fade" id="ratingModal" tabindex="-1" aria-labelledby="ratingModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content bg-dark text-white">
            <form id="rating-form" method="POST">
                @csrf
                <div class="modal-header border-secondary">
                    <h5 class="modal-title" id="ratingModalLabel">Avaliar</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <input type="hidden" name="rateableType" id="rateable-type-hidden">
                    <input type="hidden" name="rateableId" id="rateable-id-hidden">

                    <p class="mb-2 small" id="rateable-name"></p>
                    <div class="rating-stars-10-interactive" id="interactive-stars">
                        @for ($i = 1; $i <= 10; $i++)
                            <i class="bi bi-star" data-value="{{ $i }}" title="{{ $i }}/10"></i>
                        @endfor
                    </div>
                    <div class="mt-2" id="rating-live-feedback" style="min-height: 24px;"></div>
                    <input type="hidden" name="rating" id="rating-value-hidden">
                </div>
                <div class="modal-footer border-secondary">
                    <button type="submit" class="btn btn-primary w-100">Submeter Avaliação</button>
                </div>
            </form>
        </div>
    </div>
</div>
