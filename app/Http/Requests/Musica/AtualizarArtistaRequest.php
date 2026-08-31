<?php

declare(strict_types=1);

namespace App\Http\Requests\Musica;

use App\Models\Autenticacao\Utilizador;
use App\Models\Musica\Artista;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use LogicException;

/**
 * Valida os dados necessários para atualizar um artista.
 *
 * @since 1.0.0
 */
final class AtualizarArtistaRequest extends PedidoArtistaRequest
{
    /**
     * Artista resolvido através do parâmetro da rota.
     *
     * A instância é conservada para evitar repetir a resolução durante a
     * autorização e a construção das regras.
     *
     * @since 2.0.0
     */
    private ?Artista $artistaDaRota = null;

    /**
     * Determina se o utilizador autenticado pode atualizar o artista da rota.
     *
     * A autorização é executada antes das consultas de validação.
     *
     * @return bool Verdadeiro quando a política permite a atualização.
     *
     * @throws LogicException Quando a rota não contém um artista válido.
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
                'update',
                $this->obterArtistaDaRota(),
            );
    }

    /**
     * Obtém a regra de unicidade aplicável ao nome.
     *
     * O artista atual é ignorado na verificação. Os artistas eliminados
     * logicamente não impedem a reutilização do respetivo nome.
     *
     * Esta restrição será substituída pelo mecanismo de confirmação de
     * possíveis duplicados.
     *
     * @return Unique Regra de unicidade.
     *
     * @throws LogicException Quando a rota não contém um artista válido.
     *
     * @since 2.0.0
     */
    protected function obterRegraUnicidadeNome(): Unique
    {
        return Rule::unique(
            Artista::class,
            'nome',
        )
            ->ignore(
                $this->obterArtistaDaRota(),
            )
            ->whereNull(
                'deleted_at',
            );
    }

    /**
     * Obtém o artista associado ao parâmetro da rota.
     *
     * @return Artista Artista que será atualizado.
     *
     * @throws LogicException Quando a rota não contém um artista válido.
     *
     * @since 2.0.0
     */
    private function obterArtistaDaRota(): Artista
    {
        if ($this->artistaDaRota instanceof Artista) {
            return $this->artistaDaRota;
        }

        $artista = $this->route(
            'artista',
        );

        if (! $artista instanceof Artista) {
            throw new LogicException(
                'A rota não contém um artista válido.',
            );
        }

        $this->artistaDaRota =
            $artista;

        return $this->artistaDaRota;
    }
}
