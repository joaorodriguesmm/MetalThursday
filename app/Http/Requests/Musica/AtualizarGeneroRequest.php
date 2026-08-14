<?php

declare(strict_types=1);

namespace App\Http\Requests\Musica;

use App\Models\Autenticacao\Utilizador;
use App\Models\Musica\Genero;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use LogicException;

/**
 * Valida os dados necessários para atualizar um género musical.
 *
 * Impede que o próprio género ou qualquer um dos seus descendentes seja
 * selecionado como género pai, evitando ciclos na hierarquia.
 *
 * @since 1.0.0
 */
final class AtualizarGeneroRequest extends PedidoGeneroRequest
{
    /**
     * Género resolvido através do parâmetro da rota.
     *
     * A instância é conservada para evitar repetir a resolução e a validação
     * do parâmetro durante a construção das regras.
     *
     * @since 2.0.0
     */
    private ?Genero $generoDaRota = null;

    /**
     * Determina se o utilizador autenticado pode atualizar o género da rota.
     *
     * A autorização é executada antes da consulta recursiva e das restantes
     * regras de validação.
     *
     * @return bool Verdadeiro quando a política permite a atualização.
     *
     * @throws LogicException Quando a rota não contém um género válido.
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
                $this->obterGeneroDaRota(),
            );
    }

    /**
     * Obtém a regra de unicidade aplicável ao nome.
     *
     * O género atual é ignorado na verificação. Os géneros eliminados
     * logicamente não impedem a reutilização do respetivo nome.
     *
     * @return Unique Regra de unicidade.
     *
     * @throws LogicException Quando a rota não contém um género válido.
     *
     * @since 2.0.0
     */
    protected function obterRegraUnicidadeNome(): Unique
    {
        return Rule::unique(
            Genero::class,
            'nome',
        )
            ->ignore(
                $this->obterGeneroDaRota(),
            )
            ->whereNull(
                'deleted_at',
            );
    }

    /**
     * Obtém as regras adicionais aplicáveis aos géneros pais.
     *
     * O próprio género e todos os seus descendentes são proibidos para evitar
     * referências próprias e ciclos indiretos na hierarquia.
     *
     * @return list<mixed> Regras adicionais.
     *
     * @throws LogicException Quando a rota não contém um género válido.
     *
     * @since 2.0.0
     */
    protected function obterRegrasAdicionaisGenerosPai(): array
    {
        return [
            Rule::notIn(
                $this
                    ->obterGeneroDaRota()
                    ->obterIdentificadoresComDescendentes(),
            ),
        ];
    }

    /**
     * Obtém o género associado ao parâmetro da rota.
     *
     * @return Genero Género que será atualizado.
     *
     * @throws LogicException Quando a rota não contém um género válido.
     *
     * @since 2.0.0
     */
    private function obterGeneroDaRota(): Genero
    {
        if ($this->generoDaRota instanceof Genero) {
            return $this->generoDaRota;
        }

        $genero = $this->route(
            'genero',
        );

        if (! $genero instanceof Genero) {
            throw new LogicException(
                'A rota não contém um género válido.',
            );
        }

        $this->generoDaRota =
            $genero;

        return $this->generoDaRota;
    }
}
