<?php

namespace App\Rules;

use App\Models\MtSectionType;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Define regra de validação para campo obrigatório quando a secção tem detalhes.
 *
 * @since 1.0
 * @version 1.0
 */
class RequiredWhenSectionHasDetails implements DataAwareRule, ValidationRule
{
    protected array $data = [];
    protected string $fieldName;

    /**
     * Cria uma nova regra.
     *
     * @param string $fieldName - O nome amigável do campo (ex: 'Banda', 'Título').
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    public function __construct(string $fieldName)
    {
        $this->fieldName = $fieldName;
    }

    /**
     * Define os dados do pedido.
     *
     * @param array  $data - Dados do pedido.
     * @return static - Objeto atualizado.
     *
     * @since 1.0
     * @version 1.0
     */
    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Executa a regra de validação.
     *
     * @param string $attribute - Nome do atributo a ser validado.
     * @param mixed $value - Valor do atributo a ser validado.
     * @param Closure - Função de falha.
     * @return void
     *
     * @since 1.0
     * @version 1.0
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        preg_match('/sections\.(\d+)\./', $attribute, $matches);
        if (count($matches) < 2) {
            return;
        }
        $index = $matches[1];

        $typeId = $this->data['sections'][$index]['type_id'] ?? null;
        if (!$typeId) {
            return;
        }

        $sectionType = cache()->remember("section_type_{$typeId}", 60, fn() => MtSectionType::find($typeId));

        if ($sectionType && $sectionType->has_details && empty($value)) {
            $sectionNumber = $index + 1;
            $fail("O campo {$this->fieldName} é obrigatório para a secção #{$sectionNumber} ('{$sectionType->name}').");
        }
    }
}
