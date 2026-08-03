<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Comentario;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Banda;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Notifica os utilizadores quando ocorre uma interação numa MetalThursday,
 * numa secção ou num comentário.
 *
 * A notificação guarda apenas um retrato de identificadores e valores
 * escalares obtidos no momento da interação. O processamento posterior da
 * fila não recupera o sujeito nem as respetivas relações da base de dados.
 *
 * @since 1.0.0
 *
 * @version 3.1.0
 */
final class NotificacaoInteracaoUtilizador extends NotificacaoAplicacao
{
    /**
     * Tipo interno correspondente a uma MetalThursday.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TIPO_METAL_THURSDAY =
        'metal_thursday';

    /**
     * Tipo interno correspondente a uma secção.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TIPO_SECCAO =
        'seccao_metal_thursday';

    /**
     * Tipo interno correspondente a um comentário.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TIPO_COMENTARIO =
        'comentario';

    /**
     * Permissão que autoriza todas as comunicações por e-mail.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const PERMISSAO_TODAS =
        'todas';

    /**
     * Permissão relativa a todas as novas interações.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const PERMISSAO_NOVAS_INTERACOES =
        'novas_interacoes';

    /**
     * Permissão relativa às interações nas publicações do utilizador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const PERMISSAO_INTERACOES_PUBLICACOES =
        'interacoes_nas_minhas_publicacoes';

    /**
     * Tipo interno do sujeito da interação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private readonly string $tipoSujeito;

    /**
     * Identificador do sujeito da interação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private readonly int $identificadorSujeito;

    /**
     * Identificador do utilizador que provocou a interação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private readonly int $identificadorCausador;

    /**
     * Nome do utilizador no momento da interação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private readonly string $nomeCausador;

    /**
     * Ação realizada pelo utilizador.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private readonly string $acao;

    /**
     * Identificador da MetalThursday relacionada com a interação.
     *
     * @since 3.1.0
     *
     * @version 1.0.0
     */
    private readonly ?int $identificadorMetalThursday;

    /**
     * Identificador do autor da MetalThursday relacionada.
     *
     * @since 3.1.0
     *
     * @version 1.0.0
     */
    private readonly ?int $identificadorAutorMetalThursday;

    /**
     * Descrição do sujeito ou do contexto do comentário.
     *
     * @since 3.1.0
     *
     * @version 1.0.0
     */
    private readonly string $descricaoContexto;

    /**
     * Identificador do autor quando o sujeito é um comentário.
     *
     * @since 3.1.0
     *
     * @version 1.0.0
     */
    private readonly ?int $identificadorAutorComentario;

    /**
     * Nome do autor quando o sujeito é um comentário.
     *
     * @since 3.1.0
     *
     * @version 1.0.0
     */
    private readonly string $nomeAutorComentario;

    /**
     * Cria a notificação e captura o contexto necessário para a fila.
     *
     * @param  MetalThursday|SeccaoMetalThursday|Comentario  $sujeito  Sujeito
     *                                                                 da
     *                                                                 interação.
     * @param  Utilizador  $causador  Utilizador que realizou a interação.
     * @param  string  $acao  Ação realizada.
     *
     * @throws InvalidArgumentException Quando um modelo, identificador ou a
     *                                  ação não são válidos.
     *
     * @since 1.0.0
     *
     * @version 3.1.0
     */
    public function __construct(
        MetalThursday|SeccaoMetalThursday|Comentario $sujeito,
        Utilizador $causador,
        string $acao,
    ) {
        $this->tipoSujeito = match (true) {
            $sujeito instanceof MetalThursday => self::TIPO_METAL_THURSDAY,

            $sujeito instanceof SeccaoMetalThursday => self::TIPO_SECCAO,

            $sujeito instanceof Comentario => self::TIPO_COMENTARIO,
        };

        $this->identificadorSujeito =
            $this->obterIdentificadorPersistido(
                $sujeito,
                'O sujeito da interação',
            );

        $this->identificadorCausador =
            $this->obterIdentificadorPersistido(
                $causador,
                'O utilizador responsável pela interação',
            );

        $this->nomeCausador =
            $this->normalizarNomeUtilizador(
                $causador->nome,
                'Um utilizador',
            );

        $this->acao =
            $this->normalizarAcao(
                $acao,
            );

        $retrato =
            $this->criarRetratoSujeito(
                $sujeito,
            );

        $this->identificadorMetalThursday =
            $retrato['identificador_metal_thursday'];

        $this->identificadorAutorMetalThursday =
            $retrato['identificador_autor_metal_thursday'];

        $this->descricaoContexto =
            $retrato['descricao_contexto'];

        $this->identificadorAutorComentario =
            $retrato['identificador_autor_comentario'];

        $this->nomeAutorComentario =
            $retrato['nome_autor_comentario'];

        $this->afterCommit();
    }

    /**
     * Determina se a notificação deve ser enviada por e-mail.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return bool Verdadeiro quando o envio está autorizado.
     *
     * @since 1.0.0
     *
     * @version 3.1.0
     */
    protected function deveEnviarPorEmail(
        Utilizador $utilizador,
    ): bool {
        $identificadorDestinatario =
            $this->normalizarIdentificador(
                $utilizador->getKey(),
            );

        $eAutor =
            $identificadorDestinatario !== null
            && $this->identificadorAutorMetalThursday !== null
            && $identificadorDestinatario
            === $this->identificadorAutorMetalThursday;

        return $utilizador->temPermissaoEmail(
            self::PERMISSAO_TODAS,
        )
            || $utilizador->temPermissaoEmail(
                self::PERMISSAO_NOVAS_INTERACOES,
            )
            || (
                $eAutor
                && $utilizador->temPermissaoEmail(
                    self::PERMISSAO_INTERACOES_PUBLICACOES,
                )
            );
    }

    /**
     * Converte a notificação para o formato guardado na base de dados.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return array{
     *     tipo: string,
     *     tipo_sujeito: string,
     *     identificador_sujeito: int,
     *     identificador_causador: int,
     *     acao: string,
     *     titulo: string,
     *     mensagem: string,
     *     ligacao: string,
     *     icone: string,
     *     cor: string
     * } Dados persistidos.
     *
     * @since 1.0.0
     *
     * @version 3.1.0
     */
    public function toArray(
        Utilizador $utilizador,
    ): array {
        return [
            'tipo' => 'interacao_utilizador',

            'tipo_sujeito' => $this->tipoSujeito,

            'identificador_sujeito' => $this->identificadorSujeito,

            'identificador_causador' => $this->identificadorCausador,

            'acao' => $this->acao,

            'titulo' => $this->obterTituloNotificacao(),

            'mensagem' => $this->obterMensagem(
                $utilizador,
            ),

            'ligacao' => $this->obterUrlAcao(
                $utilizador,
            ),

            'icone' => $this->obterIcone(),

            'cor' => $this->obterClasseCor(),
        ];
    }

    /**
     * Obtém o assunto da mensagem de e-mail.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Assunto da mensagem.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected function obterAssunto(
        Utilizador $utilizador,
    ): string {
        return 'Nova interação no MetalThursday';
    }

    /**
     * Obtém a linha principal da mensagem.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Conteúdo principal.
     *
     * @since 1.0.0
     *
     * @version 3.1.0
     */
    protected function obterLinhaMensagem(
        Utilizador $utilizador,
    ): string {
        return $this->obterMensagem(
            $utilizador,
        );
    }

    /**
     * Obtém o texto apresentado no botão da mensagem.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Texto do botão.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected function obterTextoAcao(
        Utilizador $utilizador,
    ): ?string {
        return 'Ver atividade';
    }

    /**
     * Obtém o endereço da MetalThursday relacionada com a interação.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Endereço da MetalThursday ou da página principal.
     *
     * @since 1.0.0
     *
     * @version 3.1.0
     */
    protected function obterUrlAcao(
        Utilizador $utilizador,
    ): ?string {
        if ($this->identificadorMetalThursday === null) {
            return route(
                'inicio',
            );
        }

        return route(
            'metal-thursday.detalhes',
            [
                'metalThursday' => $this->identificadorMetalThursday,
            ],
        );
    }

    /**
     * Obtém o título visual da notificação.
     *
     * @return string Título da notificação.
     *
     * @since 3.0.0
     *
     * @version 1.0.0
     */
    private function obterTituloNotificacao(): string
    {
        return match ($this->acao) {
            'avaliou' => 'Nova avaliação',

            'ouviu' => 'Nova audição',

            'gostou' => 'Novo gosto',

            'respondeu' => 'Nova resposta',

            'comentou' => 'Novo comentário',

            default => 'Nova interação',
        };
    }

    /**
     * Constrói a mensagem apresentada ao destinatário.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Mensagem construída.
     *
     * @since 1.0.0
     *
     * @version 3.1.0
     */
    private function obterMensagem(
        Utilizador $utilizador,
    ): string {
        if ($this->tipoSujeito === self::TIPO_COMENTARIO) {
            return $this->obterMensagemComentario(
                $utilizador,
            );
        }

        return match ($this->acao) {
            'comentou' => sprintf(
                '%s comentou na %s.',
                $this->nomeCausador,
                $this->descricaoContexto,
            ),

            'avaliou' => sprintf(
                '%s avaliou a %s.',
                $this->nomeCausador,
                $this->descricaoContexto,
            ),

            'ouviu' => sprintf(
                '%s assinalou a %s como ouvida.',
                $this->nomeCausador,
                $this->descricaoContexto,
            ),

            'gostou' => sprintf(
                '%s gostou da %s.',
                $this->nomeCausador,
                $this->descricaoContexto,
            ),

            default => sprintf(
                '%s %s a %s.',
                $this->nomeCausador,
                $this->acao,
                $this->descricaoContexto,
            ),
        };
    }

    /**
     * Constrói a mensagem relativa a um comentário.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Mensagem construída.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function obterMensagemComentario(
        Utilizador $utilizador,
    ): string {
        $identificadorDestinatario =
            $this->normalizarIdentificador(
                $utilizador->getKey(),
            );

        $eAutorComentario =
            $identificadorDestinatario !== null
            && $this->identificadorAutorComentario !== null
            && $identificadorDestinatario
            === $this->identificadorAutorComentario;

        if ($eAutorComentario) {
            return match ($this->acao) {
                'gostou' => sprintf(
                    '%s gostou do teu comentário em %s.',
                    $this->nomeCausador,
                    $this->descricaoContexto,
                ),

                'respondeu' => sprintf(
                    '%s respondeu ao teu comentário em %s.',
                    $this->nomeCausador,
                    $this->descricaoContexto,
                ),

                default => sprintf(
                    '%s %s o teu comentário em %s.',
                    $this->nomeCausador,
                    $this->acao,
                    $this->descricaoContexto,
                ),
            };
        }

        return match ($this->acao) {
            'gostou' => sprintf(
                '%s gostou de um comentário de %s em %s.',
                $this->nomeCausador,
                $this->nomeAutorComentario,
                $this->descricaoContexto,
            ),

            'respondeu' => sprintf(
                '%s respondeu a um comentário de %s em %s.',
                $this->nomeCausador,
                $this->nomeAutorComentario,
                $this->descricaoContexto,
            ),

            default => sprintf(
                '%s %s um comentário de %s em %s.',
                $this->nomeCausador,
                $this->acao,
                $this->nomeAutorComentario,
                $this->descricaoContexto,
            ),
        };
    }

    /**
     * Captura o retrato do sujeito e do respetivo contexto.
     *
     * @param  MetalThursday|SeccaoMetalThursday|Comentario  $sujeito  Sujeito
     *                                                                 original.
     * @return array{
     *     identificador_metal_thursday: int|null,
     *     identificador_autor_metal_thursday: int|null,
     *     descricao_contexto: string,
     *     identificador_autor_comentario: int|null,
     *     nome_autor_comentario: string
     * } Retrato escalar do contexto.
     *
     * @since 3.1.0
     *
     * @version 1.0.0
     */
    private function criarRetratoSujeito(
        MetalThursday|SeccaoMetalThursday|Comentario $sujeito,
    ): array {
        if ($sujeito instanceof MetalThursday) {
            return $this->criarRetratoMetalThursday(
                $sujeito,
            );
        }

        if ($sujeito instanceof SeccaoMetalThursday) {
            return $this->criarRetratoSeccao(
                $sujeito,
            );
        }

        return $this->criarRetratoComentario(
            $sujeito,
        );
    }

    /**
     * Captura o retrato de uma MetalThursday.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday original.
     * @return array{
     *     identificador_metal_thursday: int,
     *     identificador_autor_metal_thursday: int|null,
     *     descricao_contexto: string,
     *     identificador_autor_comentario: null,
     *     nome_autor_comentario: string
     * } Retrato escalar.
     *
     * @since 3.1.0
     *
     * @version 1.0.0
     */
    private function criarRetratoMetalThursday(
        MetalThursday $metalThursday,
    ): array {
        $metalThursday->loadMissing([
            'edicao:id,nome',
        ]);

        return [
            'identificador_metal_thursday' => $this->obterIdentificadorPersistido(
                $metalThursday,
                'A MetalThursday relacionada com a interação',
            ),

            'identificador_autor_metal_thursday' => $this->normalizarIdentificador(
                $metalThursday->autor_id,
            ),

            'descricao_contexto' => $this->obterDescricaoMetalThursday(
                $metalThursday,
            ),

            'identificador_autor_comentario' => null,

            'nome_autor_comentario' => 'um utilizador',
        ];
    }

    /**
     * Captura o retrato de uma secção.
     *
     * @param  SeccaoMetalThursday  $seccao  Secção original.
     * @return array{
     *     identificador_metal_thursday: int|null,
     *     identificador_autor_metal_thursday: int|null,
     *     descricao_contexto: string,
     *     identificador_autor_comentario: null,
     *     nome_autor_comentario: string
     * } Retrato escalar.
     *
     * @since 3.1.0
     *
     * @version 1.0.0
     */
    private function criarRetratoSeccao(
        SeccaoMetalThursday $seccao,
    ): array {
        $seccao->loadMissing([
            'metalThursday',
            'tipoSeccao:id,nome',
            'banda:id,nome',
        ]);

        $metalThursday =
            $seccao->metalThursday;

        return [
            'identificador_metal_thursday' => $metalThursday instanceof MetalThursday
                ? $this->normalizarIdentificador(
                    $metalThursday->getKey(),
                )
                : null,

            'identificador_autor_metal_thursday' => $metalThursday instanceof MetalThursday
                ? $this->normalizarIdentificador(
                    $metalThursday->autor_id,
                )
                : null,

            'descricao_contexto' => $this->obterDescricaoSeccao(
                $seccao,
            ),

            'identificador_autor_comentario' => null,

            'nome_autor_comentario' => 'um utilizador',
        ];
    }

    /**
     * Captura o retrato de um comentário e do respetivo conteúdo.
     *
     * @param  Comentario  $comentario  Comentário original.
     * @return array{
     *     identificador_metal_thursday: int|null,
     *     identificador_autor_metal_thursday: int|null,
     *     descricao_contexto: string,
     *     identificador_autor_comentario: int|null,
     *     nome_autor_comentario: string
     * } Retrato escalar.
     *
     * @since 3.1.0
     *
     * @version 1.0.0
     */
    private function criarRetratoComentario(
        Comentario $comentario,
    ): array {
        $comentario->loadMissing([
            'utilizador:id,nome',
            'comentavel',
        ]);

        $comentavel =
            $comentario->comentavel;

        if ($comentavel instanceof MetalThursday) {
            $retratoContexto =
                $this->criarRetratoMetalThursday(
                    $comentavel,
                );
        } elseif ($comentavel instanceof SeccaoMetalThursday) {
            $retratoContexto =
                $this->criarRetratoSeccao(
                    $comentavel,
                );
        } else {
            $retratoContexto = [
                'identificador_metal_thursday' => null,

                'identificador_autor_metal_thursday' => null,

                'descricao_contexto' => 'um conteúdo que foi entretanto removido',
            ];
        }

        return [
            'identificador_metal_thursday' => $retratoContexto['identificador_metal_thursday'],

            'identificador_autor_metal_thursday' => $retratoContexto['identificador_autor_metal_thursday'],

            'descricao_contexto' => $retratoContexto['descricao_contexto'],

            'identificador_autor_comentario' => $this->normalizarIdentificador(
                $comentario->utilizador_id,
            ),

            'nome_autor_comentario' => $this->normalizarNomeUtilizador(
                $comentario->utilizador?->nome,
                'um utilizador',
            ),
        ];
    }

    /**
     * Obtém a descrição de uma MetalThursday.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday descrita.
     * @return string Descrição construída.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function obterDescricaoMetalThursday(
        MetalThursday $metalThursday,
    ): string {
        $nome =
            $metalThursday->nome;

        if (
            is_string($nome)
            && trim($nome) !== ''
        ) {
            return sprintf(
                'MetalThursday «%s»',
                trim(
                    $nome,
                ),
            );
        }

        $edicao =
            $metalThursday->edicao;

        $nomeEdicao =
            $edicao instanceof Edicao
            && is_string($edicao->nome)
            && trim($edicao->nome) !== ''
            ? trim(
                $edicao->nome,
            )
            : null;

        $data =
            $metalThursday->data;

        $dataFormatada =
            $data instanceof CarbonInterface
            ? $data->format(
                'd/m/Y',
            )
            : null;

        if (
            $nomeEdicao !== null
            && $dataFormatada !== null
        ) {
            return sprintf(
                'MetalThursday de %s, em %s',
                $nomeEdicao,
                $dataFormatada,
            );
        }

        if ($nomeEdicao !== null) {
            return sprintf(
                'MetalThursday de %s',
                $nomeEdicao,
            );
        }

        if ($dataFormatada !== null) {
            return sprintf(
                'MetalThursday de %s',
                $dataFormatada,
            );
        }

        return sprintf(
            'MetalThursday #%d',
            $this->obterIdentificadorPersistido(
                $metalThursday,
                'A MetalThursday descrita',
            ),
        );
    }

    /**
     * Obtém a descrição de uma secção.
     *
     * @param  SeccaoMetalThursday  $seccao  Secção descrita.
     * @return string Descrição construída.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function obterDescricaoSeccao(
        SeccaoMetalThursday $seccao,
    ): string {
        $titulo =
            is_string($seccao->titulo)
            ? trim(
                $seccao->titulo,
            )
            : '';

        $tipo =
            $seccao->tipoSeccao;

        $nomeTipo =
            $tipo instanceof TipoSeccao
            && is_string($tipo->nome)
            && trim($tipo->nome) !== ''
            ? trim(
                $tipo->nome,
            )
            : null;

        $banda =
            $seccao->banda;

        $nomeBanda =
            $banda instanceof Banda
            && is_string($banda->nome)
            && trim($banda->nome) !== ''
            ? trim(
                $banda->nome,
            )
            : null;

        $descricao =
            $nomeTipo !== null
            ? sprintf(
                'secção %s',
                $nomeTipo,
            )
            : 'secção';

        if ($nomeBanda !== null) {
            $descricao .= sprintf(
                ' de %s',
                $nomeBanda,
            );
        }

        if ($titulo !== '') {
            $descricao .= sprintf(
                ' — «%s»',
                $titulo,
            );
        }

        return $descricao;
    }

    /**
     * Obtém o ícone correspondente à ação.
     *
     * @return string Classe do ícone.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterIcone(): string
    {
        return match ($this->acao) {
            'avaliou' => 'bi-star-fill',

            'ouviu' => 'bi-headphones',

            'gostou' => 'bi-heart-fill',

            'respondeu',
            'comentou' => 'bi-chat-quote-fill',

            default => 'bi-bell-fill',
        };
    }

    /**
     * Obtém a classe visual correspondente à ação.
     *
     * @return string Classe de apresentação.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterClasseCor(): string
    {
        return match ($this->acao) {
            'avaliou' => 'text-warning',

            'ouviu' => 'text-success',

            'gostou' => 'text-danger',

            default => 'text-info',
        };
    }

    /**
     * Normaliza e valida a ação recebida.
     *
     * @param  string  $acao  Ação original.
     * @return string Ação normalizada.
     *
     * @throws InvalidArgumentException Quando a ação contém texto inválido,
     *                                  fica vazia ou é demasiado extensa.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function normalizarAcao(
        string $acao,
    ): string {
        if (
            preg_match(
                '//u',
                $acao,
            ) !== 1
        ) {
            throw new InvalidArgumentException(
                'A ação da interação contém texto inválido.',
            );
        }

        if (
            preg_match(
                '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/',
                $acao,
            ) === 1
        ) {
            throw new InvalidArgumentException(
                'A ação da interação contém caracteres inválidos.',
            );
        }

        $acaoNormalizada =
            preg_replace(
                '/\s+/u',
                ' ',
                mb_strtolower(
                    trim(
                        $acao,
                    ),
                ),
            );

        if (
            ! is_string($acaoNormalizada)
            || $acaoNormalizada === ''
        ) {
            throw new InvalidArgumentException(
                'A ação da interação não pode estar vazia.',
            );
        }

        if (mb_strlen($acaoNormalizada) > 100) {
            throw new InvalidArgumentException(
                'A ação da interação não pode ter mais de 100 caracteres.',
            );
        }

        return $acaoNormalizada;
    }

    /**
     * Normaliza o nome de um utilizador.
     *
     * @param  mixed  $nome  Nome recebido.
     * @param  string  $alternativa  Texto utilizado perante um nome inválido.
     * @return string Nome normalizado ou alternativa.
     *
     * @since 2.0.0
     *
     * @version 2.0.0
     */
    private function normalizarNomeUtilizador(
        mixed $nome,
        string $alternativa,
    ): string {
        if (! is_string($nome)) {
            return $alternativa;
        }

        $nomeNormalizado =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    $nome,
                ),
            );

        return is_string($nomeNormalizado)
            && $nomeNormalizado !== ''
            ? $nomeNormalizado
            : $alternativa;
    }

    /**
     * Obtém o identificador inteiro de um modelo persistido.
     *
     * @param  Model  $modelo  Modelo recebido.
     * @param  string  $designacao  Designação utilizada na mensagem de erro.
     * @return int Identificador persistido.
     *
     * @throws InvalidArgumentException Quando o modelo não está persistido ou
     *                                  não possui um identificador válido.
     *
     * @since 3.1.0
     *
     * @version 1.0.0
     */
    private function obterIdentificadorPersistido(
        Model $modelo,
        string $designacao,
    ): int {
        if (! $modelo->exists) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s deve estar persistido.',
                    $designacao,
                ),
            );
        }

        $identificador =
            $this->normalizarIdentificador(
                $modelo->getKey(),
            );

        if ($identificador !== null) {
            return $identificador;
        }

        throw new InvalidArgumentException(
            sprintf(
                '%s deve possuir um identificador válido.',
                $designacao,
            ),
        );
    }

    /**
     * Normaliza um identificador inteiro positivo.
     *
     * @param  mixed  $identificador  Valor recebido.
     * @return int|null Identificador normalizado ou nulo.
     *
     * @since 3.1.0
     *
     * @version 1.0.0
     */
    private function normalizarIdentificador(
        mixed $identificador,
    ): ?int {
        if (
            is_int($identificador)
            && $identificador > 0
        ) {
            return $identificador;
        }

        if (! is_string($identificador)) {
            return null;
        }

        $identificadorNormalizado =
            trim(
                $identificador,
            );

        if (
            $identificadorNormalizado === ''
            || ! ctype_digit(
                $identificadorNormalizado,
            )
        ) {
            return null;
        }

        $identificadorInteiro =
            (int) $identificadorNormalizado;

        return $identificadorInteiro > 0
            ? $identificadorInteiro
            : null;
    }
}
