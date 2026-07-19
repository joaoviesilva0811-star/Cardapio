<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
exigirLogin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    if (contarProdutosNaCategoria($id) > 0) {
        header('Location: categorias.php?erro=tem_produtos');
        exit;
    }

    $pdo = getConexao();
    $pdo->prepare('DELETE FROM categorias WHERE id = :id')->execute(['id' => $id]);
}

header('Location: categorias.php');
exit;
