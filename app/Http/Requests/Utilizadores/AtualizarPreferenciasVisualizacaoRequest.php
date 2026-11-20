<?php

declare(strict_types=1);

namespace App\Http\Requests\Utilizadores;

use App\Enumeracoes\ModoVisualizacaoLigacao;
use App\Enumeracoes\PlataformaLigacao;
use App\Models\Autenticacao\Utilizador;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use LogicException;

/**
 * Valida as opções pessoais de apresentação das plataformas musicais.
 *
 * @since 2.0.0
 */
final class AtualizarPreferenciasVisualizacaoRequest extends FormRequest
{
    /**
     * @var string
     */
    protected $errorBag = 'preferenciasVisualizacao';

    public function authorize(): bool
    {
        return $this->user('sessao') instanceof Utilizador;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $modos = array_map(
            static fn (ModoVisualizacaoLigacao $modo): string => $modo->value,
            ModoVisualizacaoLigacao::cases(),
        );

        return [
            'plataformas' => [
                'bail',
                'required',
                'array:spotify,apple_music,youtube',
                'required_array_keys:spotify,apple_music,youtube',
            ],
            'plataformas.spotify' => ['bail', 'required', Rule::in($modos)],
            'plataformas.apple_music' => ['bail', 'required', Rule::in($modos)],
            'plataformas.youtube' => ['bail', 'required', Rule::in($modos)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'plataformas' => 'preferências de visualização',
            'plataformas.spotify' => 'Spotify',
            'plataformas.apple_music' => 'Apple Music',
            'plataformas.youtube' => 'YouTube',
        ];
    }

    /**
     * Obtém exclusivamente os três valores previamente validados.
     *
     * @return array<string, string>
     *
     * @since 2.0.0
     */
    public function obterPreferencias(): array
    {
        $dados = $this->validated('plataformas');

        if (! is_array($dados)) {
            throw new LogicException('As preferências validadas são inválidas.');
        }

        $resultado = [];

        foreach (PlataformaLigacao::cases() as $plataforma) {
            if ($plataforma === PlataformaLigacao::Outro) {
                continue;
            }

            $valor = $dados[$plataforma->value] ?? null;

            if (! is_string($valor)) {
                throw new LogicException('Uma preferência validada é inválida.');
            }

            $resultado[$plataforma->value] =
                ModoVisualizacaoLigacao::from($valor)->value;
        }

        return $resultado;
    }
}
