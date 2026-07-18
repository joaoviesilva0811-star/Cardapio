# Delícias da Maria — versão estática (Firebase)

Site 100% HTML/CSS/JS. Os dados (cardápio e login do admin) ficam no
**Firebase** (grátis), então funcionam de verdade em qualquer hospedagem
estática, incluindo **GitHub Pages**.

## 1. Criar o projeto no Firebase (grátis, ~5 minutos)

1. Acesse https://console.firebase.google.com → **Adicionar projeto**.
2. Depois de criado, no menu lateral vá em **Firestore Database** →
   **Criar banco de dados** → escolha **Iniciar em modo de produção** →
   escolha uma localização (ex: `southamerica-east1` / São Paulo).
3. Vá em **Authentication** → aba **Sign-in method** → ative **Email/senha**.
4. Ainda em **Authentication** → aba **Users** → **Add user** → cadastre o
   e-mail e senha de cada pessoa que vai administrar o cardápio.
5. Vá em **Configurações do projeto** (ícone de engrenagem, topo esquerdo) →
   role até **Seus apps** → clique no ícone **`</>`** (Web) → registre um
   app (não precisa do Firebase Hosting) → copie o objeto `firebaseConfig`
   que aparece.
6. Cole esse objeto em `assets/js/firebase-config.js`, substituindo os
   valores de exemplo.
7. Em **Firestore Database** → aba **Regras**, cole o conteúdo de
   `FIRESTORE_RULES.txt` (está neste zip) e clique em **Publicar**.

Pronto — o backend já existe, sem escrever nenhuma linha de servidor.

## 2. Testar localmente

Como o navegador bloqueia alguns recursos ao abrir `index.html` direto do
disco (`file://`), rode um servidor local simples antes de testar:

```bash
cd site
python3 -m http.server 8000
```

Depois abra `http://localhost:8000` no navegador.

## 3. Publicar no GitHub Pages

1. Crie um repositório no GitHub e suba todo o conteúdo desta pasta
   (`index.html`, `login.html`, `admin/`, `assets/` etc.) na raiz do repo
   (ou na branch/pasta que você configurar no Pages).
2. No repositório: **Settings → Pages → Source** → escolha a branch (ex:
   `main`) e a pasta (`/root`) → **Save**.
3. Espere alguns minutos e acesse o link que o GitHub Pages gerar
   (algo como `https://seu-usuario.github.io/seu-repo/`).

Isso funciona porque o site não depende de PHP nem de nenhum processo
rodando no servidor — GitHub Pages só precisa servir arquivos estáticos, e
quem faz o trabalho de "banco de dados" é o Firebase, direto do navegador
de quem acessa.

## Como funciona agora

- **`index.html`** — cardápio público. Lê categorias/produtos do Firestore
  e é visível para qualquer pessoa (leitura pública nas regras).
- **`login.html`** — login usando Firebase Authentication (email/senha
  cadastrados no console, não mais senha fixa no código).
- **`admin/*.html`** — painel para criar/editar/excluir seções, produtos e
  atributos. Só funciona logado; as regras do Firestore bloqueiam
  escrita de quem não está autenticado.

## Limitações a saber

- **Fotos de produto** são salvas como imagem embutida (base64) dentro do
  próprio documento no Firestore. Isso funciona bem para fotos pequenas,
  mas cada documento tem um limite de ~1MB — evite fotos muito grandes
  (o ideal é usar uma foto já otimizada para web, algo entre 100–300KB).
  Se isso virar um problema no futuro, o passo natural é ligar o
  **Firebase Storage** para guardar as fotos separadamente.
- O plano gratuito do Firebase (Spark) tem limites generosos de leitura/
  escrita por dia — mais que suficiente para um cardápio de uma
  confeitaria, mas vale saber que existe um teto caso o site cresça muito.
- Como não há mais um "super admin" só de olhar o código: qualquer pessoa
  cadastrada em Authentication → Users tem acesso total ao painel
  (criar/editar/excluir tudo). Cadastre só quem for realmente administrar.
