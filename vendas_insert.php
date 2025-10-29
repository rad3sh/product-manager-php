<?php
require_once 'connection.php';
$required_action = 'vendas';
require_once __DIR__ . '/auth_check.php';
if (!isset($pdo) || !$pdo) {
    header('Location: vendas.php?error=' . urlencode('Falha na conexão com o banco de dados.'));
    exit;
}

$actor = $_SESSION['username'] ?? 'system';
$actor_ip = $_SERVER['REMOTE_ADDR'] ?? null;
try {
    // $pdo é sua conexão PDO (já criada em connection.php)
    $pdo->exec("SET @actor = " . $pdo->quote($actor));
    $pdo->exec("SET @actor_ip = " . $pdo->quote($actor_ip));
} catch (Exception $e) {
    // se falhar, não bloqueia a operação principal
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: vendas.php');
    exit;
}

$produto_id_raw = trim($_POST['produto_id'] ?? '');
$quantidade_raw = trim($_POST['quantidade'] ?? '');

// validações básicas
if ($produto_id_raw === '' || !preg_match('/^\d{1,6}$/', $produto_id_raw)) {
    header('Location: vendas.php?error=' . urlencode('ID inválido. Informe o ID do produto.'));
    exit;
}
if ($quantidade_raw === '' || !is_numeric($quantidade_raw) || (int)$quantidade_raw < 1) {
    header('Location: vendas.php?error=' . urlencode('Quantidade inválida. Informe valor inteiro >= 1.'));
    exit;
}

$produto_id = (int)$produto_id_raw;
$qtd = (int)$quantidade_raw;

try {
    // Transação para garantir consistência
    $pdo->beginTransaction();

    // lock the product row
    $stmt = $pdo->prepare('SELECT quantidade FROM produtos WHERE id = :id LIMIT 1 FOR UPDATE');
    $stmt->execute([':id' => $produto_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $pdo->rollBack();
        header('Location: vendas.php?error=' . urlencode('Produto não encontrado.'));
        exit;
    }

    $available = (int)$row['quantidade'];
    if ($available < $qtd) {
        $pdo->rollBack();
        header('Location: vendas.php?error=' . urlencode('Estoque insuficiente. Disponível: ' . $available));
        exit;
    }

    // registrar venda (assume tabela `vendas` com colunas id, produto_id, quantidade, created_at)
    $ins = $pdo->prepare('INSERT INTO vendas (produto_id, quantidade, created_at) VALUES (:produto_id, :quantidade, NOW())');
    $ins->execute([':produto_id' => $produto_id, ':quantidade' => $qtd]);

    // decrementar estoque
    $upd = $pdo->prepare('UPDATE produtos SET quantidade = quantidade - :q WHERE id = :id');
    $upd->execute([':q' => $qtd, ':id' => $produto_id]);

    $pdo->commit();

    header('Location: vendas.php?success=1');
    exit;
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    header('Location: vendas.php?error=' . urlencode('Erro ao registrar venda: ' . $e->getMessage()));
    exit;
}
?>