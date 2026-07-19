<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
exigirLogin();

$pdo = getConexao();

$categorias = buscarCategorias();
if (count($categorias) === 0) {
    header('Location: categorias.php');
    exit;
}

$produtoId = isset($_GET['id']) ? (int) $_GET['id'] : null;
$editando = $produtoId !== null;
$produto = $editando ? buscarProdutoPorId($produtoId) : null;

if ($editando && !$produto) {
    header('Location: produtos.php');
    exit;
}

$erro = '';

// Valores padrão do formulário (usados também para repopular em caso de erro)
$valores = [
    'nome' => $produto['nome'] ?? '',
    'descricao' => $produto['descricao'] ?? '',
    'preco' => $produto['preco'] ?? '',
    'categoria_id' => $produto['categoria_id'] ?? $categorias[0]['id'],
    'disponivel' => $produto['disponivel'] ?? 1,
];
$saboresSelecionados = $editando ? array_column($produto['atributos']['sabor'], 'id') : [];
$coberturasSelecionadas = $editando ? array_column($produto['atributos']['cobertura'], 'id') : [];
$recheiosSelecionados = $editando ? array_column($produto['atributos']['recheio'], 'id') : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valores['nome'] = trim($_POST['nome'] ?? '');
    $valores['descricao'] = trim($_POST['descricao'] ?? '');
    $valores['preco'] = str_replace(',', '.', trim($_POST['preco'] ?? '0'));
    $valores['categoria_id'] = (int) ($_POST['categoria_id'] ?? 0);
    $valores['disponivel'] = isset($_POST['disponivel']) ? 1 : 0;

    $saboresSelecionados = array_map('intval', $_POST['sabores'] ?? []);
    $coberturasSelecionadas = array_map('intval', $_POST['coberturas'] ?? []);
    $recheiosSelecionados = array_map('intval', $_POST['recheios'] ?? []);

    $idsCategoriasValidas = array_column($categorias, 'id');

    if ($valores['nome'] === '') {
        $erro = 'Dê um nome para o produto.';
    } elseif (!is_numeric($valores['preco']) || (float) $valores['preco'] < 0) {
        $erro = 'Informe um preço válido.';
    } elseif (!in_array($valores['categoria_id'], $idsCategoriasValidas, true)) {
        $erro = 'Escolha uma seção válida para o produto.';
    }

    if ($erro === '') {
        try {
            $nomeImagem = $produto['imagem'] ?? null;

            // Só processa upload se um novo arquivo foi enviado
            if (!empty($_FILES['imagem']['name'])) {
                $novaImagem = fazerUploadImagem($_FILES['imagem']);
                if ($novaImagem) {
                    if ($editando) {
                        apagarImagem($produto['imagem']); // remove a foto antiga
                    }
                    $nomeImagem = $novaImagem;
                }
            }

            // Remover foto atual, se solicitado
            if (!empty($_POST['remover_imagem']) && empty($_FILES['imagem']['name'])) {
                apagarImagem($nomeImagem);
                $nomeImagem = null;
            }

            if ($editando) {
                $stmt = $pdo->prepare(
                    'UPDATE produtos SET categoria_id = :categoria_id, nome = :nome, descricao = :descricao, preco = :preco, imagem = :imagem, disponivel = :disponivel WHERE id = :id'
                );
                $stmt->execute([
                    'categoria_id' => $valores['categoria_id'],
                    'nome' => $valores['nome'],
                    'descricao' => $valores['descricao'],
                    'preco' => $valores['preco'],
                    'imagem' => $nomeImagem,
                    'disponivel' => $valores['disponivel'],
                    'id' => $produtoId,
                ]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO produtos (categoria_id, nome, descricao, preco, imagem, disponivel) VALUES (:categoria_id, :nome, :descricao, :preco, :imagem, :disponivel)'
                );
                $stmt->execute([
                    'categoria_id' => $valores['categoria_id'],
                    'nome' => $valores['nome'],
                    'descricao' => $valores['descricao'],
                    'preco' => $valores['preco'],
                    'imagem' => $nomeImagem,
                    'disponivel' => $valores['disponivel'],
                ]);
                $produtoId = (int) $pdo->lastInsertId();
            }

            // Sincroniza os atributos vinculados (apaga e reinsere é o jeito mais simples e seguro)
            $pdo->prepare('DELETE FROM produto_atributos WHERE produto_id = :id')->execute(['id' => $produtoId]);

            $todosAtributos = array_merge($saboresSelecionados, $coberturasSelecionadas, $recheiosSelecionados);
            if (count($todosAtributos) > 0) {
                $stmtVinculo = $pdo->prepare('INSERT INTO produto_atributos (produto_id, atributo_id) VALUES (:produto_id, :atributo_id)');
                foreach (array_unique($todosAtributos) as $atributoId) {
                    $stmtVinculo->execute(['produto_id' => $produtoId, 'atributo_id' => $atributoId]);
                }
            }

            header('Location: produtos.php?msg=salvo');
            exit;
        } catch (Exception $e) {
            $erro = $e->getMessage();
        }
    }
}

$listaSabores = buscarAtributos('sabor');
$listaCoberturas = buscarAtributos('cobertura');
$listaRecheios = buscarAtributos('recheio');

$paginaAtual = 'produtos';
$urlBase = '../';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <title><?= $editando ? 'Editar produto' : 'Novo produto' ?> — Painel Delícias da Maria</title>
  <?php include __DIR__ . '/../includes/partials/head.php'; ?>
</head>
<body>
<div class="painel">
  <?php include __DIR__ . '/../includes/partials/admin_sidebar.php'; ?>

  <main class="painel-conteudo">
    <div class="painel-cabecalho">
      <div>
        <h1><?= $editando ? 'Editar produto' : 'Novo produto' ?></h1>
        <p>Preencha as informações que os clientes verão no cardápio.</p>
      </div>
      <a href="produtos.php" class="btn btn-fantasma">← Voltar</a>
    </div>

    <?php if ($erro): ?>
      <div class="alerta alerta-erro"><?= h($erro) ?></div>
    <?php endif; ?>

    <?php if (count($listaSabores) === 0 && count($listaCoberturas) === 0 && count($listaRecheios) === 0): ?>
      <div class="alerta alerta-erro">
        Você ainda não cadastrou sabores, coberturas ou recheios (isso é opcional — só cadastre se fizer sentido para esse tipo de produto).
        Cadastre em <a href="atributos.php?tipo=sabor" style="text-decoration:underline;">Sabores</a>,
        <a href="atributos.php?tipo=cobertura" style="text-decoration:underline;">Coberturas</a> ou
        <a href="atributos.php?tipo=recheio" style="text-decoration:underline;">Recheios</a> para poder vinculá-los aos produtos.
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="painel-bloco">
      <div class="linha-form">
        <div class="campo" style="flex:2;">
          <label for="nome">Nome do produto</label>
          <input type="text" id="nome" name="nome" required value="<?= h($valores['nome']) ?>" placeholder="Ex: Bolo de Chocolate com Morango">
        </div>
        <div class="campo">
          <label for="preco">Preço (R$)</label>
          <input type="text" id="preco" name="preco" required value="<?= h((string) $valores['preco']) ?>" placeholder="Ex: 75.00">
        </div>
      </div>

      <div class="campo">
        <label for="categoria_id">Seção do cardápio</label>
        <select id="categoria_id" name="categoria_id" required>
          <?php foreach ($categorias as $categoria): ?>
            <option value="<?= (int) $categoria['id'] ?>" <?= (int) $valores['categoria_id'] === (int) $categoria['id'] ? 'selected' : '' ?>>
              <?= h($categoria['emoji']) ?> <?= h($categoria['nome']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <p class="campo-ajuda">Não achou a seção certa? <a href="categorias.php" style="color:var(--rosa-forte-escuro); font-weight:700;">Crie uma nova seção</a>.</p>
      </div>

      <div class="campo">
        <label for="descricao">Descrição</label>
        <textarea id="descricao" name="descricao" rows="3" placeholder="Conte um pouco sobre esse produto..."><?= h($valores['descricao']) ?></textarea>
      </div>

      <div class="campo">
        <label>Foto do produto</label>
        <div class="upload-preview" id="preview-container">
          <?php if (!empty($produto['imagem'])): ?>
            <img src="<?= h(urlImagemProduto($produto['imagem'])) ?>" alt="" id="preview-img">
          <?php else: ?>
            <span id="preview-texto">Nenhuma foto ainda</span>
            <img src="" alt="" id="preview-img" style="display:none;">
          <?php endif; ?>
        </div>
        <input type="file" id="imagem" name="imagem" accept="image/jpeg,image/png,image/webp">
        <p class="campo-ajuda">JPG, PNG ou WEBP, até 5MB.</p>

        <?php if (!empty($produto['imagem'])): ?>
          <label style="display:flex; align-items:center; gap:8px; margin-top:10px; font-weight:600; font-size:0.85rem;">
            <input type="checkbox" name="remover_imagem" value="1" style="width:auto;">
            Remover foto atual
          </label>
        <?php endif; ?>
      </div>

      <?php if (count($listaSabores) > 0): ?>
        <div class="campo">
          <label>Sabores disponíveis para este produto (opcional)</label>
          <div class="checkbox-grade">
            <?php foreach ($listaSabores as $item): ?>
              <div class="checkbox-item">
                <input type="checkbox" id="sabor-<?= $item['id'] ?>" name="sabores[]" value="<?= $item['id'] ?>" <?= in_array($item['id'], $saboresSelecionados, true) ? 'checked' : '' ?>>
                <label for="sabor-<?= $item['id'] ?>"><?= h($item['nome']) ?></label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (count($listaCoberturas) > 0): ?>
        <div class="campo">
          <label>Coberturas disponíveis para este produto (opcional)</label>
          <div class="checkbox-grade">
            <?php foreach ($listaCoberturas as $item): ?>
              <div class="checkbox-item">
                <input type="checkbox" id="cobertura-<?= $item['id'] ?>" name="coberturas[]" value="<?= $item['id'] ?>" <?= in_array($item['id'], $coberturasSelecionadas, true) ? 'checked' : '' ?>>
                <label for="cobertura-<?= $item['id'] ?>"><?= h($item['nome']) ?></label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (count($listaRecheios) > 0): ?>
        <div class="campo">
          <label>Recheios disponíveis para este produto (opcional)</label>
          <div class="checkbox-grade">
            <?php foreach ($listaRecheios as $item): ?>
              <div class="checkbox-item">
                <input type="checkbox" id="recheio-<?= $item['id'] ?>" name="recheios[]" value="<?= $item['id'] ?>" <?= in_array($item['id'], $recheiosSelecionados, true) ? 'checked' : '' ?>>
                <label for="recheio-<?= $item['id'] ?>"><?= h($item['nome']) ?></label>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <div class="campo">
        <label style="display:flex; align-items:center; gap:8px;">
          <input type="checkbox" name="disponivel" value="1" style="width:auto;" <?= $valores['disponivel'] ? 'checked' : '' ?>>
          Mostrar este produto no cardápio público
        </label>
      </div>

      <div class="form-acoes">
        <button type="submit" class="btn btn-rosa"><?= $editando ? 'Salvar alterações' : 'Cadastrar produto' ?></button>
        <a href="produtos.php" class="btn btn-fantasma">Cancelar</a>
      </div>
    </form>
  </main>
</div>

<script>
  // Pré-visualização da imagem antes de enviar o formulário
  document.getElementById('imagem').addEventListener('change', function (evento) {
    const arquivo = evento.target.files[0];
    if (!arquivo) return;

    const imgPreview = document.getElementById('preview-img');
    const textoPreview = document.getElementById('preview-texto');

    const leitor = new FileReader();
    leitor.onload = function (e) {
      imgPreview.src = e.target.result;
      imgPreview.style.display = 'block';
      if (textoPreview) textoPreview.style.display = 'none';
    };
    leitor.readAsDataURL(arquivo);
  });
</script>
</body>
</html>
