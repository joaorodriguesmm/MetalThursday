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
     * Autoriza notificações relativas a interações em qualquer publicação.
     *
     * @since 2.0.0
     */
    case TodasInteracoes = 'todas_interacoes';

    /**
     * Autoriza notificações relativas a interações nas publicações do
     * utilizador.
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

            self::TodasInteracoes => 'Todas as interações',

            self::InteracoesNasMinhasPublicacoes => 'Interações nas minhas publicações',

            self::LembreteDiarioTarefas => 'Lembrete diário de tarefas',

            self::LembreteDiarioAtrasos => 'Lembrete diário de atrasos',

            self::AlertaNomeacao => 'Nomeação para uma MetalThursday',
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

            self::TodasInteracoes => 'Receber e-mails sobre comentários, avaliações, gostos e outras interações realizadas em qualquer publicação.',

            self::InteracoesNasMinhasPublicacoes => 'Receber e-mails sobre comentários, avaliações, gostos e outras interações realizadas nas minhas publicações.',

            self::LembreteDiarioTarefas => 'Receber um e-mail no dia em que exista uma tarefa por concluir, como a submissão de uma MetalThursday.',

            self::LembreteDiarioAtrasos => 'Receber diariamente um e-mail quando existir uma tarefa em atraso, como uma MetalThursday ainda não submetida.',

            self::AlertaNomeacao => 'Receber um e-mail quando for nomeado para apresentar a próxima MetalThursday.',
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

            self::TodasInteracoes => 3,

            self::InteracoesNasMinhasPublicacoes => 4,

            self::LembreteDiarioTarefas => 5,

            self::LembreteDiarioAtrasos => 6,

            self::AlertaNomeacao => 7,
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
