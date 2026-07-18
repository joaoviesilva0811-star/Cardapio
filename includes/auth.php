<?php
/**
 * Autenticação simples de administrador via sessão PHP.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Tenta autenticar um admin pelo email e senha.
 * Retorna true/false. Em caso de sucesso, grava dados na sessão.
 */
function tentarLogin(string $email, string $senha): bool
{
    $pdo = getConexao();
    $stmt = $pdo->prepare('SELECT id, nome, email, senha_hash FROM admins WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($senha, $admin['senha_hash'])) {
        // Evita fixação de sessão
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_nome'] = $admin['nome'];
        $_SESSION['admin_email'] = $admin['email'];
        return true;
    }

    return false;
}

function estaLogado(): bool
{
    return isset($_SESSION['admin_id']);
}

/**
 * Bloqueia o acesso à página se não houver admin logado.
 * Chame no topo de toda página dentro de /admin.
 */
function exigirLogin(): void
{
    if (!estaLogado()) {
        header('Location: ../login.php');
        exit;
    }
}

function fazerLogout(): void
{
    $_SESSION = [];
    session_destroy();
}
