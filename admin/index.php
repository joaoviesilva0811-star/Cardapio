<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
exigirLogin();

$pdo = getConexao();
$totalCategorias = (int) $pdo->query('SELECT COUNT(*) AS c FROM categorias')->fetch()['c'];
$totalProdutos = (int) $pdo->query('SELECT COUNT(*) AS c FROM produtos')->fetch()['c'];
$totalDisponiveis = (int) $pdo->query('SELECT COUNT(*) AS c FROM produtos WHERE disponivel = 1')->fetch()['c'];
$totalSabores = (int) $pdo->query("SELECT COUNT(*) AS c FROM atributos WHERE tipo = 'sabor'")->fetch()['c'];
$totalCoberturas = (int) $pdo->query("SELECT COUNT(*) AS c FROM atributos WHERE tipo = 'cobertura'")->fetch()['c'];
$totalRecheios = (int) $pdo->query("SELECT COUNT(*) AS c FROM atributos WHERE tipo = 'recheio'")->fetch()['c'];

$ultimosProdutos = $totalCategorias > 0
    ? array_slice(buscarProdutos(false), 0, 5)
    : [];

$paginaAtual = 'dashboard';
$urlBase = '../';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <title>Painel — Delícias da Maria</title>
  <?php include __DIR__ . '/../includes/partials/head.php'; ?>
</head>
<body>
<div class="painel">
  <?php include __DIR__ . '/../includes/partials/admin_sidebar.php'; ?>

  <main class="painel-conteudo">
    <div class="painel-cabecalho">
      <div>
        <h1>Olá, <?= h($_SESSION['admin_nome']) ?> 👋</h1>
        <p>Aqui está um resumo do cardápio da Delícias da Maria.</p>
      </div>
      <?php if ($totalCategorias > 0): ?>
        <a href="produto_form.php" class="btn btn-rosa">+ Novo produto</a>
      <?php else: ?>
        <a href="categorias.php" class="btn btn-rosa">+ Criar primeira seção</a>
      <?php endif; ?>
    </div>

    <?php if ($totalCategorias === 0): ?>
      <div class="alerta alerta-erro">
        Comece criando as seções do seu cardápio (ex: Bolos, Doces, Tortas, Salgados) em
        <a href="categorias.php" style="text-decoration:underline; font-weight:700;">Seções</a>.
        Depois disso você já pode cadastrar os produtos de cada seção.
      </div>
    <?php endif; ?>

    <div class="cartoes-resumo">
      <div class="cartao-resumo">
        <div class="numero"><?= $totalCategorias ?></div>
        <div class="rotulo">Seções criadas</div>
      </div>
      <div class="cartao-resumo">
        <div class="numero"><?= $totalProdutos ?></div>
        <div class="rotulo">Produtos cadastrados</div>
      </div>
      <div class="cartao-resumo">
        <div class="numero"><?= $totalDisponiveis ?></div>
        <div class="rotulo">Disponíveis no cardápio</div>
      </div>
      <div class="cartao-resumo">
        <div class="numero"><?= $totalSabores ?></div>
        <div class="rotulo">Sabores cadastrados</div>
      </div>
      <div class="cartao-resumo">
        <div class="numero"><?= $totalCoberturas ?></div>
        <div class="rotulo">Coberturas cadastradas</div>
      </div>
      <div class="cartao-resumo">
        <div class="numero"><?= $totalRecheios ?></div>
        <div class="rotulo">Recheios cadastrados</div>
      </div>
    </div>

    <div class="painel-bloco">
      <h2>Últimos produtos adicionados</h2>

      <?php if (count($ultimosProdutos) === 0): ?>
        <p style="color:var(--marrom-suave);">
          Nenhum produto cadastrado ainda.
          <?php if ($totalCategorias > 0): ?>
            <a href="produto_form.php" style="color:var(--rosa-forte-escuro); font-weight:700;">Cadastre o primeiro</a>.
          <?php endif; ?>
        </p>
      <?php else: ?>
        <div class="tabela-scroll">
        <table class="tabela-admin">
          <thead>
            <tr>
              <th>Foto</th>
              <th>Nome</th>
              <th>Seção</th>
              <th>Preço</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($ultimosProdutos as $produto): ?>
              <tr>
                <td><img class="miniatura" src="<?= h(urlImagemProduto($produto['imagem'])) ?>" alt=""></td>
                <td><?= h($produto['nome']) ?></td>
                <td><span class="tag lilas"><?= h($produto['categoria_emoji']) ?> <?= h($produto['categoria_nome']) ?></span></td>
                <td><?= h(formatarPreco((float) $produto['preco'])) ?></td>
                <td>
                  <?php if ($produto['disponivel']): ?>
                    <span class="badge badge-disponivel">Disponível</span>
                  <?php else: ?>
                    <span class="badge badge-indisponivel">Oculto</span>
                  <?php endif; ?>
                </td>
                <td class="acoes">
                  <a href="produto_form.php?id=<?= (int) $produto['id'] ?>" class="icone-btn" title="Editar">✏️</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>
</body>
</html>
