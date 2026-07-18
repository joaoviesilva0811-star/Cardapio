<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
exigirLogin();

$pdo = getConexao();
$erro = '';
$editando = null;

// Adicionar nova seção
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'adicionar') {
    $nome = trim($_POST['nome'] ?? '');
    $emoji = trim($_POST['emoji'] ?? '') ?: '🍰';

    if ($nome === '') {
        $erro = 'Dê um nome para a seção.';
    } else {
        try {
            $proximaOrdem = (int) $pdo->query('SELECT COALESCE(MAX(ordem), 0) + 1 AS o FROM categorias')->fetch()['o'];
            $stmt = $pdo->prepare('INSERT INTO categorias (nome, emoji, ordem) VALUES (:nome, :emoji, :ordem)');
            $stmt->execute(['nome' => $nome, 'emoji' => $emoji, 'ordem' => $proximaOrdem]);
            header('Location: categorias.php');
            exit;
        } catch (PDOException $e) {
            $erro = 'Já existe uma seção com esse nome.';
        }
    }
}

// Editar seção existente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'editar') {
    $id = (int) ($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $emoji = trim($_POST['emoji'] ?? '') ?: '🍰';

    if ($nome === '') {
        $erro = 'Dê um nome para a seção.';
        $editando = $id;
    } else {
        try {
            $stmt = $pdo->prepare('UPDATE categorias SET nome = :nome, emoji = :emoji WHERE id = :id');
            $stmt->execute(['nome' => $nome, 'emoji' => $emoji, 'id' => $id]);
            header('Location: categorias.php');
            exit;
        } catch (PDOException $e) {
            $erro = 'Já existe uma seção com esse nome.';
            $editando = $id;
        }
    }
}

// Reordenar (subir/descer)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'mover') {
    $id = (int) ($_POST['id'] ?? 0);
    $direcao = $_POST['direcao'] ?? '';

    $categorias = buscarCategorias();
    $posicao = null;
    foreach ($categorias as $i => $cat) {
        if ((int) $cat['id'] === $id) { $posicao = $i; break; }
    }

    if ($posicao !== null) {
        $alvo = $direcao === 'cima' ? $posicao - 1 : $posicao + 1;
        if ($alvo >= 0 && $alvo < count($categorias)) {
            $ordemAtual = $categorias[$posicao]['ordem'];
            $ordemAlvo = $categorias[$alvo]['ordem'];
            $pdo->prepare('UPDATE categorias SET ordem = :o WHERE id = :id')->execute(['o' => $ordemAlvo, 'id' => $categorias[$posicao]['id']]);
            $pdo->prepare('UPDATE categorias SET ordem = :o WHERE id = :id')->execute(['o' => $ordemAtual, 'id' => $categorias[$alvo]['id']]);
        }
    }
    header('Location: categorias.php');
    exit;
}

if (isset($_GET['editar'])) {
    $editando = (int) $_GET['editar'];
}

$mensagemErroExclusao = $_GET['erro'] ?? '';
$categorias = buscarCategorias();

// conta quantos produtos existem em cada categoria, para mostrar na lista
$contagens = [];
foreach ($categorias as $cat) {
    $contagens[$cat['id']] = contarProdutosNaCategoria((int) $cat['id']);
}

$paginaAtual = 'categorias';
$urlBase = '../';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <title>Seções do cardápio — Painel Delícias da Maria</title>
  <?php include __DIR__ . '/../includes/partials/head.php'; ?>
</head>
<body>
<div class="painel">
  <?php include __DIR__ . '/../includes/partials/admin_sidebar.php'; ?>

  <main class="painel-conteudo">
    <div class="painel-cabecalho">
      <div>
        <h1>🗂️ Seções do cardápio</h1>
        <p>Crie as seções que organizam o cardápio, como Bolos, Doces, Tortas ou Salgados.</p>
      </div>
    </div>

    <?php if ($erro): ?>
      <div class="alerta alerta-erro"><?= h($erro) ?></div>
    <?php endif; ?>

    <?php if ($mensagemErroExclusao === 'tem_produtos'): ?>
      <div class="alerta alerta-erro">Essa seção ainda tem produtos cadastrados. Mova ou exclua esses produtos antes de remover a seção.</div>
    <?php endif; ?>

    <div class="painel-bloco">
      <h2>Nova seção</h2>
      <form method="post" class="linha-form" style="align-items:flex-end;">
        <input type="hidden" name="acao" value="adicionar">
        <div class="campo" style="max-width:100px;">
          <label for="emoji-novo">Emoji</label>
          <input type="text" id="emoji-novo" name="emoji" value="🍰" maxlength="4" style="text-align:center; font-size:1.2rem;">
        </div>
        <div class="campo" style="flex:1;">
          <label for="nome-novo">Nome da seção</label>
          <input type="text" id="nome-novo" name="nome" required placeholder="Ex: Bolos, Doces, Tortas, Salgados...">
        </div>
        <button type="submit" class="btn btn-rosa" style="height:48px;">Adicionar</button>
      </form>
    </div>

    <div class="painel-bloco">
      <h2>Seções cadastradas</h2>

      <?php if (count($categorias) === 0): ?>
        <p style="color:var(--marrom-suave);">Nenhuma seção criada ainda. Crie a primeira acima — por exemplo "Bolos" — para depois poder cadastrar produtos nela.</p>
      <?php else: ?>
        <div class="lista-simples">
          <?php foreach ($categorias as $indice => $categoria): ?>
            <?php if ($editando === (int) $categoria['id']): ?>
              <form method="post" class="item-simples" style="gap:10px;">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="id" value="<?= (int) $categoria['id'] ?>">
                <input type="text" name="emoji" value="<?= h($categoria['emoji']) ?>" maxlength="4" style="width:60px; text-align:center; padding:8px; border-radius:8px; border:2px solid var(--rosa-pastel-2);">
                <input type="text" name="nome" value="<?= h($categoria['nome']) ?>" required style="flex:1; padding:8px 12px; border-radius:8px; border:2px solid var(--rosa-pastel-2);">
                <div class="acoes">
                  <button type="submit" class="icone-btn" title="Salvar">💾</button>
                  <a href="categorias.php" class="icone-btn" title="Cancelar">✕</a>
                </div>
              </form>
            <?php else: ?>
              <div class="item-simples">
                <span><?= h($categoria['emoji']) ?> <?= h($categoria['nome']) ?>
                  <span style="font-weight:400; color:var(--marrom-suave); font-size:0.8rem;">
                    (<?= $contagens[$categoria['id']] ?> <?= $contagens[$categoria['id']] === 1 ? 'produto' : 'produtos' ?>)
                  </span>
                </span>
                <div class="acoes">
                  <form method="post" style="display:inline;">
                    <input type="hidden" name="acao" value="mover">
                    <input type="hidden" name="id" value="<?= (int) $categoria['id'] ?>">
                    <input type="hidden" name="direcao" value="cima">
                    <button type="submit" class="icone-btn" title="Mover para cima" <?= $indice === 0 ? 'disabled' : '' ?>>⬆️</button>
                  </form>
                  <form method="post" style="display:inline;">
                    <input type="hidden" name="acao" value="mover">
                    <input type="hidden" name="id" value="<?= (int) $categoria['id'] ?>">
                    <input type="hidden" name="direcao" value="baixo">
                    <button type="submit" class="icone-btn" title="Mover para baixo" <?= $indice === count($categorias) - 1 ? 'disabled' : '' ?>>⬇️</button>
                  </form>
                  <a href="categorias.php?editar=<?= (int) $categoria['id'] ?>" class="icone-btn" title="Editar">✏️</a>
                  <a
                    href="categoria_delete.php?id=<?= (int) $categoria['id'] ?>"
                    class="icone-btn"
                    title="Excluir"
                    onclick="return confirm('Excluir a seção &quot;<?= h(addslashes($categoria['nome'])) ?>&quot;?');"
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
