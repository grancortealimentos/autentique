# Roteiro de teste — autenticação (autentique ↔ qualite)

Teste de ponta a ponta da integração de autenticação entre a API `autentique`
(fonte da verdade de identidade/credenciais) e a aplicação `qualite`
(consumidora, com mirror local de `pessoas`/`users`).

## 0. Por que precisa de bootstrap manual

Todo endpoint de criação (`POST /persons`, `POST /users`) exige `auth:sanctum`
— ou seja, precisa de um token, que só existe depois de um login. E login
exige que já exista uma `Person` + `User` vinculada à aplicação. É um
ovo-e-galinha: **não existe endpoint de "primeiro cadastro"** no autentique
hoje. A primeiríssima pessoa/usuário do sistema tem que entrar direto no
banco via tinker — depois disso, tudo o mais passa a ser 100% via API/Postman.

## 1. Preparar o ambiente

Escolha se quer dados limpos ou reaproveitar o que já existe no banco.

**Do zero de verdade:**
```bash
cd "/work/Gran Corte/autentique"
php artisan migrate:fresh
php artisan api-client:create qualite http://localhost:8000
```
Copie a chave impressa e cole em `qualite/.env`, na linha `AUTENTIQUE_API_KEY=`.

**Reaproveitando o banco atual:** pule o passo acima — a chave que já está em
`qualite/.env` (`AUTENTIQUE_API_KEY`) continua válida enquanto o `ApiClient`
"qualite" existir no banco do autentique.

Suba os dois servidores em terminais separados (portas combinando com
`qualite/.env`, chave `AUTENTIQUE_BASE_URL`):
```bash
cd "/work/Gran Corte/autentique" && php artisan serve --port=8010
cd "/work/Gran Corte/qualite" && php artisan serve --port=8000
```

## 2. Bootstrap do primeiro admin (só se o banco estiver vazio)

```bash
cd "/work/Gran Corte/autentique"
php artisan tinker
```
Cole:
```php
$person = App\Models\Person::create([
    'name' => 'Administrador do Sistema',
    'person_type' => 'PF',
    'document' => '11144477735',
    'is_active' => true,
    'email' => 'admin@grancorte.com.br',
]);

$user = App\Models\User::create([
    'person_id' => $person->id,
    'is_active' => true,
    'name' => 'Administrador do Sistema',
    'email' => 'admin@grancorte.com.br',
    'password' => 'Admin!2025',
    'force_password_change' => false,
]);

$apiClient = App\Models\ApiClient::where('name', 'qualite')->first();

App\Models\PersonApiClient::create([
    'person_id' => $person->id,
    'api_client_id' => $apiClient->id,
    'is_active' => true,
    'granted_by' => $user->id,
    'created_at' => now(),
]);
```
Guarde `admin@grancorte.com.br` / `Admin!2025` — é o login usado no Postman e
no frontend.

## 3. Postman — coleção de autenticação

Environment do Postman:

| var | valor |
|---|---|
| `base_url` | `http://127.0.0.1:8010/api/v1` |
| `api_key` | a chave de `AUTENTIQUE_API_KEY` no `.env` do qualite |
| `token` | (vazio, preenchido no passo 3.1) |

Em toda request: header `X-API-KEY: {{api_key}}`. Nas autenticadas, some
`Authorization: Bearer {{token}}`.

### 3.1 — Login
`POST {{base_url}}/login`
```json
{ "email": "admin@grancorte.com.br", "password": "Admin!2025" }
```
→ 200. Copie o `token` da resposta para a variável `{{token}}`. Dá pra
automatizar na aba **Tests** da request:
```js
pm.environment.set("token", pm.response.json().token);
```

### 3.2 — Quem sou eu
`GET {{base_url}}/me` (Bearer) → 200 com os dados do admin.

### 3.3 — Criar uma pessoa nova
`POST {{base_url}}/persons` (Bearer)
```json
{ "name": "Fulano de Tal", "person_type": "PF", "document": "39053344705", "email": "fulano@teste.com" }
```
→ 201. Guarde o `id` retornado.

### 3.4 — Criar usuário pra essa pessoa
`POST {{base_url}}/users` (Bearer)
```json
{ "person_id": 2, "name": "Fulano de Tal", "email": "fulano@teste.com", "password": "Fulano!2025" }
```
→ 201.

### 3.5 — Login como o novo usuário
Repita 3.1 com o e-mail/senha do Fulano, num token separado (duplique a
request no Postman como "Login Fulano").

### 3.6 — Buscar por documento (fluxo de CPF)
`GET {{base_url}}/persons/check-document?document=39053344705` (Bearer,
qualquer token) → `status: linked_here`.

### 3.7 — Esqueci a senha
`POST {{base_url}}/password/forgot`
```json
{ "email": "fulano@teste.com" }
```
→ 200 com `reset_token`. Esse endpoint é o que o qualite chama por trás — no
Postman você está simulando a própria app (chamada servidor-a-servidor).

### 3.8 — Redefinir com o token
`POST {{base_url}}/password/reset`
```json
{ "token": "<reset_token do passo anterior>", "password": "NovaSenha!456" }
```
→ 200.

### 3.9 — Trocar a própria senha (autenticado)
`PUT {{base_url}}/password/change` (Bearer do Fulano, logado com a senha nova
do 3.8)
```json
{ "current_password": "NovaSenha!456", "password": "OutraSenha!789" }
```
→ 200. Teste também mandando `current_password` errado → deve dar 422.

### 3.10 — Logout
`POST {{base_url}}/logout` (Bearer) → 200. Depois tente `GET /me` com o
mesmo token → deve dar 401 (token revogado).

## 4. Frontend do qualite

Acesse `http://localhost:8000/login` no navegador.

1. Logue com `admin@grancorte.com.br` / `Admin!2025` (ou a senha vigente, se
   já tiver mexido nela pelo Postman — recomendado usar o Fulano no Postman e
   o admin no frontend, pra não embaralhar senha entre os dois testes).
2. Confirme que o dashboard carrega.
3. Vá em `/pessoas/novo`, cadastre uma pessoa nova pelo formulário.
4. Confira que ela apareceu **nas duas bases**:
   ```bash
   cd "/work/Gran Corte/autentique" && php artisan tinker --execute="App\Models\Person::latest()->first(['id','name','document'])"
   cd "/work/Gran Corte/qualite" && php artisan tinker --execute="App\Models\Pessoa::latest()->first(['id','nome_completo','autentique_person_id'])"
   ```
5. Teste "esqueci minha senha" em `http://localhost:8000/forgot-password` com
   o e-mail do admin, depois olhe o e-mail simulado em:
   ```bash
   tail -60 "/work/Gran Corte/qualite/storage/logs/laravel.log"
   ```
   (o `MAIL_MAILER` está em `log`, então o e-mail cai no log em vez de ser
   enviado de verdade). Copie o link e cole no navegador pra completar a
   troca.
6. Logado, vá em `/alterar-senha` e teste a troca com senha atual errada
   (deve bloquear) e depois certa.
7. Faça logout pelo menu e confirme que `/dashboard` te redireciona pro login
   de novo.

## O que esse roteiro cobre

- Login / logout (com revogação real do token no autentique).
- Esqueci-senha / reset (token gerado pela API, e-mail enviado pelo qualite).
- Troca de senha autenticada (exigindo senha atual).
- Criação de identidade (pessoa + usuário) refletindo nas duas bases.
- Escopo por `X-API-KEY` (o token de uma aplicação não deveria autenticar em
  outra sem vínculo ativo — vale testar isso também, criando um segundo
  `ApiClient` e tentando logar com credenciais de uma pessoa não vinculada a
  ele).
