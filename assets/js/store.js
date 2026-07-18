// ============================================================
// Delícias da Maria — camada de dados (Firebase)
// Substitui o MySQL/PDO original por Firestore (dados) +
// Firebase Authentication (login admin). Os dados agora são
// compartilhados de verdade entre todos os visitantes do site.
// Requer que assets/js/firebase-config.js tenha sido preenchido
// e carregado ANTES deste arquivo.
// ============================================================

const DM = (function () {
  const db = firebase.firestore();
  const auth = firebase.auth();
  const WHATSAPP_NUMERO = '5512982037844';

  let authProntoResolvido = false;
  let usuarioAtual = null;
  const aguardandoAuth = new Promise((resolve) => {
    auth.onAuthStateChanged((usuario) => {
      usuarioAtual = usuario;
      authProntoResolvido = true;
      resolve(usuario);
    });
  });

  function h(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
  }

  function formatarPreco(preco) {
    return 'R$ ' + Number(preco).toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?=,))/g, '.');
  }

  function urlImagemProduto(imagem) {
    return imagem || 'assets/img/sem-foto.svg';
  }

  // ------------------------------------------------------------
  // Categorias
  // ------------------------------------------------------------
  async function buscarCategorias() {
    const snap = await db.collection('categorias').orderBy('ordem', 'asc').get();
    return snap.docs.map((d) => Object.assign({ id: d.id }, d.data()));
  }

  async function adicionarCategoria(nome, emoji) {
    const existentes = await buscarCategorias();
    if (existentes.some((c) => c.nome.toLowerCase() === nome.toLowerCase())) {
      throw new Error('Já existe uma seção com esse nome.');
    }
    const proximaOrdem = existentes.reduce((max, c) => Math.max(max, c.ordem), 0) + 1;
    await db.collection('categorias').add({ nome, emoji: emoji || '🍰', ordem: proximaOrdem });
  }

  async function editarCategoria(id, nome, emoji) {
    const existentes = await buscarCategorias();
    if (existentes.some((c) => c.id !== id && c.nome.toLowerCase() === nome.toLowerCase())) {
      throw new Error('Já existe uma seção com esse nome.');
    }
    await db.collection('categorias').doc(id).update({ nome, emoji: emoji || '🍰' });
  }

  async function moverCategoria(id, direcao) {
    const lista = await buscarCategorias();
    const posicao = lista.findIndex((c) => c.id === id);
    if (posicao === -1) return;
    const alvo = direcao === 'cima' ? posicao - 1 : posicao + 1;
    if (alvo < 0 || alvo >= lista.length) return;

    const batch = db.batch();
    batch.update(db.collection('categorias').doc(lista[posicao].id), { ordem: lista[alvo].ordem });
    batch.update(db.collection('categorias').doc(lista[alvo].id), { ordem: lista[posicao].ordem });
    await batch.commit();
  }

  async function contarProdutosNaCategoria(categoriaId) {
    const snap = await db.collection('produtos').where('categoria_id', '==', categoriaId).get();
    return snap.size;
  }

  async function excluirCategoria(id) {
    const total = await contarProdutosNaCategoria(id);
    if (total > 0) throw new Error('tem_produtos');
    await db.collection('categorias').doc(id).delete();
  }

  // ------------------------------------------------------------
  // Atributos (sabor / cobertura / recheio)
  // ------------------------------------------------------------
  async function buscarAtributos(tipo) {
    const snap = await db.collection('atributos').where('tipo', '==', tipo).get();
    return snap.docs
      .map((d) => Object.assign({ id: d.id }, d.data()))
      .sort((a, b) => a.nome.localeCompare(b.nome));
  }

  async function adicionarAtributo(tipo, nome) {
    const existentes = await buscarAtributos(tipo);
    if (existentes.some((a) => a.nome.toLowerCase() === nome.toLowerCase())) {
      throw new Error('Esse item já existe nessa lista.');
    }
    await db.collection('atributos').add({ tipo, nome });
  }

  async function editarAtributo(id, tipo, nome) {
    const existentes = await buscarAtributos(tipo);
    if (existentes.some((a) => a.id !== id && a.nome.toLowerCase() === nome.toLowerCase())) {
      throw new Error('Já existe um item com esse nome.');
    }
    await db.collection('atributos').doc(id).update({ nome });
  }

  async function excluirAtributo(id) {
    await db.collection('atributos').doc(id).delete();

    // remove a referência desse atributo de qualquer produto que o usava
    const snap = await db.collection('produtos').get();
    const batch = db.batch();
    let houveAlteracao = false;

    snap.docs.forEach((docSnap) => {
      const p = docSnap.data();
      const atributos = p.atributos || { sabor: [], cobertura: [], recheio: [] };
      let alterado = false;
      ['sabor', 'cobertura', 'recheio'].forEach((tipo) => {
        if ((atributos[tipo] || []).includes(id)) {
          atributos[tipo] = atributos[tipo].filter((x) => x !== id);
          alterado = true;
        }
      });
      if (alterado) {
        batch.update(docSnap.ref, { atributos });
        houveAlteracao = true;
      }
    });

    if (houveAlteracao) await batch.commit();
  }

  async function nomesDosAtributos(tipo, ids) {
    if (!ids || ids.length === 0) return [];
    const snap = await db.collection('atributos').where('tipo', '==', tipo).get();
    const mapa = {};
    snap.docs.forEach((d) => { mapa[d.id] = d.data().nome; });
    return ids.map((id) => mapa[id]).filter(Boolean);
  }

  // ------------------------------------------------------------
  // Produtos
  // ------------------------------------------------------------
  async function buscarProdutos(apenasDisponiveis, categoriaId) {
    const [snap, categorias] = await Promise.all([
      db.collection('produtos').get(),
      buscarCategorias(),
    ]);

    const catMap = {};
    categorias.forEach((c) => { catMap[c.id] = c; });

    let lista = snap.docs.map((d) => {
      const p = Object.assign({ id: d.id }, d.data());
      const cat = catMap[p.categoria_id];
      p.categoria_nome = cat ? cat.nome : '';
      p.categoria_emoji = cat ? cat.emoji : '';
      return p;
    });

    if (apenasDisponiveis) lista = lista.filter((p) => p.disponivel);
    if (categoriaId != null) lista = lista.filter((p) => p.categoria_id === categoriaId);

    const ordemCategoria = {};
    categorias.forEach((c) => { ordemCategoria[c.id] = c.ordem; });

    lista.sort((a, b) => {
      const oa = ordemCategoria[a.categoria_id] || 0;
      const ob = ordemCategoria[b.categoria_id] || 0;
      if (oa !== ob) return oa - ob;
      return (b.criado_em || 0) - (a.criado_em || 0);
    });

    return lista;
  }

  async function buscarProdutoPorId(id) {
    const doc = await db.collection('produtos').doc(id).get();
    return doc.exists ? Object.assign({ id: doc.id }, doc.data()) : null;
  }

  async function salvarProduto(dados) {
    if (dados.id) {
      const id = dados.id;
      const dadosSemId = Object.assign({}, dados);
      delete dadosSemId.id;
      await db.collection('produtos').doc(id).update(dadosSemId);
      return id;
    }
    dados.criado_em = Date.now();
    const ref = await db.collection('produtos').add(dados);
    return ref.id;
  }

  async function alternarDisponibilidade(id) {
    const doc = await db.collection('produtos').doc(id).get();
    const atual = doc.data().disponivel;
    await db.collection('produtos').doc(id).update({ disponivel: atual ? 0 : 1 });
  }

  async function excluirProduto(id) {
    await db.collection('produtos').doc(id).delete();
  }

  // ------------------------------------------------------------
  // Autenticação (Firebase Authentication no lugar da sessão PHP)
  // ------------------------------------------------------------
  async function tentarLogin(email, senha) {
    try {
      await auth.signInWithEmailAndPassword(email, senha);
      return true;
    } catch (e) {
      return false;
    }
  }

  // Espera o Firebase confirmar se há (ou não) usuário logado.
  // Necessário porque o estado de auth do Firebase é assíncrono.
  async function aguardarAuthPronto() {
    if (authProntoResolvido) return usuarioAtual;
    return aguardandoAuth;
  }

  async function estaLogado() {
    const usuario = await aguardarAuthPronto();
    return !!usuario;
  }

  async function adminLogado() {
    const usuario = await aguardarAuthPronto();
    if (!usuario) return null;
    return { email: usuario.email, nome: usuario.displayName || usuario.email };
  }

  async function exigirLogin(caminhoLogin) {
    const logado = await estaLogado();
    if (!logado) window.location.href = caminhoLogin;
  }

  async function fazerLogout() {
    await auth.signOut();
  }

  return {
    WHATSAPP_NUMERO,
    h,
    formatarPreco,
    urlImagemProduto,
    buscarCategorias, adicionarCategoria, editarCategoria, moverCategoria, excluirCategoria, contarProdutosNaCategoria,
    buscarAtributos, adicionarAtributo, editarAtributo, excluirAtributo, nomesDosAtributos,
    buscarProdutos, buscarProdutoPorId, salvarProduto, alternarDisponibilidade, excluirProduto,
    tentarLogin, estaLogado, adminLogado, exigirLogin, fazerLogout,
  };
})();
