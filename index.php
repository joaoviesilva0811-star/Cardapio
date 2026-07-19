<?php
require_once __DIR__ . '/includes/functions.php';

$urlBase = '';
$categorias = buscarCategorias();
$produtos = buscarProdutos(true); // só os disponíveis aparecem para o cliente
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <title>Delícias da Maria — Cardápio</title>
  <?php include __DIR__ . '/includes/partials/head.php'; ?>
</head>
<body>

  <header class="topo">
    <div class="container topo-conteudo">
      <img src="assets/img/logo.jpg" alt="Delícias da Maria" class="logo-topo">
      <span class="topo-selo">Feito à mão, com carinho</span>
      <h1>Bolos, doces <em>e muito mais</em></h1>
      <p>Escolha o seu, monte o sabor do jeitinho que gosta e peça direto pelo WhatsApp.</p>
      <div class="topo-acoes">
        <a href="#cardapio" class="btn btn-rosa">Ver cardápio</a>
        <a href="https://wa.me/<?= h(WHATSAPP_NUMERO) ?>" class="btn btn-fantasma" target="_blank" rel="noopener">Falar no WhatsApp</a>
      </div>
    </div>
    <?php include __DIR__ . '/includes/partials/divisor.php'; ?>
  </header>

  <main>
    <section class="secao" id="cardapio">
      <div class="container">
        <div class="secao-titulo">
          <span class="eyebrow">Cardápio</span>
          <h2>Nosso cardápio</h2>
          <p>Todos os itens podem ser personalizados com o sabor, cobertura e recheio disponíveis.</p>
        </div>

        <?php if (count($produtos) > 0): ?>
          <div class="filtro-barra">
            <button type="button" class="filtro-chip ativo" data-categoria="todas">Todos</button>
            <?php foreach ($categorias as $categoria): ?>
              <?php
                // só mostra o filtro de seções que realmente têm produto disponível
                $temProdutoNaCategoria = false;
                foreach ($produtos as $p) {
                    if ((int) $p['categoria_id'] === (int) $categoria['id']) { $temProdutoNaCategoria = true; break; }
                }
              ?>
              <?php if ($temProdutoNaCategoria): ?>
                <button type="button" class="filtro-chip" data-categoria="<?= (int) $categoria['id'] ?>">
                  <?= h($categoria['emoji']) ?> <?= h($categoria['nome']) ?>
                </button>
              <?php endif; ?>
            <?php endforeach; ?>
          </div>

          <div style="margin-bottom:30px; display:flex; justify-content:center;">
            <input
              type="search"
              id="busca-produto"
              placeholder="Buscar pelo nome..."
              aria-label="Buscar item pelo nome"
              style="max-width:320px; width:100%; padding:11px 18px; border-radius:999px; border:2px solid var(--rosa-pastel-2); font-family:var(--fonte-corpo); font-size:0.9rem;"
            >
          </div>
        <?php endif; ?>

        <div class="grade-bolos" id="grade-produtos">
          <?php foreach ($produtos as $produto): ?>
            <?php
              $sabores = array_column($produto['atributos']['sabor'], 'nome');
              $coberturas = array_column($produto['atributos']['cobertura'], 'nome');
              $recheios = array_column($produto['atributos']['recheio'], 'nome');
            ?>
            <article
              class="cartao-bolo"
              data-nome="<?= h(mb_strtolower($produto['nome'])) ?>"
              data-categoria="<?= (int) $produto['categoria_id'] ?>"
            >
              <div class="cartao-bolo-foto">
                <img src="<?= h(urlImagemProduto($produto['imagem'])) ?>" alt="Foto de <?= h($produto['nome']) ?>" loading="lazy">
              </div>
              <div class="cartao-bolo-corpo">
                <span class="tag" style="align-self:flex-start; background:var(--lilas-pastel); color:#6A5794;">
                  <?= h($produto['categoria_emoji']) ?> <?= h($produto['categoria_nome']) ?>
                </span>
                <h3><?= h($produto['nome']) ?></h3>
                <?php if (!empty($produto['descricao'])): ?>
                  <p class="cartao-bolo-desc"><?= h($produto['descricao']) ?></p>
                <?php endif; ?>

                <div class="tag-lista">
                  <?php foreach (array_slice($sabores, 0, 3) as $s): ?><span class="tag rosa"><?= h($s) ?></span><?php endforeach; ?>
                  <?php foreach (array_slice($coberturas, 0, 2) as $c): ?><span class="tag"><?= h($c) ?></span><?php endforeach; ?>
                  <?php foreach (array_slice($recheios, 0, 2) as $r): ?><span class="tag lilas"><?= h($r) ?></span><?php endforeach; ?>
                </div>

                <div class="cartao-bolo-rodape">
                  <span class="preco"><?= h(formatarPreco((float) $produto['preco'])) ?></span>
                  <button
                    type="button"
                    class="btn btn-rosa btn-pequeno botao-pedir"
                    data-produto="<?= h($produto['nome']) ?>"
                    data-preco="<?= h(formatarPreco((float) $produto['preco'])) ?>"
                    data-sabores="<?= h(implode('|', $sabores)) ?>"
                    data-coberturas="<?= h(implode('|', $coberturas)) ?>"
                    data-recheios="<?= h(implode('|', $recheios)) ?>"
                  >
                    Pedir agora
                  </button>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <?php if (count($produtos) === 0): ?>
          <div class="estado-vazio">
            <p>O cardápio ainda está sendo preparado. Volte em breve! 🍰</p>
          </div>
        <?php endif; ?>

        <p id="sem-resultado" class="estado-vazio" style="display:none;">Nenhum item encontrado.</p>
      </div>
    </section>
  </main>

  <!-- Modal de escolha do pedido -->
  <div class="modal-fundo" id="modal-pedido">
    <div class="modal-caixa">
      <button type="button" class="modal-fechar" id="fechar-modal" aria-label="Fechar">✕</button>
      <h3 id="modal-nome-produto">Item</h3>
      <span class="preco" id="modal-preco"></span>

      <form id="form-pedido">
        <div class="grupo-escolha" id="grupo-sabor" hidden>
          <label class="rotulo">Escolha o sabor</label>
          <div class="pill-opcoes" id="opcoes-sabor"></div>
        </div>
        <div class="grupo-escolha" id="grupo-cobertura" hidden>
          <label class="rotulo">Escolha a cobertura</label>
          <div class="pill-opcoes" id="opcoes-cobertura"></div>
        </div>
        <div class="grupo-escolha" id="grupo-recheio" hidden>
          <label class="rotulo">Escolha o recheio</label>
          <div class="pill-opcoes" id="opcoes-recheio"></div>
        </div>

        <button type="submit" class="btn btn-whatsapp" style="width:100%; margin-top:8px;">
          Enviar pedido pelo WhatsApp
        </button>
      </form>
    </div>
  </div>

  <footer class="rodape">
    <div class="container">
      <p>Delícias da Maria — feito com amor 💗</p>
      <p><a href="https://wa.me/<?= h(WHATSAPP_NUMERO) ?>" target="_blank" rel="noopener">Chamar no WhatsApp</a></p>
      <a href="login.php" class="rodape-admin-link">Área administrativa</a>
    </div>
  </footer>

  <script>
    const WHATSAPP_NUMERO = '<?= h(WHATSAPP_NUMERO) ?>';
  </script>
  <script src="assets/js/main.js"></script>
</body>
</html>
