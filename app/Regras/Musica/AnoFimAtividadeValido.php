<?php

declare(strict_types=1);

namespace App\Regras\Musica;

use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Garante que o ano de fim de atividade não antecede o ano de início.
 *
 * @since 2.0.0
 */
final class AnoFimAtividadeValido implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> Dados completos submetidos. */
    private array $dados = [];

    /**
     * Recebe os dados completos do validador.
     *
     * @param  array<string, mixed>  $data  Dados submetidos.
     * @return $this Regra configurada.
     *
     * @since 2.0.0
     */
    public function setData(array $data): static
    {
        $this->dados = $data;

        return $this;
    }

    /**
     * Valida a coerência do período de atividade.
     *
     * @param  string  $attribute  Atributo validado.
     * @param  mixed  $value  Valor recebido.
     * @param  Closure(string): void  $fail  Função de falha.
     *
     * @since 2.0.0
     */
    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail,
    ): void {
        $anoInicio = $this->dados['ano_inicio_atividade'] ?? null;

        if (
            ! is_numeric($value)
            || ! is_numeric($anoInicio)
        ) {
            return;
        }

        if ((int) $value >= (int) $anoInicio) {
            return;
        }

        $fail(
            'O ano de fim de atividade não pode ser anterior ao ano de início.',
        );
    }
}
