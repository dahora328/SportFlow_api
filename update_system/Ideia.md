Pergunta 1:
Você consegue olhar a estrutura toda do meu projeto e analisar, para me falar como poderia fazer a questão da empresa. Porque queria fazer um sistema multiempresa, porém agora quero ter o controle para eu como gestor conseguir cadastrar a empresa e só após eu cadastrar o usuário conseguir fazer o cadastro do seus funcionários para usar o sistema, não precisa fazer a modificação agora, só quero uma explicação para ter ideia como fazer e aplicar sem ter que fazer tudo novamente

Listed directory Models
Listed directory migrations
Viewed User.php:1-72
Viewed Enterprise.php:1-40
Viewed Athlete.php:1-72

Com base na análise da estrutura do seu projeto atual, percebo que você **já começou com o pé direito!** Você já tem as colunas `enterprise_id` nas tabelas `users` e `athletes`, e também já tem uma flag `is_admin` na tabela `users`.

Isso significa que a fundação para o sistema "multi-tenant" (multiempresa) já existe no seu banco de dados. O que você precisa fazer é apenas ajustar a lógica de como esses dados interagem.

Como você pediu apenas uma explicação para ter o norte de como aplicar isso no futuro sem refazer o sistema, aqui está o fluxo e as lógicas principais que você aplicará:

### 1. Separação de Papéis (Roles)

O seu campo `is_admin` na tabela `users` será o seu **Super Admin** (Dono do Sistema - você).

- **Se `is_admin = true`**: É você. Você não pertence a nenhuma empresa específica (seu `enterprise_id` pode ser nulo). Seu painel vai mostrar a gestão de **Empresas** (cadastrar, inativar, etc).
- **Se `is_admin = false`**: É o cliente (Gestor ou Funcionário). Este usuário **obrigatoriamente** tem um `enterprise_id`.

### 2. O Fluxo de Cadastro que você deseja

A lógica de criação acontecerá na seguinte ordem:

1. **Você (Super Admin)** acessa o painel e **Cadastra a Empresa**.
2. **Você (Super Admin)** cria o **Primeiro Usuário** para aquela empresa (o Gestor). Ao criar esse usuário, você passa o `enterprise_id` da empresa recém-criada para o perfil dele.
3. **O Gestor** faz login. O sistema identifica que ele tem um `enterprise_id`.
4. **O Gestor** vai na tela de "Usuários/Funcionários" e cadastra a sua equipe. Como o Gestor já pertence à Empresa X, o backend pega automaticamente o `enterprise_id` do Gestor e injeta nos novos funcionários que ele está criando. Assim, todos ficam "presos" sob o mesmo guarda-chuva.

### 3. O "Pulo do Gato" no Backend: _Global Scopes_

No seu arquivo `app/Models/Athlete.php`, eu vi que você tem esta função excelente que isola os dados por usuário:

```php
// Atual no Athlete.php
static::addGlobalScope('owner', function (Builder $builder) {
    if (Auth::check()) {
        $builder->where('owner_id', Auth::id());
    }
});
```

Para virar **Multiempresa**, você vai simplesmente trocar essa lógica do "dono que criou" pelo "id da empresa". Assim, qualquer funcionário da mesma empresa verá os mesmos alunos/atletas. A lógica mudará para algo como:

```php
// Futuro no Athlete (e em qualquer outra tabela como Planos, Aulas, etc)
static::addGlobalScope('enterprise', function (Builder $builder) {
    if (Auth::check() && !Auth::user()->is_admin) { // Se for usuário normal
        $builder->where('enterprise_id', Auth::user()->enterprise_id);
    }
});
```

Fazendo apenas isso no Model, **você não precisa mudar suas consultas nos Controllers**. Se você fizer `Athlete::all()`, o Laravel automaticamente filtra e só traz os atletas da empresa do usuário logado. Isso evita que você tenha que refazer as buscas em todo o sistema.

E ao **criar** um novo atleta, em vez de salvar o `owner_id`, você salvará o `enterprise_id`:

```php
static::creating(function ($athlete) {
    if (Auth::check() && !Auth::user()->is_admin) {
        $athlete->enterprise_id = Auth::user()->enterprise_id;
    }
});
```

### 4. Proteção de Rotas (Middlewares)

Para que o Gestor de uma empresa não consiga acessar a rota de cadastrar empresas, você no futuro criará um **Middleware** simples no Laravel (ex: `CheckSuperAdmin`).
Nas suas rotas `api.php`, você fará uma separação:

- **Rotas de Super Admin (protegidas pelo middleware):** `/enterprises` (Criar, Editar empresas).
- **Rotas de Empresa (padrão):** `/athletes`, `/users` (Aqui, os Controllers apenas salvam e buscam dados, e os _Global Scopes_ dos Models cuidam para que a Empresa A não veja os dados da Empresa B).

### Resumo

Você não precisará refazer tudo! A arquitetura que você montou já suporta essa transição. Quando for o momento de aplicar, seus passos serão:

1. Criar um Middleware para bloquear as rotas de gerenciamento de empresas apenas para o `is_admin`.
2. Trocar o `Global Scope` do `Athlete.php` (e outros) de `owner_id` para `enterprise_id`.
3. Ajustar o `UserController` para que, se um gestor comum estiver criando um usuário, o `enterprise_id` injetado seja o do próprio gestor (amarrando a equipe).

Fazendo isso na camada dos _Models_, o seu Frontend praticamente não precisará saber de nada disso, ele continuará consumindo as rotas `/athletes` e o backend magicamente só devolverá os atletas da empresa correta.

Pergunta 2:
Com essa sugestão você me solicitou, conseguir entender, porém caso precise colocar funções que somente o dono da empresa possa fazer como deletar algum funcionário ou até mesmo futuramente relatórios somente ele possa ver, como faria isso pois o campo is_admin tinha primeiramente pensado para isso

Edited Ideia.md
Edited EnterpriseController.php
Edited EnterpriseController.php
Viewed index.tsx:38-67

Essa é uma dúvida excelente e é exatamente onde muitos sistemas SaaS (Software as a Service) precisam definir bem sua arquitetura. Você tem o **"Dono do Sistema" (Você)** e o **"Dono da Empresa" (Seu Cliente)**.

A ótima notícia é que você **pode sim usar o `is_admin` para isso, sem precisar criar colunas novas no banco de dados agora**. A mágica acontece quando você combina a coluna `is_admin` com a coluna `enterprise_id` que você já tem na tabela `users`.

Aqui está como você pode estruturar essa hierarquia de forma elegante usando o que você já tem:

### A Hierarquia usando `is_admin` + `enterprise_id`

1. **Nível 1: Super Admin (Você / Dono do SportFlow)**
   - `is_admin = true`
   - `enterprise_id = null`
   - _Lógica:_ Como ele é admin, mas não pertence a nenhuma empresa específica (é nulo), ele tem acesso total para gerenciar assinaturas, criar novas empresas, ver faturamento geral do sistema, etc.

2. **Nível 2: Gestor / Dono da Empresa Cliente**
   - `is_admin = true`
   - `enterprise_id = 1` (exemplo)
   - _Lógica:_ Ele é um admin, **porém o poder dele está "preso" aos limites da Empresa 1**. Ele pode ver relatórios financeiros da empresa dele, deletar funcionários da empresa dele e editar configurações da empresa dele.

3. **Nível 3: Funcionário Comum**
   - `is_admin = false`
   - `enterprise_id = 1`
   - _Lógica:_ É a equipe da empresa. Não podem acessar relatórios, não podem deletar usuários, apenas fazem operações do dia a dia (cadastrar alunos, marcar aulas, etc).

---

### Como aplicar isso na prática?

#### 1. No Backend (Laravel - Exemplo para deletar funcionário)

Quando bater uma requisição no Laravel para deletar um usuário, no seu Controller (ou em uma _Policy_), você faria uma verificação parecida com essa:

```php
public function destroy($id)
{
    $usuarioLogado = auth('api')->user();
    $funcionarioParaDeletar = User::findOrFail($id);

    // Verifica se o usuário logado é Admin da MESMA empresa do funcionário que será deletado
    if ($usuarioLogado->is_admin && $usuarioLogado->enterprise_id === $funcionarioParaDeletar->enterprise_id) {
        $funcionarioParaDeletar->delete();
        return response()->json(['message' => 'Funcionário deletado com sucesso.']);
    }

    // Se for o Super Admin (Você), também poderia deixar:
    if ($usuarioLogado->is_admin && $usuarioLogado->enterprise_id === null) {
        $funcionarioParaDeletar->delete();
        return response()->json(['message' => 'Deletado pelo Super Admin.']);
    }

    return response()->json(['error' => 'Acesso negado. Apenas o gestor da empresa pode deletar.'], 403);
}
```

#### 2. No Frontend (React - Escondendo Botões)

No seu frontend, quando você receber os dados do usuário logado, você pode ocultar ou exibir botões dependendo dessas duas variáveis.

```tsx
// Exemplo no React
const user = getUsuarioLogado(); // Seu hook/estado de auth

// Botão de deletar que só aparece para o Gestor da Empresa
{
  user.is_admin && user.enterprise_id !== null && (
    <button onClick={() => deletarFuncionario(id)}>Deletar Funcionário</button>
  );
}

// Menu de Relatórios da Empresa
{
  user.is_admin && <Link to="/relatorios">Relatórios Financeiros</Link>;
}
```

### E se o sistema crescer muito no futuro? (Plano B)

Se no futuro as empresas clientes pedirem níveis de acesso mais complexos (exemplo: "Quero que meu Gerente veja relatórios, mas minha Recepcionista e o Professor de Educação Física tenham acessos diferentes"), aí sim a flag `is_admin` (true/false) vai ficar limitada.

Neste cenário futuro, a solução padrão de mercado é criar uma coluna nova na tabela `users` chamada `role` (cargo) guardando uma string: `'owner'`, `'manager'`, `'receptionist'`, `'trainer'`.

Mas para o momento atual do seu projeto, onde você quer separar **Gestor vs Funcionário**, usar o `is_admin` associado ao fato de ter ou não um `enterprise_id` atende 100% o que você pensou inicialmente e deixa o sistema extremamente seguro e organizado!

---

--------------PLANO B------------- (VAI SER USADO NO FUTURO)

Agora tenho uma visão completa do seu projeto. Vou te dar um plano concreto e prático.

Primeiro, um ponto importante que identifiquei no código: o seu `AuthController.php` no `register` já tem um comentário `// Futuramente arrumar para permitir várias empresas.` e hardcoda o `enterprise_id = 1`. Isso significa que o cadastro de usuários **hoje está quebrado para multiempresa**. Esse será o primeiro ponto a corrigir.

---

## O Plano Completo: Sem quebrar o que existe

### O problema atual em resumo

Você tem 3 pontos que precisam ser conectados:

1. `EnterpriseController@index` → Só retorna empresa se o usuário logado já tiver `enterprise_id` ✅
2. `EnterpriseController@store` → Cria a empresa e já vincula ao usuário que criou ✅
3. `AuthController@register` → **Problema:** Hardcoda `enterprise_id = 1`, então qualquer um que se registrar vai para a empresa 1 ❌

---

### Passo a Passo

#### **Passo 1 — Criar um `UserController` no backend**

Hoje você não tem uma rota para criar usuários de forma controlada. Você precisará criar um `UserController` com um método `store` específico para que **você (Super Admin) possa criar o primeiro usuário de uma empresa**. A lógica será:

```php
// UserController@store (somente para Super Admin usar)
public function store(Request $request)
{
    $logado = auth('api')->user();

    // Segurança: só você (is_admin sem enterprise) pode usar essa rota
    if (!$logado->is_admin || $logado->enterprise_id !== null) {
        return response()->json(['error' => 'Acesso negado'], 403);
    }

    $user = User::create([
        'name'          => $request->name,
        'email'         => $request->email,
        'password'      => Hash::make($request->password),
        'is_admin'      => true, // Gestor da empresa
        'enterprise_id' => $request->enterprise_id, // passado por você no form
    ]);

    return response()->json(['message' => 'Usuário criado!', 'user' => $user], 201);
}
```

#### **Passo 2 — Corrigir o `register` público**

O endpoint `/register` público precisa deixar de aceitar `enterprise_id` hardcoded. Como você não quer que qualquer pessoa se cadastre livremente, a opção mais simples para o deploy agora é **desabilitar o `/register` público** (ou deixar protegido por senha interna), pois quem cria usuário é você pelo painel de admin:

```php
// AuthController@register — bloquear registro público
public function register(Request $request)
{
    return response()->json(['error' => 'Registro público desabilitado.'], 403);
}
```

#### **Passo 3 — Criar uma tela de "Painel do Gestor Geral" no Frontend**

No React, criar uma nova página (ex: `/admin`) que **só aparece no menu se `user.is_admin === true && user.enterprise_id === null`**. Essa página terá dois formulários simples:

```
┌─────────────────────────────────────────────────────┐
│  PAINEL GESTOR GERAL                                │
│                                                     │
│  [1. Cadastrar Empresa]          [2. Cadastrar      │
│  - Nome                          Primeiro Usuário]  │
│  - CNPJ                          - Nome             │
│  - Email...                      - Email            │
│  [Salvar]                        - Senha            │
│                                  - Empresa (select) │
│                                  [Criar Usuário]    │
└─────────────────────────────────────────────────────┘
```

O formulário de "Cadastrar Primeiro Usuário" buscaria a lista de empresas cadastradas (via `GET /enterprises`) e exibiria um `<select>` para você escolher a qual empresa vincular o novo gestor.

#### **Passo 4 — Adicionar rota para o novo UserController**

```php
// routes/api.php — dentro do middleware auth:api
Route::middleware(['auth:api'])->group(function () {
    // ... rotas existentes ...
    Route::post('/users', [UserController::class, 'store']); // Novo
    Route::get('/users', [UserController::class, 'index']);  // Opcional: listar usuários da empresa
});
```

---

### Fluxo final após implementação

```
Você (Super Admin) faz login
        │
        ▼
   Painel Admin aparece no menu
        │
   ┌────┴────────────────────┐
   │                         │
   ▼                         ▼
Cria a Empresa         (depois)
   │
   ▼
Cria o 1º Usuário
(vincula à empresa)
   │
   ▼
Cliente recebe email/senha e faz login
   │
   ▼
Entra no sistema com enterprise_id = X
(tudo isolado da empresa X automaticamente)
```

---

### O que NÃO precisa mudar para não quebrar

| O que já existe                 | Status                  |
| ------------------------------- | ----------------------- |
| `EnterpriseController@update`   | ✅ Continua funcionando |
| `EnterpriseController@index`    | ✅ Continua funcionando |
| `AthletesController` (tudo)     | ✅ Não muda nada        |
| Tela de Empresa (`/enterprise`) | ✅ Continua funcionando |
| Login / Refresh token           | ✅ Não muda nada        |
| Toda lógica de atletas          | ✅ Não muda nada        |

A única coisa que **muda de verdade** é: bloquear o `/register` público, criar o `UserController`, e criar uma pequena tela de admin no Frontend. Isso é tudo — cirúrgico, sem refatorar nada do que já funciona.

Fazer isso futuramente:

- Adicionar campo no cadastro Posição do jogador.
- Adicionar um campo OBS para fins de relatório e histórico.
  Pedi sugestão da IA para ver o que fica melhor
