<?php

use App\Http\Controllers\Entities\BandController;
use App\Http\Controllers\Entities\GenreController;
use App\Http\Controllers\MetalThursday\MetalThursdayController;
use App\Http\Controllers\MetalThursday\MtEditionController;
use App\Http\Controllers\Interactions\CommentController;
use App\Http\Controllers\Interactions\LikeController;
use App\Http\Controllers\Interactions\ListenController;
use App\Http\Controllers\Interactions\RatingController;
use App\Http\Controllers\User\NotificationController;
use Illuminate\Support\Facades\Route;

/**
 * Rotas para a funcionalidade principal da aplicação (MetalThursday).
 * Estas rotas requerem que o utilizador esteja autenticado e com o e-mail verificado.
 *
 * @since 1.0
 * @version 1.0
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/', [MetalThursdayController::class, 'index'])->name('home');

    Route::get('/metalthursday/criar', [MetalThursdayController::class, 'create'])->name('metalthursday.create');
    Route::post('/metalthursday', [MetalThursdayController::class, 'store'])->name('metalthursday.store');
    Route::get('/metalthursday/{metalThursday}', [MetalThursdayController::class, 'show'])->name('metalthursday.show');
    Route::get('/metalthursday/{metalthursday}/editar', [MetalThursdayController::class, 'edit'])->name('metalthursday.edit');
    Route::patch('/metalthursday/{metalthursday}', [MetalThursdayController::class, 'update'])->name('metalthursday.update');
    Route::delete('/metalthursday/{metalthursday}', [MetalThursdayController::class, 'destroy'])->name('metalthursday.destroy');

    // Rotas de recursos
    Route::resource('bands', BandController::class);
    Route::resource('editions', MtEditionController::class);
    Route::post('/editions/{edition}/rankings', [MtEditionController::class, 'storeRanking'])->name('editions.rankings.store');
    Route::patch('/editions/{edition}/link', [MtEditionController::class, 'updateLink'])->name('editions.link.update');
    Route::resource('genres', GenreController::class);

    // Rotas de utilizadores
    Route::get('/users/longest-not-nominated', [MetalThursdayController::class, 'getLongestNotNominatedUser'])->name('users.longest-not-nominated');

    // Rotas de interações
    Route::post('/{commentableType}/{commentableId}/comments', [CommentController::class, 'store'])->name('comments.store');
    Route::post('/comments/{comment}/replies', [CommentController::class, 'storeReply'])->name('comments.reply.store');
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');
    Route::patch('/comments/{comment}', [CommentController::class, 'update'])->name('comments.update');
    Route::post('/comments/{comment}/like', [LikeController::class, 'toggleLike'])->name('likes.toggle');
    Route::post('/{rateableType}/{rateableId}/rate', [RatingController::class, 'store'])->name('ratings.store');
    Route::post('/{listenableType}/{listenableId}/listen', [ListenController::class, 'toggleListen'])->name('listens.toggle');
    Route::get('/comments/{comment}/likers', [LikeController::class, 'getLikers'])->name('comments.likers');

    // Rotas de Notificações
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
});
