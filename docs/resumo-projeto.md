# Resumo do Projeto — `autentique`

> Documento gerado em 2026-08-17 para consulta rápida sobre a arquitetura, funcionalidades e endpoints da API.

## Visão geral

API Laravel 13 (PHP 8.3+) de **identidade e autenticação multi-tenant**, atuando como fonte central de credenciais para o ecossistema de aplicações "Gran Corte" (ex.: `qualite`, que consome esta API). O nome do banco (`auth_api`) e a documentação `docs/roteiro-teste-autenticacao.md` confirmam esse propósito.

**Conceito central:** uma `Person` (indivíduo/CPF ou empresa/CNPJ) pode ter no máximo um `User` (login), mas pode estar vinculada a **múltiplas aplicações clientes** (`ApiClient`) via tabela pivot `person_api_clients`. Cada vínculo pode ser concedido/revogado individualmente e é auditado. Cada app cliente se autentica com uma `X-API-KEY` própria; os usuários finais se autenticam com tokens Sanctum.

## Arquitetura

Fluxo em camadas: **Controller → Service → Repository → Model**.

- **DTOs** (`app/DTOs/`) — `PersonData`, `UserData`: validam e filtram dados antes de chegar ao repositório (`paraCriacao()` aplica defaults; `paraEdicao()` só inclui campos presentes no request, evitando que um PATCH zere campos omitidos).
- **Trait `Auditable`** — grava automaticamente eventos de create/update/delete/restore na tabela `audits`, excluindo campos ocultos e ignorando updates sem mudança real.
- **`AuditContext`** — contexto por requisição com `userId`, `apiClientId` e `correlationId` (ULID gerado automaticamente se não enviado via header).
- **Tratamento de erros** — exceções não tratadas são logadas em `error_logs` via `ErrorLogService`, com redação de campos sensíveis (`password`, `token`, `api_key`, headers de autenticação, etc). Rotas `api/*` sempre retornam JSON; usuários não autenticados nunca são redirecionados (401 direto).
- **Autenticação em duas camadas:**
  1. `X-API-KEY` (middleware `api.client`) — identifica a aplicação cliente (hash SHA-256 comparado com `api_clients.api_key_hash`).
  2. Sanctum (`auth:sanctum`) — identifica o usuário final autenticado.
- **Comando artisan** `php artisan api-client:create {name} {url}` — provisiona um novo `ApiClient` e imprime a API key em texto puro uma única vez (o hash é o que fica salvo).

## Endpoints expostos (prefixo `/v1`)

| Método | Rota | Middleware | Descrição |
|---|---|---|---|
| GET | `/v1/status` | — | Health check público (verifica conexão com o banco) |
| POST | `/v1/login` | `api.client`, `throttle:10,1` | Login — emite token Sanctum |
| POST | `/v1/logout` | `api.client`, `auth:sanctum` | Revoga o token atual |
| GET | `/v1/me` | `api.client`, `auth:sanctum` | Retorna usuário autenticado + flag `force_password_change` |
| POST | `/v1/password/forgot` | `api.client`, `throttle:10,1` | Gera token de reset de senha (o app cliente é responsável por enviar o e-mail) |
| POST | `/v1/password/reset` | `api.client`, `throttle:10,1` | Redefine senha a partir do token |
| PUT | `/v1/password/change` | `api.client`, `auth:sanctum`, `throttle:10,1` | Troca de senha (auto-atendimento, exige senha atual) |
| GET | `/v1/persons/check-document` | `api.client`, `auth:sanctum` | Verifica se um CPF/CNPJ já existe (globalmente ou só para o app atual) |
| GET | `/v1/persons` | `api.client`, `auth:sanctum` | Lista pessoas vinculadas ao app chamador (paginado, `?search=`) |
| POST | `/v1/persons` | `api.client`, `auth:sanctum` | Cria pessoa e vincula automaticamente ao app chamador |
| GET | `/v1/persons/{id}` | `api.client`, `auth:sanctum`, `person.scope` | Detalhe de uma pessoa |
| PATCH | `/v1/persons/{id}` | `api.client`, `auth:sanctum`, `person.scope` | Edição parcial |
| POST | `/v1/persons/{id}/link` | `api.client`, `auth:sanctum` | Vincula pessoa existente a outro app |
| DELETE | `/v1/persons/{id}/link` | `api.client`, `auth:sanctum`, `person.scope` | Revoga vínculo pessoa↔app (não apaga a pessoa) |
| GET | `/v1/users` | `api.client`, `auth:sanctum` | Lista usuários ativos (`?term=&per_page=`) |
| POST | `/v1/users` | `api.client`, `auth:sanctum` | Cria usuário vinculado 1:1 a uma `Person` |
| GET | `/v1/users/{id}` | `api.client`, `auth:sanctum`, `user.scope` | Detalhe de um usuário |
| PATCH | `/v1/users/{id}` | `api.client`, `auth:sanctum`, `user.scope` | Edição parcial |
| PATCH | `/v1/users/{id}/activate` | `api.client`, `auth:sanctum`, `user.scope` | Ativa usuário |
| PATCH | `/v1/users/{id}/deactivate` | `api.client`, `auth:sanctum`, `user.scope` | Desativa usuário |

`routes/web.php` contém apenas a página padrão do Laravel (`GET /`). O framework também expõe `GET /up` como health check interno (configurado em `bootstrap/app.php`).

## Modelos (`app/Models/`)

Todos usam a trait `Auditable` (exceto `Audit` e `Error`).

- **`Person`** (`persons`) — identidade compartilhada (PF/PJ), soft delete. Campos: nome, `document` (CPF/CNPJ único), e-mail, telefone, endereço completo. Relação: `apiClients()` (many-to-many via `person_api_clients`).
- **`User`** (`users`) — credenciais de login, 1:1 com `Person` (FK única, cascade delete). Usa Sanctum (`HasApiTokens`), soft delete. Guarda `previous_password` para bloquear reuso de senha e `force_password_change`.
- **`ApiClient`** (`api_clients`) — aplicação consumidora. Guarda `api_key_hash` (SHA-256, oculto, nunca preenchível via HTTP).
- **`PersonApiClient`** (`person_api_clients`) — pivot Person↔ApiClient, com `granted_by`/`revoked_by`/`revoked_at`. Único por `(person_id, api_client_id)`.
- **`Audit`** (`audits`) — log imutável de auditoria (before/after em JSON, IP, user-agent, correlation ID).
- **`Error`** (`error_logs`) — log de exceções (stack trace, request/headers com dados sensíveis redigidos).
- **`Company`** (`companies`) — ⚠️ **em desenvolvimento, ainda não commitado**. Modelo para "filiais" (CNPJ, IE, endereço, geolocalização). Ainda sem controller, service, repository ou rotas — próximo passo provável do desenvolvimento.

## Integrações e configuração

- **Banco de dados:** PostgreSQL (`auth_api`), com colunas `jsonb` para payloads de auditoria/erro e buscas `ILIKE`.
- **Auth:** `laravel/sanctum` v4 para tokens de usuário final, combinado com camada própria de API key por aplicação.
- **Sessão/cache/fila:** driver `database`.
- **Ferramentas de dev:** `laravel/boost` (integração MCP para assistentes de IA), `laravel/pint`, `pestphp/pest` (ainda sem cobertura de testes real — apenas stubs padrão).
- Variáveis `AWS_*` e `MAIL_*` presentes no `.env.example` mas não utilizadas no código (placeholders do skeleton do Laravel).
