<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enumeracoes\IdentificadorPermissaoEmail;
use App\Models\Autenticacao\Utilizador;
use App\Models\Interacoes\Audicao;
use App\Models\Interacoes\Avaliacao;
use App\Models\Interacoes\Comentario;
use App\Models\Interacoes\Gosto;
use App\Models\MetalThursday\Edicao;
use App\Models\MetalThursday\MetalThursday;
use App\Models\MetalThursday\SeccaoMetalThursday;
use App\Models\MetalThursday\TipoSeccao;
use App\Models\Musica\Artista;
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
 */
final class NotificacaoInteracaoUtilizador extends NotificacaoAplicacao
{
    /**
     * Tipo interno correspondente a uma MetalThursday.
     *
     * @since 2.0.0
     */
    private const TIPO_METAL_THURSDAY =
        'metal_thursday';

    /**
     * Tipo interno correspondente a uma secção.
     *
     * @since 2.0.0
     */
    private const TIPO_SECCAO =
        'seccao_metal_thursday';

    /**
     * Tipo interno correspondente a um comentário.
     *
     * @since 2.0.0
     */
    private const TIPO_COMENTARIO =
        'comentario';

    /**
     * Tipo interno do sujeito da interação.
     *
     * @since 2.0.0
     */
    private readonly string $tipoSujeito;

    /**
     * Identificador do sujeito da interação.
     *
     * @since 2.0.0
     */
    private readonly int $identificadorSujeito;

    /**
     * Identificador do utilizador que provocou a interação.
     *
     * @since 2.0.0
     */
    private readonly int $identificadorCausador;

    /**
     * Nome do utilizador no momento da interação.
     *
     * @since 2.0.0
     */
    private readonly string $nomeCausador;

    /**
     * Ação realizada pelo utilizador.
     *
     * @since 2.0.0
     */
    private readonly string $acao;

    /**
     * Identificador do registo que originou a notificação.
     *
     * Permanece nulo quando a ação não possui um registo de origem persistido.
     * O valor predefinido também evita erros
     * ao desserializar trabalhos criados antes desta versão.
     *
     * @since 2.0.0
     */
    private ?int $identificadorRegistoAcao = null;

    /**
     * Identificador da MetalThursday relacionada com a interação.
     *
     * @since 2.0.0
     */
    private readonly ?int $identificadorMetalThursday;

    /**
     * Identificador do autor da MetalThursday relacionada.
     *
     * @since 2.0.0
     */
    private readonly ?int $identificadorAutorMetalThursday;

    /**
     * Descrição do sujeito ou do contexto do comentário.
     *
     * @since 2.0.0
     */
    private readonly string $descricaoContexto;

    /**
     * Identificador do autor quando o sujeito é um comentário.
     *
     * @since 2.0.0
     */
    private readonly ?int $identificadorAutorComentario;

    /**
     * Nome do autor quando o sujeito é um comentário.
     *
     * @since 2.0.0
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
     * @param  Comentario|null  $comentarioOrigem  Comentário ou resposta criado
     *                                             pela ação, quando aplicável.
     *
     * @throws InvalidArgumentException Quando um modelo, identificador ou a
     *                                  ação não são válidos.
     *
     * @since 1.0.0
     */
    public function __construct(
        MetalThursday|SeccaoMetalThursday|Comentario $sujeito,
        Utilizador $causador,
        string $acao,
        ?Comentario $comentarioOrigem = null,
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

        $this->identificadorRegistoAcao =
            $comentarioOrigem !== null
            && in_array($this->acao, ['comentou', 'respondeu'], true)
                ? $this->obterIdentificadorPersistido(
                    $comentarioOrigem,
                    'O comentário que originou a interação',
                )
                : $this->obterIdentificadorRegistoAcao($sujeito);

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
     * Captura o registo concreto de uma interação reversível.
     *
     * Consultar apenas a existência de outra interação do mesmo utilizador
     * seria insuficiente: uma ação retirada pode ser recriada antes da entrega.
     *
     * @param  MetalThursday|SeccaoMetalThursday|Comentario  $sujeito  Sujeito.
     * @return int|null Identificador do registo original.
     *
     * @since 2.0.0
     */
    private function obterIdentificadorRegistoAcao(
        MetalThursday|SeccaoMetalThursday|Comentario $sujeito,
    ): ?int {
        $identificador = match (true) {
            $this->acao === 'avaliou'
                && ! $sujeito instanceof Comentario => $sujeito->avaliacoes()
                    ->where('utilizador_id', $this->identificadorCausador)
                    ->value('id'),

            $this->acao === 'ouviu'
                && ! $sujeito instanceof Comentario => $sujeito->audicoes()
                    ->where('utilizador_id', $this->identificadorCausador)
                    ->value('id'),

            $this->acao === 'gostou'
                && $sujeito instanceof Comentario => $sujeito->gostos()
                    ->where('utilizador_id', $this->identificadorCausador)
                    ->value('id'),

            default => null,
        };

        return $this->normalizarIdentificador($identificador);
    }

    /**
     * Confirma a existência do comentário ou resposta que originou o aviso.
     *
     * Verifica também o conteúdo removido por marcador e a relação original
     * entre a resposta e o comentário respondido.
     *
     * @return bool Verdadeiro apenas para um comentário ou resposta válido.
     *
     * @since 2.0.0
     */
    private function comentarioOriginalAindaExiste(): bool
    {
        if ($this->identificadorRegistoAcao === null) {
            return false;
        }

        $comentarioOriginal = Comentario::query()
            ->whereKey($this->identificadorRegistoAcao)
            ->where('utilizador_id', $this->identificadorCausador)
            ->whereNull('conteudo_eliminado_em');

        if ($this->acao === 'comentou') {
            if ($this->tipoSujeito === self::TIPO_COMENTARIO) {
                return false;
            }

            return $comentarioOriginal
                ->whereNull('comentario_pai_id')
                ->where('tipo_comentavel', $this->tipoSujeito)
                ->where('comentavel_id', $this->identificadorSujeito)
                ->exists();
        }

        if (
            $this->acao !== 'respondeu'
            || $this->tipoSujeito !== self::TIPO_COMENTARIO
        ) {
            return false;
        }

        $comentarioRespondido = Comentario::query()
            ->whereKey($this->identificadorSujeito)
            ->whereNull('conteudo_eliminado_em')
            ->first(['tipo_comentavel', 'comentavel_id']);

        if (! $comentarioRespondido instanceof Comentario) {
            return false;
        }

        return $comentarioOriginal
            ->where('comentario_pai_id', $this->identificadorSujeito)
            ->where('tipo_comentavel', $comentarioRespondido->tipo_comentavel)
            ->where('comentavel_id', $comentarioRespondido->comentavel_id)
            ->exists();
    }

    /**
     * Consulta a interação original, com o mesmo sujeito e causador.
     *
     * @return bool Verdadeiro se o registo original ainda existe.
     *
     * @since 2.0.0
     */
    private function registoAcaoAindaExiste(): bool
    {
        if ($this->identificadorRegistoAcao === null) {
            return false;
        }

        return match ($this->acao) {
            'avaliou' => Avaliacao::query()
                ->whereKey($this->identificadorRegistoAcao)
                ->where('utilizador_id', $this->identificadorCausador)
                ->where('tipo_avaliavel', $this->tipoSujeito)
                ->where('avaliavel_id', $this->identificadorSujeito)
                ->exists(),

            'ouviu' => Audicao::query()
                ->whereKey($this->identificadorRegistoAcao)
                ->where('utilizador_id', $this->identificadorCausador)
                ->where('tipo_audivel', $this->tipoSujeito)
                ->where('audivel_id', $this->identificadorSujeito)
                ->exists(),

            'gostou' => Gosto::query()
                ->whereKey($this->identificadorRegistoAcao)
                ->where('utilizador_id', $this->identificadorCausador)
                ->where('comentario_id', $this->identificadorSujeito)
                ->whereIn(
                    'comentario_id',
                    Comentario::query()
                        ->whereKey($this->identificadorSujeito)
                        ->whereNull('conteudo_eliminado_em')
                        ->select('id'),
                )
                ->exists(),

            default => false,
        };
    }

    /**
     * Revalida a secção onde a interação foi realizada.
     *
     * A publicação pode continuar disponível depois de uma secção ter sido
     * eliminada logicamente. Para respostas e gostos, a secção é resolvida
     * através do comentário original, evitando guardar novos dados na fila.
     *
     * @return bool Verdadeiro se não existe uma secção indisponível.
     *
     * @since 2.0.0
     */
    private function seccaoDaInteracaoAindaDisponivel(): bool
    {
        if ($this->tipoSujeito === self::TIPO_METAL_THURSDAY) {
            return true;
        }

        if ($this->tipoSujeito === self::TIPO_SECCAO) {
            return SeccaoMetalThursday::query()
                ->whereKey($this->identificadorSujeito)
                ->where('metal_thursday_id', $this->identificadorMetalThursday)
                ->exists();
        }

        if ($this->tipoSujeito !== self::TIPO_COMENTARIO) {
            return false;
        }

        $comentario = Comentario::query()
            ->whereKey($this->identificadorSujeito)
            ->first(['tipo_comentavel', 'comentavel_id']);

        if (! $comentario instanceof Comentario) {
            return false;
        }

        if ($comentario->tipo_comentavel === self::TIPO_METAL_THURSDAY) {
            return $comentario->comentavel_id === $this->identificadorMetalThursday;
        }

        if ($comentario->tipo_comentavel !== self::TIPO_SECCAO) {
            return false;
        }

        return SeccaoMetalThursday::query()
            ->whereKey($comentario->comentavel_id)
            ->where('metal_thursday_id', $this->identificadorMetalThursday)
            ->exists();
    }

    /**
     * Revalida o acesso do destinatário no momento efetivo da entrega.
     *
     * O destinatário pode ser suspenso depois de a notificação entrar na fila.
     * A consulta à base de dados evita utilizar o estado potencialmente
     * desatualizado do modelo serializado no trabalho da fila e impede a
     * entrega por qualquer canal a uma conta suspensa ou entretanto removida.
     *
     * @param  object  $notificavel  Destinatário da notificação.
     * @param  string  $canal  Canal que está prestes a ser utilizado.
     * @return bool Verdadeiro apenas para destinatários ainda ativos.
     *
     * @since 2.0.0
     */
    public function shouldSend(
        object $notificavel,
        string $canal,
    ): bool {
        if (
            ! $notificavel instanceof Utilizador
            || ! $notificavel->exists
        ) {
            return false;
        }

        if (
            ! Utilizador::query()
                ->comAcessoAtivo()
                ->whereKey($notificavel->getKey())
                ->exists()
        ) {
            return false;
        }

        // A publicação pode ter sido eliminada ou reagendada depois do COMMIT.
        // O scope exclui registos eliminados logicamente e datas futuras.
        if (
            $this->identificadorMetalThursday === null
            || ! MetalThursday::query()
                ->publicadas()
                ->whereKey($this->identificadorMetalThursday)
                ->exists()
        ) {
            return false;
        }

        // Uma secção eliminada invalida também as interações nela registadas.
        if (! $this->seccaoDaInteracaoAindaDisponivel()) {
            return false;
        }

        // Validar o registo original, não apenas uma ação entretanto recriada.
        if (
            in_array($this->acao, ['avaliou', 'gostou', 'ouviu'], true)
            && ! $this->registoAcaoAindaExiste()
        ) {
            return false;
        }

        // Uma resposta ou comentário entretanto removido não gera avisos.
        if (
            in_array($this->acao, ['comentou', 'respondeu'], true)
            && ! $this->comentarioOriginalAindaExiste()
        ) {
            return false;
        }

        return parent::shouldSend($notificavel, $canal);
    }

    /**
     * Determina se a notificação deve ser enviada por e-mail.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return bool Verdadeiro quando o envio está autorizado.
     *
     * @since 1.0.0
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
            IdentificadorPermissaoEmail::TodasNotificacoes->value,
        )
            || $utilizador->temPermissaoEmail(
                IdentificadorPermissaoEmail::TodasInteracoes->value,
            )
            || (
                $eAutor
                && $utilizador->temPermissaoEmail(
                    IdentificadorPermissaoEmail::InteracoesNasMinhasPublicacoes->value,
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
     */
    protected function obterTextoAcao(
        Utilizador $utilizador,
    ): string {
        return 'Ver atividade';
    }

    /**
     * Obtém o endereço da MetalThursday relacionada com a interação.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Endereço da MetalThursday ou da página principal.
     *
     * @since 1.0.0
     */
    protected function obterUrlAcao(
        Utilizador $utilizador,
    ): string {
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
     * @since 2.0.0
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
     * @since 2.0.0
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
     * @since 2.0.0
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
     * @since 2.0.0
     */
    private function criarRetratoSeccao(
        SeccaoMetalThursday $seccao,
    ): array {
        $seccao->loadMissing([
            'metalThursday',
            'tipoSeccao:id,nome',
            'artista:id,nome',
        ]);

        $metalThursday =
            $seccao->metalThursday;

        $identificadorMetalThursday = null;
        $identificadorAutorMetalThursday = null;

        if ($metalThursday instanceof MetalThursday) {
            $identificadorMetalThursday =
                $this->normalizarIdentificador(
                    $metalThursday->getKey(),
                );

            $identificadorAutorMetalThursday =
                $this->normalizarIdentificador(
                    $metalThursday->autor_id,
                );
        }

        return [
            'identificador_metal_thursday' => $identificadorMetalThursday,

            'identificador_autor_metal_thursday' => $identificadorAutorMetalThursday,

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
     * @since 2.0.0
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

        $artista =
            $seccao->artista;

        $nomeArtista =
            $artista instanceof Artista
            && is_string($artista->nome)
            && trim($artista->nome) !== ''
            ? trim(
                $artista->nome,
            )
            : null;

        $descricao =
            $nomeTipo !== null
            ? sprintf(
                'secção %s',
                $nomeTipo,
            )
            : 'secção';

        if ($nomeArtista !== null) {
            $descricao .= sprintf(
                ' de %s',
                $nomeArtista,
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
     * @since 2.0.0
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
     * Representações textuais podem conter apenas espaços ASCII exteriores.
     * Restantes caracteres, incluindo caracteres de controlo, não são
     * removidos antes da validação.
     *
     * @param  mixed  $identificador  Valor recebido.
     * @return int|null Identificador normalizado ou nulo.
     *
     * @since 2.0.0
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
                ' ',
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
