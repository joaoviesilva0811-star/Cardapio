<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
exigirLogin();

$tiposValidos = [
    'sabor' => ['titulo' => 'Sabores', 'singular' => 'sabor', 'emoji' => '🍰'],
    'cobertura' => ['titulo' => 'Coberturas', 'singular' => 'cobertura', 'emoji' => '🍯'],
    'recheio' => ['titulo' => 'Recheios', 'singular' => 'recheio', 'emoji' => '🍫'],
];

$tipo = $_GET['tipo'] ?? 'sabor';
if (!array_key_exists($tipo, $tiposValidos)) {
    $tipo = 'sabor';
}
$infoTipo = $tiposValidos[$tipo];

$pdo = getConexao();
$erro = '';
$editando = null;

// Adicionar novo item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'adicionar') {
    $nome = trim($_POST['nome'] ?? '');
    if ($nome === '') {
        $erro = 'Digite um nome.';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO atributos (tipo, nome) VALUES (:tipo, :nome)');
            $stmt->execute(['tipo' => $tipo, 'nome' => $nome]);
            header('Location: atributos.php?tipo=' . $tipo);
            exit;
        } catch (PDOException $e) {
            $erro = 'Esse item já existe na lista de ' . mb_strtolower($infoTipo['titulo']) . '.';
        }
    }
}

// Editar item existente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'editar') {
    $id = (int) ($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    if ($nome === '') {
        $erro = 'Digite um nome.';
        $editando = $id;
    } else {
        try {
            $stmt = $pdo->prepare('UPDATE atributos SET nome = :nome WHERE id = :id AND tipo = :tipo');
            $stmt->execute(['nome' => $nome, 'id' => $id, 'tipo' => $tipo]);
            header('Location: atributos.php?tipo=' . $tipo);
            exit;
        } catch (PDOException $e) {
            $erro = 'Já existe um item com esse nome.';
            $editando = $id;
        }
    }
}

if (isset($_GET['editar'])) {
    $editando = (int) $_GET['editar'];
}

$stmt = $pdo->prepare('SELECT id, nome FROM atributos WHERE tipo = :tipo ORDER BY nome ASC');
$stmt->execute(['tipo' => $tipo]);
$itens = $stmt->fetchAll();

$paginaAtual = $tipo;
$urlBase = '../';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <title><?= h($infoTipo['titulo']) ?> — Painel Delícias da Maria</title>
  <?php include __DIR__ . '/../includes/partials/head.php'; ?>
</head>
<body>
<div class="painel">
  <?php include __DIR__ . '/../includes/partials/admin_sidebar.php'; ?>

  <main class="painel-conteudo">
    <div class="painel-cabecalho">
      <div>
        <h1><?= $infoTipo['emoji'] ?> <?= h($infoTipo['titulo']) ?></h1>
        <p>Esses itens ficam disponíveis para você escolher ao cadastrar um bolo.</p>
      </div>
    </div>

    <?php if ($erro): ?>
      <div class="alerta alerta-erro"><?= h($erro) ?></div>
    <?php endif; ?>

    <div class="painel-bloco">
      <h2>Adicionar novo <?= h($infoTipo['singular']) ?></h2>
      <form method="post" class="linha-form" style="align-items:flex-end;">
        <input type="hidden" name="acao" value="adicionar">
        <div class="campo" style="flex:1;">
          <label for="nome-novo">Nome</label>
          <input type="text" id="nome-novo" name="nome" required placeholder="Ex: Chocolate Belga">
        </div>
        <button type="submit" class="btn btn-rosa" style="height:48px;">Adicionar</button>
      </form>
    </div>

    <div class="painel-bloco">
      <h2>Lista de <?= h(mb_strtolower($infoTipo['titulo'])) ?></h2>

      <?php if (count($itens) === 0): ?>
        <p style="color:var(--marrom-suave);">Nenhum item cadastrado ainda.</p>
      <?php else: ?>
        <div class="lista-simples">
          <?php foreach ($itens as $item): ?>
            <?php if ($editando === (int) $item['id']): ?>
              <form method="post" class="item-simples" style="gap:10px;">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                <input type="text" name="nome" value="<?= h($item['nome']) ?>" required style="flex:1; padding:8px 12px; border-radius:8px; border:2px solid var(--rosa-pastel-2);">
                <div class="acoes">
                  <button type="submit" class="icone-btn" title="Salvar">💾</button>
                  <a href="atributos.php?tipo=<?= $tipo ?>" class="icone-btn" title="Cancelar">✕</a>
                </div>
              </form>
            <?php else: ?>
              <div class="item-simples">
                <span><?= h($item['nome']) ?></span>
                <div class="acoes">
                  <a href="atributos.php?tipo=<?= $tipo ?>&editar=<?= (int) $item['id'] ?>" class="icone-btn" title="Editar">✏️</a>
                  <a
                    href="atributo_delete.php?id=<?= (int) $item['id'] ?>&tipo=<?= $tipo ?>"
                    class="icone-btn"
                    title="Excluir"
                    onclick="return confirm('Excluir &quot;<?= h(addslashes($item['nome'])) ?>&quot;? Ele também será removido de qualquer bolo que o utilize.');"
                  >🗑️</a>
                </div>
              </div>
            <?php endif; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>
</body>
</html>
