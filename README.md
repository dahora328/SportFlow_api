# SportFlow API

API back-end para gerenciar atletas em um contexto de treinamento esportivo. A
API utiliza autenticação por JWT, autorização baseada em Policy e um padrão de
rotas REST para CRUD de atletas e gestão de usuários. Projeto ainda falta muitas
coisa para implementar como é meu primeiro projeto prático do zero com API
separado do frontend, estou em constante desenvolvimento.

Objetivo principal

- Fornecer endpoints seguros para criar, visualizar, atualizar e excluir
  atletas.
- Garantir que apenas o proprietário de um atleta (owner_id) ou um admin possa
  modificar registros via Policy.
- Suportar operações de autenticação (cadastro, login, atualização de usuário,
  logout) e listing/filtering de atletas do próprio usuário.

Tecnologias

- PHP 8+ com Laravel (estrutura MVC, Eloquent).
- JWT-based authentication ( Tymon\JWTAuth ).
- Banco de dados relacional (ex.: MySQL) com migrations já incluídas no projeto.
- Testes com PHPUnit (Laravel TestSuite).

Arquitetura e conceitos-chave

- Model Athlete: representa o atleta com informações pessoais e de contato; cada
  atleta possui owner_id que referencia o usuário criador.
- Model User: representa usuários do sistema; campo is_admin define permissões
  elevadas.
- Policy AthletesPolicy: regras de autorização para Athlete (ex.: update
  permitido para owner ou admin).
- Global Scope:Athlete::addGlobalScope('owner', ...) garante que usuários vejam
  apenas seus próprios atletas (escopo de leitura).
- Requests: UpdateAthletesRequest e StoreAthletesRequest garantem validação de
  dados e autorização via policy antes de processar as operações.

Rotas (exposição principal) Observação: todas as rotas protegidas exigem
autenticação via middleware auth:api.

- Public
  - GET /: retorna mensagem de boas-vindas (ex.: Hello world!).

- Auth (sem prefixo de namespace para o controller)
  - POST /api/register -> AuthController@register
  - POST /api/login -> AuthController@login
  - POST /api/refresh -> AuthController@refresh

- Usuário autenticado (auth:api)
  - GET /api/user -> AuthController@getUser
  - PUT /api/user -> AuthController@updateUser
  - POST /api/logout -> AuthController@logout

- Atletas (auth:api) - Recurso API padrão
  - GET /api/athletes -> index
  - GET /api/athletes/{id} -> show
  - POST /api/athletes -> store
  - PUT/PATCH /api/athletes/{id} -> update
  - DELETE /api/athletes/{id} -> destroy
  - GET /api/athletes/search -> searchByName

Observações de segurança e autorização

- Ao criar um atleta, owner_id é automaticamente preenchido com o usuário logado
  (Athlete::creating).
- Usuários só podem ver seus próprios atletas (Global Scope). A atualização
  requer autorização via AthletesPolicy::update: owner ou admin.
- O campo is_admin no usuário permite override de permissões pela policy.

Como iniciar localmente (quick-start)

- Requisitos: PHP 8+, Composer, MySQL (ou outro DB suportado).
- Instalação
  1. git clone <repositório>
  2. composer install
  3. copie .env.example para .env e ajuste DB\_\*, APP_KEY etc.
  4. php artisan migrate --seed
  5. php artisan serve --port=8000

Testes

- php artisan test
- Exemplos de testes já incluídos no repositório (p.ex.
  AthleteAuthorizationTest).

Limpeza de logs de teste (utilitários simples)

- scripts/clear_logs.sh: remove storage/logs/\*.log (Unix-like)
- scripts/clear_logs.ps1: remove storage/logs/\*.log (Windows)

## Setup (Bash)

Sessão de configuração de ambiente e dependências usando Bash. Este guia assume
um ambiente Unix-like (Linux/macOS) com bash disponível. Pré-requisitos

- PHP 8.x com extensão PDO/MySQL, mbstring, tokenizer, xml, json, etc.
- Composer instalado (composer -V)
- MySQL (ou SQLite) configurado e acessível
- Node.js e npm (opcional, para assets front-end)
- Acesso à internet para instalar pacotes

Passos de instalação e configuração

- Clone ou atualize o repositório e entre no diretório do projeto cd
  /path/to/SportFlow_api
- Instalar dependências PHP composer install
- Copiar arquivo de configuração de ambiente cp .env.example .env
- Gerar chave do app (APP_KEY) php artisan key:generate
- Configurar JWT (se estiver usando tymon/jwt-auth) composer require
  tymon/jwt-auth php artisan jwt:secret
- Limpar e recarregar configurações php artisan config:clear php artisan
  cache:clear

- Criar base de dados e seeders (migrações) php artisan migrate --seed
- (Opcional) Instalar dependências do front-end (se houver assets) npm install
- Compilar assets (opcional) npm run dev
- Iniciar servidor de desenvolvimento php artisan serve --host=0.0.0.0
  --port=8000
- Rodar testes (opcional) php artisan test

Notas úteis

- Se usar Docker/Laravel Sail, prefira as instruções específicas para esse
  ambiente.
- Caso o JWT não seja gerado, verifique as chaves do .env e a configuração do
  Tymon JWTAuth no config/jwt.php.
- Em ambientes Windows com Git Bash, alguns caminhos podem exigir ajuste de
  permissões.

## Testes e logs

- Rode php artisan test para validar a autorização e endpoints.
- Logs ficam em storage/logs/ . Pode usar scripts de limpeza se necessário (veja
  README para utilitários). Contribuição
- Adicione testes de autorização/integração caso altere regras.
- Siga o padrão existente de mensagens de retorno e validação.
