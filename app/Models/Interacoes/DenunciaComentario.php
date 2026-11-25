<?php

declare(strict_types=1);

namespace App\Models\Interacoes;

use App\Models\Autenticacao\Utilizador;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Denúncia única de um comentário por utilizador.
 *
 * @property int $id
 * @property int $comentario_id
 * @property int $denunciante_id
 * @property string $motivo
 * @property string|null $detalhes
 * @property 'pendente'|'rejeitada'|'aceite' $estado
 * @property int|null $moderador_id
 * @property CarbonInterface|null $decidida_em
 * @property-read Comentario|null $comentario
 * @property-read Utilizador|null $denunciante
 */
final class DenunciaComentario extends Model
{
    protected $table = 'denuncias_comentarios';

    /** @var array<string, string> */
    protected $attributes = [
        'estado' => 'pendente',
    ];

    /** @var list<string> */
    protected $fillable = [
        'comentario_id',
        'denunciante_id',
        'motivo',
        'detalhes',
    ];

    /**
     * Comentário denunciado, incluindo registos eliminados logicamente.
     *
     * @return BelongsTo<Comentario, $this>
     */
    public function comentario(): BelongsTo
    {
        return $this->belongsTo(Comentario::class, 'comentario_id')->withTrashed();
    }

    /**
     * @return BelongsTo<Utilizador, $this>
     */
    public function denunciante(): BelongsTo
    {
        return $this->belongsTo(Utilizador::class, 'denunciante_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'comentario_id' => 'integer',
            'denunciante_id' => 'integer',
            'moderador_id' => 'integer',
            'decidida_em' => 'datetime',
        ];
    }
}
