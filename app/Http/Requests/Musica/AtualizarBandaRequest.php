<?php

declare(strict_types=1);

namespace App\Http\Requests\Musica;

use App\Models\Autenticacao\Utilizador;
use App\Models\Musica\Banda;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use LogicException;

/**
 * Valida os dados necessários para atualizar uma banda.
 *
 * @since 1.0.0
 */
final class AtualizarBandaRequest extends PedidoBandaRequest
{
    /**
     * Banda resolvida através do parâmetro da rota.
     *
     * A instância é conservada para evitar repetir a resolução durante a
     * autorização e a construção das regras.
     *
     * @since 2.0.0
     */
    private ?Banda $bandaDaRota = null;

    /**
     * Determina se o utilizador autenticado pode atualizar a banda da rota.
     *
     * A autorização é executada antes das consultas de validação.
     *
     * @return bool Verdadeiro quando a política permite a atualização.
     *
     * @throws LogicException Quando a rota não contém uma banda válida.
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
                $this->obterBandaDaRota(),
            );
    }

    /**
     * Obtém a regra de unicidade aplicável ao nome.
     *
     * A banda atual é ignorada na verificação. As bandas eliminadas
     * logicamente não impedem a reutilização do respetivo nome.
     *
     * @return Unique Regra de unicidade.
     *
     * @throws LogicException Quando a rota não contém uma banda válida.
     *
     * @since 2.0.0
     */
    protected function obterRegraUnicidadeNome(): Unique
    {
        return Rule::unique(
            Banda::class,
            'nome',
        )
            ->ignore(
                $this->obterBandaDaRota(),
            )
            ->whereNull(
                'deleted_at',
            );
    }

    /**
     * Obtém a banda associada ao parâmetro da rota.
     *
     * @return Banda Banda que será atualizada.
     *
     * @throws LogicException Quando a rota não contém uma banda válida.
     *
     * @since 2.0.0
     */
    private function obterBandaDaRota(): Banda
    {
        if ($this->bandaDaRota instanceof Banda) {
            return $this->bandaDaRota;
        }

        $banda = $this->route(
            'banda',
        );

        if (! $banda instanceof Banda) {
            throw new LogicException(
                'A rota não contém uma banda válida.',
            );
        }

        $this->bandaDaRota =
            $banda;

        return $this->bandaDaRota;
    }
}
