<?php
// Espera que $paginaAtual esteja definida na página que inclui este partial
$paginaAtual = $paginaAtual ?? '';
?>
<aside class="painel-lateral" id="painel-lateral">
  <div class="painel-lateral-topo">
    <div class="marca">
      <img src="../assets/img/logo.jpg" alt="" class="logo-sidebar">
      <span>
        Delícias da Maria
        <small>Painel admin</small>
      </span>
    </div>
    <button type="button" class="hamburguer-btn" id="botao-menu-admin" aria-label="Abrir menu" aria-expanded="false" aria-controls="painel-menu-mobile">
      <span></span><span></span><span></span>
    </button>
  </div>

  <div class="painel-menu-mobile" id="painel-menu-mobile">
    <nav class="painel-nav">
      <a href="index.php" class="<?= $paginaAtual === 'dashboard' ? 'ativo' : '' ?>">📊 Visão geral</a>
      <a href="categorias.php" class="<?= $paginaAtual === 'categorias' ? 'ativo' : '' ?>">🗂️ Seções</a>
      <a href="produtos.php" class="<?= $paginaAtual === 'produtos' ? 'ativo' : '' ?>">🎂 Produtos</a>
      <a href="atributos.php?tipo=sabor" class="<?= $paginaAtual === 'sabor' ? 'ativo' : '' ?>">🍰 Sabores</a>
      <a href="atributos.php?tipo=cobertura" class="<?= $paginaAtual === 'cobertura' ? 'ativo' : '' ?>">🍯 Coberturas</a>
      <a href="atributos.php?tipo=recheio" class="<?= $paginaAtual === 'recheio' ? 'ativo' : '' ?>">🍫 Recheios</a>
      <a href="../index.php" target="_blank">🔗 Ver site</a>
    </nav>
    <div class="painel-rodape-lateral">
      <div class="painel-usuario">
        Logado como<br>
        <strong><?= h($_SESSION['admin_nome'] ?? '') ?></strong>
      </div>
      <a href="../logout.php" class="btn btn-fantasma btn-pequeno" style="width:100%;">Sair</a>
    </div>
  </div>
</aside>

<script>
  (function () {
    var botao = document.getElementById('botao-menu-admin');
    var menu = document.getElementById('painel-menu-mobile');
    if (!botao || !menu) return;

    botao.addEventListener('click', function () {
      var aberto = menu.classList.toggle('aberto');
      botao.classList.toggle('aberto', aberto);
      botao.setAttribute('aria-expanded', aberto ? 'true' : 'false');
      botao.setAttribute('aria-label', aberto ? 'Fechar menu' : 'Abrir menu');
    });
  })();
</script>
