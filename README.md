# Café Aroma

Sistema web para gerenciamento do Café Aroma, com catálogo de produtos,
pedidos, encomendas para retirada, área do cliente e dashboard administrativo.

## Tecnologias

- Laravel 13
- PHP 8.3 ou superior
- Vue 3
- Inertia.js
- Vite Plus
- SQLite (configuração padrão)
- Node.js e npm

## Requisitos

Instale antes de iniciar:

- PHP 8.3+
- Composer
- Node.js 20+ e npm
- Git

No Windows, confirme as instalações:

```powershell
php --version
composer --version
node --version
npm --version
```

## Instalação

Clone o projeto e entre na pasta:

```powershell
git clone <url-do-repositorio>
cd projetofinal
```

Instale as dependências do Laravel:

```powershell
composer install
```

Crie o arquivo de ambiente e gere a chave da aplicação:

```powershell
Copy-Item .env.example .env
php artisan key:generate
```

O projeto usa SQLite por padrão. Caso o arquivo ainda não exista, crie-o:

```powershell
New-Item -ItemType File database\database.sqlite
```

No arquivo `.env`, configure a senha inicial do administrador:

```dotenv
ADMIN_INITIAL_PASSWORD=sua-senha-segura
```

As configurações padrão do banco são:

```dotenv
DB_CONNECTION=sqlite
```

Execute as migrations:

```powershell
php artisan migrate
```

Execute o seed do banco de dados:

```powershell
php artisan db:seed
```

O seed cria ou atualiza o administrador inicial:

- E-mail: `adm@adm.com`
- Nome: `administrador`
- Senha: o valor definido em `ADMIN_INITIAL_PASSWORD`

Instale as dependências do frontend, incluindo Vue, Inertia e Vite:

```powershell
npm install
```

Crie o link para arquivos públicos armazenados pelo Laravel:

```powershell
php artisan storage:link
```

## Executando em desenvolvimento

Inicie o servidor Laravel:

```powershell
php artisan serve
```

Em outro terminal, inicie o Vite:

```powershell
npm run dev
```

Acesse:

```text
http://localhost:8000
```

Também é possível usar o script de desenvolvimento configurado no Composer:

```powershell
composer run dev
```

## Build para produção

Compile os assets do Vue e do Vite:

```powershell
npm run build
```

Para gerar também os assets SSR:

```powershell
npm run build:ssr
```

Em produção, configure o `.env` com `APP_ENV=production`,
`APP_DEBUG=false`, uma `APP_KEY` válida e as credenciais do banco. Depois,
execute:

```powershell
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
```

## Views e frontend

As views principais usam Inertia.js e Vue:

- `resources/js/pages/Cafe.vue`: página principal, catálogo, checkout, conta
  do cliente e dashboard administrativo.
- `resources/js/pages/customer/`: telas de cadastro e recuperação de senha
  do cliente.
- `resources/js/pages/auth/`: telas de autenticação administrativa.
- `resources/js/components/cafe/`: componentes reutilizáveis da interface.
- `resources/js/router.ts`: rotas de navegação do frontend.

O template Blade que inicializa o Inertia e os assets do Vite está em:

```text
resources/views/app.blade.php
```

As rotas HTTP do Laravel estão em:

```text
routes/web.php
```

## Banco de dados

Para recriar o banco SQLite do zero, apagar os dados e executar novamente
as migrations e o seed:

```powershell
php artisan migrate:fresh --seed
```

Para executar somente as migrations:

```powershell
php artisan migrate
```

Para executar somente os seeders:

```powershell
php artisan db:seed
```

## Testes e verificações

Execute os testes do Laravel:

```powershell
php artisan test
```

Verifique os tipos do frontend:

```powershell
npm run types:check
```

Execute a verificação de código do frontend:

```powershell
npm run check
```
