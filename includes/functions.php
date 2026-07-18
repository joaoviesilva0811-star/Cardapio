<?php
/**
 * Funções auxiliares usadas no site público e no painel admin.
 */

require_once __DIR__ . '/../config/database.php';

function h(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Busca todas as seções/categorias do cardápio, na ordem definida.
 */
function buscarCategorias(): array
{
    $pdo = getConexao();
    return $pdo->query('SELECT id, nome, emoji, ordem FROM categorias ORDER BY ordem ASC, nome ASC')->fetchAll();
}

function buscarCategoriaPorId(int $id): ?array
{
    $pdo = getConexao();
    $stmt = $pdo->prepare('SELECT id, nome, emoji, ordem FROM categorias WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $categoria = $stmt->fetch();
    return $categoria ?: null;
}

/**
 * Conta quantos produtos existem em uma categoria (usado para
 * impedir excluir uma seção que ainda tem produtos dentro).
 */
function contarProdutosNaCategoria(int $categoriaId): int
{
    $pdo = getConexao();
    $stmt = $pdo->prepare('SELECT COUNT(*) AS c FROM produtos WHERE categoria_id = :id');
    $stmt->execute(['id' => $categoriaId]);
    return (int) $stmt->fetch()['c'];
}

/**
 * Busca todos os atributos de um tipo (sabor, cobertura ou recheio).
 */
function buscarAtributos(string $tipo): array
{
    $pdo = getConexao();
    $stmt = $pdo->prepare('SELECT id, nome FROM atributos WHERE tipo = :tipo ORDER BY nome ASC');
    $stmt->execute(['tipo' => $tipo]);
    return $stmt->fetchAll();
}

/**
 * Busca os atributos vinculados a um produto específico, já agrupados por tipo.
 * Retorna algo como ['sabor' => [...], 'cobertura' => [...], 'recheio' => [...]]
 */
function buscarAtributosDoProduto(int $produtoId): array
{
    $pdo = getConexao();
    $stmt = $pdo->prepare(
        'SELECT a.id, a.tipo, a.nome
         FROM atributos a
         INNER JOIN produto_atributos pa ON pa.atributo_id = a.id
         WHERE pa.produto_id = :produto_id
         ORDER BY a.nome ASC'
    );
    $stmt->execute(['produto_id' => $produtoId]);

    $agrupado = ['sabor' => [], 'cobertura' => [], 'recheio' => []];
    foreach ($stmt->fetchAll() as $linha) {
        $agrupado[$linha['tipo']][] = $linha;
    }
    return $agrupado;
}

/**
 * Busca todos os produtos do cardápio (opcionalmente só os disponíveis
 * e/ou filtrados por categoria) já com seus atributos e nome da seção.
 */
function buscarProdutos(bool $apenasDisponiveis = false, ?int $categoriaId = null): array
{
    $pdo = getConexao();
    $sql = 'SELECT p.*, c.nome AS categoria_nome, c.emoji AS categoria_emoji
            FROM produtos p
            INNER JOIN categorias c ON c.id = p.categoria_id
            WHERE 1=1';
    $params = [];

    if ($apenasDisponiveis) {
        $sql .= ' AND p.disponivel = 1';
    }
    if ($categoriaId !== null) {
        $sql .= ' AND p.categoria_id = :categoria_id';
        $params['categoria_id'] = $categoriaId;
    }

    $sql .= ' ORDER BY c.ordem ASC, p.criado_em DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $produtos = $stmt->fetchAll();

    foreach ($produtos as &$produto) {
        $produto['atributos'] = buscarAtributosDoProduto((int) $produto['id']);
    }

    return $produtos;
}

function buscarProdutoPorId(int $id): ?array
{
    $pdo = getConexao();
    $stmt = $pdo->prepare(
        'SELECT p.*, c.nome AS categoria_nome
         FROM produtos p
         INNER JOIN categorias c ON c.id = p.categoria_id
         WHERE p.id = :id'
    );
    $stmt->execute(['id' => $id]);
    $produto = $stmt->fetch();

    if (!$produto) {
        return null;
    }

    $produto['atributos'] = buscarAtributosDoProduto($id);
    return $produto;
}

/**
 * Faz o upload de uma foto de produto e retorna o nome do arquivo salvo.
 * Lança uma Exception com mensagem amigável se algo estiver errado.
 */
function fazerUploadImagem(array $arquivo): ?string
{
    // Nenhuma imagem enviada — não é um erro (campo opcional em edições)
    if (!isset($arquivo['error']) || $arquivo['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Houve um problema ao enviar a imagem. Tente novamente.');
    }

    $tamanhoMaximo = 5 * 1024 * 1024; // 5MB
    if ($arquivo['size'] > $tamanhoMaximo) {
        throw new Exception('A imagem é muito grande. O tamanho máximo é 5MB.');
    }

    $extensoesPermitidas = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
    $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));

    $tipoReal = mime_content_type($arquivo['tmp_name']);
    if (!in_array($extensao, array_keys($extensoesPermitidas), true) || !in_array($tipoReal, $extensoesPermitidas, true)) {
        throw new Exception('Formato de imagem inválido. Envie um arquivo JPG, PNG ou WEBP.');
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $nomeArquivo = 'produto_' . uniqid() . '.' . $extensao;
    $destino = UPLOAD_DIR . $nomeArquivo;

    if (!move_uploaded_file($arquivo['tmp_name'], $destino)) {
        throw new Exception('Não foi possível salvar a imagem no servidor.');
    }

    return $nomeArquivo;
}

function apagarImagem(?string $nomeArquivo): void
{
    if ($nomeArquivo && file_exists(UPLOAD_DIR . $nomeArquivo)) {
        unlink(UPLOAD_DIR . $nomeArquivo);
    }
}

function urlImagemProduto(?string $nomeArquivo): string
{
    if (!$nomeArquivo) {
        return 'assets/img/sem-foto.svg';
    }
    return UPLOAD_URL . rawurlencode($nomeArquivo);
}

function formatarPreco(float $preco): string
{
    return 'R$ ' . number_format($preco, 2, ',', '.');
}

/**
 * Monta o link do WhatsApp com a mensagem do pedido já preenchida.
 */
function montarLinkWhatsapp(string $nomeProduto, array $escolhas): string
{
    $mensagem = "Olá! Vim pelo cardápio online e gostaria de encomendar:\n\n";
    $mensagem .= "🎂 Item: {$nomeProduto}\n";

    if (!empty($escolhas['sabor'])) {
        $mensagem .= "🍰 Sabor: {$escolhas['sabor']}\n";
    }
    if (!empty($escolhas['cobertura'])) {
        $mensagem .= "🍯 Cobertura: {$escolhas['cobertura']}\n";
    }
    if (!empty($escolhas['recheio'])) {
        $mensagem .= "🍫 Recheio: {$escolhas['recheio']}\n";
    }

    $mensagem .= "\nPode me passar mais detalhes sobre tamanho, data de entrega e valor?";

    return 'https://wa.me/' . WHATSAPP_NUMERO . '?text=' . rawurlencode($mensagem);
}
