<?php

declare(strict_types=1);

namespace App\Http\Requests\Musica;

use App\Models\Autenticacao\Utilizador;
use App\Models\Musica\Artista;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Valida os dados necessários para criar um artista.
 *
 * @since 1.0.0
 */
final class CriarArtistaRequest extends PedidoArtistaRequest
{
    /**
     * Determina se o utilizador autenticado pode criar artistas.
     *
     * A autorização é executada antes das consultas de validação.
     *
     * @return bool Verdadeiro quando a política permite a criação.
     *
     * @since 2.0.0
     */
    public function authorize(): bool
    {
        $utilizador = $this->user(
            'sessao',
        );

        return $utilizador instanceof Utilizador
            && $utilizador->can(
                'create',
                Artista::class,
            );
    }

    /**
     * Obtém a regra de unicidade aplicável ao nome.
     *
     * Os artistas eliminados logicamente não impedem a reutilização do
     * respetivo nome.
     *
     * Esta restrição será substituída pelo mecanismo de confirmação de
     * possíveis duplicados.
     *
     * @return Unique Regra de unicidade.
     *
     * @since 2.0.0
     */
    protected function obterRegraUnicidadeNome(): Unique
    {
        return Rule::unique(
            Artista::class,
            'nome',
        )->whereNull(
            'deleted_at',
        );
    }
}
