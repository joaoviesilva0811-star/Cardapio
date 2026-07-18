<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
exigirLogin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $produto = buscarProdutoPorId($id);
    if ($produto) {
        $pdo = getConexao();
        // produto_atributos é apagado automaticamente por causa do ON DELETE CASCADE
        $pdo->prepare('DELETE FROM produtos WHERE id = :id')->execute(['id' => $id]);
        apagarImagem($produto['imagem']);
    }
}

header('Location: produtos.php?msg=excluido');
exit;
