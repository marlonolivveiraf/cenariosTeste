# Guia de Uso - Gerador de Cenários de Testes

Bem-vindo ao Gerador de Cenários de Testes. Este projeto utiliza IA para criar cenários de testes baseados em documentação e permite a publicação direta no Confluence.

## Requisitos do Sistema

- **PHP**: ^8.1
- **Composer**
- **Node.js** & **NPM**
- **Banco de Dados**: MySQL (ou outro suportado pelo Laravel)

## Instalação e Configuração

Siga os passos abaixo para preparar o ambiente de desenvolvimento.

### 1. Instalação das Dependências

Instale as dependências do backend (PHP) e frontend (Node.js):

```bash
# Dependências PHP
composer install

# Dependências Frontend
npm install
npm run build
```

### 2. Configuração do Ambiente (.env)

Copie o arquivo de exemplo e gere a chave da aplicação:

```bash
cp .env.example .env
php artisan key:generate
```

Edite o arquivo `.env` para incluir as seguintes configurações obrigatórias:

#### Banco de Dados
```env
DB_CONNECTION=sqlite
DB_HOST=127.0.0.1
DB_PORT=3306
# DB_DATABASE=laravel
DB_USERNAME=root
DB_PASSWORD=
```

#### Integração OpenAI
Necessário para a geração dos cenários.
```env
OPENAI_API_KEY=sk-...
```

#### Integração Confluence
Necessário para publicar os cenários gerados.
```env
CONFLUENCE_URL=https://seu-dominio.atlassian.net/wiki/rest/api/content
CONFLUENCE_EMAIL=seu-email@dominio.com
CONFLUENCE_API_TOKEN=seu-api-token
```
> **Nota**: O `CONFLUENCE_URL` deve apontar para a base da API REST do seu espaço Confluence.

### 3. Migrações

Execute as migrações para criar as tabelas necessárias:

```bash
php artisan migrate
```

---

## Configuração da Página do Confluence (Uso)

Ao utilizar o gerador para criar e publicar cenários, você precisará fornecer informações para direcionar a publicação corretamente.

### Campos Necessários

1. **Título (Title)**
   - Formato sugerido: `EIXO-XXXX`
   - Descrição: O identificador ou título da tarefa/história.

2. **Espaço (Space)**
   - Valor Padrão: `EIXO`
   - Descrição: A chave do espaço (Space Key) no Confluence.

3. **ID da Página Pai (Parent ID)**
   - Exemplo: `222756866`
   - Descrição: O ID numérico da página onde a nova página será criada como filha (ex: "Cenários de Testes EIXO").
   - **Como encontrar:** Navegue até a página no Confluence e veja o parâmetro `pageId` na URL.

### Exemplo Prático de Uso

Para publicar cenários da tarefa **EIXO-1234** dentro da pasta "Cenários de Testes EIXO" (ID: 222756866):

- **Título**: `EIXO-1234`
- **Space**: `EIXO`
- **ID**: `222756866`
