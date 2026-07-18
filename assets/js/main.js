// ============================================================
// Delícias da Maria — site público
// Renderiza o cardápio a partir do DM (store.js), depois liga
// filtro por seção + busca por nome + modal de pedido.
// Espelha exatamente o que o index.php fazia no lado do servidor.
// ============================================================

document.addEventListener('DOMContentLoaded', async () => {
  await renderizarCardapio();
  configurarFiltros();
  configurarModalPedido();
});

async function renderizarCardapio() {
  const [categorias, produtos] = await Promise.all([
    DM.buscarCategorias(),
    DM.buscarProdutos(true), // só os disponíveis aparecem para o cliente
  ]);

  const filtroBarraContainer = document.getElementById('filtro-barra-container');
  const grade = document.getElementById('grade-produtos');
  const estadoVazioGeral = document.getElementById('estado-vazio-geral');

  // --- barra de filtros + busca (só aparece se houver produtos) ---
  if (produtos.length > 0) {
    const categoriasComProduto = categorias.filter((cat) =>
      produtos.some((p) => p.categoria_id === cat.id)
    );

    let chipsHtml = `<button type="button" class="filtro-chip ativo" data-categoria="todas">Todos</button>`;
    categoriasComProduto.forEach((cat) => {
      chipsHtml += `<button type="button" class="filtro-chip" data-categoria="${cat.id}">${DM.h(cat.emoji)} ${DM.h(cat.nome)}</button>`;
    });

    filtroBarraContainer.innerHTML = `
      <div class="filtro-barra">${chipsHtml}</div>
      <div style="margin-bottom:30px; display:flex; justify-content:center;">
        <input
          type="search"
          id="busca-produto"
          placeholder="Buscar pelo nome..."
          aria-label="Buscar item pelo nome"
          style="max-width:320px; width:100%; padding:11px 18px; border-radius:999px; border:2px solid var(--rosa-pastel-2); font-family:var(--fonte-corpo); font-size:0.9rem;"
        >
      </div>`;
  } else {
    filtroBarraContainer.innerHTML = '';
  }

  // --- cartões de produto ---
  const cartoesHtml = await Promise.all(produtos.map(async (produto) => {
    const [sabores, coberturas, recheios] = await Promise.all([
      DM.nomesDosAtributos('sabor', produto.atributos.sabor),
      DM.nomesDosAtributos('cobertura', produto.atributos.cobertura),
      DM.nomesDosAtributos('recheio', produto.atributos.recheio),
    ]);

    const tagsHtml =
      sabores.slice(0, 3).map((s) => `<span class="tag rosa">${DM.h(s)}</span>`).join('') +
      coberturas.slice(0, 2).map((c) => `<span class="tag">${DM.h(c)}</span>`).join('') +
      recheios.slice(0, 2).map((r) => `<span class="tag lilas">${DM.h(r)}</span>`).join('');

    const descricaoHtml = produto.descricao
      ? `<p class="cartao-bolo-desc">${DM.h(produto.descricao)}</p>`
      : '';

    const precoFormatado = DM.formatarPreco(produto.preco);

    return `
      <article class="cartao-bolo" data-nome="${DM.h(produto.nome.toLowerCase())}" data-categoria="${produto.categoria_id}">
        <div class="cartao-bolo-foto">
          <img src="${DM.h(DM.urlImagemProduto(produto.imagem))}" alt="Foto de ${DM.h(produto.nome)}" loading="lazy">
        </div>
        <div class="cartao-bolo-corpo">
          <span class="tag" style="align-self:flex-start; background:var(--lilas-pastel); color:#6A5794;">
            ${DM.h(produto.categoria_emoji)} ${DM.h(produto.categoria_nome)}
          </span>
          <h3>${DM.h(produto.nome)}</h3>
          ${descricaoHtml}
          <div class="tag-lista">${tagsHtml}</div>
          <div class="cartao-bolo-rodape">
            <span class="preco">${DM.h(precoFormatado)}</span>
            <button
              type="button"
              class="btn btn-rosa btn-pequeno botao-pedir"
              data-produto="${DM.h(produto.nome)}"
              data-preco="${DM.h(precoFormatado)}"
              data-sabores="${DM.h(sabores.join('|'))}"
              data-coberturas="${DM.h(coberturas.join('|'))}"
              data-recheios="${DM.h(recheios.join('|'))}"
            >
              Pedir agora
            </button>
          </div>
        </div>
      </article>`;
  }));

  grade.innerHTML = cartoesHtml.join('');

  estadoVazioGeral.style.display = produtos.length === 0 ? 'block' : 'none';
}

function configurarFiltros() {
  const chips = Array.from(document.querySelectorAll('.filtro-chip'));
  const input = document.getElementById('busca-produto');
  const cartoes = Array.from(document.querySelectorAll('#grade-produtos .cartao-bolo'));
  const semResultado = document.getElementById('sem-resultado');

  if (cartoes.length === 0) return;

  let categoriaAtiva = 'todas';

  function aplicarFiltros() {
    const termo = (input ? input.value.trim().toLowerCase() : '');
    let algumVisivel = false;

    cartoes.forEach((cartao) => {
      const nome = cartao.dataset.nome || '';
      const categoria = cartao.dataset.categoria || '';

      const combinaCategoria = categoriaAtiva === 'todas' || categoria === categoriaAtiva;
      const combinaTexto = nome.includes(termo);
      const visivel = combinaCategoria && combinaTexto;

      cartao.style.display = visivel ? '' : 'none';
      if (visivel) algumVisivel = true;
    });

    if (semResultado) {
      semResultado.style.display = algumVisivel ? 'none' : 'block';
    }
  }

  chips.forEach((chip) => {
    chip.addEventListener('click', () => {
      chips.forEach((c) => c.classList.remove('ativo'));
      chip.classList.add('ativo');
      categoriaAtiva = chip.dataset.categoria;
      aplicarFiltros();
    });
  });

  if (input) {
    input.addEventListener('input', aplicarFiltros);
  }
}

function configurarModalPedido() {
  const modal = document.getElementById('modal-pedido');
  if (!modal) return;

  const botaoFechar = document.getElementById('fechar-modal');
  const form = document.getElementById('form-pedido');
  const nomeEl = document.getElementById('modal-nome-produto');
  const precoEl = document.getElementById('modal-preco');

  const grupos = {
    sabor: { wrap: document.getElementById('grupo-sabor'), opcoes: document.getElementById('opcoes-sabor') },
    cobertura: { wrap: document.getElementById('grupo-cobertura'), opcoes: document.getElementById('opcoes-cobertura') },
    recheio: { wrap: document.getElementById('grupo-recheio'), opcoes: document.getElementById('opcoes-recheio') },
  };

  let produtoAtual = { nome: '', preco: '' };

  document.querySelectorAll('.botao-pedir').forEach((botao) => {
    botao.addEventListener('click', () => {
      produtoAtual = {
        nome: botao.dataset.produto,
        preco: botao.dataset.preco,
      };

      nomeEl.textContent = produtoAtual.nome;
      precoEl.textContent = produtoAtual.preco;

      preencherGrupo('sabor', botao.dataset.sabores);
      preencherGrupo('cobertura', botao.dataset.coberturas);
      preencherGrupo('recheio', botao.dataset.recheios);

      modal.classList.add('aberto');
      document.body.style.overflow = 'hidden';
    });
  });

  function preencherGrupo(tipo, valoresString) {
    const { wrap, opcoes } = grupos[tipo];
    const valores = (valoresString || '').split('|').filter(Boolean);

    opcoes.innerHTML = '';

    if (valores.length === 0) {
      wrap.hidden = true;
      return;
    }

    wrap.hidden = false;

    valores.forEach((valor, indice) => {
      const id = `opt-${tipo}-${indice}-${Date.now()}`;
      const input = document.createElement('input');
      input.type = 'radio';
      input.name = `escolha-${tipo}`;
      input.id = id;
      input.value = valor;
      if (indice === 0) input.checked = true;

      const label = document.createElement('label');
      label.setAttribute('for', id);
      label.textContent = valor;

      opcoes.appendChild(input);
      opcoes.appendChild(label);
    });
  }

  function fecharModal() {
    modal.classList.remove('aberto');
    document.body.style.overflow = '';
  }

  botaoFechar.addEventListener('click', fecharModal);
  modal.addEventListener('click', (evento) => {
    if (evento.target === modal) fecharModal();
  });
  document.addEventListener('keydown', (evento) => {
    if (evento.key === 'Escape' && modal.classList.contains('aberto')) fecharModal();
  });

  form.addEventListener('submit', (evento) => {
    evento.preventDefault();

    const saborSelecionado = form.querySelector('input[name="escolha-sabor"]:checked');
    const coberturaSelecionada = form.querySelector('input[name="escolha-cobertura"]:checked');
    const recheioSelecionado = form.querySelector('input[name="escolha-recheio"]:checked');

    let mensagem = 'Olá! Vim pelo cardápio online e gostaria de encomendar:\n\n';
    mensagem += `🎂 Item: ${produtoAtual.nome}\n`;
    if (saborSelecionado) mensagem += `🍰 Sabor: ${saborSelecionado.value}\n`;
    if (coberturaSelecionada) mensagem += `🍯 Cobertura: ${coberturaSelecionada.value}\n`;
    if (recheioSelecionado) mensagem += `🍫 Recheio: ${recheioSelecionado.value}\n`;
    mensagem += '\nPode me passar mais detalhes sobre tamanho, data de entrega e valor?';

    const link = `https://wa.me/${DM.WHATSAPP_NUMERO}?text=${encodeURIComponent(mensagem)}`;
    window.open(link, '_blank', 'noopener');
    fecharModal();
  });
}
