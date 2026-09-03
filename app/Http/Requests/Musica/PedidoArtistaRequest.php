<?php

declare(strict_types=1);

namespace App\Http\Requests\Musica;

use App\Enumeracoes\EstadoAtividadeArtista;
use App\Models\Comum\Ligacao;
use App\Models\Geografia\OrigemGeografica;
use App\Models\Musica\Artista;
use App\Models\Musica\Genero;
use App\Regras\Musica\AnoFimAtividadeValido;
use App\Regras\Musica\EnderecoWebSeguro;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;

/**
 * Define a validação comum dos pedidos de criação e atualização de artistas.
 *
 * @since 2.0.0
 */
abstract class PedidoArtistaRequest extends FormRequest
{
    /**
     * Normaliza os dados antes da validação.
     *
     * @since 2.0.0
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'nome' => $this->normalizarNome(
                $this->input(
                    'nome',
                ),
            ),

            'origem_geografica_id' => $this->normalizarIdentificadorOpcional(
                $this->input(
                    'origem_geografica_id',
                ),
            ),

            'ano_inicio_atividade' => $this->normalizarInteiroOpcional(
                $this->input(
                    'ano_inicio_atividade',
                ),
            ),

            'ano_fim_atividade' => $this->normalizarInteiroOpcional(
                $this->input(
                    'ano_fim_atividade',
                ),
            ),

            'estado_atividade' => $this->normalizarTextoOpcional(
                $this->input(
                    'estado_atividade',
                ),
            ),

            'biografia' => $this->normalizarTextoLongoOpcional(
                $this->input(
                    'biografia',
                ),
            ),

            'imagem' => $this->normalizarTextoOpcional(
                $this->input(
                    'imagem',
                ),
            ),

            'musicbrainz_id' => $this->normalizarMbidOpcional(
                $this->input(
                    'musicbrainz_id',
                ),
            ),

            'discogs_id' => $this->normalizarInteiroOpcional(
                $this->input(
                    'discogs_id',
                ),
            ),

            'ligacoes' => $this->normalizarLigacoes(
                $this->input(
                    'ligacoes',
                    [],
                ),
            ),

            'generos' => $this->normalizarIdentificadores(
                $this->input(
                    'generos',
                    [],
                ),
            ),
        ]);
    }

    /**
     * Obtém as regras de validação.
     *
     * @return array<string, list<mixed>> Regras de validação.
     *
     * @since 1.0.0
     */
    public function rules(): array
    {
        $regrasNome = [
            'bail',
            'required',
            'string',
            $this->criarRegraNome(),
            'max:'.Artista::COMPRIMENTO_MAXIMO_NOME,
        ];

        $regraUnicidadeNome =
            $this->obterRegraUnicidadeNome();

        if ($regraUnicidadeNome instanceof Unique) {
            $regrasNome[] =
                $regraUnicidadeNome;
        }

        $artistaAtual =
            $this->route(
                'artista',
            );

        $regraMusicBrainz =
            Rule::unique(
                Artista::class,
                'musicbrainz_id',
            );

        $regraDiscogs =
            Rule::unique(
                Artista::class,
                'discogs_id',
            );

        if ($artistaAtual instanceof Artista) {
            $regraMusicBrainz->ignore(
                $artistaAtual,
            );

            $regraDiscogs->ignore(
                $artistaAtual,
            );
        }

        $anoAtual =
            (int) date(
                'Y',
            );

        return [
            'nome' => $regrasNome,

            'origem_geografica_id' => [
                'bail',
                'nullable',
                'integer',

                Rule::exists(
                    OrigemGeografica::class,
                    'id',
                ),
            ],

            'ano_inicio_atividade' => [
                'bail',
                'nullable',
                'integer',
                'min:'.Artista::ANO_MINIMO_ATIVIDADE,
                'max:'.$anoAtual,
            ],

            'ano_fim_atividade' => [
                'bail',
                'nullable',
                'integer',
                'min:'.Artista::ANO_MINIMO_ATIVIDADE,
                'max:'.$anoAtual,
                new AnoFimAtividadeValido,
            ],

            'estado_atividade' => [
                'bail',
                'nullable',

                Rule::enum(
                    EstadoAtividadeArtista::class,
                ),
            ],

            'biografia' => [
                'bail',
                'nullable',
                'string',
                'max:'.Artista::COMPRIMENTO_MAXIMO_BIOGRAFIA,
            ],

            'imagem' => [
                'bail',
                'nullable',
                'string',
                'max:'.Artista::COMPRIMENTO_MAXIMO_URL_IMAGEM,
                new EnderecoWebSeguro,
            ],

            'musicbrainz_id' => [
                'bail',
                'nullable',
                'string',
                'uuid',
                $regraMusicBrainz,
            ],

            'discogs_id' => [
                'bail',
                'nullable',
                'integer',
                'min:1',
                $regraDiscogs,
            ],

            'ligacoes' => [
                'bail',
                'array',
                'list',
                'max:50',
            ],

            'ligacoes.*' => [
                'bail',
                'array',
                'required_array_keys:titulo,url',
            ],

            'ligacoes.*.titulo' => [
                'bail',
                'required',
                'string',
                'max:'.Ligacao::COMPRIMENTO_MAXIMO_TITULO,
            ],

            'ligacoes.*.url' => [
                'bail',
                'required',
                'string',
                'max:'.Ligacao::COMPRIMENTO_MAXIMO_URL,
                new EnderecoWebSeguro,
                'distinct:strict',
            ],

            'generos' => [
                'bail',
                'array',
                'list',
            ],

            'generos.*' => [
                'bail',
                'required',
                'integer',
                'distinct:strict',

                Rule::exists(
                    Genero::class,
                    'id',
                )->whereNull(
                    'deleted_at',
                ),
            ],
        ];
    }

    /**
     * Obtém as mensagens de validação apresentadas ao utilizador.
     *
     * @return array<string, string> Mensagens de validação.
     *
     * @since 1.0.0
     */
    public function messages(): array
    {
        $anoAtual =
            (int) date(
                'Y',
            );

        return [
            'nome.required' => 'Por favor, insere o nome do artista.',

            'nome.string' => 'O nome do artista deve ser uma sequência de caracteres.',

            'nome.max' => sprintf(
                'O nome do artista não pode ter mais de %d caracteres.',
                Artista::COMPRIMENTO_MAXIMO_NOME,
            ),

            'nome.unique' => 'Já existe um artista com esse nome.',

            'origem_geografica_id.integer' => 'A origem geográfica selecionada não é válida.',

            'origem_geografica_id.exists' => 'A origem geográfica selecionada não existe.',

            'ano_inicio_atividade.integer' => 'O ano de início de atividade deve ser um número inteiro.',

            'ano_inicio_atividade.min' => sprintf(
                'O ano de início de atividade não pode ser anterior a %d.',
                Artista::ANO_MINIMO_ATIVIDADE,
            ),

            'ano_inicio_atividade.max' => 'O ano de início de atividade não pode ser posterior ao ano atual.',

            'ano_fim_atividade.integer' => 'O ano de fim de atividade deve ser um número inteiro.',

            'ano_fim_atividade.min' => sprintf(
                'O ano de fim de atividade não pode ser anterior a %d.',
                Artista::ANO_MINIMO_ATIVIDADE,
            ),

            'ano_fim_atividade.max' => sprintf(
                'O ano de fim de atividade não pode ser posterior a %d.',
                $anoAtual,
            ),

            'estado_atividade.enum' => 'O estado de atividade selecionado não é válido.',

            'biografia.string' => 'A biografia deve ser uma sequência de caracteres.',

            'biografia.max' => sprintf(
                'A biografia não pode ter mais de %d caracteres.',
                Artista::COMPRIMENTO_MAXIMO_BIOGRAFIA,
            ),

            'imagem.string' => 'O endereço da imagem deve ser uma sequência de caracteres.',

            'imagem.max' => sprintf(
                'O endereço da imagem não pode ter mais de %d caracteres.',
                Artista::COMPRIMENTO_MAXIMO_URL_IMAGEM,
            ),

            'musicbrainz_id.string' => 'O identificador MusicBrainz não é válido.',

            'musicbrainz_id.uuid' => 'O identificador MusicBrainz não é válido.',

            'musicbrainz_id.unique' => 'Este perfil MusicBrainz já está associado a outro artista.',

            'discogs_id.integer' => 'O identificador do Discogs não é válido.',

            'discogs_id.min' => 'O identificador do Discogs não é válido.',

            'discogs_id.unique' => 'Este perfil do Discogs já está associado a outro artista.',

            'ligacoes.array' => 'As ligações devem ser enviadas numa lista.',

            'ligacoes.list' => 'A lista de ligações não tem um formato válido.',

            'ligacoes.max' => 'Não podem ser guardadas mais de 50 ligações por entidade.',

            'ligacoes.*.required_array_keys' => 'Uma das ligações não tem um formato válido.',

            'ligacoes.*.titulo.required' => 'Indica o título da ligação.',

            'ligacoes.*.titulo.string' => 'O título da ligação deve ser uma sequência de caracteres.',

            'ligacoes.*.titulo.max' => sprintf(
                'O título da ligação não pode ter mais de %d caracteres.',
                Ligacao::COMPRIMENTO_MAXIMO_TITULO,
            ),

            'ligacoes.*.url.required' => 'Indica o endereço da ligação.',

            'ligacoes.*.url.string' => 'O endereço da ligação deve ser uma sequência de caracteres.',

            'ligacoes.*.url.max' => sprintf(
                'O endereço da ligação não pode ter mais de %d caracteres.',
                Ligacao::COMPRIMENTO_MAXIMO_URL,
            ),

            'ligacoes.*.url.distinct' => 'A mesma ligação foi indicada mais do que uma vez.',

            'generos.array' => 'Os géneros devem ser enviados numa lista.',

            'generos.list' => 'A lista de géneros não tem um formato válido.',

            'generos.*.required' => 'Um dos géneros selecionados não é válido.',

            'generos.*.integer' => 'Um dos géneros selecionados não é válido.',

            'generos.*.distinct' => 'O mesmo género foi selecionado mais do que uma vez.',

            'generos.*.exists' => 'Um dos géneros selecionados não existe ou não está disponível.',
        ];
    }

    /**
     * Obtém os nomes legíveis apresentados para os atributos validados.
     *
     * @return array<string, string> Nomes legíveis dos atributos.
     *
     * @since 2.0.0
     */
    public function attributes(): array
    {
        return [
            'nome' => 'nome do artista',
            'origem_geografica_id' => 'origem geográfica',
            'ano_inicio_atividade' => 'ano de início de atividade',
            'ano_fim_atividade' => 'ano de fim de atividade',
            'estado_atividade' => 'estado de atividade',
            'biografia' => 'biografia',
            'imagem' => 'imagem',
            'musicbrainz_id' => 'perfil MusicBrainz',
            'discogs_id' => 'perfil Discogs',
            'ligacoes' => 'ligações',
            'ligacoes.*.titulo' => 'título da ligação',
            'ligacoes.*.url' => 'endereço da ligação',
            'generos' => 'géneros',
            'generos.*' => 'género',
        ];
    }

    /**
     * Obtém a regra de unicidade aplicável ao nome, quando necessária.
     *
     * A criação pode omitir a regra quando a confirmação explícita de um nome
     * repetido faz parte do fluxo. A atualização pode fornecer uma regra
     * ajustada ao artista atual.
     *
     * @return Unique|null Regra de unicidade ou nulo.
     *
     * @since 2.0.0
     */
    abstract protected function obterRegraUnicidadeNome(): ?Unique;

    /**
     * Cria a regra adicional de validação estrutural do nome.
     *
     * A regra rejeita texto UTF-8 inválido e caracteres de controlo antes de o
     * valor poder chegar ao modelo.
     *
     * @return Closure(string, mixed, Closure(string): void): void Regra.
     *
     * @since 2.0.0
     */
    private function criarRegraNome(): Closure
    {
        return static function (
            string $atributo,
            mixed $valor,
            Closure $falhar,
        ): void {
            if (! is_string($valor)) {
                return;
            }

            if (
                preg_match(
                    '//u',
                    $valor,
                ) !== 1
            ) {
                $falhar(
                    'O nome do artista contém texto inválido.',
                );

                return;
            }

            if (
                preg_match(
                    '/[\x00-\x1F\x7F]/',
                    $valor,
                ) === 1
            ) {
                $falhar(
                    'O nome do artista contém caracteres inválidos.',
                );
            }
        };
    }

    /**
     * Normaliza o nome do artista antes da validação.
     *
     * Texto estruturalmente inválido é mantido sem transformação para que as
     * regras de validação possam apresentar o erro correspondente.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Nome normalizado ou valor original quando não é texto válido.
     *
     * @since 2.0.0
     */
    private function normalizarNome(
        mixed $valor,
    ): mixed {
        if (! is_string($valor)) {
            return $valor;
        }

        if (
            preg_match(
                '//u',
                $valor,
            ) !== 1
            || preg_match(
                '/[\x00-\x1F\x7F]/',
                $valor,
            ) === 1
        ) {
            return $valor;
        }

        return Str::squish(
            $valor,
        );
    }

    /**
     * Normaliza um identificador numérico opcional.
     *
     * Valores ausentes são convertidos para nulo. Os restantes valores são
     * delegados ao normalizador comum de identificadores.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Identificador normalizado ou nulo.
     *
     * @since 2.0.0
     */
    private function normalizarIdentificadorOpcional(
        mixed $valor,
    ): mixed {
        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        return $this->normalizarIdentificador(
            $valor,
        );
    }

    /**
     * Normaliza um inteiro opcional.
     *
     * Valores ausentes são convertidos para nulo. Sequências constituídas apenas
     * por algarismos são convertidas para inteiros.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Inteiro normalizado, nulo ou valor original.
     *
     * @since 2.0.0
     */
    private function normalizarInteiroOpcional(
        mixed $valor,
    ): mixed {
        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        return $this->normalizarIdentificador(
            $valor,
        );
    }

    /**
     * Normaliza um identificador MusicBrainz opcional.
     *
     * O MBID é convertido para minúsculas e tem os espaços exteriores removidos.
     * A validação formal do UUID é efetuada posteriormente pelas regras do pedido.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Identificador normalizado, nulo ou valor original.
     *
     * @since 2.0.0
     */
    private function normalizarMbidOpcional(
        mixed $valor,
    ): mixed {
        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        if (! is_string($valor)) {
            return $valor;
        }

        return mb_strtolower(
            trim(
                $valor,
            ),
        );
    }

    /**
     * Normaliza um texto curto opcional.
     *
     * Sequências vazias são convertidas para nulo e os espaços exteriores de
     * textos válidos são removidos.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Texto normalizado, nulo ou valor original.
     *
     * @since 2.0.0
     */
    private function normalizarTextoOpcional(
        mixed $valor,
    ): mixed {
        if (
            $valor === null
            || $valor === ''
        ) {
            return null;
        }

        return is_string($valor)
            ? trim(
                $valor,
            )
            : $valor;
    }

    /**
     * Normaliza um texto longo opcional sem destruir a estrutura interior.
     *
     * Apenas são removidos os espaços exteriores. Quebras de linha e separação
     * entre parágrafos são preservadas.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Texto normalizado, nulo ou valor original.
     *
     * @since 2.0.0
     */
    private function normalizarTextoLongoOpcional(
        mixed $valor,
    ): mixed {
        if ($valor === null) {
            return null;
        }

        if (! is_string($valor)) {
            return $valor;
        }

        $texto =
            trim(
                $valor,
            );

        return $texto !== ''
            ? $texto
            : null;
    }

    /**
     * Normaliza a lista de ligações e elimina linhas totalmente vazias.
     *
     * Os títulos têm os espaços interiores compactados e os endereços mantêm
     * apenas a remoção dos espaços exteriores. Entradas estruturalmente inválidas
     * são preservadas para serem rejeitadas pela validação.
     *
     * @param  mixed  $valor  Ligações recebidas.
     * @return mixed Lista normalizada ou valor original.
     *
     * @since 2.0.0
     */
    private function normalizarLigacoes(
        mixed $valor,
    ): mixed {
        if (! is_array($valor)) {
            return $valor;
        }

        $ligacoes = [];

        foreach ($valor as $ligacao) {
            if (! is_array($ligacao)) {
                $ligacoes[] =
                    $ligacao;

                continue;
            }

            $titulo =
                $ligacao['titulo']
                ?? null;

            $url =
                $ligacao['url']
                ?? null;

            $tituloNormalizado =
                is_string($titulo)
                ? Str::squish(
                    $titulo,
                )
                : $titulo;

            $urlNormalizado =
                is_string($url)
                ? trim(
                    $url,
                )
                : $url;

            if (
                (
                    $tituloNormalizado === ''
                    || $tituloNormalizado === null
                )
                && (
                    $urlNormalizado === ''
                    || $urlNormalizado === null
                )
            ) {
                continue;
            }

            $ligacoes[] = [
                'titulo' => $tituloNormalizado,

                'url' => $urlNormalizado,
            ];
        }

        return $ligacoes;
    }

    /**
     * Normaliza um identificador numérico recebido.
     *
     * Sequências constituídas exclusivamente por algarismos são convertidas para
     * inteiros. Outros tipos e formatos permanecem inalterados para validação.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Identificador convertido ou valor original.
     *
     * @since 2.0.0
     */
    private function normalizarIdentificador(
        mixed $valor,
    ): mixed {
        if (
            is_string($valor)
            && ctype_digit(
                $valor,
            )
        ) {
            return (int) $valor;
        }

        return $valor;
    }

    /**
     * Normaliza uma lista de identificadores numéricos.
     *
     * A estrutura original da lista é preservada para que a validação possa
     * detetar índices ou valores inválidos.
     *
     * @param  mixed  $valor  Lista recebida.
     * @return mixed Lista normalizada ou valor original.
     *
     * @since 2.0.0
     */
    private function normalizarIdentificadores(
        mixed $valor,
    ): mixed {
        if (! is_array($valor)) {
            return $valor;
        }

        $identificadores = [];

        foreach ($valor as $indice => $identificador) {
            $identificadores[$indice] =
                $this->normalizarIdentificador(
                    $identificador,
                );
        }

        return $identificadores;
    }
}
