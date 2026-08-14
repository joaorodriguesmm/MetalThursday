<?php

declare(strict_types=1);

namespace App\Http\Requests\Autenticacao;

use App\Models\Autenticacao\Convite;
use App\Models\Comunicacao\PermissaoEmail;
use App\ObjetosValor\Utilizadores\EnderecoEmail;
use App\ObjetosValor\Utilizadores\NomeUtilizador;
use App\Regras\Autenticacao\RequisitosPalavraPasse;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use InvalidArgumentException;
use LogicException;

/**
 * Valida e normaliza os dados necessários para aceitar um convite.
 *
 * A disponibilidade, expiração, utilização e correspondência do destinatário
 * do convite são verificadas pelo serviço de registo dentro da respetiva
 * transação.
 *
 * O nome e o endereço de e-mail são validados através dos objetos de valor do
 * domínio. O código original do convite nunca é procurado diretamente na base
 * de dados, porque apenas o respetivo hash é persistido.
 *
 * @since 1.0.0
 */
final class AceitarConviteRequest extends FormRequest
{
    /**
     * Tamanho máximo permitido para a fotografia, em kilobytes.
     *
     * Dez megabytes correspondem a 10 240 kilobytes.
     *
     * @var int
     *
     * @since 2.0.0
     */
    private const TAMANHO_MAXIMO_FOTOGRAFIA_KILOBYTES =
        10 * 1024;

    /**
     * Determina se o pedido pode ser processado.
     *
     * A validade do convite é uma regra de domínio e não uma regra de
     * autorização do pedido.
     *
     * @return bool Verdadeiro para permitir a validação.
     *
     * @since 1.0.0
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normaliza os valores recebidos antes da validação.
     *
     * Valores válidos são convertidos para as representações canónicas
     * definidas pelos respetivos contratos de domínio. Valores inválidos
     * permanecem inalterados para que as regras de validação os rejeitem sem
     * remover ou transformar silenciosamente caracteres proibidos.
     *
     * O código do convite mantém a capitalização por ser sensível a
     * maiúsculas e minúsculas.
     *
     * Quando nenhuma permissão de e-mail é enviada, é criada deliberadamente
     * uma lista vazia. A ausência de seleção é válida neste formulário.
     *
     * @since 2.0.0
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'codigo_convite' => $this->normalizarCodigoConvite(
                $this->input(
                    'codigo_convite',
                ),
            ),

            'nome' => $this->normalizarNome(
                $this->input(
                    'nome',
                ),
            ),

            'email' => $this->normalizarEmail(
                $this->input(
                    'email',
                ),
            ),

            'permissoes_email' => $this->normalizarPermissoesEmail(
                $this->input(
                    'permissoes_email',
                    [],
                ),
            ),
        ]);
    }

    /**
     * Obtém as regras de validação.
     *
     * O código do convite não utiliza a regra `exists`, porque apenas o hash
     * do código é guardado na base de dados. A confirmação definitiva do
     * convite é realizada pelo serviço dentro de uma transação.
     *
     * @return array<string, list<mixed>> Regras de validação.
     *
     * @since 1.0.0
     */
    public function rules(): array
    {
        return [
            'codigo_convite' => [
                'bail',
                'required',
                'string',
                'min:'.Convite::COMPRIMENTO_MINIMO_CODIGO,
                'max:'.Convite::COMPRIMENTO_MAXIMO_CODIGO,
                'regex:/\A'.Convite::PADRAO_CODIGO.'\z/',
            ],

            'nome' => [
                'bail',
                'required',
                'string',
                $this->criarRegraNome(),
            ],

            'email' => [
                'bail',
                'required',
                'string',
                $this->criarRegraEnderecoEmail(),
            ],

            'fotografia' => [
                'bail',
                'nullable',

                File::image()
                    ->types([
                        'jpg',
                        'jpeg',
                        'png',
                        'webp',
                    ])
                    ->max(
                        self::TAMANHO_MAXIMO_FOTOGRAFIA_KILOBYTES,
                    ),
            ],

            'palavra_passe' => RequisitosPalavraPasse::regrasObrigatorias(),

            'confirmacao_palavra_passe' => [
                'bail',
                'required',
                'string',
                'max:'.RequisitosPalavraPasse::comprimentoMaximo(),
                'same:palavra_passe',
            ],

            'permissoes_email' => [
                'bail',
                'array',
                'list',
            ],

            'permissoes_email.*' => [
                'bail',
                'required',
                'integer',
                'distinct:strict',

                Rule::exists(
                    PermissaoEmail::class,
                    'id',
                ),
            ],
        ];
    }

    /**
     * Obtém as mensagens de erro específicas.
     *
     * @return array<string, string> Mensagens de validação.
     *
     * @since 1.0.0
     */
    public function messages(): array
    {
        return [
            'codigo_convite.required' => 'Não foi recebido um código de convite.',

            'codigo_convite.string' => 'O código do convite não é válido.',

            'codigo_convite.min' => 'O código do convite não é válido.',

            'codigo_convite.max' => 'O código do convite não é válido.',

            'codigo_convite.regex' => 'O código do convite não é válido.',

            'nome.required' => 'Por favor, insere o teu nome.',

            'nome.string' => 'O nome deve ser uma sequência de caracteres.',

            'email.required' => 'Por favor, insere o teu endereço de e-mail.',

            'email.string' => 'O endereço de e-mail deve ser uma sequência de caracteres.',

            'fotografia.image' => 'A fotografia deve ser uma imagem válida.',

            'fotografia.mimes' => 'A fotografia deve estar no formato JPG, PNG ou WebP.',

            'fotografia.max' => 'A fotografia não pode ter mais de 10 MiB.',

            'palavra_passe.required' => 'Por favor, insere uma palavra-passe.',

            'palavra_passe.string' => 'A palavra-passe deve ser uma sequência de caracteres.',

            'palavra_passe.max' => 'A palavra-passe é demasiado longa.',

            'confirmacao_palavra_passe.required' => 'Por favor, confirma a palavra-passe.',

            'confirmacao_palavra_passe.string' => 'A confirmação da palavra-passe não é válida.',

            'confirmacao_palavra_passe.max' => 'A confirmação da palavra-passe é demasiado longa.',

            'confirmacao_palavra_passe.same' => 'A palavra-passe e a confirmação não coincidem.',

            'permissoes_email.array' => 'As permissões de e-mail recebidas não são válidas.',

            'permissoes_email.list' => 'A lista de permissões de e-mail não tem um formato válido.',

            'permissoes_email.*.required' => 'Uma das permissões de e-mail não é válida.',

            'permissoes_email.*.integer' => 'Uma das permissões de e-mail não é válida.',

            'permissoes_email.*.distinct' => 'A mesma permissão de e-mail foi selecionada mais do que uma vez.',

            'permissoes_email.*.exists' => 'Uma das permissões de e-mail selecionadas não existe.',
        ];
    }

    /**
     * Obtém os nomes apresentados para os atributos validados.
     *
     * @return array<string, string> Nomes legíveis dos atributos.
     *
     * @since 2.0.0
     */
    public function attributes(): array
    {
        return [
            'codigo_convite' => 'código do convite',

            'nome' => 'nome',

            'email' => 'endereço de e-mail',

            'fotografia' => 'fotografia',

            'palavra_passe' => 'palavra-passe',

            'confirmacao_palavra_passe' => 'confirmação da palavra-passe',

            'permissoes_email' => 'permissões de e-mail',

            'permissoes_email.*' => 'permissão de e-mail',
        ];
    }

    /**
     * Obtém o código do convite validado.
     *
     * @return string Código do convite.
     *
     * @since 2.0.0
     */
    public function codigoConvite(): string
    {
        return $this->obterTextoValidado(
            'codigo_convite',
        );
    }

    /**
     * Obtém o nome validado e normalizado.
     *
     * @return string Nome do utilizador.
     *
     * @throws LogicException Quando o resultado validado deixa de cumprir o
     *                        contrato do objeto de valor.
     *
     * @since 2.0.0
     */
    public function nome(): string
    {
        $nome =
            $this->obterTextoValidado(
                'nome',
            );

        try {
            return NomeUtilizador::deTexto(
                $nome,
            )->valor();
        } catch (InvalidArgumentException $excecao) {
            throw new LogicException(
                'O pedido validado não contém um nome de utilizador válido.',
                previous: $excecao,
            );
        }
    }

    /**
     * Obtém o endereço de e-mail validado e normalizado.
     *
     * @return string Endereço de e-mail.
     *
     * @throws LogicException Quando o resultado validado deixa de cumprir o
     *                        contrato do objeto de valor.
     *
     * @since 2.0.0
     */
    public function email(): string
    {
        $email =
            $this->obterTextoValidado(
                'email',
            );

        try {
            return EnderecoEmail::deTexto(
                $email,
            )->valor();
        } catch (InvalidArgumentException $excecao) {
            throw new LogicException(
                'O pedido validado não contém um endereço de e-mail válido.',
                previous: $excecao,
            );
        }
    }

    /**
     * Obtém a palavra-passe validada.
     *
     * @return string Palavra-passe em texto simples.
     *
     * @since 2.0.0
     */
    public function palavraPasse(): string
    {
        return $this->obterTextoValidado(
            'palavra_passe',
        );
    }

    /**
     * Obtém a fotografia validada.
     *
     * @return UploadedFile|null Fotografia enviada ou nulo.
     *
     * @throws LogicException Quando o ficheiro validado possui um tipo
     *                        inesperado.
     *
     * @since 2.0.0
     */
    public function fotografia(): ?UploadedFile
    {
        $fotografia =
            $this->file(
                'fotografia',
            );

        if ($fotografia === null) {
            return null;
        }

        if (! $fotografia instanceof UploadedFile) {
            throw new LogicException(
                'A fotografia validada possui um tipo inesperado.',
            );
        }

        return $fotografia;
    }

    /**
     * Obtém os identificadores validados das permissões de e-mail.
     *
     * @return list<int> Identificadores das permissões.
     *
     * @throws LogicException Quando a lista validada possui uma estrutura ou
     *                        um tipo inesperado.
     *
     * @since 2.0.0
     */
    public function identificadoresPermissoesEmail(): array
    {
        $identificadores =
            $this->validated(
                'permissoes_email',
                [],
            );

        if (
            ! is_array($identificadores)
            || ! array_is_list($identificadores)
        ) {
            throw new LogicException(
                'As permissões de e-mail validadas não formam uma lista válida.',
            );
        }

        foreach ($identificadores as $identificador) {
            if (! is_int($identificador)) {
                throw new LogicException(
                    'Uma permissão de e-mail validada possui um tipo inesperado.',
                );
            }
        }

        /** @var list<int> $identificadores */
        return $identificadores;
    }

    /**
     * Cria a regra de validação do nome do utilizador.
     *
     * A normalização, o comprimento e os caracteres permitidos pertencem ao
     * objeto de valor {@see NomeUtilizador}.
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

            try {
                NomeUtilizador::deTexto(
                    $valor,
                );
            } catch (InvalidArgumentException) {
                $falhar(
                    'Por favor, insere um nome válido.',
                );
            }
        };
    }

    /**
     * Cria a regra de validação do endereço de e-mail.
     *
     * A sintaxe, o comprimento e a normalização definitiva pertencem ao
     * objeto de valor {@see EnderecoEmail}.
     *
     * @return Closure(string, mixed, Closure(string): void): void Regra.
     *
     * @since 2.0.0
     */
    private function criarRegraEnderecoEmail(): Closure
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
                EnderecoEmail::deTexto(
                    $valor,
                );
            } catch (InvalidArgumentException) {
                $falhar(
                    'Por favor, insere um endereço de e-mail válido.',
                );
            }
        };
    }

    /**
     * Obtém um texto validado.
     *
     * @param  string  $campo  Nome do campo validado.
     * @return string Texto validado.
     *
     * @throws LogicException Quando o valor validado possui um tipo
     *                        inesperado.
     *
     * @since 2.0.0
     */
    private function obterTextoValidado(
        string $campo,
    ): string {
        $valor =
            $this->validated(
                $campo,
            );

        if (! is_string($valor)) {
            throw new LogicException(
                sprintf(
                    'O campo validado "%s" possui um tipo inesperado.',
                    $campo,
                ),
            );
        }

        return $valor;
    }

    /**
     * Normaliza preliminarmente o código do convite.
     *
     * Quando o código cumpre o contrato de {@see Convite}, é utilizada a
     * representação canónica. Um valor inválido permanece inalterado para que
     * as regras de validação o rejeitem sem ocultar caracteres proibidos.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Código normalizado ou valor original.
     *
     * @since 2.0.0
     */
    private function normalizarCodigoConvite(
        mixed $valor,
    ): mixed {
        if (! is_string($valor)) {
            return $valor;
        }

        try {
            return Convite::normalizarCodigo(
                $valor,
            );
        } catch (InvalidArgumentException) {
            return $valor;
        }
    }

    /**
     * Normaliza preliminarmente o nome recebido.
     *
     * Quando o nome cumpre o contrato de {@see NomeUtilizador}, é utilizada a
     * representação canónica. Um valor inválido permanece inalterado para que
     * a regra do objeto de valor o rejeite sem ocultar caracteres proibidos.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Nome normalizado ou valor original.
     *
     * @since 2.0.0
     */
    private function normalizarNome(
        mixed $valor,
    ): mixed {
        if (! is_string($valor)) {
            return $valor;
        }

        try {
            return NomeUtilizador::deTexto(
                $valor,
            )->valor();
        } catch (InvalidArgumentException) {
            return $valor;
        }
    }

    /**
     * Normaliza preliminarmente o endereço de e-mail.
     *
     * Quando o endereço cumpre o contrato de {@see EnderecoEmail}, é utilizada
     * a representação canónica. Um valor inválido permanece inalterado para
     * que a regra do objeto de valor o rejeite sem ocultar caracteres
     * proibidos.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Endereço normalizado ou valor original.
     *
     * @since 2.0.0
     */
    private function normalizarEmail(
        mixed $valor,
    ): mixed {
        if (! is_string($valor)) {
            return $valor;
        }

        try {
            return EnderecoEmail::deTexto(
                $valor,
            )->valor();
        } catch (InvalidArgumentException) {
            return $valor;
        }
    }

    /**
     * Normaliza os identificadores das permissões de e-mail.
     *
     * Os valores numéricos são convertidos para inteiros. Os restantes são
     * preservados para que a validação os rejeite.
     *
     * A estrutura original é mantida para permitir que a regra `list` rejeite
     * listas associativas ou com índices em falta.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Lista normalizada ou valor original.
     *
     * @since 2.0.0
     */
    private function normalizarPermissoesEmail(
        mixed $valor,
    ): mixed {
        if ($valor === null) {
            return [];
        }

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

    /**
     * Normaliza um identificador.
     *
     * @param  mixed  $valor  Valor recebido.
     * @return mixed Identificador normalizado ou valor original.
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
}
