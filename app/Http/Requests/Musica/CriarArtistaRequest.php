<?php

declare(strict_types=1);

namespace App\Http\Requests\Musica;

use App\Models\Autenticacao\Utilizador;
use App\Models\Musica\Artista;
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
     * Obtém as regras de validação aplicáveis à criação.
     *
     * O sinal de confirmação é opcional. Quando enviado, deve representar
     * explicitamente uma confirmação aceite pela validação.
     *
     * @return array<string, list<mixed>> Regras de validação.
     *
     * @since 2.0.0
     */
    public function rules(): array
    {
        $regras =
            parent::rules();

        $regras['confirmar_nome_repetido'] = [
            'sometimes',
            'accepted',
        ];

        return $regras;
    }

    /**
     * Obtém as mensagens de validação aplicáveis à criação.
     *
     * @return array<string, string> Mensagens de validação.
     *
     * @since 2.0.0
     */
    public function messages(): array
    {
        $mensagens =
            parent::messages();

        $mensagens['confirmar_nome_repetido.accepted'] =
            'A confirmação da criação de um artista com nome repetido não é válida.';

        return $mensagens;
    }

    /**
     * Indica que a criação não possui uma regra de unicidade do nome.
     *
     * A existência de artistas ativos com o mesmo nome é tratada como uma
     * situação que exige confirmação explícita e não como erro de validação.
     *
     * @return null A criação não aplica unicidade ao nome.
     *
     * @since 2.0.0
     */
    protected function obterRegraUnicidadeNome(): ?Unique
    {
        return null;
    }
}
