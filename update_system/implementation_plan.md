# Adicionar "Posição do Jogador" e "Observações" no Atleta

Este plano detalha as alterações necessárias no backend e frontend para incluir a posição e as observações no cadastro de atletas.

## User Review Required

Por favor, revise os arquivos que serão modificados e confirme se o plano está adequado. Assim que aprovado, as alterações e a migration no banco de dados serão aplicadas.

## Open Questions

> [!IMPORTANT]
>
> - O campo **Posição** será um campo de texto livre, ou você prefere uma lista fixa (ex: Goleiro, Zagueiro, Atacante)? No plano atual, usarei um `<select>` com algumas posições padrão (Goleiro, Zagueiro, Lateral, Volante, Meia, Atacante) além de permitir uma opção "Outro" ou deixar em branco, para melhor consistência.
> - Você usa o `php artisan migrate` de dentro do container Docker (ex: `docker compose exec api php artisan migrate`)? Vou criar o arquivo de migration e pedir para rodá-lo (ou rodá-lo caso você aprove a execução do comando diretamente).

## Proposed Changes

---

### Backend (API)

A tabela de atletas no banco de dados receberá os dois novos campos, e as regras de validação serão atualizadas.

#### [NEW] `database/migrations/xxxx_xx_xx_add_position_and_observations_to_athletes_table.php`

- Criação de uma nova migration para adicionar os campos:
  - `position` (`string`, `nullable`)
  - `observations` (`text`, `nullable`)

#### [MODIFY] [Athlete.php](file:///c:/Dev/Docker/SportFlow/SportFlow_api/app/Models/Athlete.php)

- Adicionar `'position'` e `'observations'` ao array `$fillable`.

#### [MODIFY] [StoreAthletesRequest.php](file:///c:/Dev/Docker/SportFlow/SportFlow_api/app/Http/Requests/StoreAthletesRequest.php)

- Adicionar as regras de validação:
  - `'position' => 'nullable|string|max:100'`
  - `'observations' => 'nullable|string'`

#### [MODIFY] [UpdateAthletesRequest.php](file:///c:/Dev/Docker/SportFlow/SportFlow_api/app/Http/Requests/UpdateAthletesRequest.php)

- Adicionar as mesmas regras de validação para a atualização.

#### [MODIFY] [AthletesResource.php](file:///c:/Dev/Docker/SportFlow/SportFlow_api/app/Http/Resources/AthletesResource.php)

- Incluir os campos `'position'` e `'observations'` no retorno da API.

---

### Frontend (React)

A interface e as chamadas da API serão ajustadas para enviar e receber os novos dados, e o formulário será atualizado.

#### [MODIFY] [athletesService.ts](file:///c:/Dev/Docker/SportFlow/SportFlow-frontEnd/src/services/athletesService.ts)

- Adicionar as propriedades opcionais na interface `AthleteData`:
  - `position?: string;`
  - `observations?: string;`

#### [MODIFY] [Athletes/index.tsx](file:///c:/Dev/Docker/SportFlow/SportFlow-frontEnd/src/pages/Athletes/index.tsx)

- No estado inicial `formData`, adicionar `position: ''` e `observations: ''`.
- Na função `loadAthleteData`, incluir o mapeamento dos novos campos retornados pela API.
- No formulário JSX (abaixo de "Cidade" ou outro local adequado):
  - Adicionar um `<select>` para **Posição**.
  - Adicionar um `<textarea>` para **Observações**.

## Verification Plan

### Automated / Backend Tests

- Rodar as migrations.
- Testar o cadastro de um atleta enviando os novos dados via formulário.

### Manual Verification

- Acessar o sistema, ir até a tela de Cadastro de Atleta.
- Preencher os campos "Posição" e "OBS".
- Salvar o atleta.
- Editar o atleta recém-criado e validar se os dados preenchidos foram carregados corretamente na tela.

# Plano de Implementação de Segurança (Parte 3)

Como existem muitos pontos na Parte 3 (desde ajustes de variáveis até refatoração de código), dividi a implementação em **3 Fases** lógicas, baseadas no nível de esforço e impacto no sistema.

## User Review Required

> [!IMPORTANT]
> **Fase 3 (Cookies HttpOnly)** exige uma mudança na forma como o frontend e backend se comunicam (abandono do localStorage). Isso pode gerar bugs de sessão temporários durante o desenvolvimento.
>
> **Pergunta:** Você deseja executar todas as 3 fases agora, ou prefere aplicar as Fases 1 e 2 (que resolvem 90% dos problemas de forma mais simples) e deixar a Fase 3 para um outro momento?

---

## 🛠️ Fase 1: Infraestrutura e Configuração (Rápido & Crítico)

Esta fase altera apenas arquivos de configuração, blindando o ambiente.

### `docker-compose.yml`

- **[MODIFY]**: Remover a exposição pública da porta `5432` do PostgreSQL (apenas o Laravel internamente deve acessá-lo) e alterar as credenciais fracas. _(Resolve SEG 2)_.

### `.env`

- **[MODIFY]**: Ajustar `APP_ENV=production` e `APP_DEBUG=false` para não vazar código fonte e senhas em telas de erro. _(Resolve SEG 1 e 3)_.

### `config/cors.php`

- **[MODIFY]**: Limpar as duplicações e preparar as origens permitidas corretamente. _(Resolve SEG 8)_.

---

## 🛡️ Fase 2: Correções no Backend (Médio Esforço)

Esta fase foca em fechar brechas no código PHP.

### `app/Models/User.php`

- **[MODIFY]**: Remover `refresh_token` e `refresh_token_expires_at` do array `$fillable` (evitando injeção) e adicioná-los ao `$hidden` para não vazarem nas respostas JSON. _(Resolve SEG 4)_.

### `routes/api.php`

- **[MODIFY]**: Adicionar o middleware `throttle:login` (limitador de taxa) na rota `/login` para impedir ataques de força-bruta (tentar senhas repetidamente). _(Resolve SEG 10)_.

### `app/Http/Controllers/EnterpriseController.php`

- **[MODIFY]**: Adicionar verificações nos métodos `show()` e `destroy()` para que gestores/funcionários só consigam ver ou deletar os dados da sua própria empresa (O `update` já foi corrigido!). _(Resolve SEG 5 e 7)_.

### `app/Http/Controllers/AuthController.php` & Recursos

- **[MODIFY]**: O endpoint `/user` (me) está retornando todos os dados do banco. Vamos criar um `UserResource` para filtrar e devolver apenas os campos não-sensíveis. _(Resolve SEG 13)_.
- **[MODIFY]**: Aumentar a política de senha no cadastro, exigindo mínimo de 8 caracteres, letras e números. _(Resolve SEG 12)_.

### (Opcional) Validação de CPF/CNPJ

- **[MODIFY]**: Adicionar uma regra de validação em formato e tamanho exatos para o campo document. _(Resolve SEG 11)_.

---

## 🏗️ Fase 3: Refatoração de Sessão (Alto Esforço)

Esta fase altera profundamente como o login funciona no Frontend.

### `SportFlow-frontEnd/src/contexts/AuthContext.tsx` e `api.ts`

- **[MODIFY]**: Remover completamente a leitura e gravação de tokens do `localStorage`. O frontend passará a depender do backend enviando um cookie seguro e HTTP Only (`withCredentials = true` no Axios). _(Resolve SEG 9)_.

### Configuração de Cookies Seguros no Backend

- **[MODIFY]**: Alterar a forma como o `AuthController` envia o token no login para acoplar no Header da resposta como um Cookie em vez de texto puro no JSON.

---

## Verification Plan

### Testes Manuais Pós-Fase 2

1. Acessar uma rota inexistente para verificar se a página de erro agora não expõe código fonte (Erro genérico do Laravel).
2. Tentar acessar via URL os dados de uma empresa diferente com a conta de um Gestor (deverá ser barrado).
3. Tentar fazer 10 logins errados seguidos: o sistema deve bloquear (Too Many Requests).
4. Checar o JSON de resposta ao consultar o próprio perfil: os campos de token devem estar ocultos.

# Plano de Implementação: Melhorias Restantes (Parte 4)

A **Parte 4** do documento de análise é, na verdade, um grande resumo priorizado de tudo o que estava nas Partes 2 e 3. A excelente notícia é que **já implementamos quase todas as prioridades Críticas (C) e Altas (A)** nos nossos passos anteriores!

O que sobrou são melhorias de Qualidade (Média) e Novas Funcionalidades (Baixa).

## User Review Required

> [!WARNING]
> Respondendo à sua pergunta: **Sim, algumas dessas melhorias VÃO alterar a estrutura do sistema (banco de dados).**
> Itens como "Esqueci minha Senha" e "Campo Role" exigem novas tabelas e colunas.
>
> Por favor, revise a separação abaixo e me diga: você quer implementar apenas o que **NÃO** altera a estrutura agora?

---

## 🟢 Bloco 1: NÃO altera a estrutura do sistema (Apenas Código)

Estas implementações são seguras e não exigem rodar `migrations` no banco de dados.

### 1. Tratamento de Erros e Logs no Frontend

- **Passos**:
  1. Criar um utilitário de log para silenciar `console.log` em produção _(M5)_.
  2. Editar o formulário de Cadastro de Empresa (`src/pages/Enterprise/index.tsx`) para capturar os erros 422 da API e mostrá-los em vermelho embaixo de cada campo (nome, cnpj, etc) _(M7)_.

### 2. Testes Automatizados (Backend)

- **Passos**:
  1. Criar arquivos de teste no PHPUnit (`tests/Feature`).
  2. Escrever testes automatizados cobrindo Login, e o CRUD (Criar, Ler, Atualizar, Deletar) de Atletas e Empresas _(M2)_.

### 3. Melhorias de Validação e Regras de Negócio (Backend)

- **Passos**:
  1. Instalar um pacote ou criar uma regra customizada no Laravel para validar matematicamente se um CPF/CNPJ é real _(B1)_.
  2. Alterar o `StoreAthletesRequest` para garantir que o email do atleta seja único apenas **dentro daquela empresa** (hoje aceita repetido) _(B10)_.

### 4. Paginação e Facilitadores

- **Passos**:
  1. Alterar o `EnterpriseController@index` para usar `->paginate(15)` em vez de `->get()`.
  2. Ajustar a tela do Painel Admin no React para exibir os botões de "Próxima Página" nas empresas _(B8)_.
  3. Criar um `DatabaseSeeder` para gerar o primeiro Super Admin automaticamente com um comando _(B9)_.

---

## 🔴 Bloco 2: ALTERA a estrutura do sistema (Banco de Dados)

Estas implementações exigem criar novas tabelas ou colunas, mudando a arquitetura do banco.

### 1. Funcionalidade "Esqueci Minha Senha" _(B7)_

- **Passos**:
  1. Criar tabela de `password_resets` no banco de dados.
  2. Configurar o envio de e-mail real no `.env` (SMTP).
  3. Criar rotas no backend para gerar token de recuperação e enviar e-mail.
  4. Criar a tela de "Esqueci minha senha" e "Redefinir Senha" no frontend.

### 2. Novo Nível de Acesso (Roles) _(B5)_

- **Passos**:
  1. Criar uma migration para adicionar a coluna `role` (ex: admin, manager, viewer) na tabela `users`.
  2. Atualizar as `Policies` do Laravel para verificar esse novo campo em vez de apenas checar `is_admin`.
  3. Alterar as telas de controle de acesso no React.
