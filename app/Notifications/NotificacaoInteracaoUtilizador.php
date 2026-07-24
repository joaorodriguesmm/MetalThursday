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
use InvalidArgumentException;

/**
 * Notifica os utilizadores quando ocorre uma interação numa MetalThursday,
 * numa secção ou num comentário.
 *
 * A notificação guarda secção ou num comentário.
 *
 * A notificação guarda apenas identificadores e valores escalares, evitando
 * serializar modelos Eloquent completos para a fila.
 *
 * @since 1.0.0
 *
 * @version 2.0.0
 */
final class NotificacaoInteracaoUtilizador extends NotificacaoAplicacao
{
    /**
     * Tipo interno correspondente a uma MetalThursday.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TIPO_METAL_THURSDAY = 'metal_thursday';

    /**
     * Tipo interno correspondente a uma secção.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TIPO_SECCAO = 'seccao_metal_thursday';

    /**
     * Tipo interno correspondente a um comentário.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const TIPO_COMENTARIO = 'comentario';

    /**
     * Permissão que autoriza todas as comunicações por e-mail.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const PERMISSAO_TODAS = 'todas';

    /**
     * Permissão relativa a todas as novas interações.
     *
     * @var string
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private const PERMISSAO_NOVAS_INTERACOES = 'novas_interacoes';

    /**
     * Permissão relativa às interações nas publicações do utilizador.
     *
     * @var string
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
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private readonly string $tipoSujeito;

    /**
     * Identificador do sujeito da interação.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private readonly int $identificadorSujeito;

    /**
     * Identificador do utilizador que provocou a interação.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private readonly int $identificadorCausador;

    /**
     * Nome do utilizador no momento da interação.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private readonly string $nomeCausador;

    /**
     * Ação realizada pelo utilizador.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private readonly string $acao;

    /**
     * Sujeito recuperado da base de dados.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private MetalThursday|SeccaoMetalThursday|Comentario|null $sujeitoRecuperado =
        null;

    /**
     * Indica se já foi tentada a recuperação do sujeito.
     *
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private bool $sujeitoFoiProcurado = false;

    /**
     * Cria a notificação.
     *
     * @param  MetalThursday|SeccaoMetalThursday|Comentario  $sujeito  Sujeito da
     *                                                                 interação.
     * @param  Utilizador  $causador  Utilizador que realizou a interação.
     * @param  string  $acao  Ação realizada.
     *
     * @throws InvalidArgumentException Quando um identificador ou a ação não
     *                                  são válidos.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function __construct(
        MetalThursday|SeccaoMetalThursday|Comentario $sujeito,
        Utilizador $causador,
        string $acao,
    ) {
        $identificadorSujeito =
            $sujeito->getKey();

        if (
            ! is_numeric($identificadorSujeito)
            || (int) $identificadorSujeito < 1
        ) {
            throw new InvalidArgumentException(
                'O sujeito da interação deve estar persistido.',
            );
        }

        $identificadorCausador =
            $causador->getKey();

        if (
            ! is_numeric($identificadorCausador)
            || (int) $identificadorCausador < 1
        ) {
            throw new InvalidArgumentException(
                'O utilizador responsável pela interação deve estar persistido.',
            );
        }

        $acaoNormalizada =
            $this->normalizarAcao(
                $acao,
            );

        $this->tipoSujeito = match (true) {
            $sujeito instanceof MetalThursday => self::TIPO_METAL_THURSDAY,

            $sujeito instanceof SeccaoMetalThursday => self::TIPO_SECCAO,

            $sujeito instanceof Comentario => self::TIPO_COMENTARIO,
        };

        $this->identificadorSujeito =
            (int) $identificadorSujeito;

        $this->identificadorCausador =
            (int) $identificadorCausador;

        $this->nomeCausador =
            $this->normalizarNomeUtilizador(
                $causador->nome,
            );

        $this->acao =
            $acaoNormalizada;

        $this->afterCommit();
    }

    /**
     * Determina se a notificação deve ser enviada por e-mail.
     *
     * O autor da MetalThursday pode utilizar uma permissão específica para
     * receber apenas as interações nas próprias publicações.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return bool Verdadeiro quando o envio está autorizado.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    protected function deveEnviarPorEmail(
        Utilizador $utilizador,
    ): bool {
        $metalThursday =
            $this->obterMetalThursdayPai();

        if (! $metalThursday instanceof MetalThursday) {
            return false;
        }

        $eAutor =
            is_numeric(
                $metalThursday->autor_id,
            )
            && (int) $metalThursday->autor_id
            === (int) $utilizador->getKey();

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
     *     sujeito_id: int,
     *     causador_id: int,
     *     acao: string,
     *     mensagem: string,
     *     url: string,
     *     icone: string,
     *     classe_cor: string
     * } Dados persistidos.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    public function toArray(
        Utilizador $utilizador,
    ): array {
        return [
            'tipo' => 'interacao_utilizador',

            'tipo_sujeito' => $this->tipoSujeito,

            'sujeito_id' => $this->identificadorSujeito,

            'causador_id' => $this->identificadorCausador,

            'acao' => $this->acao,

            'mensagem' => $this->obterMensagem(
                $utilizador,
            ),

            'url' => $this->obterUrlAcao(
                $utilizador,
            ),

            'icone' => $this->obterIcone(),

            'classe_cor' => $this->obterClasseCor(),
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
     * @version 2.0.0
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
     * @version 2.0.0
     */
    protected function obterUrlAcao(
        Utilizador $utilizador,
    ): ?string {
        $metalThursday =
            $this->obterMetalThursdayPai();

        if (! $metalThursday instanceof MetalThursday) {
            return route(
                'home',
            );
        }

        return route(
            'metalthursday.show',
            [
                'metalThursday' => $metalThursday,
            ],
        );
    }

    /**
     * Obtém o sujeito da interação.
     *
     * @return MetalThursday|SeccaoMetalThursday|Comentario|null Sujeito
     *                                                           encontrado.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private function obterSujeito(): MetalThursday|SeccaoMetalThursday|Comentario|null
    {
        if ($this->sujeitoFoiProcurado) {
            return $this->sujeitoRecuperado;
        }

        $this->sujeitoFoiProcurado =
            true;

        $this->sujeitoRecuperado = match ($this->tipoSujeito) {
            self::TIPO_METAL_THURSDAY => MetalThursday::query()
                ->find(
                    $this->identificadorSujeito,
                ),

            self::TIPO_SECCAO => SeccaoMetalThursday::query()
                ->find(
                    $this->identificadorSujeito,
                ),

            self::TIPO_COMENTARIO => Comentario::query()
                ->find(
                    $this->identificadorSujeito,
                ),

            default => null,
        };

        return $this->sujeitoRecuperado;
    }

    /**
     * Obtém a MetalThursday principal da interação.
     *
     * @return MetalThursday|null MetalThursday encontrada.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private function obterMetalThursdayPai(): ?MetalThursday
    {
        $sujeito =
            $this->obterSujeito();

        if ($sujeito instanceof MetalThursday) {
            return $sujeito;
        }

        if ($sujeito instanceof SeccaoMetalThursday) {
            return $this->obterMetalThursdayDaSeccao(
                $sujeito,
            );
        }

        if ($sujeito instanceof Comentario) {
            $comentavel =
                $sujeito
                    ->comentavel()
                    ->first();

            if ($comentavel instanceof MetalThursday) {
                return $comentavel;
            }

            if ($comentavel instanceof SeccaoMetalThursday) {
                return $this->obterMetalThursdayDaSeccao(
                    $comentavel,
                );
            }
        }

        return null;
    }

    /**
     * Obtém a MetalThursday associada a uma secção.
     *
     * @param  SeccaoMetalThursday  $secao  Secção consultada.
     * @return MetalThursday|null MetalThursday encontrada.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterMetalThursdayDaSeccao(
        SeccaoMetalThursday $secao,
    ): ?MetalThursday {
        $identificador =
            $secao->metal_thursday_id;

        if (
            ! is_numeric($identificador)
            || (int) $identificador < 1
        ) {
            return null;
        }

        return MetalThursday::query()
            ->find(
                (int) $identificador,
            );
    }

    /**
     * Constrói a mensagem apresentada ao destinatário.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @return string Mensagem construída.
     *
     * @since 1.0.0
     *
     * @version 2.0.0
     */
    private function obterMensagem(
        Utilizador $utilizador,
    ): string {
        $sujeito =
            $this->obterSujeito();

        if (
            ! $sujeito instanceof MetalThursday
            && ! $sujeito instanceof SeccaoMetalThursday
            && ! $sujeito instanceof Comentario
        ) {
            return sprintf(
                '%s realizou uma interação num conteúdo que foi entretanto removido.',
                $this->nomeCausador,
            );
        }

        if ($sujeito instanceof Comentario) {
            return $this->obterMensagemComentario(
                $utilizador,
                $sujeito,
            );
        }

        $descricao =
            $this->obterDescricaoConteudo(
                $sujeito,
            );

        return match ($this->acao) {
            'comentou' => sprintf(
                '%s comentou na %s.',
                $this->nomeCausador,
                $descricao,
            ),

            'avaliou' => sprintf(
                '%s avaliou a %s.',
                $this->nomeCausador,
                $descricao,
            ),

            'ouviu' => sprintf(
                '%s assinalou a %s como ouvida.',
                $this->nomeCausador,
                $descricao,
            ),

            'gostou' => sprintf(
                '%s gostou da %s.',
                $this->nomeCausador,
                $descricao,
            ),

            default => sprintf(
                '%s %s a %s.',
                $this->nomeCausador,
                $this->acao,
                $descricao,
            ),
        };
    }

    /**
     * Constrói a mensagem relativa a um comentário.
     *
     * @param  Utilizador  $utilizador  Utilizador destinatário.
     * @param  Comentario  $comentario  Comentário relacionado.
     * @return string Mensagem construída.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterMensagemComentario(
        Utilizador $utilizador,
        Comentario $comentario,
    ): string {
        $descricao =
            $this->obterDescricaoContextoComentario(
                $comentario,
            );

        $eAutorComentario =
            is_numeric(
                $comentario->utilizador_id,
            )
            && (int) $comentario->utilizador_id
            === (int) $utilizador->getKey();

        if ($eAutorComentario) {
            return match ($this->acao) {
                'gostou' => sprintf(
                    '%s gostou do teu comentário em %s.',
                    $this->nomeCausador,
                    $descricao,
                ),

                'respondeu' => sprintf(
                    '%s respondeu ao teu comentário em %s.',
                    $this->nomeCausador,
                    $descricao,
                ),

                default => sprintf(
                    '%s %s o teu comentário em %s.',
                    $this->nomeCausador,
                    $this->acao,
                    $descricao,
                ),
            };
        }

        $nomeAutor =
            $this->obterNomeAutorComentario(
                $comentario,
            );

        return match ($this->acao) {
            'gostou' => sprintf(
                '%s gostou de um comentário de %s em %s.',
                $this->nomeCausador,
                $nomeAutor,
                $descricao,
            ),

            'respondeu' => sprintf(
                '%s respondeu a um comentário de %s em %s.',
                $this->nomeCausador,
                $nomeAutor,
                $descricao,
            ),

            default => sprintf(
                '%s %s um comentário de %s em %s.',
                $this->nomeCausador,
                $this->acao,
                $nomeAutor,
                $descricao,
            ),
        };
    }

    /**
     * Obtém a descrição do contexto de um comentário.
     *
     * @param  Comentario  $comentario  Comentário consultado.
     * @return string Descrição do conteúdo.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterDescricaoContextoComentario(
        Comentario $comentario,
    ): string {
        $comentavel =
            $comentario
                ->comentavel()
                ->first();

        if (
            $comentavel instanceof MetalThursday
            || $comentavel instanceof SeccaoMetalThursday
        ) {
            return $this->obterDescricaoConteudo(
                $comentavel,
            );
        }

        return 'um conteúdo que foi entretanto removido';
    }

    /**
     * Obtém a descrição de uma MetalThursday ou secção.
     *
     * @param  MetalThursday|SeccaoMetalThursday  $conteudo  Conteúdo descrito.
     * @return string Descrição construída.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterDescricaoConteudo(
        MetalThursday|SeccaoMetalThursday $conteudo,
    ): string {
        if ($conteudo instanceof MetalThursday) {
            return $this->obterDescricaoMetalThursday(
                $conteudo,
            );
        }

        return $this->obterDescricaoSeccao(
            $conteudo,
        );
    }

    /**
     * Obtém a descrição de uma MetalThursday.
     *
     * @param  MetalThursday  $metalThursday  MetalThursday descrita.
     * @return string Descrição construída.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
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
                trim($nome),
            );
        }

        $metalThursday->loadMissing([
            'edicao',
        ]);

        $edicao =
            $metalThursday->edicao;

        $nomeEdicao =
            $edicao instanceof Edicao
            && is_string($edicao->nome)
            && trim($edicao->nome) !== ''
            ? trim($edicao->nome)
            : null;

        $data =
            $metalThursday->data;

        $dataFormatada =
            $data instanceof CarbonInterface
            ? $data->format('d/m/Y')
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
            (int) $metalThursday->getKey(),
        );
    }

    /**
     * Obtém a descrição de uma secção.
     *
     * @param  SeccaoMetalThursday  $secao  Secção descrita.
     * @return string Descrição construída.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterDescricaoSeccao(
        SeccaoMetalThursday $secao,
    ): string {
        $secao->loadMissing([
            'tipoSeccao',
            'banda',
        ]);

        $titulo =
            is_string($secao->titulo)
            ? trim($secao->titulo)
            : '';

        $tipo =
            $secao->tipoSeccao;

        $nomeTipo =
            $tipo instanceof TipoSeccao
            && is_string($tipo->nome)
            && trim($tipo->nome) !== ''
            ? trim($tipo->nome)
            : null;

        $banda =
            $secao->banda;

        $nomeBanda =
            $banda instanceof Banda
            && is_string($banda->nome)
            && trim($banda->nome) !== ''
            ? trim($banda->nome)
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
     * Obtém o nome do autor de um comentário.
     *
     * @param  Comentario  $comentario  Comentário consultado.
     * @return string Nome do autor.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function obterNomeAutorComentario(
        Comentario $comentario,
    ): string {
        $comentario->loadMissing([
            'utilizador',
        ]);

        $autor =
            $comentario->utilizador;

        return $autor instanceof Utilizador
            ? $this->normalizarNomeUtilizador(
                $autor->nome,
            )
            : 'um utilizador';
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
     * Normaliza a ação recebida.
     *
     * @param  string  $acao  Ação original.
     * @return string Ação normalizada.
     *
     * @throws InvalidArgumentException Quando a ação está vazia ou é
     *                                  demasiado extensa.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarAcao(
        string $acao,
    ): string {
        $acaoNormalizada = preg_replace(
            '/\s+/u',
            ' ',
            mb_strtolower(
                trim($acao),
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
     * @return string Nome normalizado.
     *
     * @since 2.0.0
     *
     * @version 1.0.0
     */
    private function normalizarNomeUtilizador(
        mixed $nome,
    ): string {
        if (! is_string($nome)) {
            return 'Um utilizador';
        }

        $nomeNormalizado = preg_replace(
            '/\s+/u',
            ' ',
            trim($nome),
        );

        return is_string($nomeNormalizado)
            && $nomeNormalizado !== ''
            ? $nomeNormalizado
            : 'Um utilizador';
    }
}
