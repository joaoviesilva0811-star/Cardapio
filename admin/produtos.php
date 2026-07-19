<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
exigirLogin();

$pdo = getConexao();

// Alternar disponibilidade (mostrar/ocultar do cardápio) direto na listagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['alternar_id'])) {
    $stmt = $pdo->prepare('UPDATE produtos SET disponivel = NOT disponivel WHERE id = :id');
    $stmt->execute(['id' => (int) $_POST['alternar_id']]);
    header('Location: produtos.php');
    exit;
}

$mensagem = $_GET['msg'] ?? '';
$categorias = buscarCategorias();
$produtos = buscarProdutos(false);

$paginaAtual = 'produtos';
$urlBase = '../';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <title>Produtos — Painel Delícias da Maria</title>
  <?php include __DIR__ . '/../includes/partials/head.php'; ?>
</head>
<body>
<div class="painel">
  <?php include __DIR__ . '/../includes/partials/admin_sidebar.php'; ?>

  <main class="painel-conteudo">
    <div class="painel-cabecalho">
      <div>
        <h1>Produtos do cardápio</h1>
        <p>Adicione, edite ou remova os itens que aparecem para os clientes.</p>
      </div>
      <?php if (count($categorias) > 0): ?>
        <a href="produto_form.php" class="btn btn-rosa">+ Novo produto</a>
      <?php endif; ?>
    </div>

    <?php if ($mensagem === 'excluido'): ?>
      <div class="alerta alerta-sucesso">Produto excluído com sucesso.</div>
    <?php elseif ($mensagem === 'salvo'): ?>
      <div class="alerta alerta-sucesso">Produto salvo com sucesso.</div>
    <?php endif; ?>

    <?php if (count($categorias) === 0): ?>
      <div class="alerta alerta-erro">
        Você ainda não criou nenhuma seção do cardápio. Crie a primeira em
        <a href="categorias.php" style="text-decoration:underline; font-weight:700;">Seções</a>
        (por exemplo "Bolos" ou "Doces") antes de cadastrar produtos.
      </div>
    <?php endif; ?>

    <div class="painel-bloco">
      <?php if (count($produtos) === 0): ?>
        <p style="color:var(--marrom-suave);">
          Nenhum produto cadastrado ainda.
          <?php if (count($categorias) > 0): ?>
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
            <?php foreach ($produtos as $produto): ?>
              <tr>
                <td><img class="miniatura" src="<?= h(urlImagemProduto($produto['imagem'])) ?>" alt=""></td>
                <td><?= h($produto['nome']) ?></td>
                <td><span class="tag lilas"><?= h($produto['categoria_emoji']) ?> <?= h($produto['categoria_nome']) ?></span></td>
                <td><?= h(formatarPreco((float) $produto['preco'])) ?></td>
                <td>
                  <form method="post" style="display:inline;">
                    <input type="hidden" name="alternar_id" value="<?= (int) $produto['id'] ?>">
                    <button type="submit" class="badge <?= $produto['disponivel'] ? 'badge-disponivel' : 'badge-indisponivel' ?>" style="border:none; cursor:pointer;">
                      <?= $produto['disponivel'] ? 'Disponível' : 'Oculto' ?>
                    </button>
                  </form>
                </td>
                <td class="acoes">
                  <a href="produto_form.php?id=<?= (int) $produto['id'] ?>" class="icone-btn" title="Editar">✏️</a>
                  <a
                    href="produto_delete.php?id=<?= (int) $produto['id'] ?>"
                    class="icone-btn"
                    title="Excluir"
                    onclick="return confirm('Tem certeza que deseja excluir &quot;<?= h(addslashes($produto['nome'])) ?>&quot;? Essa ação não pode ser desfeita.');"
                  >🗑️</a>
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
