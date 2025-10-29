<?php
require_once 'connection.php';
$required_action = 'vendas';
require_once __DIR__ . '/auth_check.php';
if (!isset($pdo) || !$pdo) {
    echo "<!DOCTYPE html><html><head><title>Erro de Conexão</title></head><body style='padding:0;margin:0;background:#18181b;color:#fff;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;'><h2>Falha na conexão com o banco de dados.</h2></body></html>";
    exit;
}

$error = isset($_GET['error']) ? $_GET['error'] : '';
$success = isset($_GET['success']) ? true : false;
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Cadastrar Venda — Gestor de Estoque</title>
<link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include __DIR__ . '/partials/nav.php'; ?>
<main>
    <h1>Gestor de Estoque</h1>
    <div>
        <h3>Cadastrar venda</h3>
        <a href="index.php" class="btn-back action-btn secondary" aria-label="Voltar à página inicial">Voltar</a>

        <form action="vendas_insert.php" method="post" class="form-venda" style="max-width:480px;">
            <label for="produto_id">ID do Produto (6 dígitos)</label>
            <input id="produto_id" name="produto_id" type="text" inputmode="numeric" pattern="\d{6}" maxlength="6" required
                placeholder="Informe o ID do produto (ex: 012345)">

            <label for="quantidade">Quantidade</label>
            <input id="quantidade" name="quantidade" type="number" min="1" step="1" required placeholder="Quantidade a vender">

            <div style="display:inline-flex; gap:8px; margin-top:0.75rem;">
                <button type="submit" class="action-btn edit">Registrar Venda</button>
                <button type="reset" class="action-btn secondary">Limpar</button>
            </div>
        </form>
                <?php if ($error): ?>
            <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="msg success">Venda registrada com sucesso.</div>
        <?php endif; ?>
    </div>
</main>
<?php include __DIR__ . '/partials/footer.php'; ?>
</body>
</html>