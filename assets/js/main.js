// ============================================================
// Delícias da Maria — site público
// Filtro por seção + busca por nome + modal de escolha de sabor/cobertura/recheio
// ============================================================

document.addEventListener('DOMContentLoaded', () => {
  configurarFiltros();
  configurarModalPedido();
});

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

    const link = `https://wa.me/${WHATSAPP_NUMERO}?text=${encodeURIComponent(mensagem)}`;
    window.open(link, '_blank', 'noopener');
    fecharModal();
  });
}
