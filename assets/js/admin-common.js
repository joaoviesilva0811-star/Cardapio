// ============================================================
// Comum a todas as páginas do painel: exige login (Firebase Auth)
// e monta a barra lateral (equivalente ao admin_sidebar.php)
// ============================================================

async function montarSidebar(paginaAtual) {
  await DM.exigirLogin('../login.html');
  const admin = (await DM.adminLogado()) || {};

  const el = document.getElementById('painel-lateral-placeholder');
  if (!el) return;

  const ativo = (chave) => (paginaAtual === chave ? 'ativo' : '');

  el.outerHTML = `
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
          <a href="index.html" class="${ativo('dashboard')}">📊 Visão geral</a>
          <a href="categorias.html" class="${ativo('categorias')}">🗂️ Seções</a>
          <a href="produtos.html" class="${ativo('produtos')}">🎂 Produtos</a>
          <a href="atributos.html?tipo=sabor" class="${ativo('sabor')}">🍰 Sabores</a>
          <a href="atributos.html?tipo=cobertura" class="${ativo('cobertura')}">🍯 Coberturas</a>
          <a href="atributos.html?tipo=recheio" class="${ativo('recheio')}">🍫 Recheios</a>
          <a href="../index.html" target="_blank">🔗 Ver site</a>
        </nav>
        <div class="painel-rodape-lateral">
          <div class="painel-usuario">
            Logado como<br>
            <strong>${DM.h(admin.nome || '')}</strong>
          </div>
          <a href="#" id="botao-sair" class="btn btn-fantasma btn-pequeno" style="width:100%;">Sair</a>
        </div>
      </div>
    </aside>`;

  const botaoMenu = document.getElementById('botao-menu-admin');
  const menu = document.getElementById('painel-menu-mobile');
  if (botaoMenu && menu) {
    botaoMenu.addEventListener('click', function () {
      const aberto = menu.classList.toggle('aberto');
      botaoMenu.classList.toggle('aberto', aberto);
      botaoMenu.setAttribute('aria-expanded', aberto ? 'true' : 'false');
      botaoMenu.setAttribute('aria-label', aberto ? 'Fechar menu' : 'Abrir menu');
    });
  }

  document.getElementById('botao-sair').addEventListener('click', async function (evento) {
    evento.preventDefault();
    await DM.fazerLogout();
    window.location.href = '../login.html';
  });
}
