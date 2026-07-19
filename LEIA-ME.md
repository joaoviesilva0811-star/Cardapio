# Delícias da Maria — Cardápio Online

Site de cardápio para a confeitaria da sua mãe, com:
- **Site público**: cardápio com fotos, sabores, coberturas e recheios. O cliente escolhe as opções e o pedido é enviado pronto para o WhatsApp dela.
- **Painel administrativo**: login protegido (só você e sua mãe) para adicionar, editar e remover bolos, sabores, coberturas e recheios, com upload de fotos.

---

## 1. Como rodar no XAMPP (igual você já faz com o ConGlic)

1. Copie a pasta inteira `delicias-de-maria` para dentro de `htdocs` do XAMPP.
   - Windows: `C:\xampp\htdocs\delicias-de-maria`
2. Abra o **XAMPP Control Panel** e inicie o **Apache** e o **MySQL**.
3. Abra o **phpMyAdmin** (`http://localhost/phpmyadmin`).
4. Clique em **Importar**, selecione o arquivo `database.sql` desta pasta, e clique em **Executar**.
   - Isso cria o banco `delicias_de_maria`, **totalmente vazio**, com só as duas contas de login (nenhum bolo, sabor, cobertura ou recheio de exemplo — é para você e sua mãe cadastrarem do zero).
5. Acesse `http://localhost/delicias-de-maria/` no navegador. Como o cardápio está vazio, vai aparecer a mensagem "O cardápio ainda está sendo preparado" — é normal, significa que está tudo funcionando.
6. Para entrar no painel, acesse `http://localhost/delicias-de-maria/login.php` (ou clique em "Área administrativa" no rodapé do site) e comece a cadastrar sabores, coberturas, recheios e os bolos.

### Se der erro ao importar o database.sql

- **"Banco de dados já existe" / erro de tabela duplicada**: você já tinha importado antes. Vá em phpMyAdmin, clique no banco `delicias_de_maria` na lateral, aba **Operações** → **Apagar o banco de dados (DROP)**, e importe de novo.
- **"#1044 Access denied"**: o usuário do MySQL configurado não tem permissão para criar bancos. No XAMPP padrão isso não deveria acontecer (usuário `root` sem senha) — confirme em `config/database.php` que `DB_USER` é `root` e `DB_PASS` está em branco (`''`).
- **Erro ao carregar o site (tela de erro do PHP mencionando "banco de dados")**: normalmente é porque o MySQL do XAMPP não está ligado, ou o banco `delicias_de_maria` não foi criado ainda. Confira o painel do XAMPP e refaça o passo 4.
- **Qualquer outro erro**: me manda a mensagem completa que aparece na tela (ou um print) que eu já te digo exatamente o que fazer.

### Logins do painel administrativo

| Quem | Email | Senha |
|---|---|---|
| Maria | mfatima01@gmail.com | mfatima1 |
| João | joaoviesilva0811@gmail.com | k9djfami |

As senhas ficam salvas **criptografadas** no banco (nunca em texto puro), então mesmo quem tiver acesso ao banco de dados não consegue ver a senha original.

> ⚠️ Se quiser trocar as senhas depois, me avise — é só gerar um novo hash e atualizar a tabela `admins`.

---

## 2. Colocando no ar de verdade (quando quiser)

Quando quiser que os clientes acessem de fora, você vai precisar de uma hospedagem com **PHP + MySQL** (o mesmo tipo de coisa que o ConGlic usa). Algumas opções:

- **Hospedagem da própria ETEC/escola**, se ela permitir hospedar projetos externos.
- **Hospedagens brasileiras baratas**, como Hostinger, KingHost, Hostgator (planos de entrada costumam ter PHP + MySQL inclusos).
- **Opções gratuitas para testar/validar** antes de pagar por algo, como InfinityFree ou 000webhost (têm limitações, mas servem para mostrar o site funcionando para a sua mãe).

Quando for hospedar de verdade:
1. Suba todos os arquivos da pasta `delicias-de-maria` via FTP ou gerenciador de arquivos do painel de hospedagem.
2. Crie um banco MySQL no painel da hospedagem e importe o `database.sql`.
3. Edite `config/database.php` com os dados de conexão que a hospedagem fornecer (host, nome do banco, usuário, senha).
4. Confirme que a pasta `uploads/` tem permissão de escrita (geralmente permissão 755 ou 775).

Se quiser, me chama quando for para essa etapa que eu te ajudo a configurar certinho para a hospedagem que você escolher.

---

## 3. Como usar o painel administrativo

- **Visão geral**: mostra quantos bolos, sabores, coberturas e recheios estão cadastrados.
- **Bolos**: cadastre um bolo com nome, descrição, preço e foto, e marque quais sabores/coberturas/recheios estão disponíveis para ele. Dá para ocultar um bolo do cardápio sem excluir (útil quando ele está temporariamente indisponível).
- **Sabores / Coberturas / Recheios**: cadastre aqui antes de vincular a um bolo. Pode editar o nome ou excluir a qualquer momento.

## 4. Como o cliente faz o pedido

No cardápio, o cliente clica em "Pedir agora" no bolo desejado, escolhe o sabor/cobertura/recheio (só aparecem as opções que você vinculou àquele bolo) e clica em "Enviar pedido pelo WhatsApp". Isso abre o WhatsApp da confeitaria (número **12 98203-7844**) já com uma mensagem pronta:

```
Olá! Vim pelo cardápio online e gostaria de encomendar:

🎂 Bolo: [nome do bolo]
🍰 Sabor: [sabor escolhido]
🍯 Cobertura: [cobertura escolhida]
🍫 Recheio: [recheio escolhido]

Pode me passar mais detalhes sobre tamanho, data de entrega e valor?
```

---

## Estrutura de arquivos

```
delicias-de-maria/
├── database.sql              → schema do banco, importar no phpMyAdmin
├── index.php                  → cardápio público
├── login.php / logout.php     → autenticação do painel
├── config/database.php        → configurações de conexão (editar ao hospedar)
├── includes/                  → funções auxiliares e autenticação
├── admin/                     → todas as páginas do painel administrativo
├── assets/css, assets/js      → estilo e interatividade
└── uploads/                   → fotos dos bolos (criada automaticamente)
```

---

## O que eu já testei

Rodei o site localmente (servidor PHP + MySQL), com o banco totalmente zerado (do jeito que ele chega para você), e confirmei que tudo funciona de ponta a ponta:
- Importação limpa do `database.sql` sem nenhuma seção/produto/sabor/cobertura/recheio de exemplo
- Cardápio público mostrando a mensagem de "em preparação" quando está vazio
- Login com as duas contas (e bloqueio com senha errada)
- Criação, edição, reordenação e exclusão de seções (com bloqueio de exclusão se a seção ainda tiver produtos)
- Cadastro, edição e exclusão de produtos, com seleção de seção
- Upload real de foto e exibição correta no cardápio
- Cadastro de sabores/coberturas/recheios (opcionais) e vínculo com produtos
- Filtro por seção e busca por nome no cardápio público
- Ocultar/mostrar produto no cardápio sem excluir
- Geração do link de pedido do WhatsApp com as escolhas certas
- Proteção do painel: quem não está logado é redirecionado para o login
- **Responsividade**: testei automaticamente em 10 tamanhos de tela diferentes (320px até tablets de 820px, incluindo celular na horizontal) em todas as páginas do site e do painel — nenhum problema de rolagem horizontal ou quebra de layout, inclusive com nome de produto propositalmente muito comprido

## Sobre as seções (categorias)

Antes de cadastrar produtos, crie as seções do seu cardápio em **Seções**, no painel (ex: Bolos, Doces, Tortas, Salgados). Cada seção tem um emoji e pode ser reordenada com as setinhas ⬆️⬇️. Uma seção só pode ser excluída se não tiver nenhum produto dentro dela — mova ou exclua os produtos primeiro.

No cardápio público, os clientes podem filtrar por seção clicando nos botões ("Todos", "🎂 Bolos", "🍬 Doces"...) e também buscar por nome.
