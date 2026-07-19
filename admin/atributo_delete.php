<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
exigirLogin();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$tipo = $_GET['tipo'] ?? 'sabor';

if ($id > 0) {
    $pdo = getConexao();
    // A relação em produto_atributos é removida automaticamente (ON DELETE CASCADE)
    $pdo->prepare('DELETE FROM atributos WHERE id = :id')->execute(['id' => $id]);
}

header('Location: atributos.php?tipo=' . urlencode($tipo));
exit;
