<?php

declare(strict_types=1);

namespace App\Http\Requests\Utilizadores;

use App\Models\Autenticacao\Utilizador;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;

/**
 * Valida a confirmação necessária para encerrar as sessões de um utilizador.
 *
 * A autorização é executada através da política antes da validação. A
 * confirmação explícita impede o encerramento acidental das autenticações.
 *
 * @since 2.0.0
 */
final class EncerrarSessoesUtilizadorRequest extends FormRequest
{
    /**
     * Saco utilizado para os erros do encerramento das sessões.
     *
     * Esta propriedade não deve ser tipada porque é herdada do
     * {@see FormRequest}.
     *
     * @var string
     *
     * @since 2.0.0
     */
    protected $errorBag =
        'sessoes';

    /**
     * Determina se o pedido pode ser processado.
     *
     * A autenticação é resolvida explicitamente através do guard `sessao` e
     * a decisão definitiva pertence à política dos utilizadores.
     *
     * @return bool Verdadeiro quando o responsável pode encerrar as sessões
     *              do utilizador indicado na rota.
     *
     * @since 2.0.0
     */
    public function authorize(): bool
    {
        $responsavel =
            $this->user(
                'sessao',
            );

        $utilizadorAfetado =
            $this->route(
                'utilizador',
            );

        return $responsavel instanceof Utilizador
            && $utilizadorAfetado instanceof Utilizador
            && $responsavel->can(
                'encerrarSessoes',
                $utilizadorAfetado,
            );
    }

    /**
     * Obtém as regras de validação.
     *
     * @return array<string, list<string>> Regras de validação.
     *
     * @since 2.0.0
     */
    public function rules(): array
    {
        return [
            'confirmar_encerramento_sessoes' => [
                'accepted',
            ],
        ];
    }

    /**
     * Obtém as mensagens de validação.
     *
     * @return array<string, string> Mensagens de validação.
     *
     * @since 2.0.0
     */
    public function messages(): array
    {
        return [
            'confirmar_encerramento_sessoes.accepted' => 'Confirma explicitamente o encerramento das sessões.',
        ];
    }

    /**
     * Obtém os nomes apresentados para os atributos.
     *
     * @return array<string, string> Nomes legíveis dos atributos.
     *
     * @since 2.0.0
     */
    public function attributes(): array
    {
        return [
            'confirmar_encerramento_sessoes' => 'confirmação do encerramento das sessões',
        ];
    }

    /**
     * Obtém o superadministrador autenticado.
     *
     * @return Utilizador Utilizador responsável.
     *
     * @throws LogicException Quando o pedido não possui autenticação válida.
     *
     * @since 2.0.0
     */
    public function obterUtilizadorAutenticado(): Utilizador
    {
        $utilizador =
            $this->user(
                'sessao',
            );

        if (! $utilizador instanceof Utilizador) {
            throw new LogicException(
                'Não existe um utilizador autenticado válido para encerrar as sessões.',
            );
        }

        return $utilizador;
    }
}
