# Registo de alterações

Todas as alterações relevantes deste projeto serão documentadas neste ficheiro.

O formato baseia-se em [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
e este projeto segue [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Por publicar]

### Adicionado

- Gestão administrativa de utilizadores, papéis, suspensões, sessões e convites.
- Reservas, nomeações, rascunhos, publicação temporal e lembretes de
  MetalThursday.
- Pesquisa textual no arquivo e múltiplas ligações por secção.
- Perfis e histórico contextual de artistas.
- Catálogo musical estruturado em lançamentos, com importação e edição a partir
  do Discogs.
- Validação contínua com GitHub Actions, análise estática com PHPStan e testes
  JavaScript/browser com Vitest e Playwright.
- Guia de deployment e operação em produção.

### Alterado

- Reconstrução do esquema de dados e consolidação dos modelos, serviços,
  pedidos HTTP, políticas e notificações para a v2.
- Evolução do domínio musical de bandas para artistas, permitindo nomes
  repetidos e tornando origem geográfica e géneros opcionais.
- Reforço da robustez das integrações externas, incluindo limitação de pedidos,
  timeouts e controlo de concorrência.
- Alojamento local da fonte Metal Mania, removendo a dependência de Google
  Fonts.

### Corrigido

- Fluxos de autenticação, recuperação de palavra-passe, perfil e formulários.
- Comentários, gostos, audições, avaliações e restantes interações de
  MetalThursday.
- Criação, publicação, reservas, nomeações e formulários de MetalThursday.
- Apresentação e permissões de artistas, géneros e respetivas listagens.
- Compatibilidade das migrações e validações automatizadas com MariaDB.

## [1.0.0] - 2025-12-28

### Adicionado

- Primeira versão estável da aplicação web MetalThursday.
- Estrutura base do projeto em Laravel (backend, rotas e vistas Blade).
- Configuração inicial do frontend com Vite, JavaScript e SCSS.

[Por publicar]: https://github.com/joaorodriguesmm/MetalThursday/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/joaorodriguesmm/MetalThursday/releases/tag/v1.0.0
