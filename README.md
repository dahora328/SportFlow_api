# SportFlow API — Back-end

API RESTful desenvolvida com **Laravel 12** e autenticação via **JWT**, servindo como núcleo do sistema SportFlow para gestão de atletas e empresas esportivas.

---

## 🛠️ Stack

| Tecnologia | Versão | Uso |
|---|---|---|
| PHP | ^8.2 | Linguagem principal |
| Laravel | ^12.0 | Framework |
| tymon/jwt-auth | ^2.2 | Autenticação JWT |
| Laravel Sanctum | ^4.0 | Pacote base de auth |
| PostgreSQL | 15 | Banco de dados (produção/Docker) |
| SQLite | — | Banco de dados (desenvolvimento local) |
| Nginx | latest | Servidor web (Docker) |
| dedoc/scramble | ^0.13 | Documentação automática da API |
| PHPUnit | ^11.5 | Testes automatizados |

---

## 📁 Estrutura de Pastas

```
SportFlow_api/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AuthController.php        # Login, Logout, Register, Refresh, Me
│   │   │   ├── AthletesController.php    # CRUD de Atletas
│   │   │   └── EnterpriseController.php  # CRUD de Empresas
│   │   ├── Middleware/                   # Middlewares customizados
│   │   ├── Requests/
│   │   │   ├── RegisterRequest.php
│   │   │   ├── StoreAthletesRequest.php
│   │   │   ├── UpdateAthletesRequest.php
│   │   │   └── StoreEnterpriseRequest.php
│   │   └── Resources/                   # API Resources (transformação de resposta)
│   ├── Models/
│   │   ├── User.php
│   │   ├── Athlete.php                  # Soft Deletes + Global Scope por empresa
│   │   └── Enterprise.php
│   ├── Policies/
│   │   ├── AthletesPolicy.php           # Controle de acesso por atleta
│   │   └── EnterprisePolicy.php
│   ├── Providers/
│   └── Rules/
│       └── CpfCnpjRule.php              # Validação customizada de CPF/CNPJ
├── database/
│   ├── migrations/                      # 12 migrations (veja abaixo)
│   ├── factories/
│   └── seeders/
├── routes/
│   └── api.php                          # Todas as rotas da API
├── docker/ (na raiz do monorepo)
│   ├── php/                             # Dockerfile PHP-FPM
│   └── nginx/default.conf              # Config Nginx
├── .env.example
└── docker-compose.yml (na raiz do monorepo)
```

---

## 🗃️ Banco de Dados — Migrations

| Migration | Descrição |
|---|---|
| `create_users_table` | Tabela de usuários |
| `create_cache_table` | Suporte a cache |
| `create_jobs_table` | Suporte a filas |
| `create_personal_access_tokens_table` | Tokens Sanctum |
| `create_athletes_table` | Tabela de atletas |
| `add_refresh_token_to_users_table` | Campos `refresh_token` e `refresh_token_expires_at` |
| `alter_table_users_add_colunm_is_admin` | Campo `is_admin` para controle de perfil |
| `alter_table_athletes_add_column_photo_path` | Foto do atleta |
| `create_enterprises_table` | Tabela de empresas (clientes) |
| `add_enterprise_id_to_users_table` | Vínculo usuário ↔ empresa |
| `add_enterprise_id_to_athletes_table` | Vínculo atleta ↔ empresa |
| `add_position_and_observations_to_athletes_table` | Posição e observações do atleta |

---

## 🔐 Autenticação — JWT + Refresh Token

O sistema usa **JWT** para access token (curta duração) e um **Refresh Token com rotação** armazenado como hash SHA-256 no banco de dados.

### Fluxo
1. `POST /api/login` → retorna `access_token` + `refresh_token`
2. Axios interceptor no front detecta `401` e chama `POST /api/refresh`
3. Novo par de tokens é emitido (rotação do refresh token)
4. Se o refresh falhar → evento `auth:logout` é disparado, usuário é redirecionado

### Throttle
- As rotas `/login` e `/refresh` possuem rate limiting de **5 requisições por minuto**.

---

## 👥 Perfis de Usuário (Roles)

| Perfil | `is_admin` | `enterprise_id` | Capacidades |
|---|---|---|---|
| **Super Admin** | `true` | `null` | Acesso total: cria empresas, cria gestores, vê tudo |
| **Gestor** | `true` | `!= null` | Gerencia a própria empresa e seus usuários/atletas |
| **Funcionário** | `false` | `!= null` | Acesso à listagem e cadastro de atletas da empresa |

> O campo `enterprise_id` é o pivô central do sistema de multi-tenancy. Todos os dados são filtrados por empresa via **Global Scope** no model `Athlete`.

---

## 🌐 Rotas da API

### Públicas (sem autenticação)
| Método | Endpoint | Controller | Descrição |
|---|---|---|---|
| `POST` | `/api/login` | `AuthController@login` | Autenticação e geração de tokens |
| `POST` | `/api/refresh` | `AuthController@refresh` | Renovação do access token |

### Protegidas (requer JWT)
| Método | Endpoint | Controller | Descrição |
|---|---|---|---|
| `GET` | `/api/user` | `AuthController@me` | Dados do usuário logado |
| `PUT` | `/api/user` | `AuthController@updateUser` | Atualiza nome/email do usuário logado |
| `POST` | `/api/logout` | `AuthController@logout` | Encerra sessão e invalida tokens |
| `POST` | `/api/register` | `AuthController@register` | Cria novo usuário (apenas admins) |
| `GET` | `/api/athletes` | `AthletesController@index` | Lista atletas (com busca e paginação) |
| `POST` | `/api/athletes` | `AthletesController@store` | Cadastra atleta (suporta foto multipart) |
| `GET` | `/api/athletes/{id}` | `AthletesController@show` | Detalhes de um atleta |
| `PUT` | `/api/athletes/{id}` | `AthletesController@update` | Atualiza atleta (suporta foto multipart) |
| `DELETE` | `/api/athletes/{id}` | `AthletesController@destroy` | Remove atleta (soft delete + remove foto) |
| `GET` | `/api/enterprises` | `EnterpriseController@index` | Lista empresas |
| `POST` | `/api/enterprises` | `EnterpriseController@store` | Cria empresa (apenas Super Admin) |
| `GET` | `/api/enterprises/{id}` | `EnterpriseController@show` | Detalhes de uma empresa |
| `PUT` | `/api/enterprises/{id}` | `EnterpriseController@update` | Atualiza empresa (com logo multipart) |
| `DELETE` | `/api/enterprises/{id}` | `EnterpriseController@destroy` | Remove empresa |

#### Parâmetros aceitos em `GET /api/athletes`
| Parâmetro | Tipo | Descrição |
|---|---|---|
| `search` | `string` | Busca por nome (ilike, limitado a 50 chars) |
| `sort` | `full_name` \| `created_at` | Campo de ordenação (whitelist) |
| `direction` | `asc` \| `desc` | Direção da ordenação |
| `per_page` | `int` (max 100) | Itens por página |

---

## 🏗️ Funcionalidades por Módulo

### Atletas (`AthletesController`)
- Cadastro completo com foto (upload para `storage/public/athletes`)
- Validação de CPF/CNPJ via custom Rule `CpfCnpjRule`
- Email único por empresa (validação `Rule::unique` com escopo de `enterprise_id`)
- **Soft Delete** — atletas removidos não são deletados fisicamente
- Ao excluir/atualizar: foto antiga é removida do disco automaticamente
- `photo_url` é um atributo computado (`$appends`) com URL pública do Storage

### Empresas (`EnterpriseController`)
- Multi-tenancy: Super Admin vê todas, Gestor/Funcionário vê apenas a própria
- Upload de logo (`storage/public/logos`)
- Campos: `name`, `social_reason`, `fantasy_name`, `document`, `IE`, `foundation_date`, `address`, `phone`, `email`, `logo_path`, `active`
- Apenas Super Admin pode criar/deletar empresas

### Usuários (`AuthController`)
- Registro protegido (somente admins criam usuários)
- Super Admin pode definir `is_admin` e `enterprise_id` livremente
- Gestor cria apenas funcionários da própria empresa (`enterprise_id` injetado automaticamente)

---

## ⚙️ Variáveis de Ambiente

Copie `.env.example` para `.env` e ajuste:

```env
APP_NAME=SportFlow
APP_KEY=           # gerar com: php artisan key:generate
APP_URL=http://localhost:8080

# Banco de dados (Docker/produção)
DB_CONNECTION=pgsql
DB_HOST=db         # nome do serviço no docker-compose
DB_PORT=5432
DB_DATABASE=app_db
DB_USERNAME=user
DB_PASSWORD=password

# JWT
JWT_SECRET=        # gerar com: php artisan jwt:secret

DEFAULT_ENTERPRISE_ID=1
```

---

## 🐳 Subir com Docker

Na **raiz do monorepo** (`/SportFlow`):

```bash
docker-compose up -d
```

Serviços:
| Serviço | Imagem | Porta |
|---|---|---|
| `app` | PHP-FPM 8.2 (custom) | — |
| `nginx` | nginx:latest | `8080:80` |
| `db` | postgres:15 | `5432:5432` |
| `frontend` | node:20 | `5173:5173` |

---

## 🚀 Instalação Local (sem Docker)

```bash
# 1. Instalar dependências
composer install

# 2. Copiar e configurar .env
cp .env.example .env
php artisan key:generate
php artisan jwt:secret

# 3. Criar banco e rodar migrations
php artisan migrate

# 4. Criar link do Storage
php artisan storage:link

# 5. Subir o servidor
php artisan serve
```

---

## 🧪 Testes

```bash
composer test
# ou diretamente:
php artisan test
```

---

## 📄 Documentação da API

A documentação é gerada automaticamente pelo pacote **Scramble (dedoc/scramble)**.  
Acesse em: [`http://localhost:8080/docs/api`](http://localhost:8080/docs/api)
