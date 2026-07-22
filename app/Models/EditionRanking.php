<?php

namespace App\Models;

use App\Models\Autenticacao\Utilizador;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EditionRanking extends Model
{
    protected $table = 'edition_rankings';

    protected $fillable = ['edition_id', 'user_id', 'entry_text', 'submitted_by'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(Utilizador::class);
    }

    public function edition(): BelongsTo
    {
        return $this->belongsTo(MtEdition::class, 'edition_id');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(Utilizador::class, 'submitted_by');
    }
}
