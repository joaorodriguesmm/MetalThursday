<?php

declare(strict_types=1);

namespace App\Enumeracoes;

/**
 * Representa as permissões de comunicação por e-mail disponibilizadas pela
 * aplicação.
 *
 * Os valores correspondem diretamente aos identificadores técnicos
 * persistidos na tabela `permissoes_email`.
 *
 * A enumeração constitui a fonte de verdade do catálogo funcional das
 * permissões. O seeder utiliza estes valores para manter a base de dados
 * sincronizada com o domínio da aplicação.
 *
 * @since 2.0.0
 */
enum IdentificadorPermissaoEmail: string
{
    /**
     * Autoriza todas as comunicações opcionais da aplicação.
     *
     * @since 2.0.0
     */
    case TodasNotificacoes = 'todas_notificacoes';

    /**
     * Autoriza notificações relativas a novas MetalThursdays.
     *
     * @since 2.0.0
     */
    case NovasPublicacoes = 'novas_publicacoes';

    /**
     * Autoriza e-mails sobre interações pessoalmente relevantes.
     *
     * O identificador histórico é preservado para não invalidar as
     * preferências individuais já guardadas.
     *
     * @since 2.0.0
     */
    case InteracoesNasMinhasPublicacoes = 'interacoes_nas_minhas_publicacoes';

    /**
     * Autoriza lembretes de tarefas que devem ser concluídas no próprio dia.
     *
     * @since 2.0.0
     */
    case LembreteDiarioTarefas = 'lembrete_diario_tarefas';

    /**
     * Autoriza lembretes diários relativos a tarefas em atraso.
     *
     * @since 2.0.0
     */
    case LembreteDiarioAtrasos = 'lembrete_diario_atrasos';

    /**
     * Autoriza alertas quando o utilizador é nomeado para uma MetalThursday.
     *
     * @since 2.0.0
     */
    case AlertaNomeacao = 'alerta_nomeacao';

    /**
     * Autoriza e-mails sobre novas denúncias para superadministradores.
     *
     * @since 2.0.0
     */
    case NovasDenunciasComentarios = 'novas_denuncias_comentarios';

    /**
     * Autoriza e-mails sobre decisões de denúncias apresentadas pelo utilizador.
     *
     * @since 2.0.0
     */
    case DecisoesDenunciasComentarios = 'decisoes_denuncias_comentarios';

    /**
     * Obtém o nome apresentado ao utilizador.
     *
     * @return string Nome da permissão.
     *
     * @since 2.0.0
     */
    public function nome(): string
    {
        return match ($this) {
            self::TodasNotificacoes => 'Todas as notificações',

            self::NovasPublicacoes => 'Novas publicações',

            self::InteracoesNasMinhasPublicacoes => 'Interações relacionadas comigo',

            self::LembreteDiarioTarefas => 'Lembrete diário de tarefas',

            self::LembreteDiarioAtrasos => 'Lembrete diário de atrasos',

            self::AlertaNomeacao => 'Nomeação para uma MetalThursday',

            self::NovasDenunciasComentarios => 'Novas denúncias de comentários',

            self::DecisoesDenunciasComentarios => 'Decisões sobre as minhas denúncias',
        };
    }

    /**
     * Obtém a descrição apresentada ao utilizador.
     *
     * @return string Descrição da permissão.
     *
     * @since 2.0.0
     */
    public function descricao(): string
    {
        return match ($this) {
            self::TodasNotificacoes => 'Receber e-mails relativos a todas as atividades da MetalThursday.',

            self::NovasPublicacoes => 'Receber um e-mail sempre que for publicada uma nova MetalThursday.',

            self::InteracoesNasMinhasPublicacoes => 'Receber e-mails quando alguém comentar ou avaliar uma publicação minha, responder a um comentário meu ou lhe atribuir um gosto.',

            self::LembreteDiarioTarefas => 'Receber um e-mail no dia em que exista uma tarefa por concluir, como a submissão de uma MetalThursday.',

            self::LembreteDiarioAtrasos => 'Receber diariamente um e-mail quando existir uma tarefa em atraso, como uma MetalThursday ainda não submetida.',

            self::AlertaNomeacao => 'Receber um e-mail quando for nomeado para apresentar a próxima MetalThursday.',

            self::NovasDenunciasComentarios => 'Receber um e-mail quando for apresentada uma denúncia de comentário, enquanto tiveres o papel de superadministrador.',

            self::DecisoesDenunciasComentarios => 'Receber um e-mail quando a moderação decidir sobre uma denúncia apresentada por mim.',
        };
    }

    /**
     * Obtém a ordem de apresentação da permissão.
     *
     * @return int Ordem da permissão.
     *
     * @since 2.0.0
     */
    public function ordem(): int
    {
        return match ($this) {
            self::TodasNotificacoes => 1,

            self::NovasPublicacoes => 2,

            self::InteracoesNasMinhasPublicacoes => 4,

            self::LembreteDiarioTarefas => 5,

            self::LembreteDiarioAtrasos => 6,

            self::AlertaNomeacao => 7,

            self::NovasDenunciasComentarios => 8,

            self::DecisoesDenunciasComentarios => 9,
        };
    }

    /**
     * Determina se a permissão autoriza globalmente todas as comunicações
     * opcionais.
     *
     * @return bool Verdadeiro quando representa a permissão global.
     *
     * @since 2.0.0
     */
    public function eGlobal(): bool
    {
        return $this === self::TodasNotificacoes;
    }
}
