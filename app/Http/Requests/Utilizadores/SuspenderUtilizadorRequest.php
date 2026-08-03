<?php

declare(strict_types=1);

namespace App\Http\Requests\Utilizadores;

use App\Models\Autenticacao\Utilizador;
use App\ObjetosValor\Utilizadores\MotivoSuspensaoUtilizador;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use InvalidArgumentException;
use LogicException;

/**
 * Valida os dados necessários para suspender o acesso de um utilizador.
 *
 * A autorização é executada através da política antes da validação. O motivo
 * é normalizado e validado definitivamente pelo respetivo objeto de valor.
 *
 * @since 2.0.0
 *
 * @version 1.0.0
 */
final class SuspenderUtilizadorRequest extends FormRequest
{
    /**
     * Saco utilizado para os erros da suspensão.
     *
     * Esta propriedade não deve ser tipada porque é herdada do
     * {@see FormRequest}.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected $errorBag =
        'suspensao';

    /**
     * Determina se o pedido pode ser processado.
     *
     * A autenticação é resolvida explicitamente através do guard `sessao` e
     * a decisão definitiva pertence à política dos utilizadores.
     *
     * @return bool Verdadeiro quando o responsável pode suspender o
     *              utilizador indicado na rota.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
                'suspender',
                $utilizadorAfetado,
            );
    }

    /**
     * Normaliza preliminarmente o motivo antes da validação.
     *
     * Valores não textuais e texto cuja codificação não possa ser processada
     * são preservados para que as regras de validação os rejeitem.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    protected function prepareForValidation(): void
    {
        $valor =
            $this->input(
                'motivo',
            );

        if (! is_string($valor)) {
            return;
        }

        $motivo =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    $valor,
                ),
            );

        if (! is_string($motivo)) {
            return;
        }

        $this->merge([
            'motivo' => $motivo,
        ]);
    }

    /**
     * Obtém as regras de validação.
     *
     * @return array<string, list<mixed>> Regras de validação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function rules(): array
    {
        return [
            'motivo' => [
                'bail',
                'required',
                'string',
                'max:'.MotivoSuspensaoUtilizador::COMPRIMENTO_MAXIMO,
                $this->criarRegraMotivo(),
            ],
        ];
    }

    /**
     * Obtém as mensagens de validação.
     *
     * @return array<string, string> Mensagens de validação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function messages(): array
    {
        return [
            'motivo.required' => 'Por favor, indica o motivo da suspensão.',

            'motivo.string' => 'O motivo da suspensão deve ser uma sequência de caracteres.',

            'motivo.max' => sprintf(
                'O motivo da suspensão não pode ter mais de %d caracteres.',
                MotivoSuspensaoUtilizador::COMPRIMENTO_MAXIMO,
            ),
        ];
    }

    /**
     * Obtém os nomes apresentados para os atributos.
     *
     * @return array<string, string> Nomes legíveis dos atributos.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function attributes(): array
    {
        return [
            'motivo' => 'motivo da suspensão',
        ];
    }

    /**
     * Obtém o motivo validado e normalizado.
     *
     * @return string Motivo normalizado.
     *
     * @throws LogicException Quando o resultado validado deixa de cumprir o
     *                        contrato do objeto de valor.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function obterMotivo(): string
    {
        $motivo =
            $this->validated(
                'motivo',
            );

        if (! is_string($motivo)) {
            throw new LogicException(
                'O pedido validado não contém um motivo de suspensão textual.',
            );
        }

        try {
            return MotivoSuspensaoUtilizador::deTexto(
                $motivo,
            )->valor();
        } catch (InvalidArgumentException $excecao) {
            throw new LogicException(
                'O pedido validado não contém um motivo de suspensão válido.',
                previous: $excecao,
            );
        }
    }

    /**
     * Obtém o superadministrador autenticado.
     *
     * @return Utilizador Utilizador responsável.
     *
     * @throws LogicException Quando o pedido não possui autenticação válida.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    public function obterUtilizadorAutenticado(): Utilizador
    {
        $utilizador =
            $this->user(
                'sessao',
            );

        if (! $utilizador instanceof Utilizador) {
            throw new LogicException(
                'Não existe um utilizador autenticado válido para suspender o acesso.',
            );
        }

        return $utilizador;
    }

    /**
     * Cria a regra de validação definitiva do motivo.
     *
     * @return Closure(string, mixed, Closure(string): void): void Regra.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function criarRegraMotivo(): Closure
    {
        return static function (
            string $atributo,
            mixed $valor,
            Closure $falhar,
        ): void {
            if (! is_string($valor)) {
                return;
            }

            try {
                MotivoSuspensaoUtilizador::deTexto(
                    $valor,
                );
            } catch (InvalidArgumentException $excecao) {
                $falhar(
                    $excecao->getMessage(),
                );
            }
        };
    }
}
