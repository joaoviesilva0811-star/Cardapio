<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (estaLogado()) {
    header('Location: admin/index.php');
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if ($email === '' || $senha === '') {
        $erro = 'Preencha o email e a senha.';
    } elseif (tentarLogin($email, $senha)) {
        header('Location: admin/index.php');
        exit;
    } else {
        $erro = 'Email ou senha incorretos.';
    }
}

$urlBase = '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <title>Área administrativa — Delícias da Maria</title>
  <?php include __DIR__ . '/includes/partials/head.php'; ?>
</head>
<body>
  <div class="pagina-login">
    <div class="login-caixa">
      <img src="assets/img/logo.jpg" alt="Delícias da Maria" class="logo-login">
      <div class="selo-topo">
        <span class="topo-selo" style="background:var(--rosa-pastel);">Painel administrativo</span>
      </div>
      <h1>Delícias da Maria</h1>
      <p class="subtitulo">Entre para gerenciar o cardápio</p>

      <?php if ($erro): ?>
        <div class="alerta alerta-erro"><?= h($erro) ?></div>
      <?php endif; ?>

      <form method="post" novalidate>
        <div class="campo">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" required autocomplete="username" value="<?= h($email ?? '') ?>">
        </div>
        <div class="campo">
          <label for="senha">Senha</label>
          <input type="password" id="senha" name="senha" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-rosa" style="width:100%;">Entrar</button>
      </form>
    </div>
  </div>
</body>
</html>
